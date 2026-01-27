<?php
require_once 'config.php';
requireAdmin();

$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users – Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<div class="container">


    <!-- Main content -->
    <main class="main-content">
        <div class="header">
            <h1>Users</h1>
        </div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>
