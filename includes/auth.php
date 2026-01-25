<?php
// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Validate username format
 * @param string $username
 * @return bool
 */
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return [
        'valid' => empty($errors),
        'message' => implode(', ', $errors)
    ];
}

/**
 * Check if username exists
 * @param PDO $pdo
 * @param string $username
 * @return bool
 */
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() !== false;
}

/**
 * Check if email exists
 * @param PDO $pdo
 * @param string $email
 * @return bool
 */
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Register a new user
 * @param PDO $pdo
 * @param array $data
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function registerUser($pdo, $data) {
    try {
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password, full_name, bio, location, website, verification_token
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashed_password,
            $data['full_name'],
            $data['bio'] ?? '',
            $data['location'] ?? '',
            $data['website'] ?? '',
            $verification_token
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // TODO: Send verification email
        // sendVerificationEmail($data['email'], $verification_token);
        
        return [
            'success' => true,
            'message' => 'Account created successfully!',
            'user_id' => $user_id
        ];
        
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again.',
            'user_id' => null
        ];
    }
}

/**
 * Login user
 * @param PDO $pdo
 * @param string $username_or_email
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser($pdo, $username_or_email, $password) {
    try {
        // Find user by username or email
        $stmt = $pdo->prepare("
            SELECT id, username, email, password, full_name, is_active, email_verified
            FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null
            ];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.',
                'user' => null
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null
            ];
        }
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Remove password from user data
        unset($user['password']);
        
        return [
            'success' => true,
            'message' => 'Login successful!',
            'user' => $user
        ];
        
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Login failed. Please try again.',
            'user' => null
        ];
    }
}

/**
 * Set remember me token
 * @param PDO $pdo
 * @param int $user_id
 * @return string|null
 */
function setRememberToken($pdo, $user_id) {
    try {
        $token = bin2hex(random_bytes(32));
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$hashed_token, $user_id]);
        
        return $token;
    } catch (PDOException $e) {
        error_log("Remember Token Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify remember me token
 * @param PDO $pdo
 * @param int $user_id
 * @param string $token
 * @return bool
 */
function verifyRememberToken($pdo, $user_id, $token) {
    try {
        $stmt = $pdo->prepare("SELECT remember_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['remember_token']) {
            return false;
        }
        
        return password_verify($token, $user['remember_token']);
    } catch (PDOException $e) {
        error_log("Verify Token Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 * @param PDO $pdo
 * @param int $user_id
 * @return array|null
 */
function getUserById($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, bio, location, website, 
                   profile_image, cover_image, total_posts, followers, following,
                   join_date, is_verified, email_verified
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update user profile
 * @param PDO $pdo
 * @param int $user_id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function updateUserProfile($pdo, $user_id, $data) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, bio = ?, location = ?, website = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['full_name'],
            $data['bio'] ?? '',
            $data['location'] ?? '',
            $data['website'] ?? '',
            $user_id
        ]);
        
        return [
            'success' => true,
            'message' => 'Profile updated successfully!'
        ];
        
    } catch (PDOException $e) {
        error_log("Update Profile Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update profile. Please try again.'
        ];
    }
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>