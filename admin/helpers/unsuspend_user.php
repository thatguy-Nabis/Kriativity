<?php
require_once '../config.php';
requireAdmin();

$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) die('Invalid request');

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
    UPDATE users
    SET 
        is_suspended = 0,
        suspended_until = NULL,
        suspension_reason = NULL
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

logAdminActivity(
    $_SESSION['admin_id'],
    'Unsuspend User',
    'user',
    $user_id,
    'User account unsuspended'
);

    $conn->commit();
    header("Location: ../admin_dashboard.php?page=users");
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    die('Failed to unsuspend user');
}
