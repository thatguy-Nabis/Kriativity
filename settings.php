<?php
/**
 * ============================================
 * Settings Page — Kriativity
 * ============================================
 */

require_once 'init.php';
require_once 'config/database.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch full user record
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch preferences (graceful fallback if table doesn't exist yet)
$prefs = [
    'dark_mode'           => 1,
    'show_recommendations'=> 1,
    'preferred_category'  => '',
];

try {
    $p = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $p->execute([$user_id]);
    $row = $p->fetch(PDO::FETCH_ASSOC);
    if ($row) $prefs = array_merge($prefs, $row);
} catch (PDOException $e) { /* table may not exist yet */ }

// Flash messages passed via session from handler
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$categories = [
    'Digital Art', 'Photography', 'Illustration', 'Animation',
    'Graphic Design', '3D Art', 'Concept Art', 'Traditional Art',
    'Pixel Art', 'Typography',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Kriativity</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── CSS VARIABLES ─────────────────────────────────────── */
        :root {
            --purple:       #CEA1F5;
            --purple-dim:   rgba(206,161,245,.15);
            --purple-glow:  rgba(206,161,245,.25);
            --bg:           #15051d;
            --bg-card:      rgba(255,255,255,.03);
            --bg-input:     rgba(0,0,0,.35);
            --border:       rgba(206,161,245,.14);
            --border-hover: rgba(206,161,245,.35);
            --text:         #e8e2f0;
            --text-muted:   #7a6a8a;
            --red:          #ff6b6b;
            --red-dim:      rgba(255,107,107,.12);
            --green:        #4ade80;
            --green-dim:    rgba(74,222,128,.12);
            --radius:       14px;
            --transition:   .22s ease;
        }

        /* ── PAGE SHELL ────────────────────────────────────────── */
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); }

        .settings-wrapper {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 2rem;
            max-width: 1020px;
            margin: 2.5rem auto;
            padding: 0 1.5rem 4rem;
            align-items: start;
        }

        /* ── SIDEBAR NAV ───────────────────────────────────────── */
        .settings-nav {
            position: sticky;
            top: 90px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: .5rem;
            backdrop-filter: blur(12px);
        }

        .settings-nav-title {
            font-family: 'Syne', sans-serif;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: .75rem 1rem .5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem 1rem;
            border-radius: 10px;
            font-size: .9rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all var(--transition);
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .nav-item:hover { color: var(--text); background: var(--purple-dim); }

        .nav-item.active {
            color: var(--purple);
            background: var(--purple-dim);
            font-weight: 600;
        }

        .nav-item .nav-icon { font-size: 1rem; width: 18px; text-align: center; }

        .nav-divider { height: 1px; background: var(--border); margin: .4rem .75rem; }

        /* ── MAIN PANEL ────────────────────────────────────────── */
        .settings-main { min-width: 0; }

        /* Page heading */
        .settings-page-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.9rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--purple), #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.75rem;
        }

        /* ── SECTIONS ──────────────────────────────────────────── */
        .settings-section {
            display: none;
            animation: fadeUp .3s ease;
        }

        .settings-section.active { display: block; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── CARD ──────────────────────────────────────────────── */
        .settings-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            backdrop-filter: blur(10px);
            transition: border-color var(--transition);
        }

        .settings-card:hover { border-color: var(--border-hover); }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--purple);
            margin-bottom: 1.4rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
            margin-left: .5rem;
        }

        /* ── FORM ELEMENTS ─────────────────────────────────────── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            margin-bottom: 1.1rem;
        }

        .form-group:last-child { margin-bottom: 0; }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .form-input,
        .form-textarea,
        .form-select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .8rem 1.1rem;
            font-family: 'DM Sans', sans-serif;
            font-size: .93rem;
            color: var(--text);
            transition: border-color var(--transition), box-shadow var(--transition);
            width: 100%;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(206,161,245,.12);
        }

        .form-input::placeholder,
        .form-textarea::placeholder { color: #3d3348; }

        .form-textarea { min-height: 100px; resize: vertical; }

        .form-select { appearance: none; cursor: pointer; }

        .form-hint {
            font-size: .78rem;
            color: var(--text-muted);
            margin-top: .2rem;
        }

        /* ── AVATAR UPLOAD ─────────────────────────────────────── */
        .avatar-upload-row {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .avatar-preview {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), #7e3fbf);
            border: 3px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg);
            overflow: hidden;
            flex-shrink: 0;
            transition: border-color var(--transition);
        }

        .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-preview:hover { border-color: var(--purple); }

        .avatar-upload-info { flex: 1; }
        .avatar-upload-info p { font-size: .83rem; color: var(--text-muted); margin-top: .3rem; }

        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1.1rem;
            background: var(--purple-dim);
            border: 1px solid var(--border-hover);
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
            color: var(--purple);
            cursor: pointer;
            transition: all var(--transition);
        }

        .upload-label:hover { background: rgba(206,161,245,.25); }

        #avatarInput { display: none; }

        /* ── TOGGLE SWITCH ─────────────────────────────────────── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 0;
            border-bottom: 1px solid var(--border);
        }

        .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
        .toggle-row:first-child { padding-top: 0; }

        .toggle-info { flex: 1; }

        .toggle-label {
            font-size: .93rem;
            font-weight: 600;
            color: var(--text);
        }

        .toggle-desc {
            font-size: .8rem;
            color: var(--text-muted);
            margin-top: .15rem;
        }

        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .toggle-switch input { opacity: 0; width: 0; height: 0; }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.08);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all var(--transition);
        }

        .toggle-track::after {
            content: '';
            position: absolute;
            left: 3px; top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            border-radius: 50%;
            background: var(--text-muted);
            transition: all var(--transition);
        }

        .toggle-switch input:checked + .toggle-track {
            background: var(--purple-dim);
            border-color: var(--purple);
        }

        .toggle-switch input:checked + .toggle-track::after {
            left: calc(100% - 19px);
            background: var(--purple);
        }

        /* ── BUTTONS ───────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .72rem 1.6rem;
            border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all var(--transition);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--purple), #a66fd9);
            color: var(--bg);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(206,161,245,.35); }
        .btn-primary:active { transform: translateY(0); }

        .btn-ghost {
            background: transparent;
            border: 1.5px solid var(--border-hover);
            color: var(--purple);
        }
        .btn-ghost:hover { background: var(--purple-dim); }

        .btn-danger {
            background: var(--red-dim);
            border: 1.5px solid rgba(255,107,107,.3);
            color: var(--red);
        }
        .btn-danger:hover { background: rgba(255,107,107,.22); border-color: var(--red); }

        .btn-warning {
            background: rgba(251,191,36,.1);
            border: 1.5px solid rgba(251,191,36,.3);
            color: #fbbf24;
        }
        .btn-warning:hover { background: rgba(251,191,36,.18); }

        .btn[disabled] { opacity: .5; cursor: not-allowed; transform: none !important; }

        .btn-row {
            display: flex;
            gap: .85rem;
            align-items: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        /* ── ALERTS ────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .9rem 1.2rem;
            border-radius: 10px;
            font-size: .88rem;
            margin-bottom: 1.5rem;
            animation: fadeUp .3s ease;
        }

        .alert-success { background: var(--green-dim); border: 1px solid rgba(74,222,128,.25); color: var(--green); }
        .alert-error   { background: var(--red-dim);   border: 1px solid rgba(255,107,107,.25); color: var(--red); }

        .alert-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }

        /* ── SECURITY INFO ROWS ────────────────────────────────── */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .85rem 0;
            border-bottom: 1px solid var(--border);
            font-size: .9rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row-label { color: var(--text-muted); font-size: .82rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .info-row-value { color: var(--text); font-weight: 500; }

        /* Danger zone */
        .danger-zone {
            border-color: rgba(255,107,107,.2) !important;
        }
        .danger-zone .card-title { color: var(--red); }
        .danger-zone .card-title::after { background: rgba(255,107,107,.15); }
        .danger-zone-desc { font-size: .88rem; color: var(--text-muted); margin-bottom: 1.2rem; line-height: 1.6; }

        /* Password strength bar */
        .strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: .5rem; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; width: 0; transition: width .4s, background .4s; }

        /* ── NOTIFICATION TOAST ────────────────────────────────── */
        #toast {
            position: fixed;
            bottom: 2rem; right: 2rem;
            padding: .9rem 1.4rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: .88rem;
            color: #fff;
            box-shadow: 0 10px 35px rgba(0,0,0,.4);
            transform: translateY(80px);
            opacity: 0;
            transition: all .35s cubic-bezier(.34,1.56,.64,1);
            z-index: 9999;
            pointer-events: none;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        #toast.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
        #toast.error   { background: linear-gradient(135deg, #ef4444, #dc2626); }

        /* ── RESPONSIVE ────────────────────────────────────────── */
        @media (max-width: 768px) {
            .settings-wrapper { grid-template-columns: 1fr; }
            .settings-nav { position: static; display: flex; flex-wrap: wrap; gap: .3rem; padding: .6rem; }
            .settings-nav-title { display: none; }
            .nav-divider { display: none; }
            .nav-item { width: auto; padding: .5rem .85rem; font-size: .82rem; }
            .form-row { grid-template-columns: 1fr; }
            .settings-page-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- ── FLASH ALERT (from redirect) ───────────────────────────── -->
<?php if ($flash): ?>
<div style="max-width:1020px;margin:1rem auto;padding:0 1.5rem;">
    <div class="alert alert-<?= $flash['type'] ?>">
        <span class="alert-icon"><?= $flash['type'] === 'success' ? '✅' : '❌' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
</div>
<?php endif; ?>

<div class="settings-wrapper">

    <!-- ── SIDEBAR ──────────────────────────────────────────── -->
    <aside class="settings-nav" role="navigation" aria-label="Settings sections">
        <div class="settings-nav-title">Settings</div>

        <button class="nav-item active" data-tab="profile">
            <span class="nav-icon">👤</span> Profile
        </button>
        <button class="nav-item" data-tab="account">
            <span class="nav-icon">🔐</span> Account
        </button>
        <button class="nav-item" data-tab="preferences">
            <span class="nav-icon">🎨</span> Preferences
        </button>
        <div class="nav-divider"></div>
        <button class="nav-item" data-tab="security">
            <span class="nav-icon">🛡️</span> Security
        </button>
    </aside>

    <!-- ── MAIN CONTENT ─────────────────────────────────────── -->
    <main class="settings-main">
        <h1 class="settings-page-title" id="pageTitle">Profile Settings</h1>

        <!-- ════════════════════════════════════════════════════
             TAB: PROFILE
             ════════════════════════════════════════════════════ -->
        <div class="settings-section active" id="tab-profile">
            <form method="POST" action="handlers/settings_handler.php" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="action" value="update_profile">

            <div class="settings-card">
                <div class="card-title">📸 Profile Picture</div>

                <div class="avatar-upload-row">
                    <div class="avatar-preview" id="avatarPreview">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_image']) ?>" id="avatarImg" alt="Profile">
                        <?php else: ?>
                            <span id="avatarInitial"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-upload-info">
                        <label class="upload-label" for="avatarInput">
                            📁 Choose Image
                        </label>
                        <input type="file" id="avatarInput" name="profile_image" accept="image/*" hidden>
                        <p>JPG, PNG or GIF · Max 2MB</p>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-title">✏️ Basic Info</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input"
                                   value="<?= htmlspecialchars($user['username']) ?>" required
                                   autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="full_name" class="form-input"
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input"
                               value="<?= htmlspecialchars($user['email']) ?>" required
                               autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-textarea"
                                  maxlength="500"
                                  placeholder="Tell the community about yourself…"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        <span class="form-hint">
                            <span id="bioCount"><?= strlen($user['bio'] ?? '') ?></span>/500 characters
                        </span>
                    </div>

                    <div class="form-row">
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
                                   placeholder="https://yoursite.com">
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                            💾 Save Profile
                        </button>
                    </div>
            </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB: ACCOUNT
             ════════════════════════════════════════════════════ -->
        <div class="settings-section" id="tab-account">

            <div class="settings-card">
                <div class="card-title">🔑 Change Password</div>

                <form method="POST" action="handlers/settings_handler.php" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password"
                               class="form-input" required autocomplete="current-password"
                               placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password"
                               class="form-input" required autocomplete="new-password"
                               placeholder="••••••••" minlength="8">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="form-hint" id="strengthText">Enter a password to check strength</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password"
                               class="form-input" required autocomplete="new-password"
                               placeholder="••••••••">
                        <span class="form-hint" id="confirmHint"></span>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                            🔒 Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="settings-card danger-zone">
                <div class="card-title">⚠️ Danger Zone</div>

                <p class="danger-zone-desc">
                    Permanently deleting your account removes all your content, comments, likes,
                    and profile data. <strong>This action cannot be undone.</strong>
                </p>

                <button class="btn btn-danger" id="deleteAccountBtn" type="button">
                    🗑️ Delete My Account
                </button>
            </div>

            <!-- Confirm delete modal -->
            <div id="deleteModal" style="
                display:none; position:fixed; inset:0;
                background:rgba(0,0,0,.75); backdrop-filter:blur(6px);
                z-index:9998; align-items:center; justify-content:center;">
                <div style="
                    background:#1a0929; border:1px solid rgba(255,107,107,.3);
                    border-radius:16px; padding:2rem; max-width:420px; width:90%;
                    text-align:center; animation:fadeUp .25s ease;">
                    <div style="font-size:2.5rem; margin-bottom:1rem;">⚠️</div>
                    <h3 style="font-family:'Syne',sans-serif; color:var(--red); margin-bottom:.75rem;">
                        Delete Account?
                    </h3>
                    <p style="color:var(--text-muted); font-size:.9rem; margin-bottom:1.5rem; line-height:1.6;">
                        Type your username <strong style="color:var(--text);">
                        <?= htmlspecialchars($user['username']) ?></strong> to confirm.
                    </p>
                    <input type="text" id="deleteConfirmInput" class="form-input"
                           placeholder="Your username" style="margin-bottom:1.25rem;">
                    <form method="POST" action="handlers/settings_handler.php">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="confirm_username" id="deleteConfirmHidden">
                        <div style="display:flex; gap:.75rem; justify-content:center;">
                            <button type="button" class="btn btn-ghost" id="deleteCancelBtn">Cancel</button>
                            <button type="submit" class="btn btn-danger" id="deleteConfirmBtn">
                                Yes, Delete Forever
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB: PREFERENCES
             ════════════════════════════════════════════════════ -->
        <div class="settings-section" id="tab-preferences">

            

            <div class="settings-card">
                <div class="card-title">🖼️ Content</div>

                <form method="POST" action="handlers/settings_handler.php" id="prefsForm">
                    <input type="hidden" name="action" value="update_preferences">
                    <input type="hidden" name="dark_mode" id="darkModeHidden"
                           value="<?= $prefs['dark_mode'] ? '1' : '0' ?>">
                    <input type="hidden" name="show_recommendations" id="recsHidden"
                           value="<?= $prefs['show_recommendations'] ? '1' : '0' ?>">

                    <div class="form-group">
                        <label class="form-label" for="preferredCategory">Preferred Category</label>
                        <select id="preferredCategory" name="preferred_category" class="form-select">
                            <option value="">— All Categories —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($prefs['preferred_category'] === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Your feed will prioritise this category.</span>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary">
                            💾 Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             TAB: SECURITY
             ════════════════════════════════════════════════════ -->
        <div class="settings-section" id="tab-security">

            <div class="settings-card">
                <div class="card-title">📋 Account Info</div>

                <div class="info-row">
                    <span class="info-row-label">Account ID</span>
                    <span class="info-row-value">#<?= $user_id ?></span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Username</span>
                    <span class="info-row-value">@<?= htmlspecialchars($user['username']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Email</span>
                    <span class="info-row-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Joined</span>
                    <span class="info-row-value"><?= date('F j, Y', strtotime($user['join_date'])) ?></span>
                </div>
                <?php if (!empty($user['last_login'])): ?>
                <div class="info-row">
                    <span class="info-row-label">Last Login</span>
                    <span class="info-row-value">
                        <?= date('M j, Y · g:i A', strtotime($user['last_login'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>



        </div><!-- /tab-security -->

    </main>
</div>

<?php include 'footer.php'; ?>

<div id="toast"></div>

<script>
/* ── TAB NAVIGATION ──────────────────────────────────────────── */
const tabTitles = {
    profile:     'Profile Settings',
    account:     'Account Settings',
    preferences: 'Preferences',
    security:    'Security',
};

document.querySelectorAll('.nav-item[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('pageTitle').textContent = tabTitles[tab];

        // Update URL hash without scroll jump
        history.replaceState(null, '', '#' + tab);
    });
});

// Restore tab from hash on load
const hash = location.hash.replace('#', '');
if (hash && document.getElementById('tab-' + hash)) {
    document.querySelector(`[data-tab="${hash}"]`)?.click();
}

/* ── AVATAR LIVE PREVIEW ─────────────────────────────────────── */
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        showToast('Image must be under 2 MB', 'error');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatarPreview');
        preview.innerHTML = `<img src="${e.target.result}" id="avatarImg" alt="Preview">`;


    };
    reader.readAsDataURL(file);
});

