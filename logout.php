<?php
// ============================================
// LOGOUT HANDLER
// ============================================

require_once 'init.php';

// Include database and auth functions if needed
require_once 'config/database.php';
require_once 'includes/auth.php';

// Clear remember me token from database if exists
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy remember me cookies
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/', '', true, true);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>