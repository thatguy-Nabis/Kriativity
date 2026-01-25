<?php
// ============================================
// PROFILE UPDATE HANDLER
// ============================================

session_start();

// Include required files
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to update your profile'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and sanitize form data
$full_name = sanitizeInput($_POST['full_name'] ?? '');
$bio = sanitizeInput($_POST['bio'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');
$website = sanitizeInput($_POST['website'] ?? '');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Validation
$errors = [];

// Full name validation
if (empty($full_name)) {
    $errors['full_name'] = 'Full name is required';
} elseif (strlen($full_name) > 100) {
    $errors['full_name'] = 'Full name must be less than 100 characters';
}

// Website validation
if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
    $errors['website'] = 'Invalid website URL';
}

// Bio validation
if (strlen($bio) > 500) {
    $errors['bio'] = 'Bio must be less than 500 characters';
}

// If there are validation errors, return them
if (!empty($errors)) {
    $response['errors'] = $errors;
    $response['message'] = 'Please fix the errors below';
    echo json_encode($response);
    exit;
}

// Prepare update data
$updateData = [
    'full_name' => $full_name,
    'bio' => $bio,
    'location' => $location,
    'website' => $website
];

// Update user profile
$result = updateUserProfile($pdo, $_SESSION['user_id'], $updateData);

if ($result['success']) {
    // Update session data
    $_SESSION['full_name'] = $full_name;
    
    $response['success'] = true;
    $response['message'] = $result['message'];
    $response['data'] = $updateData;
} else {
    $response['message'] = $result['message'];
}

echo json_encode($response);
exit;
?>