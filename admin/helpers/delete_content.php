<?php
require_once '../config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid ID');

$conn->query("DELETE FROM content WHERE id = $id");
logAdminActivity($_SESSION['admin_id'], 'Delete Content', 'content', $id, 'Content deleted');

header('Location: content.php');
