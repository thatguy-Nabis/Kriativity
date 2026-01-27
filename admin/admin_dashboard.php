<?php
require_once 'config.php';
requireAdmin();

$page = $_GET['page'] ?? 'dashboard';

// allowed pages (security)
$allowed_pages = [
    'dashboard',
    'users',
    'reports',
    'content',
    'suspensions',
    'activity'
];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">⚡ Admin Panel</div>
        <nav>
            <a href="admin_dashboard.php?page=dashboard" class="nav-item">📊 Dashboard</a>
            <a href="admin_dashboard.php?page=users" class="nav-item">👥 Users</a>
            <a href="admin_dashboard.php?page=reports" class="nav-item">🚩 Reports</a>
            <a href="admin_dashboard.php?page=content" class="nav-item">📝 Content</a>
            <a href="admin_dashboard.php?page=suspensions" class="nav-item">🔒 Suspensions</a>
            <a href="admin_dashboard.php?page=activity" class="nav-item">📈 Activity</a>
            <a href="logout.php" class="nav-item logout" style="margin-top: auto;" >🚪 Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <?php
            require __DIR__ . '/' . $page . '.php';
        ?>
    </main>

</div>

</body>
</html>
