<?php
// ============================================
// SEARCH PAGE - Backend Logic
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================

session_start();

// Database connection
$db_host = 'localhost';
$db_name = 'content_discovery';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed");
}

// Get and sanitize search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query_safe = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');

// Initialize results arrays
$users_results = [];
$content_results = [];
$collections_results = [];
$total_results = 0;

// Only search if query is not empty
if (!empty($search_query) && strlen($search_query) >= 2) {
    // Prepare search pattern for LIKE queries
    $search_pattern = '%' . $search_query . '%';
    
    try {
        // ============================================
        // SEARCH USERS (Creators)
        // ============================================
        $users_stmt = $pdo->prepare("
            SELECT 
                id, 
                username, 
                full_name, 
                bio, 
                location,
                profile_image,
                followers,
                total_posts
            FROM users
            WHERE is_active = 1 
            AND (
                username LIKE :pattern 
                OR full_name LIKE :pattern 
                OR bio LIKE :pattern
                OR location LIKE :pattern
            )
            ORDER BY followers DESC
            LIMIT 10
        ");
        $users_stmt->execute(['pattern' => $search_pattern]);
        $users_results = $users_stmt->fetchAll();
        
        // ============================================
        // SEARCH CONTENT
        // ============================================
        $content_stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.title,
                c.description,
                c.category,
                c.image_url,
                c.content_type,
                c.views,
                c.likes,
                c.created_at,
                u.username,
                u.full_name,
                u.profile_image
            FROM content c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.is_published = 1
            AND (
                c.title LIKE :pattern 
                OR c.description LIKE :pattern
                OR c.category LIKE :pattern
            )
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $content_stmt->execute(['pattern' => $search_pattern]);
        $content_results = $content_stmt->fetchAll();
        
        // ============================================
        // SEARCH COLLECTIONS
        // ============================================
        $collections_stmt = $pdo->prepare("
            SELECT 
                col.id,
                col.name,
                col.description,
                col.created_at,
                u.username,
                u.full_name,
                COUNT(ci.id) as item_count
            FROM collections col
            INNER JOIN users u ON col.user_id = u.id
            LEFT JOIN collection_items ci ON col.id = ci.collection_id
            WHERE col.is_public = 1
            AND (
                col.name LIKE :pattern 
                OR col.description LIKE :pattern
            )
            GROUP BY col.id
            ORDER BY col.created_at DESC
            LIMIT 10
        ");
        $collections_stmt->execute(['pattern' => $search_pattern]);
        $collections_results = $collections_stmt->fetchAll();
        
    } catch(PDOException $e) {
        error_log("Search Error: " . $e->getMessage());
    }
}

// Calculate total results
$total_results = count($users_results) + count($content_results) + count($collections_results);

// Helper function to highlight search terms
function highlightSearchTerm($text, $search) {
    if (empty($search) || empty($text)) return htmlspecialchars($text);
    
    $highlighted = preg_replace(
        '/(' . preg_quote($search, '/') . ')/i',
        '<mark>$1</mark>',
        htmlspecialchars($text)
    );
    
    return $highlighted;
}

// Helper function to truncate text
function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// Helper function to format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($search_query_safe) ? 'Search: ' . $search_query_safe : 'Search' ?> - Discover</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Search-specific styles */
        .search-header {
            padding: 2rem 0 1rem;
            border-bottom: 1px solid rgba(206, 161, 245, 0.1);
            margin-bottom: 2rem;
        }

        .search-title {
            font-size: 2rem;
            font-weight: 700;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
        }

        .search-query {
            color: #CEA1F5;
        }

        .search-stats {
            color: #a0a0a0;
            font-size: 0.95rem;
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1.25rem;
            background: rgba(206, 161, 245, 0.1);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 20px;
            color: #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            border-color: transparent;
        }

        .filter-btn:hover:not(.active) {
            background: rgba(206, 161, 245, 0.15);
            border-color: #CEA1F5;
        }

        .results-section {
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            padding: 0.25rem 0.75rem;
            font-weight: 700;
            color: #CEA1F5;
            margin-top: 25px;
        }

        .section-count {
            background: rgba(206, 161, 245, 0.15);
            color: #CEA1F5;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* User Results */
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .user-card {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-card:hover {
            transform: translateY(-5px);
            border-color: #CEA1F5;
            box-shadow: 0 10px 30px rgba(206, 161, 245, 0.2);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            font-weight: 700;
            color: #15051d;
        }

        .user-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #e0e0e0;
            margin-bottom: 0.25rem;
        }

        .user-username {
            color: #CEA1F5;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .user-bio {
            color: #a0a0a0;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .user-stats {
            display: flex;
            justify-content: space-around;
            padding-top: 1rem;
            border-top: 1px solid rgba(206, 161, 245, 0.1);
        }

        .user-stat {
            text-align: center;
        }

        .stat-value {
            display: block;
            font-weight: 700;
            color: #CEA1F5;
            font-size: 1rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #a0a0a0;
        }

        /* Highlight matched text */
        mark {
            background-color: rgba(206, 161, 245, 0.3);
            color: #CEA1F5;
            padding: 0.1rem 0.2rem;
            border-radius: 3px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #a0a0a0;
            font-size: 1rem;
        }

        .suggestions {
            margin-top: 1.5rem;
            color: #a0a0a0;
            font-size: 0.9rem;
        }

        .suggestions ul {
            list-style: none;
            padding: 0;
            margin-top: 0.75rem;
        }

        .suggestions li {
            margin-bottom: 0.5rem;
        }

        .suggestions li::before {
            content: "•";
            color: #CEA1F5;
            font-weight: bold;
            display: inline-block;
            width: 1em;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <!-- Search Header -->
        <div class="search-header">
            <?php if (!empty($search_query_safe)): ?>
                <h1 class="search-title">
                    Search results for "<span class="search-query"><?= $search_query_safe ?></span>"
                </h1>
                <p class="search-stats">
                    Found <?= $total_results ?> result<?= $total_results !== 1 ? 's' : '' ?>
                </p>
            <?php else: ?>
                <h1 class="search-title">Search</h1>
                <p class="search-stats">Enter a search term to find creators, content, and collections</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($search_query_safe)): ?>
            <!-- Filter Tabs -->
            <div class="search-filters">
                <button class="filter-btn active" data-filter="all">
                    All Results (<?= $total_results ?>)
                </button>
                <button class="filter-btn" data-filter="users">
                    Creators (<?= count($users_results) ?>)
                </button>
                <button class="filter-btn" data-filter="content">
                    Content (<?= count($content_results) ?>)
                </button>
                <button class="filter-btn" data-filter="collections">
                    Collections (<?= count($collections_results) ?>)
                </button>
            </div>

            <?php if ($total_results > 0): ?>
                <!-- Users Results -->
                <?php if (count($users_results) > 0): ?>
                <div class="results-section" data-section="users">
                    <div class="section-header">
                        <h2 class="section-title">Creators</h2>
                        <span class="section-count"><?= count($users_results) ?></span>
                    </div>
                    
                    <div class="users-grid">
                        <?php foreach ($users_results as $user): ?>
                        <a href="profile.php?id=<?= $user['id'] ?>" class="user-card">
                            <div class="user-avatar">
                                <?= strtoupper($user['full_name'][0]) ?>
                            </div>
                            <div class="user-name"><?= highlightSearchTerm($user['full_name'], $search_query) ?></div>
                            <div class="user-username">@<?= highlightSearchTerm($user['username'], $search_query) ?></div>
                            <?php if (!empty($user['bio'])): ?>
                            <p class="user-bio">
                                <?= highlightSearchTerm(truncateText($user['bio'], 100), $search_query) ?>
                            </p>
                            <?php endif; ?>
                            <div class="user-stats">
                                <div class="user-stat">
                                    <span class="stat-value"><?= formatNumber($user['total_posts']) ?></span>
                                    <span class="stat-label">Posts</span>
                                </div>
                                <div class="user-stat">
                                    <span class="stat-value"><?= formatNumber($user['followers']) ?></span>
                                    <span class="stat-label">Followers</span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

<!-- Content Results -->
<?php if (count($content_results) > 0): ?>
<div class="results-section" data-section="content">
    <div class="section-header">
        <h2 class="section-title">Content</h2>
        <span class="section-count"><?= count($content_results) ?></span>
    </div>
    
    <div class="cards-grid">
        <?php foreach ($content_results as $content): ?>
        <a href="post.php?id=<?= $content['id'] ?>" class="content-card">
            <div class="card-image" style="background: url('<?= htmlspecialchars($content['image_url']) ?>') center/cover no-repeat;">
                <div class="card-overlay">
                    <span class="overlay-text">View Details</span>
                </div>
            </div>
            <div class="card-content">
                <span class="card-category"><?= htmlspecialchars($content['category']) ?></span>
                <h3 class="card-title"><?= highlightSearchTerm($content['title'], $search_query) ?></h3>
                <?php if (!empty($content['description'])): ?>
                <p class="card-description">
                    <?= highlightSearchTerm(truncateText($content['description'], 120), $search_query) ?>
                </p>
                <?php endif; ?>
                <div class="card-meta">
                    By <?= htmlspecialchars($content['full_name']) ?>
                </div>
                <div class="card-stats">
                    <span class="stat-item">👁️ <?= formatNumber($content['views']) ?></span>
                    <span class="stat-item">❤️ <?= formatNumber($content['likes']) ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Collections Results -->
<?php if (count($collections_results) > 0): ?>
<div class="results-section" data-section="collections">
    <div class="section-header">
        <h2 class="section-title">Collections</h2>
        <span class="section-count"><?= count($collections_results) ?></span>
    </div>
    
    <div class="cards-grid">
        <?php foreach ($collections_results as $collection): ?>
        <a href="collection.php?id=<?= $collection['id'] ?>" class="content-card">
            <div class="card-image" style="
                background: <?= !empty($collection['image_url']) 
                    ? "url('" . htmlspecialchars($collection['image_url']) . "') center/cover no-repeat" 
                    : 'linear-gradient(135deg, #CEA1F5 0%, #15051d 100%)' ?>;">
                <div class="card-overlay">
                    <span class="overlay-text"><?= $collection['item_count'] ?> items</span>
                </div>
            </div>
            <div class="card-content">
                <h3 class="card-title"><?= highlightSearchTerm($collection['name'], $search_query) ?></h3>
                <?php if (!empty($collection['description'])): ?>
                <p class="card-description">
                    <?= highlightSearchTerm(truncateText($collection['description'], 120), $search_query) ?>
                </p>
                <?php endif; ?>
                <div class="card-meta">
                    By <?= htmlspecialchars($collection['full_name']) ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h2 class="empty-title">No results found</h2>
                    <p class="empty-text">
                        We couldn't find anything matching "<strong><?= $search_query_safe ?></strong>"
                    </p>
                    <div class="suggestions">
                        <strong>Try:</strong>
                        <ul>
                            <li>Checking your spelling</li>
                            <li>Using different keywords</li>
                            <li>Searching for more general terms</li>
                            <li>Using fewer keywords</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- No Query State -->
            <div class="empty-state">
                <div class="empty-icon">✨</div>
                <h2 class="empty-title">Start Searching</h2>
                <p class="empty-text">
                    Use the search bar above to discover amazing creators, content, and collections
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Filter functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const sections = document.querySelectorAll('.results-section');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Show/hide sections
                if (filter === 'all') {
                    sections.forEach(s => s.style.display = 'block');
                } else {
                    sections.forEach(s => {
                        if (s.dataset.section === filter) {
                            s.style.display = 'block';
                        } else {
                            s.style.display = 'none';
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>