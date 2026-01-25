<?php
// ============================================
// User Profile Page - Updated with Handlers
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================

// Include session check
require_once 'includes/session_check.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require user to be logged in
requireLogin('profile.php');

// Get current user data from database
$user = getCurrentUser($pdo);

if (!$user) {
    // If user not found, logout and redirect
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    
    <!-- Global Stylesheet -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        /* Additional profile-specific styles */
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-header {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.1) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            margin-bottom: 2rem;
        }

        .cover-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
            position: relative;
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

        .profile-info-section {
            padding: 2rem;
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-top: -80px;
            position: relative;
        }

        .profile-image-container {
            position: relative;
            flex-shrink: 0;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #15051d;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 700;
            color: #15051d;
            box-shadow: 0 8px 30px rgba(206, 161, 245, 0.3);
        }

        .upload-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: #CEA1F5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid #15051d;
            transition: all 0.3s ease;
        }

        .upload-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(206, 161, 245, 0.5);
        }

        .profile-details {
            flex: 1;
            padding-top: 60px;
        }

        .profile-username {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-handle {
            font-size: 1rem;
            color: #a0a0a0;
            margin-bottom: 1rem;
        }

        .profile-bio {
            font-size: 1rem;
            line-height: 1.6;
            color: #d0d0d0;
            margin-bottom: 1.5rem;
            max-width: 600px;
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
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

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #CEA1F5;
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #a0a0a0;
            margin-top: 0.25rem;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
        }

        .action-button {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
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

        .edit-section {
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%);
            border: 1px solid rgba(206, 161, 245, 0.15);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            display: none;
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
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

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
            font-weight: 600;
            color: #CEA1F5;
            font-size: 0.9rem;
        }

        .form-input,
        .form-textarea {
            padding: 0.875rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            background-color: rgba(206, 161, 245, 0.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .form-input.error {
            border-color: #ff6b6b;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
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
            color: #ff6b6b;
            font-size: 0.85rem;
            display: none;
        }

        .error-message.show {
            display: block;
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
            .profile-info-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 1.5rem;
            }

            .profile-details {
                padding-top: 1rem;
            }

            .profile-meta,
            .profile-stats,
            .profile-actions {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .profile-container {
                padding: 0 1rem;
            }

            .cover-image {
                height: 180px;
            }

            .profile-image {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }

            .notification {
                right: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header Component -->
    <?php include 'header.php'; ?>

    <!-- ============================================
         PROFILE CONTAINER
         ============================================ -->
    <div class="profile-container">
        <!-- Profile Header with Cover -->
        <div class="profile-header">
            <div class="cover-image"></div>
            
            <div class="profile-info-section">
                <!-- Profile Image -->
                <div class="profile-image-container">
                    <div class="profile-image">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="upload-badge" title="Change profile picture">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="profile-details">
                    <h1 class="profile-username" id="displayFullName"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-handle">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="profile-bio" id="displayBio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>

                    <!-- Profile Meta Information -->
                    <div class="profile-meta">
                        <?php if (!empty($user['location'])): ?>
                        <div class="meta-item">
                            <span class="meta-icon">📍</span>
                            <span id="displayLocation"><?php echo htmlspecialchars($user['location']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <div class="meta-item">
                            <span class="meta-icon">🌐</span>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" style="color: #CEA1F5; text-decoration: none;" id="displayWebsite">
                                <?php echo htmlspecialchars($user['website']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-icon">📅</span>
                            <span>Joined <?php echo date('F Y', strtotime($user['join_date'])); ?></span>
                        </div>
                    </div>

                    <!-- Profile Stats -->
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
                        <button class="action-button btn-secondary">
                            Share Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             EDIT PROFILE SECTION
             ============================================ -->
        <div class="edit-section" id="editSection">
            <h2 class="section-title">Edit Profile Information</h2>
            
            <form id="profileForm">
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
                        >
                        <span class="error-message" id="fullNameError"></span>
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
                        >
                        <span class="error-message" id="websiteError"></span>
                    </div>

                    <!-- Bio -->
                    <div class="form-group full-width">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            class="form-textarea"
                            maxlength="500"
                        ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount"><?php echo strlen($user['bio'] ?? ''); ?></span>/500 characters
                        </div>
                        <span class="error-message" id="bioError"></span>
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
        </div>
    </div>

    <?php
// ============================================
// Add this section to profile.php after the edit section
// ============================================

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

<!-- Add this CSS to the <style> section in profile.php -->
<style>
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
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.btn-create-post {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
    color: #15051d;
    border: none;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
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
    transition: all 0.3s ease;
    cursor: pointer;
}

.post-card:hover {
    transform: translateY(-5px);
    border-color: rgba(206, 161, 245, 0.3);
    box-shadow: 0 10px 30px rgba(206, 161, 245, 0.2);
}

.post-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background: linear-gradient(135deg, #CEA1F5 0%, #6a3f9e 50%, #15051d 100%);
}

.post-content {
    padding: 1.25rem;
}

.post-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(206, 161, 245, 0.2);
    color: #CEA1F5;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.post-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.5rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.post-description {
    font-size: 0.9rem;
    color: #b0b0b0;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.post-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #a0a0a0;
    padding-top: 1rem;
    border-top: 1px solid rgba(206, 161, 245, 0.1);
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

.empty-posts {
    text-align: center;
    padding: 4rem 2rem;
    color: #a0a0a0;
}

.empty-posts-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-posts-text {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .posts-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>

<!-- Add this HTML section after the edit-section div and before the footer -->
<div class="posts-section">
    <div class="posts-header">
        <h2 class="posts-title">My Posts (<?php echo count($user_posts); ?>)</h2>
        <a href="create-post.php" class="btn-create-post">+ Create New Post</a>
    </div>

    <?php if (empty($user_posts)): ?>
        <div class="section" style="background: linear-gradient(135deg, rgba(206, 161, 245, 0.05) 0%, rgba(21, 5, 29, 0.8) 100%); border: 1px solid rgba(206, 161, 245, 0.15); border-radius: 16px;">
            <div class="empty-posts">
                <div class="empty-posts-icon">📝</div>
                <div class="empty-posts-text">You haven't created any posts yet</div>
                <a href="create-post.php" class="btn-create-post">Create Your First Post</a>
            </div>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($user_posts as $post): ?>
                <div class="post-card" onclick="window.location.href='post.php?id=<?php echo $post['id']; ?>'">
                    <?php if ($post['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                    <?php else: ?>
                        <div class="post-image"></div>
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
                                    <span>👁️</span>
                                    <span><?php echo number_format($post['views']); ?></span>
                                </div>
                                <div class="post-stat">
                                    <span>❤️</span>
                                    <span><?php echo number_format($post['likes']); ?></span>
                                </div>
                            </div>
                            <div class="post-date">
                                <?php 
                                $date = new DateTime($post['created_at']);
                                echo $date->format('M d, Y');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

    <!-- Include Footer Component -->
    <?php include 'footer.php'; ?>

    <!-- Notification Element -->
    <div class="notification" id="notification"></div>

    <script>
        // ============================================
        // JAVASCRIPT - Profile Management
        // ============================================

        const toggleEditBtn = document.getElementById('toggleEditBtn');
        const editSection = document.getElementById('editSection');
        const profileForm = document.getElementById('profileForm');
        const cancelBtn = document.getElementById('cancelBtn');
        const bioInput = document.getElementById('bio');
        const charCount = document.getElementById('charCount');
        const notification = document.getElementById('notification');

        // Toggle edit section visibility
        toggleEditBtn.addEventListener('click', () => {
            editSection.classList.toggle('active');
            
            if (editSection.classList.contains('active')) {
                toggleEditBtn.textContent = 'Close Editor';
                editSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                toggleEditBtn.textContent = 'Edit Profile';
            }
        });

        // Cancel button
        cancelBtn.addEventListener('click', () => {
            editSection.classList.remove('active');
            toggleEditBtn.textContent = 'Edit Profile';
            profileForm.reset();
            clearErrors();
        });

        // Character counter
        bioInput.addEventListener('input', () => {
            charCount.textContent = bioInput.value.length;
        });

        // Show notification
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }

        // Clear all errors
        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });
            document.querySelectorAll('.form-input, .form-textarea').forEach(el => {
                el.classList.remove('error');
            });
        }

        // Display errors
        function displayErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorEl = document.getElementById(field + 'Error');
                const inputEl = document.getElementById(field);
                
                if (errorEl && inputEl) {
                    errorEl.textContent = errors[field];
                    errorEl.classList.add('show');
                    inputEl.classList.add('error');
                }
            });
        }

        // Handle form submission
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearErrors();

            const saveBtn = document.getElementById('saveBtn');
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
                    document.getElementById('displayFullName').textContent = formData.get('full_name');
                    document.getElementById('displayBio').textContent = formData.get('bio');
                    document.getElementById('displayLocation').textContent = formData.get('location');
                    
                    const websiteLink = document.getElementById('displayWebsite');
                    if (websiteLink) {
                        websiteLink.textContent = formData.get('website');
                        websiteLink.href = formData.get('website');
                    }

                    showNotification(result.message, 'success');

                    // Close edit section
                    editSection.classList.remove('active');
                    toggleEditBtn.textContent = 'Edit Profile';
                } else {
                    showNotification(result.message, 'error');
                    if (result.errors) {
                        displayErrors(result.errors);
                    }
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showNotification('Failed to update profile. Please try again.', 'error');
            } finally {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        });

        // Real-time validation
        document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
            input.addEventListener('blur', () => {
                const errorEl = document.getElementById(input.id + 'Error');
                
                if (input.hasAttribute('required') && !input.value.trim()) {
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                    if (errorEl) {
                        errorEl.classList.remove('show');
                    }
                }
            });
        });

        console.log('Profile page loaded with handler integration');
    </script>
</body>
</html>