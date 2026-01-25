<?php
// ============================================
// Like/Unlike Handler
// ============================================

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to like posts'
    ]);
    exit;
}

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=content_discovery;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['post_id']) || !isset($input['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$post_id = intval($input['post_id']);
$action = $input['action'];
$user_id = $_SESSION['user_id'];

// Validate post exists
try {
    $check_stmt = $pdo->prepare("SELECT id FROM content WHERE id = :post_id AND is_published = 1");
    $check_stmt->execute([':post_id' => $post_id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Post not found'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error validating post'
    ]);
    exit;
}

try {
    if ($action === 'like') {
        // Check if already liked
        $check_like = $pdo->prepare("SELECT id FROM likes WHERE user_id = :user_id AND content_id = :content_id");
        $check_like->execute([':user_id' => $user_id, ':content_id' => $post_id]);
        
        if ($check_like->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'You have already liked this post'
            ]);
            exit;
        }
        
        // Add like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, content_id) VALUES (:user_id, :content_id)");
        $stmt->execute([':user_id' => $user_id, ':content_id' => $post_id]);
        
        // Update like count
        $update_stmt = $pdo->prepare("UPDATE content SET likes = likes + 1 WHERE id = :post_id");
        $update_stmt->execute([':post_id' => $post_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Post liked successfully',
            'action' => 'liked'
        ]);
        
    } elseif ($action === 'unlike') {
        // Remove like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = :user_id AND content_id = :content_id");
        $stmt->execute([':user_id' => $user_id, ':content_id' => $post_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update like count
            $update_stmt = $pdo->prepare("UPDATE content SET likes = likes - 1 WHERE id = :post_id");
            $update_stmt->execute([':post_id' => $post_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Post unliked successfully',
                'action' => 'unliked'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Like not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Like handler error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process like. Please try again.'
    ]);
}
?>