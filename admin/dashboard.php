<?php
require_once 'config.php';
requireAdmin();

// =========================
// STATS
// =========================
$total_users = $conn->query(
    "SELECT COUNT(*) c FROM users"
)->fetch_assoc()['c'];

$active_users = $conn->query(
    "SELECT COUNT(*) c FROM users WHERE is_active = 1 AND is_suspended = 0"
)->fetch_assoc()['c'];

$suspended_users = $conn->query(
    "SELECT COUNT(*) c FROM users WHERE is_suspended = 1"
)->fetch_assoc()['c'];

$total_posts = $conn->query(
    "SELECT COUNT(*) c FROM content"
)->fetch_assoc()['c'];

$published_posts = $conn->query(
    "SELECT COUNT(*) c FROM content WHERE is_published = 1"
)->fetch_assoc()['c'];

$pending_reports = $conn->query(
    "SELECT COUNT(*) c FROM user_reports WHERE status = 'pending'"
)->fetch_assoc()['c'];

// =========================
// RECENT ACTIVITY
// =========================
$activity = $conn->query("
    SELECT aal.*, a.username 
    FROM admin_activity_log aal
    JOIN admins a ON aal.admin_id = a.id
    ORDER BY aal.created_at DESC
    LIMIT 10
");

// =========================
// RECENT REPORTS
// =========================
$reports = $conn->query("
    SELECT ur.id, ur.report_type, ur.status, ur.created_at,
           u.username AS reporter
    FROM user_reports ur
    JOIN users u ON ur.reporter_id = u.id
    ORDER BY ur.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<div class="container">

    <main class="main-content">
        <h1>Dashboard Overview</h1>

        <!-- ================= STATS ================= -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?= $active_users ?></div>
                <div class="stat-label">Active Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?= $suspended_users ?></div>
                <div class="stat-label">Suspended Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?= $total_posts ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?= $published_posts ?></div>
                <div class="stat-label">Published Posts</div>
            </div>

            <div class="stat-card highlight">
                <div class="stat-value"><?= $pending_reports ?></div>
                <div class="stat-label">Pending Reports</div>
            </div>
        </div>

        <!-- ================= ACTIVITY ================= -->
        <section class="section">
            <div class="section-header">
                <h2>Recent Admin Activity</h2>
                <a href="activity.php" class="btn btn-primary">View All</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $activity->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['action']) ?></td>
                        <td><?= ucfirst($row['target_type']) ?> #<?= $row['target_id'] ?></td>
                        <td><?= timeAgo($row['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- ================= REPORTS ================= -->
        <section class="section">
            <div class="section-header">
                <h2>Recent Reports</h2>
                <a href="reports.php" class="btn btn-primary">Manage Reports</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Reporter</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = $reports->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['reporter']) ?></td>
                        <td><?= ucfirst($r['report_type']) ?></td>
                        <td>
                            <span class="badge <?= $r['status'] ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td><?= timeAgo($r['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </section>

    </main>
</div>

</body>
</html>
