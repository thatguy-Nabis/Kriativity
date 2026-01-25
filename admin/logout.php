<?php
require_once 'config.php';

if (isAdminLoggedIn()) {
    // Log activity
    logAdminActivity($_SESSION['admin_id'], 'Logout', 'admin', $_SESSION['admin_id'], 'Admin logged out');
    
    // Destroy session
    session_destroy();
}

// Redirect to login
header("Location: ../login.php");
exit();
?>