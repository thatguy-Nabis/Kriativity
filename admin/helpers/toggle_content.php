<?php
require_once '../config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0) die('Invalid ID');

if ($action === 'hide') {
    $conn->query("UPDATE content SET is_published = 0 WHERE id = $id");
    logAdminActivity($_SESSION['admin_id'], 'Hide Content', 'content', $id, 'Content hidden');
}

if ($action === 'publish') {
    $conn->query("UPDATE content SET is_published = 1 WHERE id = $id");
    logAdminActivity($_SESSION['admin_id'], 'Publish Content', 'content', $id, 'Content published');
}

header('Location: content.php');
