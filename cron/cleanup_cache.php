<?php
// ============================================
// FILE 4: cron/cleanup_cache.php
// Cron job to clean expired cache (run daily)
// ============================================
?>
<?php
require_once '../config/database.php';
require_once '../services/RecommendationService.php';

// Run this script via cron: 0 3 * * * php /path/to/cleanup_cache.php

$recommendationService = new RecommendationService($pdo);
$recommendationService->clearExpiredCache();

echo "Cache cleanup completed at " . date('Y-m-d H:i:s') . "\n";
?>