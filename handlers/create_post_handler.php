<?php
// ============================================
// CREATE POST HANDLER — Kriativity
// ============================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// ── Helpers ─────────────────────────────────────────────────────
function fail(string $message, array $errors = []): never
{
    echo json_encode(['success' => false, 'message' => $message, 'errors' => $errors]);
    exit;
}

function ok(array $payload = []): never
{
    echo json_encode(array_merge(['success' => true], $payload));
    exit;
}

// ── Auth ─────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    fail('You must be logged in to create a post.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.');
}

$user_id = (int) $_SESSION['user_id'];

// ── Collect inputs ───────────────────────────────────────────────
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$content_type = trim($_POST['content_type'] ?? 'image');
$tags_raw = trim($_POST['tags'] ?? '');   // comma-separated from JS

// ── Validate content type (only image + video accepted) ──────────
$allowed_types = ['image', 'video'];
if (!in_array($content_type, $allowed_types, true)) {
    $content_type = 'image';
}

// ── Validate required text fields ────────────────────────────────
$errors = [];

if ($title === '') {
    $errors['title'] = 'Title is required.';
} elseif (strlen($title) > 255) {
    $errors['title'] = 'Title must be under 255 characters.';
}

if ($category === '') {
    $errors['category'] = 'Please select a category.';
}

if ($description !== '' && strlen($description) > 2000) {
    $errors['description'] = 'Description must be under 2000 characters.';
}

// ── Validate & sanitise tags ──────────────────────────────────────
$tags = [];
if ($tags_raw !== '') {
    $raw_list = explode(',', $tags_raw);
    foreach ($raw_list as $t) {
        $t = strtolower(trim(preg_replace('/[^a-z0-9\-_]/i', '', $t)));
        if ($t !== '' && !in_array($t, $tags, true)) {
            $tags[] = $t;
        }
        if (count($tags) >= 10)
            break;  // hard cap
    }
}

// ── Early exit on text errors ────────────────────────────────────
if (!empty($errors)) {
    fail('Please fix the errors below.', $errors);
}

// ── Media upload ──────────────────────────────────────────────────
/*
 * FIX: read $_FILES['media'] — matches name="media" on the <input type="file">
 * The original handler read $_FILES['image'] which never matched.
 */
$media_url = null;
$file_error = $_FILES['media']['error'] ?? UPLOAD_ERR_NO_FILE;

if ($file_error !== UPLOAD_ERR_NO_FILE) {

    if ($file_error !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the form size limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
        ];
        fail($upload_errors[$file_error] ?? "Upload error (code {$file_error}).");
    }

    $file = $_FILES['media'];
    $tmp_path = $file['tmp_name'];

    // Validate MIME type via finfo (not just the browser-supplied type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp_path);
    finfo_close($finfo);

    if ($content_type === 'image') {
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $max_bytes = 10 * 1024 * 1024; // 10 MB
        $sub_dir = 'images';
    } else {
        // video
        $allowed_mimes = [
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
        ];
        $max_bytes = 50 * 1024 * 1024; // 50 MB
        $sub_dir = 'videos';
    }

    if (!array_key_exists($mime, $allowed_mimes)) {
        $allowed_list = implode(', ', array_keys($allowed_mimes));
        fail("Invalid file type ({$mime}). Allowed: {$allowed_list}.", ['media' => 'Invalid file type.']);
    }

    if ($file['size'] > $max_bytes) {
        $limit = $max_bytes / (1024 * 1024);
        fail("File exceeds the {$limit} MB limit.", ['media' => "File too large (max {$limit} MB)."]);
    }

    // Build upload path
    // __DIR__ = .../handlers  →  dirname(__DIR__) = project root
    $upload_dir = dirname(__DIR__) . "/uploads/posts/{$sub_dir}/";

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("[create_post] Could not create dir: {$upload_dir}");
            fail('Server error: could not create upload directory.');
        }
    }

    if (!is_writable($upload_dir)) {
        error_log("[create_post] Dir not writable: {$upload_dir}");
        fail('Server error: upload directory is not writable.');
    }

    $ext = $allowed_mimes[$mime];
    $filename = $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $upload_dir . $filename;

    if (!move_uploaded_file($tmp_path, $dest)) {
        error_log("[create_post] move_uploaded_file failed: {$tmp_path} → {$dest}");
        fail('Failed to save uploaded file. Check folder permissions.');
    }

    $media_url = "uploads/posts/{$sub_dir}/{$filename}";
    error_log("[create_post] Saved: {$media_url}");
}
// ── Thumbnail upload (video only) ────────────────────────────────
$thumbnail_url = null;
$thumb_error   = $_FILES['thumbnail']['error'] ?? UPLOAD_ERR_NO_FILE;

