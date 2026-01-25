<?php
// ============================================
// Single Post View Page
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================
require_once 'init.php';

// Include database connection
require_once 'config/database.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id <= 0) {
    header('Location: homepage.php');
    exit;
}

// Fetch post details
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.full_name, u.profile_image 
        FROM content c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = :id AND c.is_published = 1
    ");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: homepage.php');
        exit;
    }
    
    // Increment view count
    $update_stmt = $pdo->prepare("UPDATE content SET views = views + 1 WHERE id = :id");
    $update_stmt->execute([':id' => $post_id]);
    $post['views']++; // Update local copy
    
} catch (PDOException $e) {
    error_log("Error fetching post: " . $e->getMessage());
    header('Location: homepage.php');
    exit;
}

// Check if user has liked this post (if logged in)
$user_has_liked = false;
if (isset($_SESSION['user_id'])) {
    try {
        $like_stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = :user_id AND content_id = :content_id");
        $like_stmt->execute([':user_id' => $_SESSION['user_id'], ':content_id' => $post_id]);
        $user_has_liked = $like_stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error checking like status: " . $e->getMessage());
    }
}

// Get related posts
try {
    $related_stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM content c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.category = :category 
        AND c.id != :id 
        AND c.is_published = 1 
        ORDER BY RAND() 
        LIMIT 4
    ");
    $related_stmt->execute([':category' => $post['category'], ':id' => $post_id]);
    $related_posts = $related_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching related posts: " . $e->getMessage());
    $related_posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Content Discovery</title>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        .post-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .post-header {
            margin-bottom: 2rem;
        }

        .post-breadcrumb {
            font-size: 0.9rem;
            color: #a0a0a0;
            margin-bottom: 1rem;
        }

        .post-breadcrumb a {
            color: #CEA1F5;
            text-decoration: none;
            transition: color 0.3s;
        }

        .post-breadcrumb a:hover {
            color: #b881e6;
        }

        .post-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #CEA1F5 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .post-meta {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(206, 161, 245, 0.1);
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #CEA1F5, #9B59B6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: #15051d;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 600;
            color: #fff;
        }

        .author-username {
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        .post-stats {
            display: flex;
            gap: 1.5rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #b0b0b0;
        }

        .post-category {
            padding: 0.5rem 1rem;
            background: rgba(206, 161, 245, 0.2);
            color: #CEA1F5;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .post-main-content {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .post-image {
            width: 100%;
            max-height: 600px;
            object-fit: cover;
            background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
        }

        .post-description {
            padding: 2rem;
        }

        .post-description-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #d0d0d0;
            white-space: pre-wrap;
        }

        .post-actions {
            padding: 1.5rem 2rem;
            border-top: 1px solid rgba(206, 161, 245, 0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-like {
            background: rgba(206, 161, 245, 0.1);
            color: #CEA1F5;
            border: 2px solid rgba(206, 161, 245, 0.3);
        }

        .btn-like:hover {
            background: rgba(206, 161, 245, 0.2);
            border-color: #CEA1F5;
        }

        .btn-like.liked {
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            border-color: #CEA1F5;
        }

        .btn-share {
            background: transparent;
            color: #CEA1F5;
            border: 2px solid rgba(206, 161, 245, 0.3);
        }

        .btn-share:hover {
            background: rgba(206, 161, 245, 0.1);
            border-color: #CEA1F5;
        }

        .related-section {
            margin-top: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .related-card {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .related-card:hover {
            transform: translateY(-5px);
            border-color: rgba(206, 161, 245, 0.3);
            box-shadow: 0 10px 30px rgba(206, 161, 245, 0.2);
        }

        .related-card-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
        }

        .related-card-content {
            padding: 1rem;
        }

        .related-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-card-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        .notification {
            position: fixed;
            top: 100px;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1001;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .post-container {
                padding: 0 1rem;
            }

            .post-title {
                font-size: 1.8rem;
            }

            .post-meta {
                gap: 1rem;
            }

            .post-description {
                padding: 1.5rem;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="post-container">
        <!-- Breadcrumb -->
        <div class="post-breadcrumb">
            <a href="homepage.php">Home</a> / 
            <a href="homepage.php?category=<?php echo urlencode($post['category']); ?>"><?php echo htmlspecialchars($post['category']); ?></a> / 
            <?php echo htmlspecialchars($post['title']); ?>
        </div>

        <!-- Post Header -->
        <div class="post-header">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="post-meta">
                <div class="post-author">
                    <div class="author-avatar">
                        <?php echo strtoupper(substr($post['full_name'], 0, 1)); ?>
                    </div>
                    <div class="author-info">
                        <div class="author-name"><?php echo htmlspecialchars($post['full_name']); ?></div>
                        <div class="author-username">@<?php echo htmlspecialchars($post['username']); ?></div>
                    </div>
                </div>
                
                <div class="post-stats">
                    <div class="stat-item">
                        <span>👁️</span>
                        <span><?php echo number_format($post['views']); ?> views</span>
                    </div>
                    <div class="stat-item">
                        <span>❤️</span>
                        <span id="likeCount"><?php echo number_format($post['likes']); ?></span> likes
                    </div>
                </div>
                
                <div class="post-category"><?php echo htmlspecialchars($post['category']); ?></div>
            </div>
        </div>

        <!-- Post Content -->
        <div class="post-main-content">
            <?php if ($post['image_url']): ?>
                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
            <?php else: ?>
                <div class="post-image" style="min-height: 400px;"></div>
            <?php endif; ?>
            
            <?php if ($post['description']): ?>
                <div class="post-description">
                    <p class="post-description-text"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="post-actions">
                <button class="action-btn btn-like <?php echo $user_has_liked ? 'liked' : ''; ?>" id="likeBtn" data-post-id="<?php echo $post_id; ?>">
                    <span id="likeIcon"><?php echo $user_has_liked ? '❤️' : '🤍'; ?></span>
                    <span id="likeText"><?php echo $user_has_liked ? 'Liked' : 'Like'; ?></span>
                </button>
                <button class="action-btn btn-share" onclick="sharePost()">
                    <span>🔗</span>
                    <span>Share</span>
                </button>
            </div>
        </div>

        <!-- Related Posts -->
        <?php if (!empty($related_posts)): ?>
            <div class="related-section">
                <h2 class="section-title">Related Content</h2>
                <div class="related-grid">
                    <?php foreach ($related_posts as $related): ?>
                        <div class="related-card" onclick="window.location.href='post.php?id=<?php echo $related['id']; ?>'">
                            <?php if ($related['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="related-card-image">
                            <?php else: ?>
                                <div class="related-card-image"></div>
                            <?php endif; ?>
                            <div class="related-card-content">
                                <h3 class="related-card-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                                <div class="related-card-stats">
                                    <span>👁️ <?php echo number_format($related['views']); ?></span>
                                    <span>❤️ <?php echo number_format($related['likes']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <div class="notification" id="notification"></div>

    <script>
        const likeBtn = document.getElementById('likeBtn');
        const likeIcon = document.getElementById('likeIcon');
        const likeText = document.getElementById('likeText');
        const likeCount = document.getElementById('likeCount');
        const notification = document.getElementById('notification');
        
        let isLiked = <?php echo $user_has_liked ? 'true' : 'false'; ?>;
        let currentLikes = <?php echo $post['likes']; ?>;

        // Like button handler
        likeBtn.addEventListener('click', async () => {
            <?php if (!isset($_SESSION['user_id'])): ?>
                showNotification('Please login to like posts', 'error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
                return;
            <?php endif; ?>

            likeBtn.disabled = true;

            try {
                const response = await fetch('handlers/like_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: <?php echo $post_id; ?>,
                        action: isLiked ? 'unlike' : 'like'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    isLiked = !isLiked;
                    
                    if (isLiked) {
                        likeBtn.classList.add('liked');
                        likeIcon.textContent = '❤️';
                        likeText.textContent = 'Liked';
                        currentLikes++;
                    } else {
                        likeBtn.classList.remove('liked');
                        likeIcon.textContent = '🤍';
                        likeText.textContent = 'Like';
                        currentLikes--;
                    }
                    
                    likeCount.textContent = currentLikes.toLocaleString();
                    showNotification(result.message, 'success');
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Failed to process like. Please try again.', 'error');
            } finally {
                likeBtn.disabled = false;
            }
        });

        // Share post
        function sharePost() {
            const url = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($post['title']); ?>',
                    url: url
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                });
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>