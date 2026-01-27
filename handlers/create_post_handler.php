<?php
// ============================================
// CREATE POST HANDLER (MATCHES NEW DATABASE)
// ============================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// ----------------------------
// AUTH CHECK
// ----------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to create a post'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// ----------------------------
// INPUT SANITIZATION
// ----------------------------
$user_id      = (int) $_SESSION['user_id'];
$title        = trim($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$category     = trim($_POST['category'] ?? '');
$content_type = trim($_POST['content_type'] ?? 'image');

$errors = [];

// ----------------------------
// VALIDATION
// ----------------------------
if ($title === '') {
    $errors['title'] = 'Title is required';
} elseif (strlen($title) > 255) {
    $errors['title'] = 'Title must be under 255 characters';
}

if ($category === '') {
    $errors['category'] = 'Category is required';
}

if ($description !== '' && strlen($description) > 2000) {
    $errors['description'] = 'Description must be under 2000 characters';
}

$allowed_types = ['image', 'video', 'article', 'audio'];
if (!in_array($content_type, $allowed_types, true)) {
    $content_type = 'image';
}

// ----------------------------
// IMAGE UPLOAD (OPTIONAL)
// ----------------------------
$image_url = null;

if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];

    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10MB

    if (!in_array($file['type'], $allowed_mime, true)) {
        $errors['image'] = 'Invalid image type';
    } elseif ($file['size'] > $max_size) {
        $errors['image'] = 'Image exceeds 10MB';
    } else {
        $upload_dir = '../uploads/posts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('post_', true) . '.' . $ext;
        $target = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $errors['image'] = 'Image upload failed';
        } else {
            $image_url = 'uploads/posts/' . $filename;
        }
    }
}

// ----------------------------
// RETURN VALIDATION ERRORS
// ----------------------------
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $errors
    ]);
    exit;
}

// ----------------------------
// INSERT POST
// ----------------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO content (
            user_id,
            title,
            description,
            category,
            image_url,
            content_type,
            is_published,
            published_at
        ) VALUES (
            :user_id,
            :title,
            :description,
            :category,
            :image_url,
            :content_type,
            1,
            NOW()
        )
    ");

    $stmt->execute([
        ':user_id'      => $user_id,
        ':title'        => $title,
        ':description'  => $description ?: null,
        ':category'     => $category,
        ':image_url'    => $image_url,
        ':content_type' => $content_type
    ]);

    $post_id = (int) $pdo->lastInsertId();

    // Update user stats
    $pdo->prepare("
        UPDATE users
        SET total_posts = total_posts + 1
        WHERE id = ?
    ")->execute([$user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post_id' => $post_id
    ]);

} catch (PDOException $e) {
    error_log('[CREATE_POST_ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Failed to create post'
    ]);
}
