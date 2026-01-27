<?php
require_once 'config.php';
requireAdmin();

// Fetch all content with author info
$sql = "
    SELECT 
        c.id,
        c.title,
        c.category,
        c.content_type,
        c.views,
        c.likes,
        c.is_published,
        c.created_at,
        u.username
    FROM content c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
";
$result = $conn->query($sql);
$content = [];
while ($row = $result->fetch_assoc()) {
    $content[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Content – Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
<div class="container">

   

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Content Management</h1>
        </div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Views</th>
                        <th>Likes</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($content)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;opacity:.6;">
                            No content found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($content as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['title']) ?></td>
                            <td><?= htmlspecialchars($c['username']) ?></td>
                            <td><?= htmlspecialchars($c['category']) ?></td>
                            <td><?= htmlspecialchars($c['content_type']) ?></td>
                            <td><?= number_format($c['views']) ?></td>
                            <td><?= number_format($c['likes']) ?></td>
                            <td>
                                <span class="badge <?= $c['is_published'] ? 'active' : 'inactive' ?>">
                                    <?= $c['is_published'] ? 'Published' : 'Hidden' ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
                            <td>
                                <div class="btn-group">

                                    <?php if ($c['is_published']): ?>
                                        <a href="helpers/toggle_content.php?id=<?= $c['id'] ?>&action=hide"
                                           class="btn btn-warning btn-sm">
                                           Hide
                                        </a>
                                    <?php else: ?>
                                        <a href="helpers/toggle_content.php?id=<?= $c['id'] ?>&action=publish"
                                           class="btn btn-success btn-sm">
                                           Publish
                                        </a>
                                    <?php endif; ?>

                                    <a href="helpers/delete_content.php?id=<?= $c['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this post permanently?');">
                                       Delete
                                    </a>

                                </div>
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
