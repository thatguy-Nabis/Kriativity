<?php
require_once '../config.php';
requireAdmin();

$user_id = (int)($_POST['user_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$type = $_POST['suspension_type'] ?? 'temporary';
$days = (int)($_POST['days'] ?? 0);

if ($user_id <= 0 || $reason === '') {
    die('Invalid request');
}

$suspended_until = null;
if ($type === 'temporary') {
    if ($days <= 0) {
        die('Invalid duration');
    }
    $suspended_until = date('Y-m-d H:i:s', strtotime("+$days days"));
}

$conn->begin_transaction();

try {
    // Mark user suspended
    $stmt = $conn->prepare("
        UPDATE users 
        SET is_suspended = 1, suspension_reason = ?, suspended_until = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $reason, $suspended_until, $user_id);
    $stmt->execute();

    // Log suspension
    $stmt = $conn->prepare("
        INSERT INTO user_suspensions 
        (user_id, admin_id, reason, suspension_type, suspended_until)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iisss",
        $user_id,
        $_SESSION['admin_id'],
        $reason,
        $type,
        $suspended_until
    );
    $stmt->execute();

    logAdminActivity(
        $_SESSION['admin_id'],
        'Suspend user',
        'user',
        $user_id,
        $reason
    );

    $conn->commit();
    header("Location: ../admin_dashboard.php?page=users");
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    die('Failed to suspend user');
}
