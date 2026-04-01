<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// ----------------------------
// AUTH CHECK
// ----------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ----------------------------
// INPUT
// ----------------------------
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$action = $data['action'] ?? '';

if (!in_array($action, ['add', 'edit', 'delete', 'fetch'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
$comment_text = trim($data['comment_text'] ?? '');
$comment_id = isset($data['comment_id']) ? (int) $data['comment_id'] : 0;

$parent_id = isset($data['parent_id']) && $data['parent_id'] !== '' 
    ? (int)$data['parent_id'] 
    : null;

try {

    // ----------------------------
    // ADD COMMENT
    // ----------------------------
    if ($action === 'add') {

        if ($post_id <= 0 || $comment_text === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        // validate parent comment (if replying)
        if ($parent_id) {
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
            $stmt->execute([$parent_id]);

            if (!$stmt->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Invalid parent comment']);
                exit;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO comments (user_id, content_id, parent_id, comment_text, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([$user_id, $post_id, $parent_id, $comment_text]);

        $new_id = $pdo->lastInsertId();

        // fetch user data
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.full_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
            AND c.is_deleted = 0
        ");
        $stmt->execute([$new_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Comment added',
            'comment' => $stmt->fetch(PDO::FETCH_ASSOC)
        ]);
        exit;
    }

    // ----------------------------
    // EDIT COMMENT
    // ----------------------------
    if ($action === 'edit') {

        if ($comment_id <= 0 || $comment_text === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT user_id, is_deleted FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['user_id'] !== $user_id || (int)$row['is_deleted'] === 1) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE comments
            SET comment_text = ?, is_edited = 1, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$comment_text, $comment_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Comment updated'
        ]);
        exit;
    }

    // ----------------------------
    // DELETE COMMENT (FIXED)
    // ----------------------------
    if ($action === 'delete') {

        if ($comment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT user_id, is_deleted FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['user_id'] !== $user_id || (int)$row['is_deleted'] === 1) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE comments
            SET is_deleted = 1, comment_text = '[deleted]', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$comment_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Comment deleted'
        ]);
        exit;
    }

    // ----------------------------
    // FETCH COMMENTS
    // ----------------------------
    if ($action === 'fetch') {

        if ($post_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid post']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.comment_text,
                c.parent_id,
                c.is_edited,
                c.is_deleted,
                c.user_id,
                c.created_at,
                DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') AS formatted_date,
                u.username,
                u.full_name
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.content_id = ?
            AND c.is_deleted = 0
            ORDER BY c.parent_id ASC, c.created_at ASC
        ");

        $stmt->execute([$post_id]);

        echo json_encode([
            'success' => true,
            'comments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;

} catch (Throwable $e) {
    error_log('[COMMENT ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
    exit;
}