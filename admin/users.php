<?php
require_once 'config.php';
requireAdmin();

$result = $conn->query("
    SELECT id, username, email, is_active, is_suspended, created_at
    FROM users
    ORDER BY created_at DESC
");
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
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                <?php while ($u = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>

                        <td>
                            <?php if ($u['is_suspended']): ?>
                                <span class="badge suspended">Suspended</span>
                            <?php elseif ($u['is_active']): ?>
                                <span class="badge active">Active</span>
                            <?php else: ?>
                                <span class="badge inactive">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>

                        <td>
                            <?php if ($u['is_suspended']): ?>
                                <!-- UNSUSPEND -->
                                <form method="POST" action="helpers/unsuspend_user.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button class="btn btn-success btn-sm"
                                            onclick="return confirm('Unsuspend this user?')">
                                        Unsuspend
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- SUSPEND -->
                                <button class="btn btn-danger btn-sm"
                                        onclick="openSuspendModal(<?= $u['id'] ?>)">
                                    Suspend
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- =========================
     SUSPEND MODAL
========================= -->
<div id="suspendModal" class="modal">
    <div class="modal-content">
        <h3>Suspend User</h3>

        <form method="POST" action="helpers/suspend_user.php">
            <input type="hidden" name="user_id" id="suspendUserId">

            <label>Reason</label>
            <textarea name="reason" required placeholder="Enter suspension reason"></textarea>

            <label>Suspension Type</label>
            <select name="suspension_type" id="suspensionType" required>
                <option value="temporary">Temporary</option>
                <option value="permanent">Permanent</option>
            </select>

            <div id="durationField">
                <label>Duration (days)</label>
                <input type="number" name="days" min="1" placeholder="e.g. 7">
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Suspend</button>
                <button type="button" class="btn" onclick="closeSuspendModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSuspendModal(userId) {
    document.getElementById('suspendUserId').value = userId;
    document.getElementById('suspendModal').style.display = 'block';
}

function closeSuspendModal() {
    document.getElementById('suspendModal').style.display = 'none';
}

document.getElementById('suspensionType').addEventListener('change', function () {
    document.getElementById('durationField').style.display =
        this.value === 'temporary' ? 'block' : 'none';
});
</script>

</body>
</html>
