<?php
require_once '../printer.php';
require_once '../init.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// =========================
// AUTH CHECK
// =========================
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
// =========================
// INPUT
// =========================
$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid post ID'
    ]);
    exit;
}
try {
    $pdo->beginTransaction();

    // =========================
    // CHECK IF ALREADY LIKED
    // =========================
    $check = $pdo->prepare(
        "SELECT id FROM likes WHERE user_id = ? AND content_id = ?"
    );
    $check->execute([$user_id, $post_id]);
    $existing_like = $check->fetchColumn();

    if ($existing_like) {
        // =========================
        // UNLIKE
        // =========================
        $pdo->prepare(
            "DELETE FROM likes WHERE id = ?"
        )->execute([$existing_like]);

        $pdo->prepare(
            "UPDATE content 
             SET likes = GREATEST(likes - 1, 0) 
             WHERE id = ?"
        )->execute([$post_id]);

        $liked = false;
        $message = 'Like removed';
    } else {
        // =========================
        // LIKE
        // =========================

        $stmt = $pdo->prepare(
            "INSERT INTO likes (user_id, content_id)
            VALUES (?, ?)"
        );
        $check->execute([$user_id, $post_id]);

        echo json_encode([
            'success' => false,
            'message' => 'Error while failing to process the like' . 'content id:' . $post_id . ' user_Id ' . $user_id
        ]);
        exit;


        $pdo->prepare(
            "UPDATE content 
             SET likes = likes + 1 
             WHERE id = ?"
        )->execute([$post_id]);

        $liked = true;
        $message = 'Post liked';
    }

    // =========================
    // FETCH UPDATED LIKE COUNT
    // =========================
    $count = $pdo->prepare(
        "SELECT likes FROM content WHERE id = ?"
    );
    $count->execute([$post_id]);
    $likes = (int) $count->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes' => $likes,
        'message' => $message
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[LIKE_HANDLER_ERROR] ' . $e->getMessage());


    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
