<?php

// ============================================
// LOGIN HANDLER (FINAL – SUSPENSION SAFE)
// ============================================

session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$username_or_email = trim($_POST['username_or_email'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

if ($username_or_email === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Username or email is required'
    ]);
    exit;
}

if ($password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Password is required'
    ]);
    exit;
}

try {// ============================================
    // 1️⃣ ADMIN LOGIN CHECK (EMAIL ONLY)
    // ============================================
    $stmt = $pdo->prepare("
    SELECT *
    FROM admins
    WHERE email = ?
    LIMIT 1
");
    $stmt->execute([$username_or_email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {

        // Password check
        if (!password_verify($password, $admin['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Incorrect admin password'
            ]);
            exit;
        }

        // Optional: admin suspension / active check
        if (isset($admin['is_active']) && (int) $admin['is_active'] !== 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Admin account is disabled'
            ]);
            exit;
        }

        // ✅ ADMIN LOGIN SUCCESS
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['is_admin'] = true;

        generateCSRFToken();

        echo json_encode([
            'success' => true,
            'message' => 'Admin login successful',
            'redirect' => 'admin/admin_dashboard.php'
        ]);
        exit;
    }

    // ============================================
    // FETCH USER
    // ============================================
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email = ? OR username = ?
        LIMIT 1
        ");
    $stmt->execute([$username_or_email, $username_or_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'No account found with those credentials'
        ]);
        exit;
    }

    // ============================================
    // PASSWORD CHECK (FIRST!)
    // ============================================
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Incorrect password'
        ]);
        exit;
    }

    // ============================================
    // 🔴 SUSPENSION CHECK (CRITICAL FIX)
    // ============================================
    if ((int) $user['is_suspended'] === 1) {

        // Temporary suspension expired → auto lift
        if (!empty($user['suspended_until']) && strtotime($user['suspended_until']) <= time()) {

            $pdo->prepare("
                UPDATE users
                SET is_suspended = 0,
                    suspension_reason = NULL,
                    suspended_until = NULL,
                    is_active = 1
                WHERE id = ?
            ")->execute([$user['id']]);

        } else {
            // Still suspended
            if (!empty($user['suspended_until'])) {
                $date = date('F j, Y', strtotime($user['suspended_until']));
                echo json_encode([
                    'success' => false,
                    'message' => "Your account has been suspended due to guideline violations. Please retry on {$date}."
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Your account has been permanently suspended due to guideline violations.'
                ]);
            }
            exit;
        }
    }

    // ============================================
    // 🔒 ACTIVE CHECK
    // ============================================
    if ((int) $user['is_active'] !== 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Your account is currently deactivated'
        ]);
        exit;
    }

    // ============================================
    // ✅ LOGIN SUCCESS
    // ============================================
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];

    generateCSRFToken();

    // ============================================
    // REMEMBER ME
    // ============================================
    if ($remember_me) {
        $token = setRememberToken($pdo, $user['id']);
        if ($token) {
            setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
            setcookie('remember_user', $user['id'], time() + 86400 * 30, '/', '', false, true);
        }
    }

    // Update last login
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
        ->execute([$user['id']]);

    // ============================================
    // ONBOARDING REDIRECT
    // ============================================
    if ((int) $user['onboarding_completed'] === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'onboarding_preferences.php'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'homepage.php'
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[LOGIN ERROR] ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
    exit;
}
