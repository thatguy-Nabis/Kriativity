<?php
require_once 'init.php';
require_once 'config/database.php';

/**
 * ==========================================================
 * DEBUG CONFIG
 * ==========================================================
 * Use:
 *   ?q=Bee&debug=1      -> shows debug panel on page
 *   ?q=Bee&debug=json   -> returns JSON only
 */
$DEBUG_MODE = $_GET['debug'] ?? ''; // '' | '1' | 'json'
$DEBUG = ($DEBUG_MODE === '1' || $DEBUG_MODE === 'json');

$debug = [
    'step_1_input' => [],
    'step_2_env'   => [],
    'step_3_db'    => [],
    'step_4_users' => [],
    'step_5_posts' => [],
    'step_6_images'=> [],
    'step_7_render'=> [],
    'errors'       => [],
];

function dbg_add(&$debug, $step, $data) {
    $debug[$step][] = $data;
}
function dbg_error(&$debug, $message, $extra = null) {
    $debug['errors'][] = ['message' => $message, 'extra' => $extra];
}

// ==========================================================
// STEP 1: INPUT
// ==========================================================
$search_query = trim($_GET['q'] ?? '');
$search_safe  = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');

dbg_add($debug, 'step_1_input', [
    'raw_GET' => $_GET,
    'search_query' => $search_query,
    'length_chars' => mb_strlen($search_query),
]);

// ==========================================================
// STEP 2: ENV
// ==========================================================
dbg_add($debug, 'step_2_env', [
    'php_version' => PHP_VERSION,
    'pdo_loaded' => extension_loaded('pdo'),
    'mbstring_loaded' => extension_loaded('mbstring'),
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? null,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
]);

// ==========================================================
// STEP 3: DB TEST
// ==========================================================
try {
    $pdo->query("SELECT 1");
    dbg_add($debug, 'step_3_db', ['connected' => true, 'test' => 'SELECT 1 OK']);
} catch (Throwable $e) {
    dbg_add($debug, 'step_3_db', ['connected' => false]);
    dbg_error($debug, 'DB connection failed', $e->getMessage());
}

