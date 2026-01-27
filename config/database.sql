-- ============================================
-- DATABASE SCHEMA FOR CONTENT DISCOVERY PLATFORM
-- Primary Color: #CEA1F5 (Purple)
-- Secondary Color: #15051d (Dark Purple)
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS content_discovery
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE content_discovery;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT,
    location VARCHAR(100),
    website VARCHAR(255),
    profile_image VARCHAR(255),
    cover_image VARCHAR(255),
    
    -- Stats
    total_posts INT DEFAULT 0,
    followers INT DEFAULT 0,
    following INT DEFAULT 0,
    
    -- Account status
    is_active BOOLEAN DEFAULT 1,
    is_verified BOOLEAN DEFAULT 0,
    email_verified BOOLEAN DEFAULT 0,
    
    -- Security
    remember_token VARCHAR(100),
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expires DATETIME,
    
    -- Timestamps
    join_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ;

-- ============================================
-- CONTENT TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    image_url VARCHAR(255),
    content_type ENUM('image', 'video', 'article', 'audio') DEFAULT 'image',
    
    -- Stats
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    shares INT DEFAULT 0,
    
    -- Status
    is_published BOOLEAN DEFAULT 1,
    is_featured BOOLEAN DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME,
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at),
    INDEX idx_is_published (is_published)
) ;

-- ============================================
-- FOLLOWERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Prevent duplicate follows
    UNIQUE KEY unique_follow (follower_id, following_id),
    
    -- Indexes
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
) 

-- ============================================
-- LIKES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    
    -- Prevent duplicate likes
    UNIQUE KEY unique_like (user_id, content_id),
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_content_id (content_id)
) ;

-- ============================================
-- COLLECTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_is_public (is_public)
) ;

-- ============================================
-- COLLECTION ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS collection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    content_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    
    -- Prevent duplicate items in collection
    UNIQUE KEY unique_collection_item (collection_id, content_id),
    
    -- Indexes
    INDEX idx_collection_id (collection_id),
    INDEX idx_content_id (content_id)
) ;

-- ============================================
-- COMMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    comment_text TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_content_id (content_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id)
) ;

-- ============================================
-- INSERT DEMO DATA (Optional)
-- ============================================

-- Demo user (password: Demo1234)
INSERT INTO users (username, email, password, full_name, bio, location, website, total_posts, followers, following)
VALUES (
    'demo',
    'demo@discover.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Demo1234
    'Demo User',
    'Digital artist and content creator passionate about visual storytelling.',
    'San Francisco, CA',
    'https://example.com',
    247,
    12500,
    834
);

-- Additional demo users
INSERT INTO users (username, email, password, full_name, bio, location, total_posts, followers, following)
VALUES 
    ('creative_explorer', 'explorer@discover.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Thompson', 'Exploring the creative universe one pixel at a time.', 'New York, NY', 156, 8420, 523),
    ('art_enthusiast', 'art@discover.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Chen', 'Contemporary art collector and curator.', 'London, UK', 89, 15200, 342),
    ('tech_creator', 'tech@discover.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marcus Johnson', 'Building the future of creative technology.', 'Austin, TX', 203, 22100, 891);

-- Demo content
INSERT INTO content (user_id, title, description, category, views, likes, is_published, published_at)
VALUES 
    (1, 'Digital Art Revolution', 'Exploring new frontiers in digital creativity', 'Art', 45230, 3421, 1, NOW()),
    (1, 'Cosmic Journey', 'A visual exploration of space and time', 'Art', 32100, 2856, 1, NOW()),
    (2, 'Urban Landscapes', 'Capturing the essence of modern cities', 'Photography', 28450, 2134, 1, NOW()),
    (3, 'Web Design Trends', 'The future of web design in 2025', 'Design', 51200, 4532, 1, NOW());

-- ============================================
-- USEFUL QUERIES
-- ============================================

