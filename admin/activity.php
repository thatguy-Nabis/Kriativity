<?php
require_once 'config.php';
requireAdmin();

$sql = "
    SELECT aal.*, a.username 
    FROM admin_activity_log aal
    JOIN admins a ON aal.admin_id = a.id
    ORDER BY aal.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Activity Log</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
<div class="container">


    <main class="main-content">
        <div class="header">
            <h1>Admin Activity</h1>
        </div>

        <div class="section">
            <table>
                <thead>
                <tr>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Description</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No activity found</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                            <td><?= htmlspecialchars($row['action']) ?></td>
                            <td><?= ucfirst($row['target_type']) ?> #<?= $row['target_id'] ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= $row['ip_address'] ?: '—' ?></td>
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
