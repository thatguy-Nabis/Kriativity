<?php
require_once 'init.php';

// ============================================
// REDIRECT IF ALREADY LOGGED IN
// ============================================
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admin_dashboard.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Content Discovery Platform</title>
    <link rel="stylesheet" href="styles/login.css">
</head>

<body>
    <!-- Include Header Component -->
    <?php include 'header.php'; ?>

    <!-- ============================================
         LOGIN CONTAINER
         ============================================ -->
    <main class="auth-page">


        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <span class="brand-logo">✨</span>
                <span class="brand-text"><b>Kriativity</b></span>

                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to continue your journey</p>
            </div>


            <!-- Login Form -->
            <form class="login-form" id="loginForm" method="post">
                <!-- Username or Email -->
                <div class="form-group">
                    <label class="form-label" for="username_or_email">
                        Username or Email
                    </label>
                    <input type="text" id="username_or_email" name="username_or_email" class="form-input"
                        placeholder="Enter your username or email" required autocomplete="username">
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            👁️
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me">
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
    </main>
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

                const response = await fetch('handlers/login_handler.php', {
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
    </script>
</body>

</html>