if ($content_type === 'video' && $thumb_error !== UPLOAD_ERR_NO_FILE) {
    if ($thumb_error !== UPLOAD_ERR_OK) {
        fail('Thumbnail upload failed (code ' . $thumb_error . ').');
    }

    $thumb_file = $_FILES['thumbnail'];
    $finfo2     = finfo_open(FILEINFO_MIME_TYPE);
    $thumb_mime = finfo_file($finfo2, $thumb_file['tmp_name']);
    finfo_close($finfo2);

    $allowed_thumb = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!array_key_exists($thumb_mime, $allowed_thumb)) {
        fail('Thumbnail must be JPG, PNG, or WebP.', ['thumbnail' => 'Invalid thumbnail type.']);
    }
    if ($thumb_file['size'] > 5 * 1024 * 1024) {
        fail('Thumbnail must be under 5 MB.', ['thumbnail' => 'Thumbnail too large.']);
    }

    $thumb_dir = dirname(__DIR__) . '/uploads/posts/thumbnails/';
    if (!is_dir($thumb_dir) && !mkdir($thumb_dir, 0755, true)) {
        fail('Server error: could not create thumbnail directory.');
    }

    $t_ext      = $allowed_thumb[$thumb_mime];
    $t_filename = $user_id . '_thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $t_ext;
    $t_dest     = $thumb_dir . $t_filename;

    if (!move_uploaded_file($thumb_file['tmp_name'], $t_dest)) {
        fail('Failed to save thumbnail.');
    }

    $thumbnail_url = 'uploads/posts/thumbnails/' . $t_filename;
}
// ── Insert post ───────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
    INSERT INTO content (
        user_id, title, description, category,
        image_url, thumbnail_url, content_type,
        is_published, published_at
    ) VALUES (
        :user_id, :title, :description, :category,
        :image_url, :thumbnail_url, :content_type,
        1, NOW()
    )
");

$stmt->execute([
    ':user_id'       => $user_id,
    ':title'         => $title,
    ':description'   => $description !== '' ? $description : null,
    ':category'      => $category,
    ':image_url'     => $media_url,
    ':thumbnail_url' => $thumbnail_url,
    ':content_type'  => $content_type,
]);

    $post_id = (int) $pdo->lastInsertId();

    // ── Save tags ─────────────────────────────────────────────────
    /*
     * Tags are stored in a post_tags table:
     *   CREATE TABLE post_tags (
     *       id         INT AUTO_INCREMENT PRIMARY KEY,
     *       post_id    INT NOT NULL,
     *       tag        VARCHAR(30) NOT NULL,
     *       INDEX idx_post (post_id),
     *       INDEX idx_tag  (tag),
     *       FOREIGN KEY (post_id) REFERENCES content(id) ON DELETE CASCADE
     *   );
     *
     * If the table doesn't exist yet, tag saving is skipped gracefully.
     */
    if (!empty($tags)) {
        try {
            $tagStmt = $pdo->prepare("
                INSERT IGNORE INTO post_tags (post_id, tag) VALUES (?, ?)
            ");
            foreach ($tags as $tag) {
                $tagStmt->execute([$post_id, $tag]);
            }
        } catch (PDOException $te) {
            // Table may not exist yet — log and continue without failing the post
            error_log('[create_post] Tags insert skipped: ' . $te->getMessage());
        }
    }

    // ── Increment user post counter ───────────────────────────────
    $pdo->prepare("
        UPDATE users SET total_posts = total_posts + 1 WHERE id = ?
    ")->execute([$user_id]);

    $pdo->commit();

    ok([
        'message' => 'Post published successfully!',
        'post_id' => $post_id,
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[create_post] DB error: ' . $e->getMessage());
    fail('Failed to create post. Please try again.');
}