-- DROP DATABASE IF EXISTS kriativity;
CREATE DATABASE kriativity;
USE kriativity;

-- ============================================
-- USERS
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,

    bio TEXT,
    location VARCHAR(100),
    website VARCHAR(255),
    profile_image VARCHAR(255),
    cover_image VARCHAR(255),

    total_posts INT DEFAULT 0,
    followers INT DEFAULT 0,
    following INT DEFAULT 0,

    is_active BOOLEAN DEFAULT 1,
    is_verified BOOLEAN DEFAULT 0,
    email_verified BOOLEAN DEFAULT 0,

    is_suspended BOOLEAN DEFAULT 0,
    suspension_reason TEXT,
    suspended_until DATETIME,

    onboarding_completed TINYINT(1) DEFAULT 0,

    remember_token VARCHAR(100),
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expires DATETIME,

    join_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- CONTENT
-- ============================================
CREATE TABLE content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    image_url VARCHAR(255),
    content_type ENUM('image', 'video', 'article', 'audio') DEFAULT 'image',

    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    shares INT DEFAULT 0,

    is_published BOOLEAN DEFAULT 1,
    is_featured BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- LIKES (SIMPLE & CLEAN)
-- ============================================
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,

    UNIQUE (user_id, content_id)
);

-- ============================================
-- FOLLOWERS
-- ============================================
CREATE TABLE followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE (follower_id, following_id)
);

-- ============================================
-- COMMENTS
-- ============================================
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    parent_id INT DEFAULT NULL,

    comment_text TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- ============================================
-- ADMINS
-- ============================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,

    is_active BOOLEAN DEFAULT 1,
    remember_token VARCHAR(100),
    last_login DATETIME,
    last_login_ip VARCHAR(45),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- USER SUSPENSIONS
-- ============================================
CREATE TABLE user_suspensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,

    reason TEXT NOT NULL,
    suspension_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
    suspended_until DATETIME,
    is_active BOOLEAN DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lifted_at DATETIME,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- USER REPORTS
-- ============================================
CREATE TABLE user_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT,
    reported_content_id INT,

    report_type ENUM('spam','harassment','inappropriate','copyright','other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending','reviewed','resolved','dismissed') DEFAULT 'pending',

    reviewed_by INT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,

    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- ============================================
-- ADMIN ACTIVITY LOG
-- ============================================
CREATE TABLE admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,

    action VARCHAR(100) NOT NULL,
    target_type ENUM('user','content','report') NOT NULL,
    target_id INT,
    description TEXT,
    ip_address VARCHAR(45),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- USER PREFERENCES (RECOMMENDATION SOURCE)
-- ============================================
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,

    preferred_categories JSON,
    preferred_content_types JSON,
    discovery_goal VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
