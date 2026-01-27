<?php
require_once 'config.php';
requireAdmin();

// Fetch suspensions
$sql = "
    SELECT us.*, 
           u.username AS user_username,
           a.username AS admin_username
    FROM user_suspensions us
    JOIN users u ON us.user_id = u.id
    JOIN admins a ON us.admin_id = a.id
    ORDER BY us.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suspensions - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
<div class="container">


    <main class="main-content">
        <div class="header">
            <h1>User Suspensions</h1>
        </div>

        <div class="section">
            <table>
                <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Suspended Until</th>
                    <th>By Admin</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No suspensions found</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['user_username']) ?></strong></td>
                            <td><?= ucfirst($row['suspension_type']) ?></td>
                            <td><?= htmlspecialchars($row['reason']) ?></td>
                            <td><?= $row['suspended_until'] ?: '—' ?></td>
                            <td><?= htmlspecialchars($row['admin_username']) ?></td>
                            <td>
                                <span class="badge <?= $row['is_active'] ? 'pending' : 'resolved' ?>">
                                    <?= $row['is_active'] ? 'Active' : 'Lifted' ?>
                                </span>
                            </td>
                            <td><?= timeAgo($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
