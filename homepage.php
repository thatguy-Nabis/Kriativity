<?php
// ============================================
// HOMEPAGE - Interactive Content Discovery Platform
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================

// Include required files
require_once 'init.php';
require_once 'config/database.php';

// Include recommendation service if user is logged in
$recommendations = [];
$showRecommendations = false;

if (isset($_SESSION['user_id'])) {
    require_once 'services/RecommendationService.php';
    try {
        $recommendationService = new RecommendationService($pdo);
        $recommendations = $recommendationService->recommend($_SESSION['user_id'], 10);
        $showRecommendations = !empty($recommendations);
    } catch (Exception $e) {
        error_log("Recommendation error: " . $e->getMessage());
    }
}

// Function to get content from database
function getContentFromDatabase($pdo, $page = 1, $limit = 12, $category = null, $search = null) {
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "SELECT c.*, u.username, u.full_name 
              FROM content c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.is_published = 1";
    
    $params = [];
    
    // Add category filter
    if ($category && $category !== 'all') {
        $query .= " AND c.category = :category";
        $params[':category'] = $category;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (c.title LIKE :search OR c.description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Order by trending (views and likes combined)
    $query .= " ORDER BY c.created_at DESC, (c.views * 0.3 + c.likes * 0.7) DESC";
    $query .= " LIMIT :limit OFFSET :offset";
    
    try {
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching content: " . $e->getMessage());
        return [];
    }
}

// Get categories for filter
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT category FROM content WHERE is_published = 1 AND category IS NOT NULL ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

// Check if this is an AJAX request for infinite scroll
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';

if ($is_ajax) {
    header('Content-Type: application/json');
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    $content = getContentFromDatabase($pdo, $page, 12, $category, $search);
    echo json_encode([
        'cards' => $content,
        'page' => $page,
        'hasMore' => count($content) === 12
    ]);
    exit;
}

// Get categories for filter dropdown
$categories = getCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Discovery - Explore & Curate</title>
    
    <!-- Global Stylesheet -->
    <link rel="stylesheet" href="./styles.css">
    
    <style>
        /* Pinterest-style Tabs Navigation */
        .content-tabs {
            position: sticky;
            top: 70px;
            background: #0a0a0a;
            z-index: 100;
            border-bottom: 1px solid rgba(206, 161, 245, 0.1);
            margin-bottom: 2rem;
            padding: 1rem 0;
        }

        .tabs-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 2rem;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .tabs-container::-webkit-scrollbar {
            display: none;
        }

        .tab-button {
            position: relative;
            background: transparent;
            border: none;
            color: #a0a0a0;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.75rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-button:hover {
            color: #CEA1F5;
        }

        .tab-button.active {
            color: #CEA1F5;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #CEA1F5 0%, #a66fd9 100%);
            border-radius: 3px 3px 0 0;
        }

        .tab-badge {
            background: rgba(206, 161, 245, 0.2);
            color: #CEA1F5;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Tab Content Areas */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* For You Section Styles */
        .for-you-header {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 2rem;
            text-align: center;
        }

        .for-you-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #CEA1F5 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .for-you-description {
            color: #a0a0a0;
            font-size: 1rem;
        }

        /* Empty State for Recommendations */
        .recommendations-empty {
            max-width: 600px;
            margin: 4rem auto;
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.3) 100%);
            border-radius: 20px;
            border: 1px solid rgba(206, 161, 245, 0.2);
        }

        .recommendations-empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .recommendations-empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #CEA1F5;
            margin-bottom: 1rem;
        }

        .recommendations-empty-text {
            color: #b0b0b0;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .start-exploring-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .start-exploring-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(206, 161, 245, 0.4);
        }

        /* Match Badge for Recommendations */
        .match-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(206, 161, 245, 0.3);
            z-index: 10;
        }

        /* Additional styles for filters */
        .filters-container {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-label {
            color: #CEA1F5;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 0.75rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 50px;
            color: #e0e0e0;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .card-meta {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .meta-tag {
            background: rgba(206, 161, 245, 0.15);
            color: #CEA1F5;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #a0a0a0;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #d0d0d0;
        }

        .empty-state-subtext {
            font-size: 0.95rem;
        }

        .card-description {
            font-size: 0.9rem;
            color: #b0b0b0;
            line-height: 1.5;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
        }

        .card-author {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(206, 161, 245, 0.1);
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        .card-author span {
            color: #CEA1F5;
        }

        @media (max-width: 768px) {
            .tabs-container {
                padding: 0 1rem;
                gap: 1.5rem;
            }

            .tab-button {
                font-size: 1rem;
            }

            .filters-container {
                padding: 0 1rem;
            }

            .filter-select {
                min-width: 100%;
            }

            .filter-group {
                width: 100%;
            }

            .for-you-header {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header Component -->
    <?php include 'header.php'; ?>

    <!-- ============================================
         PINTEREST-STYLE TABS NAVIGATION
         ============================================ -->
    <div class="content-tabs">
        <div class="tabs-container">
            <?php if ($showRecommendations): ?>
            <button class="tab-button active" data-tab="for-you">
                <span>✨</span>
                For You
                <span class="tab-badge"><?= count($recommendations) ?></span>
            </button>
            <?php endif; ?>
            
            <button class="tab-button <?= !$showRecommendations ? 'active' : '' ?>" data-tab="all">
                All
            </button>
            
            <button class="tab-button" data-tab="trending">
                🔥 Trending
            </button>
        </div>
    </div>

    <!-- ============================================
         MAIN CONTENT AREA
         ============================================ -->
    <main class="main-container">
        
        <!-- ============================================
             FOR YOU TAB (Recommendations)
             ============================================ -->
        <?php if ($showRecommendations): ?>
        <div class="tab-content active" id="for-you-tab">
            <div class="for-you-header">
                <h1 class="for-you-title">Curated Just for You</h1>
                <p class="for-you-description">
                    Based on your taste, we've picked these artworks you'll love
                </p>
            </div>
            
            <div class="cards-grid">
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
        </div>
        <?php elseif (isset($_SESSION['user_id'])): ?>
        <!-- Show empty state for logged-in users without recommendations -->
        <div class="tab-content active" id="for-you-tab">
            <div class="recommendations-empty">
                <div class="recommendations-empty-icon">🎨</div>
                <h2 class="recommendations-empty-title">Start Building Your Collection</h2>
                <p class="recommendations-empty-text">
                    Like and save artworks you love, and we'll create personalized recommendations just for you!
                </p>
                <a href="#all-tab" class="start-exploring-btn" onclick="switchTab('all')">
                    Start Exploring
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ============================================
             ALL TAB (All Content)
             ============================================ -->
        <div class="tab-content <?= !$showRecommendations ? 'active' : '' ?>" id="all-tab">
            <h1 class="section-title">Explore All Content</h1>
            
            <!-- Filters Section -->
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label" for="categoryFilter">Category:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Content grid container -->
            <div class="cards-grid" id="cardsContainer">
                <!-- Cards will be loaded here via JavaScript -->
            </div>

            <!-- Empty state -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <div class="empty-state-icon">🔍</div>
                <div class="empty-state-text">No content found</div>
                <div class="empty-state-subtext">Try adjusting your filters</div>
            </div>

            <!-- Loading indicator for infinite scroll -->
            <div class="loading-indicator" id="loadingIndicator">
                <div class="spinner"></div>
            </div>

            <!-- End of content message -->
            <div class="end-message" id="endMessage" style="display: none;">
                You've reached the end of available content
            </div>
        </div>
        
        <!-- ============================================
             TRENDING TAB
             ============================================ -->
        <div class="tab-content" id="trending-tab">
            <h1 class="section-title">🔥 Trending Now</h1>
            <p style="text-align: center; color: #a0a0a0; margin-bottom: 2rem;">
                Most popular content this week
            </p>
            
            <div class="cards-grid" id="trendingContainer">
                <!-- Trending content will be loaded here -->
            </div>
            
            <div class="loading-indicator" id="trendingLoadingIndicator">
                <div class="spinner"></div>
            </div>
        </div>
    </main>

    <!-- Include Footer Component -->
    <?php include 'footer.php'; ?>

    <script>
        // ============================================
        // PINTEREST-STYLE TAB SWITCHING
        // ============================================
        (() => {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            window.switchTab = function(tabName) {
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to selected tab
                const selectedButton = document.querySelector(`[data-tab="${tabName}"]`);
                const selectedContent = document.getElementById(`${tabName}-tab`);
                
                if (selectedButton) selectedButton.classList.add('active');
                if (selectedContent) selectedContent.classList.add('active');
                
                // Load content for trending tab if not loaded yet
                if (tabName === 'trending' && !window.trendingLoaded) {
                    loadTrendingContent();
                }
            };
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabName = button.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
        })();

        // ============================================
        // ALL TAB - Infinite Scroll
        // ============================================
        (() => {
            let currentPage = 1;
            let isLoading = false;
            let hasMoreContent = true;
            let currentCategory = 'all';

            const cardsContainer = document.getElementById('cardsContainer');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const endMessage = document.getElementById('endMessage');
            const emptyState = document.getElementById('emptyState');
            const categoryFilter = document.getElementById('categoryFilter');

            function loadMoreCards() {
                if (isLoading || !hasMoreContent) return;
                
                isLoading = true;
                loadingIndicator.classList.add('active');

                const params = new URLSearchParams({
                    ajax: 'true',
                    page: currentPage,
                    category: currentCategory
                });

                fetch(`homepage.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.cards.length === 0) {
                            hasMoreContent = false;
                            if (currentPage === 1) {
                                emptyState.style.display = 'block';
                            } else {
                                endMessage.style.display = 'block';
                            }
                        } else {
                            renderCards(data.cards);
                            hasMoreContent = data.hasMore;
                            if (!data.hasMore) {
                                endMessage.style.display = 'block';
                            }
                        }
                        currentPage++;
                        isLoading = false;
                        loadingIndicator.classList.remove('active');
                    })
                    .catch(error => {
                        console.error('Error loading cards:', error);
                        isLoading = false;
                        loadingIndicator.classList.remove('active');
                    });
            }

            function renderCards(cards) {
                cards.forEach(card => {
                    const cardElement = createCardElement(card);
                    cardsContainer.appendChild(cardElement);
                });
            }

            function createCardElement(card) {
                const div = document.createElement('div');
                div.className = 'content-card';
                
                let imageStyle = '';
                if (card.image_url) {
                    imageStyle = `background-image: url('${card.image_url}'); background-size: cover; background-position: center;`;
                } else {
                    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F'];
                    const color = colors[card.id % colors.length];
                    imageStyle = `background: linear-gradient(135deg, ${color}, #15051d);`;
                }
                
                div.innerHTML = `
                    <div class="card-image" style="${imageStyle}">
                        <div class="card-overlay">
                            <span class="overlay-text">View Details →</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <span class="card-category">${escapeHtml(card.category || 'Uncategorized')}</span>
                        <h3 class="card-title">${escapeHtml(card.title)}</h3>
                        ${card.description ? `<p class="card-description">${escapeHtml(card.description.substring(0, 100))}${card.description.length > 100 ? '...' : ''}</p>` : ''}
                        <div class="card-stats">
                            <div class="stat-item">
                                <span class="stat-icon">👁️</span>
                                <span>${formatNumber(card.views)} views</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">❤️</span>
                                <span>${formatNumber(card.likes)} likes</span>
                            </div>
                        </div>
                        <div class="card-author">
                            <span>by ${escapeHtml(card.username)}</span>
                        </div>
                    </div>
                `;
                
                div.addEventListener('click', () => {
                    window.location.href = `post.php?id=${card.id}`;
                });
                
                return div;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function formatNumber(num) {
                if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
                if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
                return num;
            }

            function resetAndReload() {
                currentPage = 1;
                hasMoreContent = true;
                cardsContainer.innerHTML = '';
                endMessage.style.display = 'none';
                emptyState.style.display = 'none';
                loadMoreCards();
            }

            categoryFilter.addEventListener('change', (e) => {
                currentCategory = e.target.value;
                resetAndReload();
            });

            function setupInfiniteScroll() {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !isLoading && hasMoreContent) {
                            const allTab = document.getElementById('all-tab');
                            if (allTab.classList.contains('active')) {
                                loadMoreCards();
                            }
                        }
                    });
                }, {
                    rootMargin: '200px'
                });

                observer.observe(loadingIndicator);
            }

            document.addEventListener('DOMContentLoaded', () => {
                loadMoreCards();
                setupInfiniteScroll();
            });
        })();

        // ============================================
        // TRENDING TAB - Load trending content
        // ============================================
        window.trendingLoaded = false;
        
        function loadTrendingContent() {
            const container = document.getElementById('trendingContainer');
            const loader = document.getElementById('trendingLoadingIndicator');
            
            loader.classList.add('active');
            
            fetch('homepage.php?ajax=true&page=1&trending=true')
                .then(response => response.json())
                .then(data => {
                    data.cards.forEach(card => {
                        const cardElement = createTrendingCard(card);
                        container.appendChild(cardElement);
                    });
                    loader.classList.remove('active');
                    window.trendingLoaded = true;
                })
                .catch(error => {
                    console.error('Error loading trending:', error);
                    loader.classList.remove('active');
                });
        }
        
        function createTrendingCard(card) {
            const div = document.createElement('div');
            div.className = 'content-card';
            
            let imageStyle = '';
            if (card.image_url) {
                imageStyle = `background-image: url('${card.image_url}'); background-size: cover; background-position: center;`;
            } else {
                const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F'];
                const color = colors[card.id % colors.length];
                imageStyle = `background: linear-gradient(135deg, ${color}, #15051d);`;
            }
            
            div.innerHTML = `
                <div class="card-image" style="${imageStyle}">
                    <div class="card-overlay">
                        <span class="overlay-text">View Details →</span>
                    </div>
                </div>
                <div class="card-content">
                    <span class="card-category">${card.category || 'Uncategorized'}</span>
                    <h3 class="card-title">${card.title}</h3>
                    <div class="card-stats">
                        <div class="stat-item">
                            <span class="stat-icon">👁️</span>
                            <span>${formatNumber(card.views)} views</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-icon">❤️</span>
                            <span>${formatNumber(card.likes)} likes</span>
                        </div>
                    </div>
                    <div class="card-author">
                        <span>by ${escapeHtml(card.username)}</span>
                    </div>
                </div>
            `;
            
            div.addEventListener('click', () => {
                window.location.href = `post.php?id=${card.id}`;
            });
            
            return div;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num;
        }
    </script>
</body>
</html>