-- Get user with their stats
-- SELECT u.*, COUNT(DISTINCT c.id) as content_count 
-- FROM users u 
-- LEFT JOIN content c ON u.id = c.user_id 
-- WHERE u.id = 1;

-- Get user's followers count
-- SELECT COUNT(*) as followers_count 
-- FROM followers 
-- WHERE following_id = 1;

-- Get user's following count
-- SELECT COUNT(*) as following_count 
-- FROM followers 
-- WHERE follower_id = 1;

-- Get trending content
-- SELECT * FROM content 
-- WHERE is_published = 1 
-- ORDER BY (views * 0.3 + likes * 0.7) DESC 
-- LIMIT 20;
-- ============================================
-- CLEANUP: DROP ALL ADMIN TABLES
-- ============================================

USE content_discovery;

-- Drop tables in correct order (respect foreign key constraints)
DROP TABLE IF EXISTS admin_activity_log;
DROP TABLE IF EXISTS user_reports;
DROP TABLE IF EXISTS content_moderation;
DROP TABLE IF EXISTS user_suspensions;
DROP TABLE IF EXISTS admins;

-- Remove admin-related columns from users table
ALTER TABLE users 
DROP COLUMN IF EXISTS is_suspended,
DROP COLUMN IF EXISTS suspension_reason,
DROP COLUMN IF EXISTS suspended_until;

-- ============================================
-- CREATE SINGLE ADMIN TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    
    -- Account status
    is_active BOOLEAN DEFAULT 1,
    
    -- Security
    remember_token VARCHAR(100),
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ;

-- ============================================
-- USER SUSPENSIONS TABLE (Simplified)
-- ============================================

CREATE TABLE IF NOT EXISTS user_suspensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    reason TEXT NOT NULL,
    suspension_type ENUM('temporary', 'permanent') DEFAULT 'temporary',
    suspended_until DATETIME,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lifted_at DATETIME,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_is_active (is_active)
) ;

-- ============================================
-- USER REPORTS TABLE (Simplified)
-- ============================================

CREATE TABLE IF NOT EXISTS user_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT,
    reported_content_id INT,
    report_type ENUM('spam', 'harassment', 'inappropriate', 'copyright', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    
    -- Foreign keys
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_reporter (reporter_id),
    INDEX idx_reported_user (reported_user_id),
    INDEX idx_status (status)
) ;

-- ============================================
-- ADMIN ACTIVITY LOG (Simplified)
-- ============================================

CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('user', 'content', 'report') NOT NULL,
    target_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ;

-- ============================================
-- ADD SUSPENSION STATUS TO USERS TABLE
-- ============================================

ALTER TABLE users 
ADD COLUMN is_suspended BOOLEAN DEFAULT 0 AFTER is_verified,
ADD COLUMN suspension_reason TEXT AFTER is_suspended,
ADD COLUMN suspended_until DATETIME AFTER suspension_reason,
ADD INDEX idx_is_suspended (is_suspended);

-- ============================================
-- INSERT SINGLE ADMIN ACCOUNT
-- ============================================

-- Admin credentials:
-- Username: admin
-- Password: Admin@123
-- Email: admin2@kriativity.com

INSERT INTO admins (username, email, password, full_name, is_active)
VALUES (
    'admin',
    'admin@kriativity.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrator',
    1
);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check if admin exists
-- SELECT * FROM admins WHERE username = 'admin';

-- Check all tables
-- SHOW TABLES LIKE '%admin%' OR SHOW TABLES LIKE '%suspension%' OR SHOW TABLES LIKE '%report%';

-- Count records
-- SELECT 
--     (SELECT COUNT(*) FROM admins) as total_admins,
--     (SELECT COUNT(*) FROM user_suspensions) as total_suspensions,
--     (SELECT COUNT(*) FROM user_reports) as total_reports,
--     (SELECT COUNT(*) FROM admin_activity_log) as total_logs;
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
ALTER TABLE users 
ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0;
