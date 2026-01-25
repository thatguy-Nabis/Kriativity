<?php
require_once 'config.php';
requireAdmin();

// Get admin info
$admin = getAdminById($_SESSION['admin_id']);

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Suspended users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_suspended = 1");
$stats['suspended_users'] = $result->fetch_assoc()['count'];

// Pending reports
$result = $conn->query("SELECT COUNT(*) as count FROM user_reports WHERE status = 'pending'");
$stats['pending_reports'] = $result->fetch_assoc()['count'];

// Published content
$result = $conn->query("SELECT COUNT(*) as count FROM content WHERE is_published = 1");
$stats['published_content'] = $result->fetch_assoc()['count'];

// Get recent reports
$recent_reports = [];
$result = $conn->query("SELECT ur.*, u1.username as reporter_username, u2.username as reported_username, c.title as content_title 
                        FROM user_reports ur 
                        LEFT JOIN users u1 ON ur.reporter_id = u1.id 
                        LEFT JOIN users u2 ON ur.reported_user_id = u2.id 
                        LEFT JOIN content c ON ur.reported_content_id = c.id 
                        ORDER BY ur.created_at DESC 
                        LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_reports[] = $row;
}

// Get recent activity
$recent_activity = [];
$result = $conn->query("SELECT aal.*, a.username as admin_username 
                        FROM admin_activity_log aal 
                        JOIN admins a ON aal.admin_id = a.id 
                        ORDER BY aal.created_at DESC 
                        LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Content Discovery Platform</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">⚡ Admin Panel</div>
            <nav>
                <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
                <a href="users.php" class="nav-item">👥 Users</a>
                <a href="reports.php" class="nav-item">🚩 Reports</a>
                <a href="content.php" class="nav-item">📝 Content</a>
                <a href="suspensions.php" class="nav-item">🔒 Suspensions</a>
                <a href="activity.php" class="nav-item">📈 Activity Log</a>
                <a href="logout.php" class="nav-item" style="margin-top: auto;">🚪 Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <div class="admin-profile">
                    <div class="admin-avatar"><?php echo strtoupper(substr($admin['username'], 0, 2)); ?></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                        <div style="font-size: 12px; color: rgba(255,255,255,0.5);"><?php echo htmlspecialchars($admin['email']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['pending_reports']); ?></div>
                    <div class="stat-label">Pending Reports</div>
                    <?php if ($stats['pending_reports'] > 0): ?>
                        <div class="stat-change negative">Requires attention</div>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['suspended_users']); ?></div>
                    <div class="stat-label">Suspended Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['published_content']); ?></div>
                    <div class="stat-label">Published Content</div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Reports</h2>
                    <a href="reports.php" class="btn btn-primary">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Reporter</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_reports)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: rgba(255,255,255,0.5);">No reports found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_reports as $report): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($report['reporter_username']); ?></strong></td>
                                    <td><?php echo ucfirst($report['report_type']); ?></td>
                                    <td>
                                        <?php 
                                        if ($report['reported_user_id']) {
                                            echo 'User: ' . htmlspecialchars($report['reported_username']);
                                        } elseif ($report['reported_content_id']) {
                                            echo 'Post: ' . htmlspecialchars($report['content_title']);
                                        }
                                        ?>
                                    </td>
                                    <td><span class="badge <?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                                    <td><?php echo timeAgo($report['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="report-details.php?id=<?php echo $report['id']; ?>" class="btn btn-primary btn-sm">Review</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Admin Activity</h2>
                    <a href="activity.php" class="btn btn-primary">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_activity)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: rgba(255,255,255,0.5);">No activity found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($activity['admin_username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo ucfirst($activity['target_type']); ?> #<?php echo $activity['target_id']; ?></td>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><?php echo timeAgo($activity['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>