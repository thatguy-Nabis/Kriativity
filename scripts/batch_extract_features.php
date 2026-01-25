<?php
// ============================================
// BATCH FEATURE EXTRACTION SCRIPT
// Run this ONCE to extract features for all existing content
// Location: scripts/batch_extract_features.php
// ============================================

// Prevent timeout for large datasets
set_time_limit(0);
ini_set('memory_limit', '512M');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/feature_extractor.php';

echo "========================================\n";
echo "BATCH FEATURE EXTRACTION STARTED\n";
echo "========================================\n\n";

try {
    // Initialize feature extractor
    $featureExtractor = new FeatureExtractor($pdo);
    
    // Configuration
    $batchSize = 50; // Process 50 items at a time
    $offset = 0;
    $totalProcessed = 0;
    $totalErrors = 0;
    
    echo "Finding content without features...\n\n";
    
    // Count total items to process
    $countStmt = $pdo->query("
        SELECT COUNT(*) 
        FROM content c 
        LEFT JOIN content_features cf ON c.id = cf.content_id 
        WHERE cf.id IS NULL AND c.is_published = 1
    ");
    $totalItems = $countStmt->fetchColumn();
    
    echo "Found {$totalItems} content items to process\n";
    echo "Processing in batches of {$batchSize}...\n\n";
    
    if ($totalItems === 0) {
        echo "✓ All content already has features!\n";
        exit(0);
    }
    
    // Process in batches
    while (true) {
        // Get batch of content IDs without features
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.category 
            FROM content c 
            LEFT JOIN content_features cf ON c.id = cf.content_id 
            WHERE cf.id IS NULL AND c.is_published = 1
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Break if no more items
        if (empty($contentItems)) {
            break;
        }
        
        // Process each item in the batch
        foreach ($contentItems as $content) {
            $contentId = $content['id'];
            $title = $content['title'];
            $category = $content['category'];
            
            try {
                echo "[{$totalProcessed}/{$totalItems}] Processing ID: {$contentId} - \"{$title}\"... ";
                
                // Extract features
                $success = $featureExtractor->extractFeatures($contentId);
                
                if ($success) {
                    echo "✓ SUCCESS\n";
                    $totalProcessed++;
                } else {
                    echo "✗ FAILED\n";
                    $totalErrors++;
                }
                
            } catch (Exception $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
                $totalErrors++;
                error_log("Feature extraction error for content {$contentId}: " . $e->getMessage());
            }
            
            // Small delay to prevent overload
            usleep(100000); // 0.1 seconds
        }
        
        // Move to next batch
        $offset += $batchSize;
        
        // Progress update
        $percentage = round(($totalProcessed / $totalItems) * 100, 2);
        echo "\n--- Batch Complete: {$percentage}% done ({$totalProcessed}/{$totalItems}) ---\n\n";
        
        // Small break between batches
        sleep(1);
    }
    
    echo "\n========================================\n";
    echo "BATCH PROCESSING COMPLETE!\n";
    echo "========================================\n";
    echo "Total Processed: {$totalProcessed}\n";
    echo "Total Errors: {$totalErrors}\n";
    echo "Success Rate: " . round(($totalProcessed / ($totalProcessed + $totalErrors)) * 100, 2) . "%\n";
    echo "========================================\n\n";
    
    // Verify results
    echo "Verifying database...\n";
    $verifyStmt = $pdo->query("
        SELECT COUNT(*) 
        FROM content c 
        LEFT JOIN content_features cf ON c.id = cf.content_id 
        WHERE cf.id IS NULL AND c.is_published = 1
    ");
    $remaining = $verifyStmt->fetchColumn();
    
    if ($remaining > 0) {
        echo "⚠ Warning: {$remaining} items still need features\n";
        echo "Run this script again or check error logs\n";
    } else {
        echo "✓ All published content now has features!\n";
    }
    
    // Optional: Update user profiles to trigger recommendations
    echo "\nUpdating user profiles...\n";
    $pdo->exec("
        UPDATE user_feature_profiles 
        SET needs_recalculation = TRUE
    ");
    echo "✓ User profiles marked for recalculation\n";
    
    echo "\n✓ Batch extraction completed successfully!\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "\n✗ DATABASE ERROR: " . $e->getMessage() . "\n";
    error_log("Batch extraction database error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    echo "\n✗ GENERAL ERROR: " . $e->getMessage() . "\n";
    error_log("Batch extraction error: " . $e->getMessage());
    exit(1);
}

// ============================================
// OPTIONAL: Enhanced version with metadata detection
// ============================================

/**
 * If you want to provide better metadata based on your content structure,
 * you can modify the processing loop like this:
 */

/*
foreach ($contentItems as $content) {
    $contentId = $content['id'];
    
    // Prepare custom metadata based on your data
    $metadata = [];
    
    // Example: Extract style from category
    $category = strtolower($content['category']);
    if (strpos($category, 'abstract') !== false) {
        $metadata['style'] = 'abstract';
    } elseif (strpos($category, 'realistic') !== false) {
        $metadata['style'] = 'realism';
    }
    
    // Example: Set default medium based on category
    if (strpos($category, 'digital') !== false) {
        $metadata['medium'] = 'digital';
    } elseif (strpos($category, 'painting') !== false) {
        $metadata['medium'] = 'oil';
    }
    
    // Extract features with custom metadata
    $success = $featureExtractor->extractFeatures($contentId, $metadata);
    
    // ... rest of the code
}
*/

// ============================================
// USAGE INSTRUCTIONS
// ============================================

/*
 * How to run this script:
 * 
 * Method 1: Command Line (Recommended)
 * -------------------------------------
 * cd /path/to/your/project
 * php scripts/batch_extract_features.php
 * 
 * 
 * Method 2: Browser (Not recommended for large datasets)
 * --------------------------------------------------------
 * Navigate to: http://yoursite.com/scripts/batch_extract_features.php
 * 
 * IMPORTANT: For security, add authentication check at the top:
 * if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
 *     die('Unauthorized');
 * }
 * 
 * 
 * Method 3: Cron Job (For regular updates)
 * -----------------------------------------
 * Add to crontab to run daily at 2 AM:
 * 0 2 * * * php /path/to/scripts/batch_extract_features.php >> /var/log/feature_extraction.log 2>&1
 * 
 * 
 * Troubleshooting:
 * ----------------
 * - If script times out: Reduce $batchSize
 * - If memory errors: Increase memory_limit
 * - If database errors: Check connection and permissions
 * - Check error logs: tail -f /var/log/php_errors.log
 */
?>