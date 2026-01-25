<?php

require_once 'config.php';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Fetch admin by username
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM admins WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];

                // Update last login info
                $ip = $_SERVER['REMOTE_ADDR'];
                $update = $conn->prepare("UPDATE admins SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
                $update->bind_param("si", $ip, $admin['id']);
                $update->execute();
                $update->close();

                // Log admin activity
                logAdminActivity($admin['id'], 'Login', 'admin', $admin['id'], 'Admin logged in');

                // Redirect to dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Content Discovery Platform</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>⚡ Admin Panel</h1>
                <p>Content Discovery Platform</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="login-footer">
                <p>Default credentials: <strong>admin</strong> / <strong>Admin@123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
