-- ============================================================================
-- PHP Code Snippet Manager - Database Schema
-- Version: 1.0.0
-- ============================================================================
-- 
-- IMPORTANT: This file creates tables ONLY (no data).
-- 
-- For full installation with admin user and default data, use:
--   http://localhost/snippet-manager/install.php
--
-- If importing manually, run install.php after to create admin user.
-- ============================================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS `snippet_manager` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `snippet_manager`;

-- ============================================================================
-- Table: users
-- Purpose: Store user accounts for authentication and team features
-- ============================================================================
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `snippet_versions`;
DROP TABLE IF EXISTS `shared_snippets`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `snippet_tags`;
DROP TABLE IF EXISTS `snippets`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique username for login',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'User email address',
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
    `full_name` VARCHAR(100) NOT NULL COMMENT 'Display name',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT 'Path to avatar image',
    `role` ENUM('admin', 'member') DEFAULT 'member' COMMENT 'User role',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Account active status',
    `last_login` DATETIME DEFAULT NULL COMMENT 'Last login timestamp',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: categories
-- Purpose: Organize snippets into categories for easy browsing
-- ============================================================================
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Category name',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-friendly slug',
    `description` TEXT DEFAULT NULL COMMENT 'Category description',
    `color` VARCHAR(7) DEFAULT '#6366f1' COMMENT 'Category color hex code',
    `icon` VARCHAR(50) DEFAULT 'folder' COMMENT 'Icon identifier',
    `parent_id` INT UNSIGNED DEFAULT NULL COMMENT 'Parent category for nesting',
    `sort_order` INT DEFAULT 0 COMMENT 'Display order',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: snippets
-- Purpose: Store code snippets with metadata
-- ============================================================================
CREATE TABLE `snippets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL COMMENT 'Snippet title',
    `slug` VARCHAR(255) NOT NULL COMMENT 'URL-friendly slug',
    `description` TEXT DEFAULT NULL COMMENT 'Brief description of the snippet',
    `code` LONGTEXT NOT NULL COMMENT 'The actual code content',
    `language` VARCHAR(50) DEFAULT 'php' COMMENT 'Programming language',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT 'Category reference',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Creator user reference',
    `is_public` TINYINT(1) DEFAULT 0 COMMENT 'Public visibility flag',
    `is_pinned` TINYINT(1) DEFAULT 0 COMMENT 'Pinned to top flag',
    `views_count` INT UNSIGNED DEFAULT 0 COMMENT 'Number of views',
    `copies_count` INT UNSIGNED DEFAULT 0 COMMENT 'Number of times copied',
    `version` INT UNSIGNED DEFAULT 1 COMMENT 'Snippet version number',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_language` (`language`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_public` (`is_public`),
    FULLTEXT INDEX `idx_search` (`title`, `description`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: tags
-- Purpose: Flexible tagging system for snippets
-- ============================================================================
CREATE TABLE `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tag name',
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL-friendly slug',
    `color` VARCHAR(7) DEFAULT '#8b5cf6' COMMENT 'Tag color',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: snippet_tags (Pivot)
-- Purpose: Many-to-many relationship between snippets and tags
-- ============================================================================
CREATE TABLE `snippet_tags` (
    `snippet_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`snippet_id`, `tag_id`),
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: favorites
-- Purpose: Track user's favorite/bookmarked snippets
-- ============================================================================
CREATE TABLE `favorites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `snippet_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_favorite` (`user_id`, `snippet_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: shared_snippets
-- Purpose: Team sharing - share snippets with specific users
-- ============================================================================
CREATE TABLE `shared_snippets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `snippet_id` INT UNSIGNED NOT NULL,
    `shared_by` INT UNSIGNED NOT NULL COMMENT 'User who shared',
    `shared_with` INT UNSIGNED NOT NULL COMMENT 'User shared with',
    `permission` ENUM('view', 'edit') DEFAULT 'view' COMMENT 'Permission level',
    `message` TEXT DEFAULT NULL COMMENT 'Optional sharing message',
    `is_read` TINYINT(1) DEFAULT 0 COMMENT 'Read status',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_share` (`snippet_id`, `shared_by`, `shared_with`),
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_with`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_shared_with` (`shared_with`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: snippet_versions
-- Purpose: Version history for snippets
-- ============================================================================
CREATE TABLE `snippet_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `snippet_id` INT UNSIGNED NOT NULL,
    `code` LONGTEXT NOT NULL COMMENT 'Code at this version',
    `version` INT UNSIGNED NOT NULL COMMENT 'Version number',
    `change_note` VARCHAR(255) DEFAULT NULL COMMENT 'What changed',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_snippet_version` (`snippet_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: activity_log
-- Purpose: Track all user activities for audit trail
-- ============================================================================
CREATE TABLE `activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'Action performed',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'Type of entity',
    `entity_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID of the entity',
    `details` JSON DEFAULT NULL COMMENT 'Additional action details',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'User IP address',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSTALLATION COMPLETE
-- ============================================================================
-- Tables created. Now run install.php in your browser to:
--   1. Create admin user with secure password
--   2. Insert default categories and tags
-- ============================================================================
