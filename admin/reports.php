<?php
require_once 'config.php';
requireAdmin();

// Fetch reports
$reports = [];
$sql = "
    SELECT 
        ur.*,
        u1.username AS reporter_username,
        u2.username AS reported_username,
        c.title AS content_title,
        a.username AS reviewed_by_username
    FROM user_reports ur
    LEFT JOIN users u1 ON ur.reporter_id = u1.id
    LEFT JOIN users u2 ON ur.reported_user_id = u2.id
    LEFT JOIN content c ON ur.reported_content_id = c.id
    LEFT JOIN admins a ON ur.reviewed_by = a.id
    ORDER BY ur.created_at DESC
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports – Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <!-- <aside class="sidebar">
        <div class="logo">⚡ Admin Panel</div>
        <nav>
            <a href="admin_dashboard.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="reports.php" class="nav-item active">🚩 Reports</a>
            <a href="content.php" class="nav-item">📝 Content</a>
            <a href="suspensions.php" class="nav-item">🔒 Suspensions</a>
            <a href="activity.php" class="nav-item">📈 Activity</a>
            <a href="logout.php" class="nav-item" style="margin-top:auto">🚪 Logout</a>
        </nav>
    </aside> -->

    <!-- Main -->
    <main class="main-content">
        <div class="header">
            <h1>User Reports</h1>
        </div>

        <div class="section">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Reporter</th>
                    <th>Type</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th>Reviewed By</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>

                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" class="muted center"style="text-align:center;">No reports found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>

                            <td>
                                <?= htmlspecialchars($r['reporter_username'] ?? '—') ?>
                            </td>

                            <td>
                                <?= ucfirst($r['report_type']) ?>
                            </td>

                            <td>
                                <?php if ($r['reported_user_id']): ?>
                                    User: <?= htmlspecialchars($r['reported_username']) ?>
                                <?php elseif ($r['reported_content_id']): ?>
                                    Post: <?= htmlspecialchars($r['content_title']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?= $r['status'] ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['reviewed_by_username'] ?? '—') ?>
                            </td>

                            <td>
                                <?= timeAgo($r['created_at']) ?>
                            </td>
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
