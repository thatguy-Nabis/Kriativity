<?php
// ============================================
// LOGIN HANDLER
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
$username_or_email = sanitizeInput($_POST['username_or_email'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Basic validation
if (empty($username_or_email)) {
    $response['message'] = 'Username or email is required';
    echo json_encode($response);
    exit;
}

if (empty($password)) {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

// Attempt login
$result = loginUser($pdo, $username_or_email, $password);

if ($result['success']) {
    $user = $result['user'];

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];

    // Generate CSRF token for the session
    generateCSRFToken();

    // 🔴 ONBOARDING SAFETY CHECK (CORRECT PLACE)
    if ((int) $user['onboarding_completed'] === 0) {
    $response['success'] = true;
    $response['redirect'] = '../onboarding_preferences.php';
    echo json_encode($response);
    exit;
}


    // Handle remember me
    if ($remember_me) {
        $token = setRememberToken($pdo, $user['id']);
        if ($token) {
            // Set cookie for 30 days
            setcookie(
                'remember_token',
                $token,
                time() + (86400 * 30), // 30 days
                '/',
                '',
                true, // Secure (HTTPS only)
                true  // HTTP only
            );
            setcookie(
                'remember_user',
                $user['id'],
                time() + (86400 * 30),
                '/',
                '',
                true,
                true
            );
        }
    }

    $response['success'] = true;
    $response['message'] = $result['message'];
    $response['redirect'] = '../homepage.php';
    $response['user'] = [
        'username' => $user['username'],
        'full_name' => $user['full_name']
    ];
} else {
    $response['message'] = $result['message'];
}

echo json_encode($response);
exit;
?>