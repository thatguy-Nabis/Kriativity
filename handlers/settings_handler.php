<?php
/**
 * ============================================
 * Settings Handler — Kriativity
 * Handles all AJAX + form POST actions
 * ============================================
 */

require_once '../init.php';
require_once '../config/database.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    respond(false, 'Not authenticated.', 401);
}

$user_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ── Route actions ───────────────────────────────────────────────
switch ($action) {

    case 'update_profile':
        handleUpdateProfile($pdo, $user_id);
        break;

    case 'change_password':
        handleChangePassword($pdo, $user_id);
        break;

    case 'update_preferences':
        handleUpdatePreferences($pdo, $user_id);
        break;

    case 'logout_all':
        handleLogoutAll($user_id);
        break;

    case 'delete_account':
        handleDeleteAccount($pdo, $user_id);
        break;

    default:
        respond(false, 'Unknown action.');
}


/* ================================================================
   HANDLERS
   ================================================================ */

/**
 * Update profile fields + optional avatar upload
 */
function handleUpdateProfile(PDO $pdo, int $user_id): void
{
    // --- Collect & sanitise inputs ---
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');

    // --- Validate ---
    if (!$username || !$full_name || !$email) {
        respond(false, 'Username, full name, and email are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Please enter a valid email address.');
    }

    if (strlen($username) < 3 || strlen($username) > 30) {
        respond(false, 'Username must be between 3 and 30 characters.');
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        respond(false, 'Username may only contain letters, numbers, and underscores.');
    }

    if (strlen($bio) > 500) {
        respond(false, 'Bio may not exceed 500 characters.');
    }

    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        respond(false, 'Please enter a valid URL (including https://).');
    }

    // --- Check username / email uniqueness ---
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check->execute([$username, $user_id]);
    if ($check->fetch()) {
        respond(false, 'That username is already taken.');
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $user_id]);
    if ($check->fetch()) {
        respond(false, 'That email address is already in use.');
    }

    // --- Avatar upload (FIXED) ---
    $profile_image_val = null;

    if (!empty($_FILES['profile_image']['tmp_name'])) {
        $file = $_FILES['profile_image'];

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            respond(false, 'Invalid image type. Use JPG, PNG, GIF, or WebP.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            respond(false, 'Image must be under 2 MB.');
        }

        // safer: normalize extension (prevents bugs + exploits)
        $ext = 'jpg';
        $filename = 'uploads/avatars/' . $user_id . '_' . time() . '.' . $ext;
        $dest = dirname(__DIR__) . '/' . $filename;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(false, 'Failed to save image.');
        }

        $profile_image_val = $filename;
    }
error_log(print_r($_FILES, true));
    // --- Persist ---
    $sql = "
        UPDATE users
        SET username  = ?,
            full_name = ?,
            email     = ?,
            bio       = ?,
            location  = ?,
            website   = ?
    ";

    $params = [$username, $full_name, $email, $bio, $location, $website];

    // ✅ FIX: only add image if it exists
    if ($profile_image_val !== null) {
        $sql .= ", profile_image = ?";
        $params[] = $profile_image_val;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    // Execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Debug (optional)
    error_log("SQL: " . $sql);
    error_log("PARAMS: " . print_r($params, true));

    // Update session
    $_SESSION['username'] = $username;

    respond(true, 'Profile updated successfully.');
}


/**
 * Change password
 */
function handleChangePassword(PDO $pdo, int $user_id): void
{
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        respond(false, 'All password fields are required.');
    }

    if ($new !== $confirm) {
        respond(false, 'New passwords do not match.');
    }

    if (strlen($new) < 8) {
        respond(false, 'New password must be at least 8 characters.');
    }

    // Fetch stored hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password'])) {
        respond(false, 'Your current password is incorrect.');
    }

    if (password_verify($new, $row['password'])) {
        respond(false, 'New password must be different from your current password.');
    }

    $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
        ->execute([$hash, $user_id]);

    respond(true, 'Password changed successfully.');
}


