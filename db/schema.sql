CREATE DATABASE IF NOT EXISTS soy_php_pdo;

USE soy_php_pdo;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL DEFAULT '',
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',

    -- Account status: controls whether the account can log in
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    -- Email verification fields
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64),
    email_verification_expires DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample users (password is 'password123' for all)
INSERT INTO users (full_name, username, email, password, role, status, is_verified) VALUES
('System Administrator', 'admin', 'admin@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'admin', 'active', 1),
('Farm Manager', 'manager', 'manager@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'manager', 'active', 1),
('Juan Dela Cruz', 'jdelacruz', 'user@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'user', 'active', 1);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,  -- NULL for failed login attempts
    email VARCHAR(255),
    action VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed') DEFAULT 'success',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Migration helper: if you already have an existing soy_php_pdo
-- database from a previous version of this project, run the statements
-- below instead of the CREATE TABLE above to upgrade in place.
-- ---------------------------------------------------------------------
-- ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NOT NULL DEFAULT '' AFTER id;
-- ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL AFTER full_name;
-- ALTER TABLE users ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER role;
-- UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username = '';