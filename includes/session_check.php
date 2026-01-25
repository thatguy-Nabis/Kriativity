<?php
// ============================================
// SESSION CHECK AND AUTO-LOGIN
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Check if user is logged in via session
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect to login page if not logged in
 * @param string $redirect_to Page to redirect to after login
 */
function requireLogin($redirect_to = '') {
    if (!isLoggedIn()) {
        if (!empty($redirect_to)) {
            $_SESSION['redirect_after_login'] = $redirect_to;
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect if already logged in
 * @param string $redirect_to Page to redirect to
 */
function redirectIfLoggedIn($redirect_to = 'homepage.php') {
    if (isLoggedIn()) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * Check remember me cookie and auto-login
 * @param PDO $pdo
 * @return bool
 */
function checkRememberMe($pdo) {
    // If already logged in, no need to check
    if (isLoggedIn()) {
        return true;
    }
    
    // Check if remember me cookies exist
    if (!isset($_COOKIE['remember_token']) || !isset($_COOKIE['remember_user'])) {
        return false;
    }
    
    $user_id = (int)$_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    
    // Verify token
    if (verifyRememberToken($pdo, $user_id, $token)) {
        // Get user data
        $user = getUserById($pdo, $user_id);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            // Generate CSRF token
            generateCSRFToken();
            
            // Update last login
            try {
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
            } catch (PDOException $e) {
                error_log("Update Last Login Error: " . $e->getMessage());
            }
            
            return true;
        }
    }
    
    // Invalid token, clear cookies
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    setcookie('remember_user', '', time() - 3600, '/', '', true, true);
    
    return false;
}

/**
 * Get current logged in user
 * @param PDO $pdo
 * @return array|null
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    return getUserById($pdo, $_SESSION['user_id']);
}

/**
 * Refresh user session data
 * @param PDO $pdo
 */
function refreshUserSession($pdo) {
    if (isLoggedIn()) {
        $user = getUserById($pdo, $_SESSION['user_id']);
        if ($user) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
        }
    }
}

// Auto-check remember me on page load
checkRememberMe($pdo);
?>