/**
 * Save preferences
 */
function handleUpdatePreferences(PDO $pdo, int $user_id): void
{
    $dark_mode = isset($_POST['dark_mode']) ? (int) (bool) $_POST['dark_mode'] : 1;
    $show_recommendations = isset($_POST['show_recommendations']) ? (int) (bool) $_POST['show_recommendations'] : 1;
    $preferred_category = trim($_POST['preferred_category'] ?? '');
    $allowed_categories = [
        'Digital Art', 'Photography', 'Illustration', 'Animation',
        'Graphic Design', '3D Art', 'Concept Art', 'Traditional Art',
        'Pixel Art', 'Typography',
    ];

    if (strlen($preferred_category) > 100) {
        respond(false, 'Preferred category is too long.');
    }

    // Validate against app-supported category list from settings UI.
    if ($preferred_category !== '' && !in_array($preferred_category, $allowed_categories, true)) {
        respond(false, 'Please select a valid preferred category.');
    }

    try {
        // New schema (settings.sql)
        $pdo->prepare("
            INSERT INTO user_preferences (user_id, dark_mode, show_recommendations, preferred_category)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                dark_mode            = VALUES(dark_mode),
                show_recommendations = VALUES(show_recommendations),
                preferred_category   = VALUES(preferred_category)
        ")->execute([$user_id, $dark_mode, $show_recommendations, $preferred_category]);
    } catch (PDOException $e) {
        // Backward compatibility for legacy schema:
        // user_preferences(preferred_categories JSON, preferred_content_types, discovery_goal)
        if ($e->getCode() !== '42S22') {
            throw $e;
        }

        $preferred_categories_json = $preferred_category === '' ? json_encode([]) : json_encode([$preferred_category]);
        $pdo->prepare("
            INSERT INTO user_preferences (user_id, preferred_categories)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                preferred_categories = VALUES(preferred_categories)
        ")->execute([$user_id, $preferred_categories_json]);
    }

    respond(true, 'Preferences saved.');
}


/**
 * Logout all devices — destroy session token in DB
 * (Assumes you store a session_token column; if not, just destroys current session.)
 */
function handleLogoutAll(int $user_id): void
{
    // Invalidate DB-stored token if you use one
    // $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?")->execute([$user_id]);

    session_destroy();

    // For AJAX callers, respond JSON; if called via plain form, redirect
    if (isAjax()) {
        respond(true, 'Logged out of all devices.');
    } else {
        header('Location: ../login.php');
        exit;
    }
}


/**
 * Delete account
 */
function handleDeleteAccount(PDO $pdo, int $user_id): void
{
    $confirm_username = trim($_POST['confirm_username'] ?? '');

    // Re-verify username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $confirm_username !== $row['username']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Username did not match. Account not deleted.'];
        header('Location: ../settings.php#account');
        exit;
    }

    // Cascade delete — adjust table names to match your schema
    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM likes           WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM comments        WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM profile_views   WHERE viewer_id = ? OR viewed_user_id = ?")->execute([$user_id, $user_id]);
        $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM content         WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users           WHERE id = ?")->execute([$user_id]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Delete account error: ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not delete account. Please try again.'];
        header('Location: ../settings.php#account');
        exit;
    }

    session_destroy();
    header('Location: ../login.php?deleted=1');
    exit;
}


/* ================================================================
   HELPERS
   ================================================================ */

/**
 * Emit JSON response and exit.
 * Falls back to session flash + redirect for non-AJAX callers.
 */
function respond(bool $success, string $message, int $code = 200): never
{
    if (isAjax()) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    // Plain form fallback
    $_SESSION['flash'] = [
        'type' => $success ? 'success' : 'error',
        'message' => $message,
    ];
    header('Location: ../settings.php');
    exit;
}

function isAjax(): bool
{
    return (
        ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === false
        && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') === false
    );
}