/* ── BIO CHARACTER COUNTER ───────────────────────────────────── */
document.getElementById('bio').addEventListener('input', function () {
    document.getElementById('bioCount').textContent = this.value.length;
});

/* ── PASSWORD STRENGTH ───────────────────────────────────────── */
document.getElementById('newPassword').addEventListener('input', function () {
    const val = this.value;
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');

    let score = 0;
    if (val.length >= 8)               score++;
    if (/[A-Z]/.test(val))             score++;
    if (/[0-9]/.test(val))             score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
        { w: '0%',   color: 'transparent', label: '' },
        { w: '25%',  color: '#ef4444',     label: 'Weak' },
        { w: '50%',  color: '#f97316',     label: 'Fair' },
        { w: '75%',  color: '#eab308',     label: 'Good' },
        { w: '100%', color: '#22c55e',     label: 'Strong' },
    ];

    const lvl = levels[score] ?? levels[0];
    fill.style.width     = lvl.w;
    fill.style.background = lvl.color;
    text.textContent     = val.length ? lvl.label : 'Enter a password to check strength';
});

/* ── PASSWORD MATCH HINT ─────────────────────────────────────── */
document.getElementById('confirmPassword').addEventListener('input', function () {
    const hint = document.getElementById('confirmHint');
    const match = this.value === document.getElementById('newPassword').value;
    hint.textContent = this.value ? (match ? '✔ Passwords match' : '✖ Passwords do not match') : '';
    hint.style.color = match ? 'var(--green)' : 'var(--red)';
});

