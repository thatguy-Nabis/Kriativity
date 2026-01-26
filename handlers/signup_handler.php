<?php
// ============================================
// SIGNUP HANDLER (CORRECT)
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

// Sanitize inputs
$username = sanitizeInput($_POST['username'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$full_name = sanitizeInput($_POST['full_name'] ?? '');
$bio = sanitizeInput($_POST['bio'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');
$website = sanitizeInput($_POST['website'] ?? '');

// Init response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

$errors = [];

/* =========================
   VALIDATION
   ========================= */

if (empty($username)) {
    $errors['username'] = 'Username is required';
} elseif (!validateUsername($username)) {
    $errors['username'] = 'Invalid username format';
} elseif (usernameExists($pdo, $username)) {
    $errors['username'] = 'Username already taken';
}

if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors['email'] = 'Invalid email format';
} elseif (emailExists($pdo, $email)) {
    $errors['email'] = 'Email already registered';
}

if (empty($password)) {
    $errors['password'] = 'Password is required';
} else {
    $passwordValidation = validatePassword($password);
    if (!$passwordValidation['valid']) {
        $errors['password'] = $passwordValidation['message'];
    }
}

if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match';
}

if (empty($full_name)) {
    $errors['full_name'] = 'Full name is required';
}

if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
    $errors['website'] = 'Invalid website URL';
}

if (strlen($bio) > 500) {
    $errors['bio'] = 'Bio must be less than 500 characters';
}

if (!empty($errors)) {
    $response['errors'] = $errors;
    $response['message'] = 'Please fix the errors below';
    echo json_encode($response);
    exit;
}

/* =========================
   CREATE USER
   ========================= */

$userData = [
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'full_name' => $full_name,
    'bio' => $bio,
    'location' => $location,
    'website' => $website
];

$result = registerUser($pdo, $userData);

if (!$result['success']) {
    $response['message'] = $result['message'];
    echo json_encode($response);
    exit;
}

/* =========================
   SET SESSION
   ========================= */

$_SESSION['user_id'] = $result['user_id'];
$_SESSION['username'] = $username;
$_SESSION['full_name'] = $full_name;

generateCSRFToken();

/* =========================
   REDIRECT TO ONBOARDING
   ========================= */

$response['success'] = true;
$response['message'] = 'Signup successful';
$response['redirect'] = './onboarding_preferences.php';

echo json_encode($response);
exit;
