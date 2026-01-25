<?php
require_once 'config.php';
requireAdmin();

$admin = getAdminById($_SESSION['admin_id']);

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($action == 'suspend' && $user_id > 0) {
        $reason = sanitize($_POST['reason']);
        $type = sanitize($_POST['suspension_type']);
        $until = ($type == 'temporary') ? sanitize($_POST['suspended_until']) : null;
        
        // Insert suspension record
        $stmt = $conn->prepare("INSERT INTO user_suspensions (user_id, admin_id, reason, suspension_type, suspended_until) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $_SESSION['admin_id'], $reason, $type, $until);
        $stmt->execute();
        $stmt->close();
        
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET is_suspended = 1, suspension_reason = ?, suspended_until = ? WHERE id = ?");
        $stmt->bind_param("ssi", $reason, $until, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Log activity
        logAdminActivity($_SESSION['admin_id'], 'User Suspended', 'user', $user_id, "Suspended user ($type): $reason");
        
        header("Location: users.php?success=User suspended successfully");
        exit();
    }
    
    if ($action == 'unsuspend' && $user_id > 0) {
        // Lift suspension
        $stmt = $conn->prepare("UPDATE user_suspensions SET is_active = 0, lifted_at = NOW() WHERE user_id = ? AND is_active = 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET is_suspended = 0, suspension_reason = NULL, suspended_until = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Log activity
        logAdminActivity($_SESSION['admin_id'], 'User Unsuspended', 'user', $user_id, "Lifted suspension for user");
        
        header("Location: users.php?success=User unsuspended successfully");
        exit();
    }
    
    if ($action == 'delete' && $user_id > 0) {
        $reason = sanitize($_POST['delete_reason']);
        
        // Log activity before deletion
        logAdminActivity($_SESSION['admin_id'], 'User Deleted', 'user', $user_id, "Deleted user: $reason");
        
        // Delete user (cascade will handle related records)
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: users.php?success=User deleted successfully");
        exit();
    }
}

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get users
$query = "SELECT id, username, email, full_name, total_posts, followers, following, is_suspended, is_active, created_at 
          FROM users 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (username LIKE '%$search%' OR email LIKE '%$search%' OR full_name LIKE '%$search%')";
}

$query .= " ORDER BY created_at DESC LIMIT 100";
$result = $conn->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">⚡ Admin Panel</div>
            <nav>
                <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="users.php" class="nav-item active">👥 Users</a>
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
                <h1>User Management</h1>
                <div class="admin-profile">
                    <div class="admin-avatar"><?php echo strtoupper(substr($admin['username'], 0, 2)); ?></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                        <div style="font-size: 12px; color: rgba(255,255,255,0.5);"><?php echo htmlspecialchars($admin['email']); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">All Users (<?php echo count($users); ?>)</h2>
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" class="search-box" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="users.php" class="btn btn-primary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Posts</th>
                            <th>Followers</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: rgba(255,255,255,0.5);">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                                <small style="color: rgba(255,255,255,0.5);"><?php echo htmlspecialchars($user['full_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo number_format($user['total_posts']); ?></td>
                                    <td><?php echo number_format($user['followers']); ?></td>
                                    <td>
                                        <?php if ($user['is_suspended']): ?>
                                            <span class="badge suspended">Suspended</span>
                                        <?php elseif ($user['is_active']): ?>
                                            <span class="badge active">Active</span>
                                        <?php else: ?>
                                            <span class="badge">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($user['is_suspended']): ?>
                                                <button class="btn btn-primary btn-sm" onclick="unsuspendUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Unsuspend</button>
                                            <?php else: ?>
                                                <button class="btn btn-warning btn-sm" onclick="suspendUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Suspend</button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Delete</button>
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

    <!-- Suspend Modal -->
    <div id="suspendModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Suspend User</h2>
            <form method="POST" id="suspendForm">
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="user_id" id="suspend_user_id">
                
                <div class="form-group">
                    <label class="form-label">User</label>
                    <input type="text" id="suspend_username" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Suspension Type</label>
                    <select name="suspension_type" id="suspension_type" class="form-control" onchange="toggleUntilDate()">
                        <option value="temporary">Temporary</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                
                <div class="form-group" id="until_group">
                    <label class="form-label">Suspend Until</label>
                    <input type="datetime-local" name="suspended_until" id="suspended_until" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-primary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unsuspend Form -->
    <form id="unsuspendForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="unsuspend">
        <input type="hidden" name="user_id" id="unsuspend_user_id">
    </form>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Delete User</h2>
            <p style="margin-bottom: 20px; color: rgba(255,255,255,0.7);">
                Are you sure you want to permanently delete this user? This action cannot be undone.
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                
                <div class="form-group">
                    <label class="form-label">User</label>
                    <input type="text" id="delete_username" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Deletion</label>
                    <textarea name="delete_reason" class="form-control" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-primary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function suspendUser(userId, username) {
            document.getElementById('suspend_user_id').value = userId;
            document.getElementById('suspend_username').value = username;
            document.getElementById('suspendModal').classList.add('active');
        }

        function unsuspendUser(userId, username) {
            if (confirm('Are you sure you want to unsuspend ' + username + '?')) {
                document.getElementById('unsuspend_user_id').value = userId;
                document.getElementById('unsuspendForm').submit();
            }
        }

        function deleteUser(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username').value = username;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
        }

        function toggleUntilDate() {
            const type = document.getElementById('suspension_type').value;
            const untilGroup = document.getElementById('until_group');
            if (type === 'permanent') {
                untilGroup.style.display = 'none';
            } else {
                untilGroup.style.display = 'block';
            }
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>