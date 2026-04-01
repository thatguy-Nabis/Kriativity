<?php
/**
 * ============================================
 * User Profile Page
 * Primary Color:   #CEA1F5 (Purple)
 * Secondary Color: #15051d (Dark Purple)
 * ============================================
 */

require_once 'includes/session_check.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// ── 1. RESOLVE WHICH PROFILE TO SHOW ─────────────────────────────────────────
$session_user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// Accept ?id= from URL; fall back to the logged-in user's own profile
$user_id = isset($_GET['id']) ? (int) $_GET['id'] : $session_user_id;

// No user at all → send to login
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// ── 2. FETCH PROFILE USER ─────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Profile not found — show friendly error instead of crashing
    http_response_code(404);
    include 'includes/404.php'; // optional graceful page; remove if you don't have one
    exit;
}

// ── 3. OWN-PROFILE FLAG ───────────────────────────────────────────────────────
$is_own_profile = ($session_user_id !== null && $session_user_id === $user_id);

// ── 4. PROFILE-VIEW TRACKING ──────────────────────────────────────────────────
// Only track when a *different* logged-in user visits this profile
if ($session_user_id && !$is_own_profile) {

    // 4a. Spam-guard: skip if this viewer already viewed within the last 30 minutes
    $spam_check = $pdo->prepare("
        SELECT id FROM profile_views
        WHERE viewer_id = ? AND viewed_user_id = ?
          AND viewed_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        LIMIT 1
    ");
    $spam_check->execute([$session_user_id, $user_id]);

    if (!$spam_check->fetch()) {
        // 4b. Log the view
        $pdo->prepare("
            INSERT INTO profile_views (viewer_id, viewed_user_id, viewed_at)
            VALUES (?, ?, NOW())
        ")->execute([$session_user_id, $user_id]);

        // 4c. Increment the denormalised counter for fast display
        $pdo->prepare("
            UPDATE users SET profile_views = profile_views + 1 WHERE id = ?
        ")->execute([$user_id]);

        // Refresh $user so the incremented value shows on this page load
        $user['profile_views'] = ($user['profile_views'] ?? 0) + 1;
    }
}

// ── 5. FETCH THIS USER'S PUBLISHED POSTS ─────────────────────────────────────
$posts_stmt = $pdo->prepare("
    SELECT * FROM content
    WHERE user_id = ? AND is_published = 1
    ORDER BY created_at DESC
");
$posts_stmt->execute([$user_id]);
$user_posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 6. RECENTLY VIEWED PROFILES (only shown on own profile) ──────────────────
$recently_viewed = [];
if ($is_own_profile) {
    $rv_stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.full_name, u.username, pv.viewed_at
        FROM profile_views pv
        JOIN users u ON u.id = pv.viewed_user_id
        WHERE pv.viewer_id = ?
        ORDER BY pv.viewed_at DESC
        LIMIT 6
    ");
    $rv_stmt->execute([$session_user_id]);
    $recently_viewed = $rv_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Profile page for <?= htmlspecialchars($user['username']) ?>">
    <title><?= htmlspecialchars($user['full_name']) ?> – Kriativity</title>

    <link rel="stylesheet" href="styles.css">

    <style>
        /* ================================
           PROFILE PAGE STYLES
           ================================ */

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* --- PROFILE HEADER --- */
        .profile-header {
            position: relative;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, rgba(206,161,245,.1) 0%, rgba(21,5,29,.8) 100%);
            border: 1px solid rgba(206,161,245,.15);
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
            inset: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.1), transparent);
        }

        .profile-info-section {
            position: relative;
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            padding: 2rem;
            margin-top: -80px;
        }

        .profile-image-container { position: relative; flex-shrink: 0; }

        .profile-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #CEA1F5, #a66fd9);
            border: 5px solid #15051d;
            border-radius: 50%;
            font-size: 3.5rem;
            font-weight: 700;
            color: #15051d;
            box-shadow: 0 8px 30px rgba(206,161,245,.3);
        }

        .upload-badge {
            position: absolute;
            right: 5px; bottom: 5px;
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px;
            background: #CEA1F5;
            border: 3px solid #15051d;
            border-radius: 50%;
            cursor: pointer;
            transition: all .3s ease;
        }

        .upload-badge:hover { transform: scale(1.1); box-shadow: 0 0 20px rgba(206,161,245,.5); }

        .profile-details { flex: 1; padding-top: 60px; }

        .profile-username {
            margin-bottom: .5rem;
            background: linear-gradient(135deg, #CEA1F5, #ffffff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem; font-weight: 700; line-height: 1.2;
        }

        .profile-handle { margin-bottom: 1rem; font-size: 1rem; color: #a0a0a0; }
        .profile-bio    { max-width: 600px; margin-bottom: 1.5rem; font-size: 1rem; line-height: 1.6; color: #d0d0d0; }

        /* --- META --- */
        .profile-meta { display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 1.5rem; font-size: .9rem; color: #b0b0b0; }
        .meta-item    { display: flex; align-items: center; gap: .5rem; }
        .meta-icon    { color: #CEA1F5; }
        .meta-item a  { color: #CEA1F5; text-decoration: none; transition: color .2s; }
        .meta-item a:hover { color: #b88de0; }

        /* Profile-view count badge */
        .views-badge {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .3rem .85rem;
            background: rgba(206,161,245,.1);
            border: 1px solid rgba(206,161,245,.2);
            border-radius: 20px;
            font-size: .85rem; color: #CEA1F5;
        }

        /* --- STATS --- */
        .profile-stats { display: flex; gap: 2rem; margin-bottom: 1.5rem; }
        .stat-box      { text-align: center; }
        .stat-number   { display: block; font-size: 1.5rem; font-weight: 700; color: #CEA1F5; }
        .stat-label    { margin-top: .25rem; font-size: .85rem; color: #a0a0a0; }

        /* --- ACTION BUTTONS --- */
        .profile-actions { display: flex; gap: 1rem; flex-wrap: wrap; }

        .action-button {
            padding: .75rem 2rem;
            border: none; border-radius: 50px;
            font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: all .3s ease;
        }

        .btn-primary { background: linear-gradient(135deg, #CEA1F5, #a66fd9); color: #15051d; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(206,161,245,.4); }

        .btn-secondary { background: transparent; border: 2px solid rgba(206,161,245,.5); color: #CEA1F5; }
        .btn-secondary:hover { background: rgba(206,161,245,.1); border-color: #CEA1F5; }

        /* --- EDIT SECTION --- */
        .edit-section {
            display: none;
            padding: 2rem; margin-top: 2rem;
            background: linear-gradient(135deg, rgba(206,161,245,.05), rgba(21,5,29,.8));
            border: 1px solid rgba(206,161,245,.15);
            border-radius: 16px;
        }
        .edit-section.active { display: block; animation: slideDown .3s ease; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #CEA1F5, #a66fd9);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; font-size: 1.5rem; font-weight: 700;
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: .5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { font-size: .9rem; font-weight: 600; color: #CEA1F5; }

        .form-input, .form-textarea {
            padding: .875rem 1.25rem;
            background: rgba(206,161,245,.08);
            border: 1px solid rgba(206,161,245,.2);
            border-radius: 10px; font-family: inherit;
            font-size: .95rem; color: #e0e0e0;
            transition: all .3s ease;
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            background: rgba(206,161,245,.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206,161,245,.2);
        }
        .form-input.error, .form-textarea.error { border-color: #ff6b6b; }
        .form-textarea { min-height: 120px; resize: vertical; }
        .char-counter  { font-size: .8rem; color: #a0a0a0; text-align: right; }
        .error-message { display: none; font-size: .85rem; color: #ff6b6b; }
        .error-message.show { display: block; }

        /* --- RECENTLY VIEWED --- */
        .recently-viewed-section {
            margin-top: 2rem; padding: 1.5rem;
            background: linear-gradient(135deg, rgba(206,161,245,.04), rgba(21,5,29,.8));
            border: 1px solid rgba(206,161,245,.12);
            border-radius: 16px;
        }

        .recently-viewed-grid { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem; }

        .rv-card {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem 1rem;
            background: rgba(0,0,0,.3);
            border: 1px solid rgba(206,161,245,.1);
            border-radius: 12px;
            text-decoration: none; color: inherit;
            transition: all .25s;
            min-width: 180px;
        }
        .rv-card:hover { border-color: rgba(206,161,245,.35); transform: translateY(-2px); }

        .rv-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #CEA1F5, #7e3fbf);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; color: #15051d; flex-shrink: 0;
        }
        .rv-name    { font-size: .9rem; font-weight: 600; color: #fff; }
        .rv-handle  { font-size: .75rem; color: #7a6a8a; }

        /* --- POSTS SECTION --- */
        .posts-section  { margin-top: 2rem; }
        .posts-header   { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .posts-title    {
            background: linear-gradient(135deg, #CEA1F5, #a66fd9);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; font-size: 1.5rem; font-weight: 700;
        }
        .btn-create-post {
            display: inline-block; padding: .75rem 1.5rem;
            background: linear-gradient(135deg, #CEA1F5, #a66fd9);
            border: none; border-radius: 50px; font-weight: 600;
            color: #15051d; text-decoration: none; cursor: pointer;
            transition: all .3s ease;
        }
        .btn-create-post:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(206,161,245,.4); }

        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 1.5rem; }

        .post-card {
            background: linear-gradient(135deg, rgba(206,161,245,.05), rgba(21,5,29,.8));
            border: 1px solid rgba(206,161,245,.15);
            border-radius: 12px; overflow: hidden;
            cursor: pointer; transition: all .3s ease;
        }
        .post-card:hover { transform: translateY(-5px); border-color: rgba(206,161,245,.3); box-shadow: 0 10px 30px rgba(206,161,245,.2); }

        .post-image {
            width: 100%; height: 200px;
            background: linear-gradient(135deg, #CEA1F5, #6a3f9e 50%, #15051d);
            object-fit: cover;
        }
        .post-content  { padding: 1.25rem; }
        .post-type-badge {
            display: inline-block; margin-bottom: .75rem;
            padding: .25rem .75rem;
            background: rgba(206,161,245,.2); border-radius: 12px;
            font-size: .75rem; font-weight: 600; color: #CEA1F5;
        }
        .post-title {
            display: -webkit-box; margin-bottom: .5rem;
            font-size: 1.1rem; font-weight: 600; line-height: 1.4; color: #fff;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .post-description {
            display: -webkit-box; margin-bottom: 1rem;
            font-size: .9rem; line-height: 1.5; color: #b0b0b0;
            -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }
        .post-meta {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(206,161,245,.1);
            font-size: .85rem; color: #a0a0a0;
        }
        .post-stats { display: flex; gap: 1rem; }
        .post-stat  { display: flex; align-items: center; gap: .25rem; }
        .post-date  { font-size: .8rem; }

        /* --- EMPTY STATE --- */
        .empty-posts       { padding: 4rem 2rem; text-align: center; color: #a0a0a0; }
        .empty-posts-icon  { margin-bottom: 1rem; font-size: 4rem; }
        .empty-posts-text  { margin-bottom: 1.5rem; font-size: 1.1rem; }

        /* --- NOTIFICATION --- */
        .notification {
            position: fixed; top: 100px; right: 2rem; z-index: 1001;
            padding: 1rem 1.5rem; border-radius: 10px;
            font-weight: 600; color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,.3);
            opacity: 0; pointer-events: none;
            transform: translateX(400px); transition: all .3s ease;
        }
        .notification.show  { opacity: 1; pointer-events: auto; transform: translateX(0); }
        .notification.success { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .notification.error   { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .profile-container { padding: 0 1rem; }
            .cover-image { height: 180px; }
            .profile-info-section { flex-direction: column; align-items: center; padding: 1.5rem; text-align: center; }
            .profile-image { width: 120px; height: 120px; font-size: 2.5rem; }
            .profile-details { padding-top: 1rem; }
            .profile-meta, .profile-stats, .profile-actions { justify-content: center; }
            .profile-actions { flex-direction: column; width: 100%; }
            .action-button { width: 100%; }
            .form-grid { grid-template-columns: 1fr; }
            .posts-grid { grid-template-columns: 1fr; }
            .posts-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .notification { right: 1rem; left: 1rem; }
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <main class="profile-container">

        <!-- ── PROFILE HEADER ─────────────────────────────────────── -->
        <section class="profile-header">
            <div class="cover-image" role="img" aria-label="Cover image"></div>

            <div class="profile-info-section">

                <!-- Avatar -->
                <div class="profile-image-container">
                    <div class="profile-image" aria-label="Profile picture">
                        <?= strtoupper(substr(htmlspecialchars($user['full_name']), 0, 1)) ?>
                    </div>

                    <?php if ($is_own_profile): ?>
                        <!-- Only own profile shows the upload badge -->
                        <button class="upload-badge" title="Change profile picture" aria-label="Change profile picture">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div class="profile-details">
                    <h1 class="profile-username" id="displayFullName">
                        <?= htmlspecialchars($user['full_name']) ?>
                    </h1>
                    <p class="profile-handle">@<?= htmlspecialchars($user['username']) ?></p>

                    <?php if (!empty($user['bio'])): ?>
                        <p class="profile-bio" id="displayBio"><?= htmlspecialchars($user['bio']) ?></p>
                    <?php endif; ?>

                    <!-- Meta info -->
                    <div class="profile-meta">
                        <?php if (!empty($user['location'])): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <span id="displayLocation"><?= htmlspecialchars($user['location']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['website'])): ?>
                            <div class="meta-item">
                                <span class="meta-icon">🌐</span>
                                <a href="<?= htmlspecialchars($user['website']) ?>"
                                   target="_blank" rel="noopener noreferrer" id="displayWebsite">
                                    <?= htmlspecialchars($user['website']) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="meta-item">
                            <span class="meta-icon">📅</span>
                            <span>Joined <?= date('F Y', strtotime($user['join_date'])) ?></span>
                        </div>

                        <!-- Profile view count — visible to everyone -->
                        <div class="meta-item">
                            <span class="views-badge">
                                👁️ <?= number_format($user['profile_views'] ?? 0) ?> profile views
                            </span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-number"><?= number_format($user['total_posts']) ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?= number_format($user['followers']) ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?= number_format($user['following']) ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>

                    <!-- Action buttons — change based on whose profile this is -->
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <button class="action-button btn-primary" id="toggleEditBtn">
                                ✏️ Edit Profile
                            </button>
                            <button class="action-button btn-secondary" id="shareProfileBtn">
                                🔗 Share Profile
                            </button>

                        <?php else: ?>
                            <!-- Visiting someone else's profile -->
                            <button class="action-button btn-primary" id="followBtn"
                                    data-user-id="<?= $user_id ?>">
                                ➕ Follow
                            </button>
                            <a href="messages.php?to=<?= $user_id ?>"
                               class="action-button btn-secondary" style="text-decoration:none;text-align:center;">
                                💬 Message
                            </a>
                            <button class="action-button btn-secondary" id="shareProfileBtn">
                                🔗 Share
                            </button>
                        <?php endif; ?>
                    </div>
                </div><!-- /.profile-details -->
            </div><!-- /.profile-info-section -->
        </section>


        <!-- ── EDIT PROFILE (own profile only) ───────────────────── -->
        <?php if ($is_own_profile): ?>
        <section class="edit-section" id="editSection" aria-labelledby="editSectionTitle">
            <h2 class="section-title" id="editSectionTitle">Edit Profile Information</h2>

            <form id="profileForm" novalidate>
                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label" for="fullName">Full Name *</label>
                        <input type="text" id="fullName" name="full_name" class="form-input"
                               value="<?= htmlspecialchars($user['full_name']) ?>"
                               required aria-required="true" aria-describedby="fullNameError">
                        <span class="error-message" id="fullNameError" role="alert"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-input"
                               value="<?= htmlspecialchars($user['location'] ?? '') ?>"
                               placeholder="City, Country">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-input"
                               value="<?= htmlspecialchars($user['website'] ?? '') ?>"
                               placeholder="https://yourwebsite.com"
                               aria-describedby="websiteError">
                        <span class="error-message" id="websiteError" role="alert"></span>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-textarea"
                                  maxlength="500"
                                  aria-describedby="charCount bioError"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        <div class="char-counter" id="charCount" aria-live="polite">
                            <span id="charCountValue"><?= strlen($user['bio'] ?? '') ?></span>/500 characters
                        </div>
                        <span class="error-message" id="bioError" role="alert"></span>
                    </div>

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


        <!-- ── RECENTLY VIEWED PROFILES (own profile only) ──────── -->
        <?php if (!empty($recently_viewed)): ?>
        <section class="recently-viewed-section">
            <h2 class="section-title">Recently Viewed Profiles</h2>
            <div class="recently-viewed-grid">
                <?php foreach ($recently_viewed as $rv): ?>
                    <a href="profile.php?id=<?= (int)$rv['id'] ?>" class="rv-card">
                        <div class="rv-avatar">
                            <?= strtoupper(substr(htmlspecialchars($rv['full_name']), 0, 1)) ?>
                        </div>
                        <div>
                            <div class="rv-name"><?= htmlspecialchars($rv['full_name']) ?></div>
                            <div class="rv-handle">@<?= htmlspecialchars($rv['username']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; // end $is_own_profile blocks ?>


        <!-- ── USER POSTS ─────────────────────────────────────────── -->
        <section class="posts-section" aria-labelledby="postsSectionTitle">
            <div class="posts-header">
                <h2 class="posts-title" id="postsSectionTitle">
                    <?= $is_own_profile ? 'My Posts' : htmlspecialchars($user['full_name']) . "'s Posts" ?>
                    (<?= count($user_posts) ?>)
                </h2>
                <?php if ($is_own_profile): ?>
                    <a href="create-post.php" class="btn-create-post">+ Create New Post</a>
                <?php endif; ?>
            </div>

            <?php if (empty($user_posts)): ?>
                <div class="empty-posts">
                    <div class="empty-posts-icon">📝</div>
                    <div class="empty-posts-text">
                        <?= $is_own_profile
                            ? "You haven't created any posts yet"
                            : htmlspecialchars($user['full_name']) . " hasn't posted anything yet" ?>
                    </div>
                    <?php if ($is_own_profile): ?>
                        <a href="create-post.php" class="btn-create-post">Create Your First Post</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($user_posts as $post): ?>
                        <article class="post-card"
                                 onclick="location.href='post.php?id=<?= (int)$post['id'] ?>'"
                                 role="button" tabindex="0">
                            <?php if ($post['image_url']): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>"
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="post-image" loading="lazy">
                            <?php else: ?>
                                <div class="post-image" role="img" aria-label="Default post image"></div>
                            <?php endif; ?>

                            <div class="post-content">
                                <span class="post-type-badge">
                                    <?= htmlspecialchars(ucfirst($post['content_type'])) ?>
                                </span>
                                <h3 class="post-title"><?= htmlspecialchars($post['title']) ?></h3>

                                <?php if (!empty($post['description'])): ?>
                                    <p class="post-description"><?= htmlspecialchars($post['description']) ?></p>
                                <?php endif; ?>

                                <div class="post-meta">
                                    <div class="post-stats">
                                        <div class="post-stat">
                                            <span aria-hidden="true">👁️</span>
                                            <span><?= number_format($post['views']) ?></span>
                                        </div>
                                        <div class="post-stat">
                                            <span aria-hidden="true">❤️</span>
                                            <span><?= number_format($post['likes']) ?></span>
                                        </div>
                                    </div>
                                    <time class="post-date" datetime="<?= htmlspecialchars($post['created_at']) ?>">
                                        <?= date('M d, Y', strtotime($post['created_at'])) ?>
                                    </time>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'footer.php'; ?>

    <div class="notification" id="notification" role="alert" aria-live="polite"></div>

<script>
// ── SHARE ──────────────────────────────────────────────────────────────────────
document.getElementById('shareProfileBtn')?.addEventListener('click', () => {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: document.title, url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => showNotification('Link copied!', 'success'));
    }
});

// ── FOLLOW (stub — wire to your follow handler) ───────────────────────────────
document.getElementById('followBtn')?.addEventListener('click', async function () {
    const userId = this.dataset.userId;
    try {
        const res = await fetch('handlers/follow_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: 'toggle' })
        });
        const data = await res.json();
        if (data.success) {
            const following = data.following;
            this.textContent = following ? '✔️ Following' : '➕ Follow';
            this.classList.toggle('btn-primary', !following);
            this.classList.toggle('btn-secondary', following);
            showNotification(following ? 'Now following!' : 'Unfollowed', 'success');
        }
    } catch (e) {
        showNotification('Action failed. Try again.', 'error');
    }
});

<?php if ($is_own_profile): ?>
// ── EDIT TOGGLE ───────────────────────────────────────────────────────────────
const toggleEditBtn = document.getElementById('toggleEditBtn');
const editSection   = document.getElementById('editSection');
const profileForm   = document.getElementById('profileForm');
const cancelBtn     = document.getElementById('cancelBtn');
const bioInput      = document.getElementById('bio');
const charCountValue = document.getElementById('charCountValue');
const saveBtn       = document.getElementById('saveBtn');

toggleEditBtn.addEventListener('click', () => {
    const isActive = editSection.classList.toggle('active');
    toggleEditBtn.textContent = isActive ? '✕ Close Editor' : '✏️ Edit Profile';
    if (isActive) {
        editSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        document.getElementById('fullName').focus();
    }
});

cancelBtn.addEventListener('click', () => {
    editSection.classList.remove('active');
    toggleEditBtn.textContent = '✏️ Edit Profile';
    profileForm.reset();
    clearErrors();
    updateCharCount();
});

bioInput.addEventListener('input', updateCharCount);
function updateCharCount() { charCountValue.textContent = bioInput.value.length; }

// ── SAVE PROFILE ───────────────────────────────────────────────────────────────
profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    const orig = saveBtn.textContent;
    saveBtn.textContent = 'Saving…';
    saveBtn.disabled = true;

    try {
        const res    = await fetch('handlers/profile_update_handler.php', { method: 'POST', body: new FormData(profileForm) });
        const result = await res.json();

        if (result.success) {
            const fd = new FormData(profileForm);

            document.getElementById('displayFullName').textContent = fd.get('full_name');

            const bioEl = document.getElementById('displayBio');
            if (bioEl) { bioEl.textContent = fd.get('bio'); bioEl.style.display = fd.get('bio') ? 'block' : 'none'; }

            const locEl = document.getElementById('displayLocation');
            if (locEl) locEl.textContent = fd.get('location');

            const webEl = document.getElementById('displayWebsite');
            if (webEl && fd.get('website')) { webEl.textContent = fd.get('website'); webEl.href = fd.get('website'); }

            showNotification(result.message, 'success');
            setTimeout(() => { editSection.classList.remove('active'); toggleEditBtn.textContent = '✏️ Edit Profile'; }, 600);
        } else {
            showNotification(result.message, 'error');
            if (result.errors) displayErrors(result.errors);
        }
    } catch (err) {
        showNotification('Failed to update. Please try again.', 'error');
    } finally {
        saveBtn.textContent = orig;
        saveBtn.disabled = false;
    }
});

function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => { el.classList.remove('show'); el.textContent = ''; });
    document.querySelectorAll('.form-input,.form-textarea').forEach(el => { el.classList.remove('error'); el.removeAttribute('aria-invalid'); });
}

function displayErrors(errors) {
    Object.keys(errors).forEach(field => {
        const errEl = document.getElementById(field + 'Error');
        const inpEl = document.getElementById(field);
        if (errEl && inpEl) { errEl.textContent = errors[field]; errEl.classList.add('show'); inpEl.classList.add('error'); inpEl.setAttribute('aria-invalid', 'true'); }
    });
}

// Real-time blur validation
document.querySelectorAll('.form-input,.form-textarea').forEach(input => {
    input.addEventListener('blur', () => {
        if (input.hasAttribute('required') && !input.value.trim()) {
            input.classList.add('error'); input.setAttribute('aria-invalid', 'true');
        } else {
            input.classList.remove('error'); input.removeAttribute('aria-invalid');
            const errEl = document.getElementById(input.id + 'Error');
            if (errEl) errEl.classList.remove('show');
        }
    });
});
<?php endif; ?>

// ── KEYBOARD ACCESSIBLE POST CARDS ────────────────────────────────────────────
document.querySelectorAll('.post-card').forEach(card => {
    card.addEventListener('keypress', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
});

// ── NOTIFICATION ──────────────────────────────────────────────────────────────
const notification = document.getElementById('notification');
function showNotification(msg, type = 'success') {
    notification.textContent = msg;
    notification.className = `notification ${type} show`;
    setTimeout(() => notification.classList.remove('show'), 4000);
}
</script>

</body>
</html>