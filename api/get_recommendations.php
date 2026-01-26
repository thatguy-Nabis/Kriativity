<?php
// ============================================
// GET RECOMMENDATIONS (FASTAPI PROXY)
// ============================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['recommendations' => []]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$limit  = max(1, min(50, $limit));

// FastAPI endpoint
$FASTAPI_URL = "http://127.0.0.1:8000/recommend"
             . "?user_id={$userId}&limit={$limit}";

$ch = curl_init($FASTAPI_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 3,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error || !$response) {
    echo json_encode(['recommendations' => []]);
    exit;
}

$data = json_decode($response, true);

// Only expose what frontend needs
echo json_encode([
    'recommendations' => $data['recommendations'] ?? []
]);
exit;
