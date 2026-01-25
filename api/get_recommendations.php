<?php
// ============================================
// FILE 2: api/get_recommendations.php
// AJAX endpoint for getting recommendations
// ============================================
?>
<?php
require_once '../init.php';
require_once '../config/database.php';
require_once '../services/RecommendationService.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$useCache = isset($_GET['use_cache']) ? $_GET['use_cache'] === 'true' : true;

try {
    $recommendationService = new RecommendationService($pdo);
    $recommendations = $recommendationService->recommend($userId, $limit, $useCache);
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} catch (Exception $e) {
    error_log("Recommendation Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get recommendations'
    ]);
}
?>

<?php
// ============================================
// FILE 3: recommendations.php
// Page to display recommendations
// ============================================
?>
<?php
require_once 'init.php';
require_once 'config/database.php';
require_once 'services/RecommendationService.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$recommendationService = new RecommendationService($pdo);

// Get recommendations
$recommendations = $recommendationService->recommend($userId, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommended for You - Kriativity</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="section-header">
            <h1 class="section-title">✨ Recommended for You</h1>
            <p class="section-subtitle">
                Based on your likes and collections, we think you'll love these
            </p>
        </div>

        <?php if (!empty($recommendations)): ?>
            <div class="cards-grid" id="recommendationsGrid">
                <?php foreach ($recommendations as $rec): ?>
                    <?php
                        // Determine background for card image
                        $imageStyle = '';
                        if (!empty($rec['image_url'])) {
                            $imageStyle = "background-image: url('" . htmlspecialchars($rec['image_url']) . "'); background-size: cover; background-position: center;";
                        } else {
                            $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F'];
                            $color = $colors[$rec['content_id'] % count($colors)];
                            $imageStyle = "background: linear-gradient(135deg, {$color}, #15051d);";
                        }
                    ?>
                    <div class="content-card" onclick="window.location.href='post.php?id=<?= $rec['content_id'] ?>'">
                        <div class="card-image" style="<?= $imageStyle ?>">
                            <div class="card-overlay">
                                <span class="overlay-text">View Details →</span>
                            </div>
                            <!-- Match score badge -->
                            <div class="match-badge">
                                <?= round($rec['similarity_score'] * 100) ?>% Match
                            </div>
                        </div>
                        <div class="card-content">
                            <span class="card-category"><?= htmlspecialchars($rec['category'] ?? 'Uncategorized') ?></span>
                            <h3 class="card-title"><?= htmlspecialchars($rec['title']) ?></h3>
                            <?php if (!empty($rec['description'])): ?>
                            <p class="card-description">
                                <?= htmlspecialchars(substr($rec['description'], 0, 100)) ?>
                                <?= strlen($rec['description']) > 100 ? '...' : '' ?>
                            </p>
                            <?php endif; ?>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <span class="stat-icon">👁️</span>
                                    <span><?= number_format($rec['views']) ?> views</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-icon">❤️</span>
                                    <span><?= number_format($rec['likes']) ?> likes</span>
                                </div>
                            </div>
                            <div class="card-author">
                                <span>by <?= htmlspecialchars($rec['username']) ?></span>
                            </div>
                            <?php if (!empty($rec['style']) || !empty($rec['medium'])): ?>
                            <div class="card-meta">
                                <?php if (!empty($rec['style'])): ?>
                                    <span class="meta-tag"><?= htmlspecialchars($rec['style']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($rec['medium'])): ?>
                                    <span class="meta-tag"><?= htmlspecialchars($rec['medium']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🎨</div>
                <h2 class="empty-title">Start Exploring to Get Recommendations</h2>
                <p class="empty-text">
                    Like and save content you enjoy, and we'll suggest similar artworks you might love!
                </p>
                <a href="homepage.php" class="btn-primary">Explore Content</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <style>
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-subtitle {
            color: #a0a0a0;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .match-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(206, 161, 245, 0.3);
        }

        .card-meta {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .meta-tag {
            background: rgba(206, 161, 245, 0.1);
            color: #CEA1F5;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(206, 161, 245, 0.4);
        }
    </style>
</body>
</html>