/* ── PREFERENCE TOGGLES (sync hidden inputs before submit) ───── */
document.getElementById('darkModeToggle').addEventListener('change', function () {
    document.getElementById('darkModeHidden').value = this.checked ? '1' : '0';
});
document.getElementById('recsToggle').addEventListener('change', function () {
    document.getElementById('recsHidden').value = this.checked ? '1' : '0';
});

/* ── BUTTON LOADING STATES (AJAX forms) ──────────────────────── */
async function submitForm(form, btnId) {
    const btn = (btnId ? document.getElementById(btnId) : null) || form.querySelector('button[type="submit"]');
    const orig = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '⏳ Saving…';
        btn.disabled = true;
    }

    try {
        const actionUrl = form.getAttribute('action') || 'handlers/settings_handler.php';
        const res  = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch {
        showToast('Something went wrong. Please try again.', 'error');
    } finally {
        if (btn) {
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    }
}

document.getElementById('profileForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    submitForm(this, 'saveProfileBtn');
});

document.getElementById('passwordForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const np = document.getElementById('newPassword').value;
    const cp = document.getElementById('confirmPassword').value;
    if (np !== cp) { showToast('Passwords do not match', 'error'); return; }
    submitForm(this, 'savePasswordBtn');
});

document.getElementById('prefsForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    submitForm(this, this.querySelector('[type=submit]')?.id || '');
});

