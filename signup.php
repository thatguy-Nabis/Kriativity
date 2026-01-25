<?php
// ============================================
// SIGN UP PAGE - Updated with Handler Integration
// ============================================

require_once 'includes/session_check.php';

// Redirect if already logged in
redirectIfLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Content Discovery Platform</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Additional styles for signup page */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

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

        .signup-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 900px;
            background: linear-gradient(135deg, rgba(206, 161, 245, 0.08) 0%, rgba(21, 5, 29, 0.95) 100%);
            border: 1px solid rgba(206, 161, 245, 0.2);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            margin-top: 100px;
        }

        .signup-header {
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

        .signup-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
        }

        .signup-subtitle {
            font-size: 1rem;
            color: #a0a0a0;
        }

        .signup-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: #CEA1F5;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required {
            color: #ff6b6b;
        }

        .form-input,
        .form-textarea {
            padding: 0.875rem 1.25rem;
            background-color: rgba(206, 161, 245, 0.08);
            border: 2px solid rgba(206, 161, 245, 0.2);
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            background-color: rgba(206, 161, 245, 0.12);
            border-color: #CEA1F5;
            box-shadow: 0 0 15px rgba(206, 161, 245, 0.2);
        }

        .form-input.error {
            border-color: #ff6b6b;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-helper {
            font-size: 0.8rem;
            color: #a0a0a0;
        }

        .error-message {
            font-size: 0.85rem;
            color: #ff6b6b;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .char-counter {
            font-size: 0.8rem;
            color: #a0a0a0;
            text-align: right;
        }

        .password-strength {
            height: 4px;
            background: rgba(206, 161, 245, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.25rem;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-bar.weak {
            width: 33%;
            background: #ff6b6b;
        }

        .strength-bar.medium {
            width: 66%;
            background: #ffa500;
        }

        .strength-bar.strong {
            width: 100%;
            background: #4CAF50;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: #a0a0a0;
        }

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
            margin-top: 1rem;
        }

        .submit-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(206, 161, 245, 0.4);
        }

        .submit-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .signup-footer {
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

        @media (max-width: 768px) {
            .signup-container {
                padding: 2rem 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .logo {
                font-size: 2rem;
            }

            .signup-title {
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
        }
    </style>
</head>
<body>


<?php include 'header.php'; ?>


    <div class="signup-container">
        <div class="signup-header">
            <div class="logo">✨ Discover</div>
            <h1 class="signup-title">Create Your Account</h1>
            <p class="signup-subtitle">Join our community of content creators and explorers</p>
        </div>

        <form class="signup-form" id="signupForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="username">
                        Username <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input"
                        placeholder="creative_explorer"
                        required
                    >
                    <span class="form-helper">Only letters, numbers, and underscores</span>
                    <span class="error-message" id="usernameError"></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">
                        Email Address <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input"
                        placeholder="you@example.com"
                        required
                    >
                    <span class="error-message" id="emailError"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">
                        Password <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        placeholder="••••••••"
                        required
                    >
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <span class="strength-text" id="strengthText"></span>
                    <span class="form-helper">Min. 8 characters with uppercase, lowercase, and number</span>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        placeholder="••••••••"
                        required
                    >
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="full_name">
                    Full Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    class="form-input"
                    placeholder="Alex Thompson"
                    required
                >
                <span class="error-message" id="fullNameError"></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        class="form-input"
                        placeholder="San Francisco, CA"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="website">Website</label>
                    <input 
                        type="url" 
                        id="website" 
                        name="website" 
                        class="form-input"
                        placeholder="https://yourwebsite.com"
                    >
                    <span class="error-message" id="websiteError"></span>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="bio">Bio</label>
                <textarea 
                    id="bio" 
                    name="bio" 
                    class="form-textarea"
                    placeholder="Tell us about yourself..."
                    maxlength="500"
                ></textarea>
                <div class="char-counter">
                    <span id="charCount">0</span>/500 characters
                </div>
                <span class="error-message" id="bioError"></span>
            </div>

            <button type="submit" class="submit-button" id="submitBtn">
                Create Account
            </button>
        </form>

        <div class="signup-footer">
            <p class="footer-text">
                Already have an account? 
                <a href="login.php" class="footer-link">Sign In</a>
            </p>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        const form = document.getElementById('signupForm');
        const submitBtn = document.getElementById('submitBtn');
        const notification = document.getElementById('notification');
        const bioInput = document.getElementById('bio');
        const charCount = document.getElementById('charCount');
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        bioInput.addEventListener('input', () => {
            charCount.textContent = bioInput.value.length;
        });

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthBar.className = 'strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#ff6b6b';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#ffa500';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#4CAF50';
            }
        });

        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });
            document.querySelectorAll('.form-input, .form-textarea').forEach(el => {
                el.classList.remove('error');
            });
        }

        function displayErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorEl = document.getElementById(field + 'Error');
                const inputEl = document.getElementById(field);
                
                if (errorEl && inputEl) {
                    errorEl.textContent = errors[field];
                    errorEl.classList.add('show');
                    inputEl.classList.add('error');
                }
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            clearErrors();
            
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Creating Account...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(form);
                
                const response = await fetch('handlers/signup_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    form.reset();
                    charCount.textContent = '0';
                    strengthBar.className = 'strength-bar';
                    strengthText.textContent = '';
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    showNotification(result.message, 'error');
                    if (result.errors) {
                        displayErrors(result.errors);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
            input.addEventListener('blur', () => {
                const errorEl = document.getElementById(input.id + 'Error');
                
                if (input.hasAttribute('required') && !input.value.trim()) {
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                    if (errorEl) {
                        errorEl.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>
</html>