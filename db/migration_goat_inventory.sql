-- =====================================================================
-- Goat Inventory Management Module - Additive Migration
-- =====================================================================
-- This migration ONLY ADDS new database objects. It does not modify,
-- rename, or drop any existing table, column, row, or constraint.
-- It is safe to run against an existing soy_php_pdo database.
--
-- What this file does:
--   1. Creates the `goats` table (extends the system with goat records,
--      each owned by an existing user via user_id -> users.id).
--   2. Adds two NULLABLE columns to the existing `activity_logs` table
--      (goat_id, details) so goat-related actions can be logged using
--      the system's existing audit/activity logging mechanism instead
--      of creating a brand new audit table. Existing INSERT statements
--      that don't reference these columns are unaffected because both
--      columns are nullable with no default requirement.
-- =====================================================================

USE soy_php_pdo;

-- ---------------------------------------------------------------------
-- 1. Goats table (new, additive)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS goats (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Unique, farmer-assigned identifier for the goat (globally unique)
    goat_id VARCHAR(50) NOT NULL,

    -- Owner: references the existing users table. Never trust a
    -- client-submitted user_id; this must always be set from the
    -- authenticated session server-side.
    user_id INT NOT NULL,

    goat_name VARCHAR(150) NOT NULL DEFAULT '',
    tag_number VARCHAR(50) NULL,
    sex ENUM('male', 'female') NOT NULL,
    breed VARCHAR(100) NOT NULL DEFAULT '',
    date_of_birth DATE NULL,
    color VARCHAR(150) NULL,
    identifying_characteristics TEXT NULL,
    date_acquired DATE NULL,

    -- Normalized health status values only: healthy | sick | unknown
    health_status ENUM('healthy', 'sick', 'unknown') NOT NULL DEFAULT 'unknown',

    -- Normalized goat status values only: active | sold | deceased
    status ENUM('active', 'sold', 'deceased') NOT NULL DEFAULT 'active',

    date_registered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_goat_id (goat_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_health_status (health_status),

    CONSTRAINT fk_goats_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. Additive columns on the existing activity_logs table so goat
--    actions can reuse the existing audit/activity logging system
--    instead of a duplicate audit table.
-- ---------------------------------------------------------------------
-- Run these two ALTERs only if the columns do not already exist.
-- (MySQL doesn't support IF NOT EXISTS for ADD COLUMN uniformly across
-- versions, so if you re-run this file and get a "duplicate column"
-- error, it simply means this step already applied successfully.)

ALTER TABLE activity_logs ADD COLUMN goat_id VARCHAR(50) NULL AFTER user_id;
ALTER TABLE activity_logs ADD COLUMN details TEXT NULL AFTER status;
ALTER TABLE activity_logs ADD INDEX idx_goat_id (goat_id);

-- =====================================================================
-- End of Goat Inventory Management Module migration.
-- Nothing above modifies existing users, roles, authentication,
-- sessions, or previously stored data.
-- =====================================================================
