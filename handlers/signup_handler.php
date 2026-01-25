<?php
// ============================================
// SIGNUP HANDLER
// ============================================

session_start();

// Include required files
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and sanitize form data
$username = sanitizeInput($_POST['username'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
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

// Username validation
if (empty($username)) {
    $errors['username'] = 'Username is required';
} elseif (!validateUsername($username)) {
    $errors['username'] = 'Username must be 3-50 characters and contain only letters, numbers, and underscores';
} elseif (usernameExists($pdo, $username)) {
    $errors['username'] = 'Username already taken';
}

// Email validation
if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors['email'] = 'Invalid email format';
} elseif (emailExists($pdo, $email)) {
    $errors['email'] = 'Email already registered';
}

// Password validation
if (empty($password)) {
    $errors['password'] = 'Password is required';
} else {
    $passwordValidation = validatePassword($password);
    if (!$passwordValidation['valid']) {
        $errors['password'] = $passwordValidation['message'];
    }
}

// Confirm password validation
if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match';
}

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

// Prepare user data
$userData = [
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'full_name' => $full_name,
    'bio' => $bio,
    'location' => $location,
    'website' => $website
];

// Register user
$result = registerUser($pdo, $userData);

if ($result['success']) {
    // Set session variables
    $_SESSION['user_id'] = $result['user_id'];
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    
    // Generate CSRF token for the session
    generateCSRFToken();
    
    $response['success'] = true;
    $response['message'] = $result['message'];
    $response['redirect'] = '../homepage.php';
    $response['user'] = [
        'username' => $username,
        'full_name' => $full_name
    ];
} else {
    $response['message'] = $result['message'];
}

echo json_encode($response);
exit;
?>