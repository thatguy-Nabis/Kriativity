<?php
require_once '../init.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$errors = [];

/* ============================
   INPUTS
============================ */
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$website = trim($_POST['website'] ?? '');
$bio = trim($_POST['bio'] ?? '');

/* ============================
   VALIDATION
============================ */

// Username
if ($username === '') {
    $errors['username'] = 'Username is required';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = 'Only letters, numbers, and underscores are allowed';
}

// Email
if ($email === '') {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email address';
}

// Password
if ($password === '') {
    $errors['password'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
} elseif (
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[0-9]/', $password)
) {
    $errors['password'] = 'Password must include uppercase, lowercase, and number';
}

// Confirm password
if ($confirm_password === '') {
    $errors['confirm_password'] = 'Please confirm your password';
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match';
}

// Full name
if ($full_name === '') {
    $errors['full_name'] = 'Full name is required';
}

// Website (OPTIONAL)
if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
    $errors['website'] = 'Website must be a valid URL (including https://)';
}

// Bio length
if (strlen($bio) > 500) {
    $errors['bio'] = 'Bio cannot exceed 500 characters';
}

/* ============================
   RETURN FIELD ERRORS
============================ */
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please correct the highlighted errors',
        'errors' => $errors
    ]);
    exit;
}

/* ============================
   CHECK DUPLICATES
============================ */
try {
    // Username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors['username'] = 'Username is already taken';
    }

    // Email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Email is already registered';
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Some fields need attention',
            'errors' => $errors
        ]);
        exit;
    }

    /* ============================
       CREATE USER
    ============================ */
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users
        (username, email, password, full_name, location, website, bio)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $username,
        $email,
        $hashed,
        $full_name,
        $location ?: null,
        $website ?: null,
        $bio ?: null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully!',
        'redirect' => 'login.php'
    ]);

} catch (PDOException $e) {
    error_log('[SIGNUP_ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
}
