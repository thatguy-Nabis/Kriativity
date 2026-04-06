<?php
// ============================================
// REUSABLE HEADER COMPONENT
// Primary Color: #CEA1F5 (Purple)
// Secondary Color: #15051d (Dark Purple)
// ============================================

// init.php MUST already be loaded by the page
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? ($_SESSION['full_name'] ?? $username) : '';
$profile_image = '';
$current_page = basename($_SERVER['PHP_SELF'], '.php');

if ($is_logged_in && !empty($_SESSION['user_id']) && isset($pdo)) {
  try {
    $avatar_stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $avatar_stmt->execute([(int) $_SESSION['user_id']]);
    $profile_image = (string) ($avatar_stmt->fetchColumn() ?: '');
  } catch (Throwable $e) {
    $profile_image = '';
  }
}
?>
<header class="app-header">
  <div class="app-header-inner">

    <a href="homepage.php" class="brand">
      <span class="brand-logo">✨</span>
      <span class="brand-text">Kriativity</span>
    </a>

    <form action="search.php" method="GET" class="header-search">
      <input type="text" name="q" placeholder="Search creators, content…" autocomplete="off">
      <button type="submit" class="search-btn">🔍</button>
    </form>

    <nav class="header-nav">
      <a href="homepage.php" class="nav-link <?= $current_page === 'homepage' ? 'is-active' : '' ?>">Home</a>

      <!-- ✅ TAB BASED -->
      <a href="homepage.php?tab=trending"
        class="nav-link <?= ($_GET['tab'] ?? '') === 'trending' ? 'is-active' : '' ?>">
        Trending
      </a>
      <?php if ($current_page !== 'create-post'): ?>
        <a href="create-post.php" class="nav-link <?= $current_page === 'create-post' ? 'is-active' : '' ?>">
          Create Post
        </a>
      <?php endif; ?>
      <?php if ($is_logged_in): ?>

        <div class="user-menu">
          <button class="user-trigger" id="userMenuBtn">
            <span class="avatar">
              <?php if ($profile_image !== ''): ?>
                <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile">
              <?php else: ?>
                <?= strtoupper($full_name[0] ?? '?') ?>
              <?php endif; ?>
            </span>
            <span class="caret">▾</span>
          </button>

          <div class="user-dropdown" id="userDropdownMenu">
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>">Profile</a>
            <a href="settings.php">Settings</a>
            <div class="divider"></div>
            <a href="logout.php" class="danger">Logout</a>
          </div>

        </div>

      <?php else: ?>
        <a href="login.php" class="nav-link">Login</a>
        <a href="signup.php" class="nav-link primary">Sign Up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
  (() => {
    const btn = document.getElementById('userMenuBtn');
    const menu = document.getElementById('userDropdownMenu');

    if (btn && menu) {
      btn.onclick = e => {
        e.stopPropagation();
        menu.classList.toggle('show');
      };
      document.onclick = () => menu.classList.remove('show');
    }
  })();
</script>


<link rel="stylesheet" href="styles/header.css">