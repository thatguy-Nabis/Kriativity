<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to report.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$targetType = $data['target_type'] ?? '';
$targetId = (int) ($data['target_id'] ?? 0);
$reportType = $data['report_type'] ?? '';
$description = trim($data['description'] ?? '');

if (!$targetType || !$targetId || !$reportType || !$description) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid report data.'
    ]);
    exit;
}

$reportedUserId = null;
$reportedContentId = null;

if ($targetType === 'user') {
    $reportedUserId = $targetId;
} elseif ($targetType === 'content') {
    $reportedContentId = $targetId;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid target']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO user_reports (
        reporter_id,
        reported_user_id,
        reported_content_id,
        report_type,
        description
    ) VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $_SESSION['user_id'],
    $reportedUserId,
    $reportedContentId,
    $reportType,
    $description
]);

echo json_encode([
    'success' => true,
    'message' => 'Report submitted. Thank you for helping keep the community safe.'
]);
