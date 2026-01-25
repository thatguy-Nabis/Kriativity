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

// Current page (for active nav)
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="app-header">
    <div class="app-header-inner">
        <!-- Brand -->
        <a href="homepage.php" class="brand">
            <span class="brand-logo">✨</span>
            <span class="brand-text">Kriativity</span>
        </a>

        <!-- Search -->
        <form action="search.php" method="GET" class="header-search" id="searchForm">
            <input
                type="text"
                name="q"
                id="searchInput"
                placeholder="Search creators, content, collections…"
                value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>"
                autocomplete="off"
                aria-label="Search"
            >
            <button type="submit" class="search-btn" aria-label="Search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
        </form>

        <!-- Navigation -->
        <nav class="header-nav">
            <a href="homepage.php" class="nav-link <?= $current_page === 'homepage' ? 'is-active' : '' ?>">Home</a>
            <a href="trending.php" class="nav-link <?= $current_page === 'trending' ? 'is-active' : '' ?>">Trending</a>

            <?php if ($is_logged_in): ?>
                <a href="collections.php" class="nav-link <?= $current_page === 'collections' ? 'is-active' : '' ?>">Collections</a>

                <!-- User Menu -->
                <div class="user-menu">
                    <button class="user-trigger" id="userMenuBtn" aria-label="User menu">
                        <span class="avatar"><?= strtoupper($full_name[0] ?? '?') ?></span>
                        <span class="caret">▾</span>
                    </button>

                    <div class="user-dropdown" id="userDropdownMenu">
                        <a href="profile.php">Profile</a>
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

        <!-- Mobile Toggle -->
        <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile Nav -->
    <div class="mobile-nav" id="mobileNav">
        <a href="homepage.php">Home</a>
        <a href="trending.php">Trending</a>
        <?php if ($is_logged_in): ?>
            <a href="collections.php">Collections</a>
            <a href="profile.php">Profile</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php" class="danger">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<script>
// ============================================
// HEADER JAVASCRIPT - ISOLATED SCOPE
// ============================================
(() => {
    const userBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userDropdownMenu');
    const mobileBtn = document.getElementById('mobileMenuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');

    // User dropdown toggle
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', e => {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            userMenu.classList.remove('show');
        });
    }

    // Mobile menu toggle
    if (mobileBtn && mobileNav) {
        mobileBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
        });
    }

    // Search functionality
    if (searchForm && searchInput) {
        // Handle Enter key press
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = searchInput.value.trim();
                
                if (query.length > 0) {
                    searchForm.submit();
                }
            }
        });

        // Prevent empty searches
        searchForm.addEventListener('submit', (e) => {
            const query = searchInput.value.trim();
            
            if (query.length === 0) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
})();
</script>

<style>
/* ============================================
   HEADER STYLES
   ============================================ */

.app-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    background: linear-gradient(135deg, #15051d 0%, #1a0a25 100%);
    border-bottom: 1px solid rgba(206, 161, 245, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.app-header-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
}

/* Brand */
.brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 700;
    color: #CEA1F5;
    transition: all 0.3s ease;
}

.brand:hover {
    transform: scale(1.05);
    text-shadow: 0 0 20px rgba(206, 161, 245, 0.5);
}

.brand-logo {
    font-size: 1.8rem;
}

.brand-text {
    background: linear-gradient(135deg, #CEA1F5, #e0c3ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Search */
.header-search {
    position: relative;
    flex: 1;
    max-width: 500px;
}

.header-search input {
    width: 100%;
    padding: 0.75rem 3rem 0.75rem 1.25rem;
    background: rgba(206, 161, 245, 0.08);
    border: 1px solid rgba(206, 161, 245, 0.2);
    border-radius: 50px;
    color: #e0e0e0;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.header-search input:focus {
    outline: none;
    border-color: #CEA1F5;
    box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
    background: rgba(206, 161, 245, 0.12);
}

.header-search input::placeholder {
    color: rgba(224, 224, 224, 0.5);
}

.search-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #CEA1F5;
    cursor: pointer;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: rgba(206, 161, 245, 0.15);
}

/* Navigation */
.header-nav {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.nav-link {
    color: #e0e0e0;
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: #CEA1F5;
    background: rgba(206, 161, 245, 0.1);
}

.nav-link.is-active {
    color: #CEA1F5;
    background: rgba(206, 161, 245, 0.15);
}

.nav-link.primary {
    background: linear-gradient(135deg, #CEA1F5, #b88de0);
    color: #15051d;
    font-weight: 600;
}

.nav-link.primary:hover {
    background: linear-gradient(135deg, #b88de0, #CEA1F5);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(206, 161, 245, 0.3);
}

/* User Menu */
.user-menu {
    position: relative;
}

.user-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(206, 161, 245, 0.1);
    border: 1px solid rgba(206, 161, 245, 0.2);
    border-radius: 50px;
    padding: 0.4rem 1rem 0.4rem 0.4rem;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #e0e0e0;
}

.user-trigger:hover {
    background: rgba(206, 161, 245, 0.15);
    border-color: #CEA1F5;
}

.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #CEA1F5, #b88de0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #15051d;
    font-weight: 700;
    font-size: 0.9rem;
}

.caret {
    font-size: 0.8rem;
    color: #CEA1F5;
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: linear-gradient(135deg, #1a0a25 0%, #15051d 100%);
    border: 1px solid rgba(206, 161, 245, 0.2);
    border-radius: 12px;
    padding: 0.5rem;
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-dropdown a {
    display: block;
    padding: 0.75rem 1rem;
    color: #e0e0e0;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.user-dropdown a:hover {
    background: rgba(206, 161, 245, 0.1);
    color: #CEA1F5;
}

.user-dropdown a.danger {
    color: #ff6b6b;
}

.user-dropdown a.danger:hover {
    background: rgba(255, 107, 107, 0.1);
}

.divider {
    height: 1px;
    background: rgba(206, 161, 245, 0.2);
    margin: 0.5rem 0;
}

/* Mobile Toggle */
.mobile-toggle {
    display: none;
    background: transparent;
    border: none;
    color: #CEA1F5;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
}

.mobile-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background: #CEA1F5;
    margin: 5px 0;
    transition: all 0.3s ease;
    border-radius: 2px;
}

/* Mobile Nav */
.mobile-nav {
    display: none;
    background: #15051d;
    border-top: 1px solid rgba(206, 161, 245, 0.2);
    padding: 1rem;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.mobile-nav.active {
    max-height: 500px;
}

.mobile-nav a {
    display: block;
    padding: 0.75rem 1rem;
    color: #e0e0e0;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
}

.mobile-nav a:hover {
    background: rgba(206, 161, 245, 0.1);
    color: #CEA1F5;
}

.mobile-nav a.danger {
    color: #ff6b6b;
}

/* Responsive */
@media (max-width: 968px) {
    .header-nav {
        display: none;
    }

    .mobile-toggle {
        display: block;
    }

    .mobile-nav {
        display: block;
    }

    .header-search {
        max-width: 300px;
    }
}

@media (max-width: 640px) {
    .app-header-inner {
        padding: 1rem;
        gap: 1rem;
    }

    .header-search {
        max-width: 200px;
    }

    .brand-text {
        display: none;
    }
}
</style>