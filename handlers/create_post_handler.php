<?php
// ============================================
// Create Post Handler
// ============================================

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to create a post'
    ]);
    exit;
}

// Database connection (adjust these to match your config)
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

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Sanitize and validate inputs
$errors = [];

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$content_type = trim($_POST['content_type'] ?? 'image');
$user_id = $_SESSION['user_id'];

// Validation
if (empty($title)) {
    $errors['title'] = 'Title is required';
} elseif (strlen($title) > 255) {
    $errors['title'] = 'Title must be less than 255 characters';
}

if (empty($category)) {
    $errors['category'] = 'Category is required';
}

if (!empty($description) && strlen($description) > 2000) {
    $errors['description'] = 'Description must be less than 2000 characters';
}

$valid_types = ['image', 'video', 'article', 'audio'];
if (!in_array($content_type, $valid_types)) {
    $content_type = 'image';
}

// Handle image upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $errors['image'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed';
    } elseif ($file['size'] > $max_size) {
        $errors['image'] = 'File size must be less than 10MB';
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('post_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $image_url = 'uploads/posts/' . $new_filename;
        } else {
            $errors['image'] = 'Failed to upload image';
        }
    }
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the errors and try again',
        'errors' => $errors
    ]);
    exit;
}

// Insert post into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO content (
            user_id, 
            title, 
            description, 
            category, 
            content_type, 
            image_url,
            is_published,
            published_at
        ) VALUES (
            :user_id, 
            :title, 
            :description, 
            :category, 
            :content_type, 
            :image_url,
            1,
            NOW()
        )
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':content_type' => $content_type,
        ':image_url' => $image_url
    ]);
    
    $post_id = $pdo->lastInsertId();
    
    // Update user's total posts count
    $update_stmt = $pdo->prepare("
        UPDATE users 
        SET total_posts = total_posts + 1 
        WHERE id = :user_id
    ");
    $update_stmt->execute([':user_id' => $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully!',
        'post_id' => $post_id
    ]);
    
} catch (PDOException $e) {
    // Log error (in production, use proper logging)
    error_log('Create post error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create post. Please try again.'
    ]);
}
?>