<?php
/**
 * ============================================
 * User Profile Page
 * Primary Color: #CEA1F5 (Purple)
 * Secondary Color: #15051d (Dark Purple)
 * ============================================
 */

// Include required files
require_once 'includes/session_check.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require authentication
requireLogin('profile.php');

// Get current user data
$user = getCurrentUser($pdo);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Get user's posts
$posts_query = $pdo->prepare("
    SELECT * FROM content 
    WHERE user_id = :user_id 
    AND is_published = 1 
    ORDER BY created_at DESC
");
$posts_query->execute([':user_id' => $user['id']]);
$user_posts = $posts_query->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Profile page for <?php echo htmlspecialchars($user['username']); ?>">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    
    <!-- Global Stylesheet -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        /* ================================
           PROFILE PAGE STYLES
           ================================ */

        /* --- LAYOUT --- */
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* --- PROFILE HEADER --- */
        .profile-header {
            position: relative;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.1) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 16px;
            overflow: hidden;
        }

        .cover-image {
            position: relative;
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
        }

        .cover-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1), transparent);
        }

        /* --- PROFILE INFO --- */
        .profile-info-section {
            position: relative;
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            padding: 2rem;
            margin-top: -80px;
        }

        .profile-image-container {
            position: relative;
            flex-shrink: 0;
        }

        .profile-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            border: 5px solid #15051d;
            border-radius: 50%;
            font-size: 3.5rem;
            font-weight: 700;
            color: #15051d;
            box-shadow: 0 8px 30px rgba(206, 161, 245, 0.3);
        }

        .upload-badge {
            position: absolute;
            right: 5px;
            bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #CEA1F5;
            border: 3px solid #15051d;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(206, 161, 245, 0.5);
        }

        .upload-badge:focus {
            outline: 2px solid rgba(206, 161, 245, 0.5);
            outline-offset: 2px;
        }

        /* --- PROFILE DETAILS --- */
        .profile-details {
            flex: 1;
            padding-top: 60px;
        }

        .profile-username {
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #ffffff 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .profile-handle {
            margin-bottom: 1rem;
            font-size: 1rem;
            color: #a0a0a0;
        }

        .profile-bio {
            max-width: 600px;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.6;
            color: #d0d0d0;
        }

        /* --- META INFO --- */
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #b0b0b0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-icon {
            color: #CEA1F5;
        }

        .meta-item a {
            color: #CEA1F5;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .meta-item a:hover {
            color: #b88de0;
        }

        /* --- STATS --- */
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #CEA1F5;
        }

        .stat-label {
            margin-top: 0.25rem;
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        /* --- ACTION BUTTONS --- */
        .profile-actions {
            display: flex;
            gap: 1rem;
        }

        .action-button {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-button:focus {
            outline: 2px solid rgba(206, 161, 245, 0.5);
            outline-offset: 2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(206, 161, 245, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid rgba(206, 161, 245, 0.5);
            color: #CEA1F5;
        }

        .btn-secondary:hover {
            background: rgba(206, 161, 245, 0.1);
            border-color: #CEA1F5;
        }

        /* --- EDIT SECTION --- */
        .edit-section {
            display: none;
            padding: 2rem;
            margin-top: 2rem;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 16px;
        }

        .edit-section.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* --- FORM ELEMENTS --- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #CEA1F5;
        }

        .form-input,
        .form-textarea {
            padding: 0.875rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            background-color: rgba(206, 161, 245, 0.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .form-input.error,
        .form-textarea.error {
            border-color: #ff6b6b;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .char-counter {
            font-size: 0.8rem;
            color: #a0a0a0;
            text-align: right;
        }

        .form-helper {
            font-size: 0.8rem;
            color: #a0a0a0;
        }

        .error-message {
            display: none;
            font-size: 0.85rem;
            color: #ff6b6b;
        }

        .error-message.show {
            display: block;
        }

        /* --- POSTS SECTION --- */
        .posts-section {
            margin-top: 2rem;
        }

        .posts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .posts-title {
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .btn-create-post {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            color: #15051d;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-create-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(206, 161, 245, 0.4);
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .post-card {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
            border-color: rgba(206, 161, 245, 0.3);
            box-shadow: 0 10px 30px rgba(206, 161, 245, 0.2);
        }

        .post-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
            object-fit: cover;
        }

        .post-content {
            padding: 1.25rem;
        }

        .post-type-badge {
            display: inline-block;
            margin-bottom: 0.75rem;
            padding: 0.25rem 0.75rem;
            background: rgba(206, 161, 245, 0.2);
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #CEA1F5;
        }

        .post-title {
            display: -webkit-box;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.4;
            color: #fff;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-description {
            display: -webkit-box;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
            color: #b0b0b0;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(206, 161, 245, 0.1);
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        .post-stats {
            display: flex;
            gap: 1rem;
        }

        .post-stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .post-date {
            font-size: 0.8rem;
        }

        /* --- EMPTY STATE --- */
        .empty-posts {
            padding: 4rem 2rem;
            text-align: center;
            color: #a0a0a0;
        }

        .empty-posts-icon {
            margin-bottom: 1rem;
            font-size: 4rem;
        }

        .empty-posts-text {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        /* --- NOTIFICATION --- */
        .notification {
            position: fixed;
            top: 100px;
            right: 2rem;
            z-index: 1001;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            transform: translateX(400px);
            transition: all 0.3s ease;
        }

        .notification.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .notification.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0 1rem;
            }

            .cover-image {
                height: 180px;
            }

            .profile-info-section {
                flex-direction: column;
                align-items: center;
                padding: 1.5rem;
                text-align: center;
            }

            .profile-image {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }

            .profile-details {
                padding-top: 1rem;
            }

            .profile-meta,
            .profile-stats,
            .profile-actions {
                justify-content: center;
            }

            .profile-actions {
                flex-direction: column;
                width: 100%;
            }

            .action-button {
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }

            .posts-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .notification {
                right: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Component -->
    <?php include 'header.php'; ?>

    <!-- ============================================
         PROFILE CONTAINER
         ============================================ -->
    <main class="profile-container">
        <!-- Profile Header -->
        <section class="profile-header">
            <div class="cover-image" role="img" aria-label="Profile cover image"></div>
            
            <div class="profile-info-section">
                <!-- Profile Image -->
                <div class="profile-image-container">
                    <div class="profile-image" role="img" aria-label="Profile picture">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <button class="upload-badge" title="Change profile picture" aria-label="Change profile picture">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </button>
                </div>

                <!-- Profile Details -->
                <div class="profile-details">
                    <h1 class="profile-username" id="displayFullName"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-handle">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <?php if (!empty($user['bio'])): ?>
                    <p class="profile-bio" id="displayBio"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php endif; ?>

                    <!-- Meta Information -->
                    <div class="profile-meta">
                        <?php if (!empty($user['location'])): ?>
                        <div class="meta-item">
                            <span class="meta-icon" aria-hidden="true">📍</span>
                            <span id="displayLocation"><?php echo htmlspecialchars($user['location']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <div class="meta-item">
                            <span class="meta-icon" aria-hidden="true">🌐</span>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               id="displayWebsite">
                                <?php echo htmlspecialchars($user['website']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-icon" aria-hidden="true">📅</span>
                            <span>Joined <?php echo date('F Y', strtotime($user['join_date'])); ?></span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo number_format($user['total_posts']); ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo number_format($user['followers']); ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo number_format($user['following']); ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="profile-actions">
                        <button class="action-button btn-primary" id="toggleEditBtn">
                            Edit Profile
                        </button>
                        <button class="action-button btn-secondary" id="shareProfileBtn">
                            Share Profile
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             EDIT PROFILE SECTION
             ============================================ -->
        <section class="edit-section" id="editSection" aria-labelledby="editSectionTitle">
            <h2 class="section-title" id="editSectionTitle">Edit Profile Information</h2>
            
            <form id="profileForm" novalidate>
                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="fullName">Full Name *</label>
                        <input 
                            type="text" 
                            id="fullName" 
                            name="full_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            required
                            aria-required="true"
                            aria-describedby="fullNameError"
                        >
                        <span class="error-message" id="fullNameError" role="alert"></span>
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                            placeholder="City, Country"
                        >
                    </div>

                    <!-- Website -->
                    <div class="form-group">
                        <label class="form-label" for="website">Website</label>
                        <input 
                            type="url" 
                            id="website" 
                            name="website" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>"
                            placeholder="https://yourwebsite.com"
                            aria-describedby="websiteError"
                        >
                        <span class="error-message" id="websiteError" role="alert"></span>
                    </div>

                    <!-- Bio -->
                    <div class="form-group full-width">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            class="form-textarea"
                            maxlength="500"
                            aria-describedby="charCount bioError"
                        ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <div class="char-counter" id="charCount" aria-live="polite">
                            <span id="charCountValue"><?php echo strlen($user['bio'] ?? ''); ?></span>/500 characters
                        </div>
                        <span class="error-message" id="bioError" role="alert"></span>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="form-group full-width">
                        <div class="profile-actions">
                            <button type="submit" class="action-button btn-primary" id="saveBtn">
                                Save Changes
                            </button>
                            <button type="button" class="action-button btn-secondary" id="cancelBtn">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <!-- ============================================
             USER POSTS SECTION
             ============================================ -->
        <section class="posts-section" aria-labelledby="postsSectionTitle">
            <div class="posts-header">
                <h2 class="posts-title" id="postsSectionTitle">My Posts (<?php echo count($user_posts); ?>)</h2>
                <a href="create-post.php" class="btn-create-post">+ Create New Post</a>
            </div>

            <?php if (empty($user_posts)): ?>
                <div class="empty-posts">
                    <div class="empty-posts-icon" aria-hidden="true">📝</div>
                    <div class="empty-posts-text">You haven't created any posts yet</div>
                    <a href="create-post.php" class="btn-create-post">Create Your First Post</a>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($user_posts as $post): ?>
                        <article class="post-card" onclick="window.location.href='post.php?id=<?php echo $post['id']; ?>'" role="button" tabindex="0">
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     class="post-image"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="post-image" role="img" aria-label="Default post image"></div>
                            <?php endif; ?>
                            
                            <div class="post-content">
                                <span class="post-type-badge"><?php echo ucfirst($post['content_type']); ?></span>
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                
                                <?php if ($post['description']): ?>
                                    <p class="post-description"><?php echo htmlspecialchars($post['description']); ?></p>
                                <?php endif; ?>
                                
                                <div class="post-meta">
                                    <div class="post-stats">
                                        <div class="post-stat">
                                            <span aria-hidden="true">👁️</span>
                                            <span><?php echo number_format($post['views']); ?></span>
                                        </div>
                                        <div class="post-stat">
                                            <span aria-hidden="true">❤️</span>
                                            <span><?php echo number_format($post['likes']); ?></span>
                                        </div>
                                    </div>
                                    <time class="post-date" datetime="<?php echo $post['created_at']; ?>">
                                        <?php 
                                        $date = new DateTime($post['created_at']);
                                        echo $date->format('M d, Y');
                                        ?>
                                    </time>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer Component -->
    <?php include 'footer.php'; ?>

    <!-- Notification Element -->
    <div class="notification" id="notification" role="alert" aria-live="polite"></div>

    <script>
        /**
         * ============================================
         * Profile Management JavaScript
         * ============================================
         */

        // DOM Elements
        const toggleEditBtn = document.getElementById('toggleEditBtn');
        const editSection = document.getElementById('editSection');
        const profileForm = document.getElementById('profileForm');
        const cancelBtn = document.getElementById('cancelBtn');
        const bioInput = document.getElementById('bio');
        const charCountValue = document.getElementById('charCountValue');
        const notification = document.getElementById('notification');
        const saveBtn = document.getElementById('saveBtn');

        /**
         * Toggle edit section visibility
         */
        toggleEditBtn.addEventListener('click', () => {
            const isActive = editSection.classList.toggle('active');
            
            toggleEditBtn.textContent = isActive ? 'Close Editor' : 'Edit Profile';
            
            if (isActive) {
                editSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                // Focus first input for accessibility
                document.getElementById('fullName').focus();
            }
        });

        /**
         * Cancel editing
         */
        cancelBtn.addEventListener('click', () => {
            editSection.classList.remove('active');
            toggleEditBtn.textContent = 'Edit Profile';
            profileForm.reset();
            clearErrors();
            updateCharCount();
        });

        /**
         * Character counter for bio
         */
        function updateCharCount() {
            charCountValue.textContent = bioInput.value.length;
        }

        bioInput.addEventListener('input', updateCharCount);

        /**
         * Show notification
         */
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }

        /**
         * Clear all form errors
         */
        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });
            
            document.querySelectorAll('.form-input, .form-textarea').forEach(el => {
                el.classList.remove('error');
            });
        }

        /**
         * Display form errors
         */
        function displayErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorEl = document.getElementById(field + 'Error');
                const inputEl = document.getElementById(field);
                
                if (errorEl && inputEl) {
                    errorEl.textContent = errors[field];
                    errorEl.classList.add('show');
                    inputEl.classList.add('error');
                    inputEl.setAttribute('aria-invalid', 'true');
                }
            });
        }

        /**
         * Handle form submission
         */
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;

            try {
                const formData = new FormData(profileForm);
                
                const response = await fetch('handlers/profile_update_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Update display values
                    const fullName = formData.get('full_name');
                    const bio = formData.get('bio');
                    const location = formData.get('location');
                    const website = formData.get('website');

                    document.getElementById('displayFullName').textContent = fullName;
                    
                    const bioDisplay = document.getElementById('displayBio');
                    if (bio) {
                        bioDisplay.textContent = bio;
                        bioDisplay.style.display = 'block';
                    } else {
                        bioDisplay.style.display = 'none';
                    }
                    
                    const locationDisplay = document.getElementById('displayLocation');
                    if (locationDisplay) {
                        locationDisplay.textContent = location;
                    }
                    
                    const websiteLink = document.getElementById('displayWebsite');
                    if (websiteLink && website) {
                        websiteLink.textContent = website;
                        websiteLink.href = website;
                    }

                    showNotification(result.message, 'success');

                    // Close edit section
                    setTimeout(() => {
                        editSection.classList.remove('active');
                        toggleEditBtn.textContent = 'Edit Profile';
                    }, 500);
                } else {
                    showNotification(result.message, 'error');
                    if (result.errors) {
                        displayErrors(result.errors);
                    }
                }
            } catch (error) {
                console.error('Profile update error:', error);
                showNotification('Failed to update profile. Please try again.', 'error');
            } finally {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        });

        /**
         * Real-time validation on blur
         */
        document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
            input.addEventListener('blur', () => {
                const errorEl = document.getElementById(input.id + 'Error');
                
                if (input.hasAttribute('required') && !input.value.trim()) {
                    input.classList.add('error');
                    input.setAttribute('aria-invalid', 'true');
                } else {
                    input.classList.remove('error');
                    input.removeAttribute('aria-invalid');
                    if (errorEl) {
                        errorEl.classList.remove('show');
                    }
                }
            });
        });

        /**
         * Make post cards keyboard accessible
         */
        document.querySelectorAll('.post-card').forEach(card => {
            card.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });

        // Initialize
        console.log('Profile page loaded successfully');
    </script>
</body>
</html>