// ==========================================================
// HELPERS
// ==========================================================
function h($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function highlight($text, $search) {
    if ($search === '') return h($text);
    return preg_replace('/' . preg_quote($search, '/') . '/i', '<mark>$0</mark>', h($text));
}
function truncate($text, $len = 120) {
    $text = h($text);
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}

/**
 * Normalize image URL (relative -> root-relative)
 * - "uploads/x.jpg" -> "/uploads/x.jpg"
 * - "/uploads/x.jpg" -> "/uploads/x.jpg"
 * - "http(s)://..." stays same
 */
function resolveImage(?string $url): string {
    if (!$url) return '';
    if (preg_match('/^https?:\/\//i', $url)) return $url;
    return '/' . ltrim($url, '/');
}

/**
 * Check if local file exists (only works for local paths).
 * If image is external URL, returns null.
 */
function localFileExists(string $publicUrl): ?bool {
    if ($publicUrl === '') return false;
    if (preg_match('/^https?:\/\//i', $publicUrl)) return null;

    $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($root === '') return null;

    $path = rtrim($root, '/') . $publicUrl; // publicUrl is like "/uploads/..."
    return file_exists($path);
}

// ==========================================================
// RESULTS
// ==========================================================
$users_results   = [];
$content_results = [];

// ==========================================================
// STEP 4 & 5: SEARCH
// ==========================================================
if ($search_query !== '' && mb_strlen($search_query) >= 2) {
    $pattern = '%' . $search_query . '%';
    dbg_add($debug, 'step_1_input', ['pattern' => $pattern]);

    // ---------------- USERS ----------------
    try {
        $sql_users = "
            SELECT
                id,
                username,
                full_name,
                bio,
                location,
                profile_image,
                followers,
                total_posts,
                is_active,
                is_suspended
            FROM users
            WHERE is_active = 1
              AND is_suspended = 0
              AND (
                    username LIKE :p1
                 OR full_name LIKE :p2
                 OR COALESCE(bio,'') LIKE :p3
                 OR COALESCE(location,'') LIKE :p4
              )
            ORDER BY followers DESC
            LIMIT 10
        ";
        dbg_add($debug, 'step_4_users', ['sql' => $sql_users]);

        $stmt = $pdo->prepare($sql_users);
        $stmt->execute([
            'p1' => $pattern,
            'p2' => $pattern,
            'p3' => $pattern,
            'p4' => $pattern
        ]);
        $users_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        dbg_add($debug, 'step_4_users', [
            'count' => count($users_results),
            'sample' => array_slice($users_results, 0, 2)
        ]);
    } catch (Throwable $e) {
        dbg_error($debug, 'Users query failed', $e->getMessage());
    }

    // ---------------- POSTS ----------------
    try {
        $sql_posts = "
            SELECT
                c.id,
                c.title,
                c.description,
                c.category,
                c.image_url,
                c.views,
                c.likes,
                c.created_at,
                c.is_published,
                u.id AS user_id,
                u.username,
                u.full_name,
                u.is_active,
                u.is_suspended
            FROM content c
            JOIN users u ON u.id = c.user_id
            WHERE c.is_published = 1
              AND u.is_active = 1
              AND u.is_suspended = 0
              AND (
                    c.title LIKE :p1
                 OR COALESCE(c.description,'') LIKE :p2
                 OR COALESCE(c.category,'') LIKE :p3
              )
            ORDER BY c.created_at DESC
            LIMIT 20
        ";
        dbg_add($debug, 'step_5_posts', ['sql' => $sql_posts]);

        $stmt = $pdo->prepare($sql_posts);
        $stmt->execute([
            'p1' => $pattern,
            'p2' => $pattern,
            'p3' => $pattern
        ]);
        $content_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        dbg_add($debug, 'step_5_posts', [
            'count' => count($content_results),
            'sample' => array_slice($content_results, 0, 2)
        ]);
    } catch (Throwable $e) {
        dbg_error($debug, 'Content query failed', $e->getMessage());
    }

} else {
    dbg_add($debug, 'step_1_input', [
        'search_skipped' => true,
        'reason' => 'empty query or < 2 chars'
    ]);
}

// ==========================================================
// STEP 6: IMAGE DEBUG (resolve + file exists)
// ==========================================================
if ($DEBUG) {
    foreach (array_slice($content_results, 0, 5) as $row) {
        $raw = $row['image_url'] ?? '';
        $resolved = resolveImage($raw);
        $exists = localFileExists($resolved);

        dbg_add($debug, 'step_6_images', [
            'content_id' => $row['id'] ?? null,
            'raw_image_url' => $raw,
            'resolved_url' => $resolved,
            'local_file_exists' => $exists, // true/false/null
        ]);
    }
}

// ==========================================================
// STEP 7: RENDER COUNTS
// ==========================================================
$total_results = count($users_results) + count($content_results);
dbg_add($debug, 'step_7_render', [
    'users_count' => count($users_results),
    'posts_count' => count($content_results),
    'total' => $total_results
]);

// ==========================================================
// DEBUG JSON OUTPUT
// ==========================================================
if ($DEBUG_MODE === 'json') {
    header('Content-Type: application/json');
    echo json_encode($debug, JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $search_safe ? "Search: $search_safe" : "Search" ?> – Kriativity</title>

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles/search.css">

    <style>
        /* debug panel only */
        .debug-panel {
            background: #0f0f0f;
            border: 1px solid rgba(206,161,245,0.25);
            border-radius: 12px;
            padding: 14px;
            margin: 16px 0;
            color: #e0e0e0;
            overflow: auto;
        }
        .debug-panel pre { margin: 0; white-space: pre-wrap; word-break: break-word; }
        mark { background: rgba(206,161,245,0.35); color: #CEA1F5; padding: 0 4px; border-radius: 4px; }

        /* image fallback in case your search.css doesn't have it yet */
        .content-card .card-image.no-image {
            background: linear-gradient(135deg, rgba(206,161,245,0.35), rgba(21,5,29,0.9));
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-container">

    <?php if ($DEBUG_MODE === '1'): ?>
        <div class="debug-panel">
            <strong>DEBUG PANEL</strong>
            <div style="opacity:.8;margin:6px 0 10px;">
                Try: <code>?q=Bee&debug=json</code> for raw JSON
            </div>
            <pre><?= h(json_encode($debug, JSON_PRETTY_PRINT)) ?></pre>
        </div>
    <?php endif; ?>

    <div class="search-header">
        <?php if ($search_safe): ?>
            <h1 class="search-title">
                Search results for "<span class="search-query"><?= $search_safe ?></span>"
            </h1>
            <p class="search-stats">
                Found <?= (int)$total_results ?> result<?= $total_results !== 1 ? 's' : '' ?>
            </p>
        <?php else: ?>
            <h1 class="search-title">Search</h1>
            <p class="search-stats">Start typing to discover creators and content</p>
        <?php endif; ?>
    </div>

    <?php if ($search_safe): ?>

        <div class="search-filters">
            <button class="filter-btn active" data-filter="all">All (<?= (int)$total_results ?>)</button>
            <button class="filter-btn" data-filter="users">Creators (<?= (int)count($users_results) ?>)</button>
            <button class="filter-btn" data-filter="content">Content (<?= (int)count($content_results) ?>)</button>
        </div>

        <?php if ($total_results === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h2 class="empty-title">No results found</h2>
                <p class="empty-text">Try a different keyword.</p>
            </div>
        <?php endif; ?>

        <?php if ($users_results): ?>
            <div class="results-section" data-section="users">
                <div class="section-header">
                    <h2 class="section-title">Creators</h2>
                    <span class="section-count"><?= (int)count($users_results) ?></span>
                </div>

                <div class="users-grid">
                    <?php foreach ($users_results as $u): ?>
                        <a href="profile.php?id=<?= (int)$u['id'] ?>" class="user-card">
                            <div class="user-avatar"><?= strtoupper(($u['full_name'] ?? 'U')[0]) ?></div>
                            <div class="user-name"><?= highlight($u['full_name'] ?? '', $search_query) ?></div>
                            <div class="user-username">@<?= highlight($u['username'] ?? '', $search_query) ?></div>
                            <?php if (!empty($u['bio'])): ?>
                                <p class="user-bio"><?= highlight(truncate($u['bio'], 100), $search_query) ?></p>
                            <?php endif; ?>
                            <div class="user-stats">
                                <div class="user-stat">
                                    <span class="stat-value"><?= (int)($u['total_posts'] ?? 0) ?></span>
                                    <span class="stat-label">Posts</span>
                                </div>
                                <div class="user-stat">
                                    <span class="stat-value"><?= (int)($u['followers'] ?? 0) ?></span>
                                    <span class="stat-label">Followers</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($content_results): ?>
            <div class="results-section" data-section="content">
                <div class="section-header">
                    <h2 class="section-title">Content</h2>
                    <span class="section-count"><?= (int)count($content_results) ?></span>
                </div>

                <div class="cards-grid">
                    <?php foreach ($content_results as $c): ?>
                        <?php
                            $img = resolveImage($c['image_url'] ?? '');
                            $hasImg = ($img !== '');
                        ?>
                        <a href="post.php?id=<?= (int)$c['id'] ?>" class="content-card">
                            <div class="card-image <?= $hasImg ? '' : 'no-image' ?>"
                                 <?= $hasImg ? "style=\"background-image:url('".h($img)."')\"" : '' ?>>
                                <div class="card-overlay">
                                    <span class="overlay-text">View</span>
                                </div>
                            </div>

                            <div class="card-content">
                                <span class="card-category"><?= h($c['category'] ?? '') ?></span>
                                <h3 class="card-title"><?= highlight($c['title'] ?? '', $search_query) ?></h3>
                                <?php if (!empty($c['description'])): ?>
                                    <p class="card-description"><?= highlight(truncate($c['description'], 120), $search_query) ?></p>
                                <?php endif; ?>
                                <div class="card-meta">By <?= h($c['full_name'] ?? '') ?></div>
                                <div class="card-stats">
                                    <span>👁️ <?= (int)($c['views'] ?? 0) ?></span>
                                    <span>❤️ <?= (int)($c['likes'] ?? 0) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    const buttons = document.querySelectorAll('.filter-btn');
    const sections = document.querySelectorAll('.results-section');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;

            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            sections.forEach(sec => {
                sec.style.display = (filter === 'all' || sec.dataset.section === filter) ? 'block' : 'none';
            });
        });
    });
</script>

</body>
</html>
