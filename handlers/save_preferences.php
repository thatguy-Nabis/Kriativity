<?php
require_once "../init.php";

if (!isset($_SESSION['user_id'])) exit;

$userId = $_SESSION['user_id'];

$categories = json_encode($_POST['categories'] ?? []);
$contentTypes = json_encode($_POST['content_types'] ?? []);
$goal = $_POST['goal'];

$stmt = $pdo->prepare("
    INSERT INTO user_preferences 
    (user_id, preferred_categories, preferred_content_types, discovery_goal)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        preferred_categories = VALUES(preferred_categories),
        preferred_content_types = VALUES(preferred_content_types),
        discovery_goal = VALUES(discovery_goal)
");
$stmt->execute([$userId, $categories, $contentTypes, $goal]);

$pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?")
    ->execute([$userId]);

header("Location: ../homepage.php");
exit;
