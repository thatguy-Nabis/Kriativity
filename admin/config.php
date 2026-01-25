<?php
// ============================================
// DATABASE CONFIGURATION
// ============================================

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'content_discovery');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Sanitize input data
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect if not logged in
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Log admin activity
 */
function logAdminActivity($admin_id, $action, $target_type, $target_id, $description) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $admin_id, $action, $target_type, $target_id, $description, $ip_address);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get admin by ID
 */
function getAdminById($admin_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, email, full_name, last_login FROM admins WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    return $admin;
}

/**
 * Format date/time ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . " seconds ago";
    } elseif ($difference < 3600) {
        return floor($difference / 60) . " minutes ago";
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . " hours ago";
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . " days ago";
    } else {
        return date('M d, Y', $timestamp);
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>