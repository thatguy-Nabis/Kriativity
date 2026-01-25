<?php
require_once 'init.php';

// ============================================
// REDIRECT IF ALREADY LOGGED IN
// ============================================
if (isset($_SESSION['admin_id'])) {
    header('Location: ./admin/admin_dashboard.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=content_discovery;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $db_connected = true;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $db_connected = false;
}

// ============================================
// HANDLE LOGIN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $login = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);

    if ($login === '' || $password === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill all fields'
        ]);
        exit;
    }

    try {
        // ============================================
        // 1️⃣ CHECK ADMIN FIRST (EMAIL ONLY)
        // ============================================
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        // echo json_encode([
        //     'debug' => $admin,
        //     'success' => false,
        //     'message' => $password
        // ]);
        // exit;

        if ($admin) {
            error_log("Debug message");
            if ($password != $admin['password']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid password'
                ]);
                exit;
            }

            // ✅ ADMIN LOGIN SUCCESS
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];

            echo json_encode([
                'success' => true,
                'message' => 'Admin login successful',
                'redirect' => './admin/admin_dashboard.php'
            ]);
            exit;
        }

        // ============================================
        // 2️⃣ CHECK NORMAL USER
        // ============================================
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = ? OR username = ?"
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
            exit;
        }

        if ((int)$user['is_active'] !== 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Account is deactivated'
            ]);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid password'
            ]);
            exit;
        }

        // ✅ USER LOGIN SUCCESS
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 86400 * 30, '/');
            $pdo->prepare(
                "UPDATE users SET remember_token = ? WHERE id = ?"
            )->execute([$token, $user['id']]);
        }

        $pdo->prepare(
            "UPDATE users SET last_login = NOW() WHERE id = ?"
        )->execute([$user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'homepage.php'
        ]);
        exit;

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Server error'
        ]);
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Content Discovery Platform</title>
    <style>
        /* ============================================
           RESET & BASE STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #15051d;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* ============================================
           BACKGROUND DECORATION
           ============================================ */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, rgba(206, 161, 245, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(206, 161, 245, 0.15) 0%, transparent 50%);
            animation: rotate 30s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* ============================================
           LOGIN CONTAINER
           ============================================ */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.08) 0%, rgba(21, 5, 29, 0.95) 100%);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            margin-top: 100px;
        }

        /* ============================================
           HEADER SECTION
           ============================================ */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            color: #a0a0a0;
        }

        /* ============================================
           DEMO CREDENTIALS BOX
           ============================================ */
        .demo-box {
            background: rgba(206, 161, 245, 0.1);
            border: 1px solid rgba(206, 161, 245, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .demo-title {
            color: #CEA1F5;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .demo-credentials {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #d0d0d0;
        }

        .db-status {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .db-status.connected {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .db-status.disconnected {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        /* ============================================
           FORM STYLES
           ============================================ */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #CEA1F5;
            font-size: 0.9rem;
        }

        .form-input {
            padding: 0.875rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 2px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            background-color: rgba(206, 161, 245, 0.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .form-input.error {
            border-color: #ff6b6b;
        }

        /* ============================================
           PASSWORD INPUT WITH TOGGLE
           ============================================ */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #CEA1F5;
            cursor: pointer;
            font-size: 1.2rem;
            transition: opacity 0.3s ease;
        }

        .password-toggle:hover {
            opacity: 0.7;
        }

        /* ============================================
           REMEMBER ME & FORGOT PASSWORD
           ============================================ */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #CEA1F5;
        }

        .remember-me label {
            font-size: 0.9rem;
            color: #d0d0d0;
            cursor: pointer;
        }

        .forgot-password {
            color: #CEA1F5;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #a66fd9;
        }

        /* ============================================
           SUBMIT BUTTON
           ============================================ */
        .submit-button {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #CEA1F5 0%, #a66fd9 100%);
            color: #15051d;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .submit-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(206, 161, 245, 0.4);
        }

        .submit-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ============================================
           DIVIDER
           ============================================ */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #a0a0a0;
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(206, 161, 245, 0.2);
        }

        .divider span {
            padding: 0 1rem;
        }

        /* ============================================
           SOCIAL LOGIN BUTTONS
           ============================================ */
        .social-login {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .social-button {
            padding: 0.875rem 1rem;
            background: rgba(206, 161, 245, 0.05);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            color: #e0e0e0;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .social-button:hover {
            background: rgba(206, 161, 245, 0.1);
            border-color: #CEA1F5;
        }

        /* ============================================
           FOOTER LINKS
           ============================================ */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(206, 161, 245, 0.1);
        }

        .footer-text {
            color: #a0a0a0;
            font-size: 0.95rem;
        }

        .footer-link {
            color: #CEA1F5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #a66fd9;
        }

        /* ============================================
           NOTIFICATION
           ============================================ */
        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1001;
            max-width: 400px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        @media (max-width: 768px) {
            .login-container {
                padding: 2rem 1.5rem;
            }

            .logo {
                font-size: 2rem;
            }

            .login-title {
                font-size: 1.5rem;
            }

            body {
                padding: 1rem;
            }

            .notification {
                right: 1rem;
                left: 1rem;
                max-width: none;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header Component -->
    <?php include 'header.php'; ?>

    <!-- ============================================
         LOGIN CONTAINER
         ============================================ -->
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">✨ Kriativity</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to continue your journey</p>
        </div>

        <!-- Demo Credentials Box -->
        <div class="demo-box">
            <div class="demo-title">🎯 Demo Credentials</div>
            <div class="demo-credentials">
                Username: <strong>demo</strong><br>
                Password: <strong>Demo1234</strong>
            </div>
            <div class="db-status <?= $db_connected ? 'connected' : 'disconnected' ?>">
                <?= $db_connected ? '✓ Database Connected' : '✗ Database Not Connected (Demo Mode Only)' ?>
            </div>
        </div>

        <!-- Login Form -->
        <form class="login-form" id="loginForm">
            <!-- Username or Email -->
            <div class="form-group">
                <label class="form-label" for="username_or_email">
                    Username or Email
                </label>
                <input 
                    type="text" 
                    id="username_or_email" 
                    name="username_or_email" 
                    class="form-input"
                    placeholder="Enter your username or email"
                    required
                    autocomplete="username"
                >
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">
                    Password
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" id="togglePassword">
                        👁️
                    </button>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="form-options">
                <div class="remember-me">
                    <input 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me"
                    >
                    <label for="remember_me">Remember me</label>
                </div>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="submit-button" id="submitBtn">
                Sign In
            </button>
        </form>

        <!-- Divider -->
        <!-- <div class="divider">
            <span>or continue with</span>
        </div> -->

        <!-- Social Login -->
        <!-- <div class="social-login">
            <button class="social-button" type="button">
                <span>🔵</span> Google
            </button>
            <button class="social-button" type="button">
                <span>⚫</span> GitHub
            </button>
        </div> -->

        <!-- Footer -->
        <div class="login-footer">
            <p class="footer-text">
                Don't have an account? 
                <a href="signup.php" class="footer-link">Sign Up</a>
            </p>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // ============================================
        // JAVASCRIPT - Login Form Handling
        // ============================================

        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const notification = document.getElementById('notification');
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        // Password visibility toggle
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type');
            
            if (type === 'password') {
                passwordInput.setAttribute('type', 'text');
                togglePassword.textContent = '👁️‍🗨️';
            } else {
                passwordInput.setAttribute('type', 'password');
                togglePassword.textContent = '👁️';
            }
        });

        // Show notification
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(el => {
                el.classList.remove('error');
            });
            
            // Disable submit button
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing In...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(form);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    
                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    showNotification(result.message, 'error');
                    
                    // Highlight error fields
                    document.getElementById('username_or_email').classList.add('error');
                    document.getElementById('password').classList.add('error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        // Clear error on input focus
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', () => {
                input.classList.remove('error');
            });
        });

        // Handle forgot password
        document.querySelector('.forgot-password').addEventListener('click', (e) => {
            e.preventDefault();
            showNotification('Password reset functionality coming soon!', 'error');
        });

        // Handle social login buttons
        document.querySelectorAll('.social-button').forEach(button => {
            button.addEventListener('click', () => {
                showNotification('Social login coming soon!', 'error');
            });
        });

        console.log('Login page loaded - Database status: <?= $db_connected ? "Connected" : "Not Connected" ?>');
        console.log('Demo credentials: demo / Demo1234');
    </script>
</body>
</html>