/* ── DELETE ACCOUNT MODAL ────────────────────────────────────── */
const deleteModal = document.getElementById('deleteModal');
const deleteInput = document.getElementById('deleteConfirmInput');

document.getElementById('deleteAccountBtn')?.addEventListener('click', () => {
    if (!deleteModal || !deleteInput) return;
    deleteModal.style.display = 'flex';
    deleteInput.focus();
});

document.getElementById('deleteCancelBtn')?.addEventListener('click', () => {
    if (!deleteModal || !deleteInput) return;
    deleteModal.style.display = 'none';
    deleteInput.value = '';
});

deleteModal?.addEventListener('click', e => {
    if (!deleteInput) return;
    if (e.target === deleteModal) { deleteModal.style.display = 'none'; deleteInput.value = ''; }
});

document.getElementById('deleteConfirmBtn')?.addEventListener('click', function () {
    if (!deleteInput) return;
    const expected = '<?= addslashes($user['username']) ?>';
    if (deleteInput.value.trim() !== expected) {
        showToast('Username does not match', 'error');
        return;
    }
    document.getElementById('deleteConfirmHidden').value = deleteInput.value.trim();
    this.closest('form').submit();
});

/* ── TOAST ───────────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `${type} show`;
    setTimeout(() => t.classList.remove('show'), 3500);
}
</script>

</body>
</html>