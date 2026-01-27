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
    <link rel="stylesheet" href="styles/signup.css">
    
</head>
<body>


<?php include 'header.php'; ?>

    <main class="auth-page">

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
</main>
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