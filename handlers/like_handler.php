<?php
// ============================================
// LIKE HANDLER (TOGGLE)
// ============================================

session_start();

require_once '../config/database.php';

header('Content-Type: application/json');

// ----------------------------
// AUTH CHECK
// ----------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ----------------------------
// INPUT VALIDATION
// ----------------------------
$data = json_decode(file_get_contents('php://input'), true);

$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$action  = $data['action'] ?? '';

if ($post_id <= 0 || $action !== 'toggle') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

try {
    // ----------------------------
    // CHECK CONTENT EXISTS
    // ----------------------------
    $stmt = $pdo->prepare("
        SELECT id, likes
        FROM content
        WHERE id = ? AND is_published = 1
        LIMIT 1
    ");
    $stmt->execute([$post_id]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$content) {
        echo json_encode([
            'success' => false,
            'message' => 'Post not found'
        ]);
        exit;
    }

    // ----------------------------
    // TRANSACTION (IMPORTANT)
    // ----------------------------
    $pdo->beginTransaction();

    // Check if already liked
    $stmt = $pdo->prepare("
        SELECT id
        FROM likes
        WHERE user_id = ? AND content_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $post_id]);
    $like = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($like) {
        // ----------------------------
        // UNLIKE
        // ----------------------------
        $pdo->prepare("
            DELETE FROM likes
            WHERE user_id = ? AND content_id = ?
        ")->execute([$user_id, $post_id]);

        $pdo->prepare("
            UPDATE content
            SET likes = GREATEST(likes - 1, 0)
            WHERE id = ?
        ")->execute([$post_id]);

        $liked = false;
        $message = 'Like removed';

    } else {
        // ----------------------------
        // LIKE
        // ----------------------------
        $pdo->prepare("
            INSERT INTO likes (user_id, content_id)
            VALUES (?, ?)
        ")->execute([$user_id, $post_id]);

        $pdo->prepare("
            UPDATE content
            SET likes = likes + 1
            WHERE id = ?
        ")->execute([$post_id]);

        $liked = true;
        $message = 'Post liked';
    }

    // ----------------------------
    // FETCH UPDATED LIKE COUNT
    // ----------------------------
    $stmt = $pdo->prepare("
        SELECT likes
        FROM content
        WHERE id = ?
    ");
    $stmt->execute([$post_id]);
    $likes_count = (int) $stmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'liked'   => $liked,
        'likes'   => $likes_count,
        'message' => $message
    ]);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[LIKE ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Failed to process like'
    ]);
    exit;
}
