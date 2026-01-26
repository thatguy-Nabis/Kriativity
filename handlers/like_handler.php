<?php
// ============================================
// Like / Unlike Handler (Robust + Sync-safe)
// Path: handlers/like_handler.php
// ============================================

require_once __DIR__ . '/../init.php';          // session + DB ($pdo)
header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
$action  = isset($input['action']) ? (string)$input['action'] : 'toggle';

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post_id']);
    exit;
}

if (!in_array($action, ['like', 'unlike', 'toggle'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Validate post exists
    $check = $pdo->prepare("SELECT id FROM content WHERE id = :id AND is_published = 1");
    $check->execute([':id' => $post_id]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    $pdo->beginTransaction();

    // Check current like state
    $likedStmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = :u AND content_id = :c LIMIT 1");
    $likedStmt->execute([':u' => $user_id, ':c' => $post_id]);
    $alreadyLiked = (bool)$likedStmt->fetch();

    $finalLiked = $alreadyLiked;

    if ($action === 'toggle') {
        $action = $alreadyLiked ? 'unlike' : 'like';
    }

    if ($action === 'like') {
        if (!$alreadyLiked) {
            $ins = $pdo->prepare("INSERT INTO likes (user_id, content_id) VALUES (:u, :c)");
            $ins->execute([':u' => $user_id, ':c' => $post_id]);
            $finalLiked = true;
        }
    } else { // unlike
        if ($alreadyLiked) {
            $del = $pdo->prepare("DELETE FROM likes WHERE user_id = :u AND content_id = :c");
            $del->execute([':u' => $user_id, ':c' => $post_id]);
            $finalLiked = false;
        }
    }

    // ✅ Sync-safe like count: recompute from likes table (prevents drift)
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM likes WHERE content_id = :c");
    $countStmt->execute([':c' => $post_id]);
    $likeCount = (int)$countStmt->fetchColumn();

    $upd = $pdo->prepare("UPDATE content SET likes = :cnt WHERE id = :id");
    $upd->execute([':cnt' => $likeCount, ':id' => $post_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $finalLiked ? 'Post liked' : 'Post unliked',
        'liked'   => $finalLiked,
        'likes'   => $likeCount
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Like handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
