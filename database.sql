-- ============================================================================
-- PHP Code Snippet Manager - Complete Database
-- Version: 1.0.0
-- ============================================================================
-- 
-- This file creates the database, all tables, and inserts all default data
-- including admin and demo users with pre-hashed passwords.
--
-- JUST IMPORT THIS FILE AND LOGIN. No extra steps needed.
--
-- Login credentials after import:
--   Admin  →  username: admin   |  password: admin123
--   Member →  username: demo    |  password: demo123
--
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `snippet_manager` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `snippet_manager`;

-- ============================================================================
-- Drop existing tables (fresh install)
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

-- ============================================================================
-- Table: users
-- ============================================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin', 'member') DEFAULT 'member',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: categories
-- ============================================================================
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT '#6366f1',
    `icon` VARCHAR(50) DEFAULT 'folder',
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
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
-- ============================================================================
CREATE TABLE `snippets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `code` LONGTEXT NOT NULL,
    `language` VARCHAR(50) DEFAULT 'php',
    `category_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `is_public` TINYINT(1) DEFAULT 0,
    `is_pinned` TINYINT(1) DEFAULT 0,
    `views_count` INT UNSIGNED DEFAULT 0,
    `copies_count` INT UNSIGNED DEFAULT 0,
    `version` INT UNSIGNED DEFAULT 1,
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
-- ============================================================================
CREATE TABLE `tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `color` VARCHAR(7) DEFAULT '#8b5cf6',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: snippet_tags (pivot)
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
-- ============================================================================
CREATE TABLE `shared_snippets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `snippet_id` INT UNSIGNED NOT NULL,
    `shared_by` INT UNSIGNED NOT NULL,
    `shared_with` INT UNSIGNED NOT NULL,
    `permission` ENUM('view', 'edit') DEFAULT 'view',
    `message` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_share` (`snippet_id`, `shared_by`, `shared_with`),
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shared_with`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_shared_with` (`shared_with`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: snippet_versions
-- ============================================================================
CREATE TABLE `snippet_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `snippet_id` INT UNSIGNED NOT NULL,
    `code` LONGTEXT NOT NULL,
    `version` INT UNSIGNED NOT NULL,
    `change_note` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_snippet_version` (`snippet_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: activity_log
-- ============================================================================
CREATE TABLE `activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- ============================================================================
--  DEFAULT DATA — Everything below is ready to use. Just import and login.
-- ============================================================================
-- ============================================================================


-- ============================================================================
-- Users
-- ============================================================================
-- IMPORTANT: The passwords below are TEMPORARY placeholders.
-- After importing this file, do ONE of the following:
--
--   Option A (Recommended): Open install.php in browser — it will
--             automatically fix the passwords with proper bcrypt hashes.
--
--   Option B: Open generate_hash.php in browser — it will show you
--             the correct SQL with real hashes. Copy and run that SQL.
--
-- Login credentials (after fixing passwords):
--   Admin  →  username: admin   |  password: admin123
--   Member →  username: demo    |  password: demo123
-- ============================================================================
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@snippetmanager.com', 'NEEDS_HASH_RUN_INSTALL_PHP', 'Administrator', 'admin'),
('demo', 'demo@snippetmanager.com', 'NEEDS_HASH_RUN_INSTALL_PHP', 'Demo User', 'member');

-- ============================================================================
-- Categories (10 default)
-- ============================================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `color`, `icon`, `sort_order`, `created_by`) VALUES
('Database', 'database', 'MySQL, PDO, and database-related snippets', '#ef4444', 'database', 1, 1),
('Authentication', 'authentication', 'Login, registration, and auth snippets', '#f97316', 'shield', 2, 1),
('File Handling', 'file-handling', 'File upload, download, and manipulation', '#eab308', 'file', 3, 1),
('API', 'api', 'REST API and cURL related snippets', '#22c55e', 'globe', 4, 1),
('String Manipulation', 'string-manipulation', 'String processing and formatting', '#3b82f6', 'type', 5, 1),
('Array Operations', 'array-operations', 'Array sorting, filtering, and manipulation', '#8b5cf6', 'list', 6, 1),
('Email', 'email', 'Email sending and template snippets', '#ec4899', 'mail', 7, 1),
('Security', 'security', 'Encryption, sanitization, and security', '#14b8a6', 'lock', 8, 1),
('Utilities', 'utilities', 'Helper functions and utilities', '#6366f1', 'tool', 9, 1),
('OOP Patterns', 'oop-patterns', 'Design patterns and OOP concepts', '#a855f7', 'code', 10, 1);

-- ============================================================================
-- Tags (10 default)
-- ============================================================================
INSERT INTO `tags` (`name`, `slug`, `color`) VALUES
('php', 'php', '#777BB4'),
('mysql', 'mysql', '#4479A1'),
('pdo', 'pdo', '#336791'),
('security', 'security', '#DC2626'),
('helper', 'helper', '#059669'),
('crud', 'crud', '#D97706'),
('ajax', 'ajax', '#2563EB'),
('oop', 'oop', '#7C3AED'),
('api', 'api', '#0891B2'),
('validation', 'validation', '#E11D48');

-- ============================================================================
-- Sample Snippets (3 snippets for admin user)
-- ============================================================================
INSERT INTO `snippets` (`title`, `slug`, `description`, `code`, `language`, `category_id`, `user_id`, `is_public`, `version`) VALUES
(
    'PDO Database Connection',
    'pdo-database-connection',
    'Secure PDO database connection with singleton pattern and error handling.',
    '<?php\nclass Database {\n    private static ?PDO $conn = null;\n\n    public static function connect(): PDO {\n        if (self::$conn === null) {\n            $dsn = \"mysql:host=localhost;dbname=mydb;charset=utf8mb4\";\n            self::$conn = new PDO($dsn, \"root\", \"\", [\n                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n                PDO::ATTR_EMULATE_PREPARES => false,\n            ]);\n        }\n        return self::$conn;\n    }\n}\n\n// Usage\n$db = Database::connect();\n$stmt = $db->prepare(\"SELECT * FROM users WHERE id = ?\");\n$stmt->execute([1]);\n$user = $stmt->fetch();\necho $user[\"name\"];\n?>',
    'php', 1, 1, 1, 1
),
(
    'Secure File Upload Handler',
    'secure-file-upload',
    'Handle file uploads with MIME validation, size limits, and unique naming.',
    '<?php\nfunction uploadFile(array $file, string $dir = \"uploads/\"): ?string {\n    $allowed = [\"image/jpeg\", \"image/png\", \"image/gif\", \"application/pdf\"];\n\n    if ($file[\"error\"] !== UPLOAD_ERR_OK) return null;\n    if ($file[\"size\"] > 5 * 1024 * 1024) return null;\n\n    $finfo = new finfo(FILEINFO_MIME_TYPE);\n    if (!in_array($finfo->file($file[\"tmp_name\"]), $allowed)) return null;\n\n    $ext = pathinfo($file[\"name\"], PATHINFO_EXTENSION);\n    $name = bin2hex(random_bytes(16)) . \".\" . $ext;\n\n    if (!is_dir($dir)) mkdir($dir, 0755, true);\n    move_uploaded_file($file[\"tmp_name\"], $dir . $name);\n\n    return $name;\n}\n\n// Usage\n$filename = uploadFile($_FILES[\"avatar\"]);\nif ($filename) echo \"Uploaded: \" . $filename;\n?>',
    'php', 3, 1, 1, 1
),
(
    'JSON API Response Helper',
    'json-api-response',
    'Standardized JSON response function for building REST APIs.',
    '<?php\nfunction apiResponse(mixed $data = null, string $message = \"Success\", int $code = 200): void {\n    http_response_code($code);\n    header(\"Content-Type: application/json; charset=utf-8\");\n\n    echo json_encode([\n        \"success\" => $code >= 200 && $code < 300,\n        \"message\" => $message,\n        \"data\"    => $data,\n        \"code\"    => $code,\n    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);\n\n    exit;\n}\n\n// Usage examples:\napiResponse([\"user\" => $user], \"User found\", 200);\napiResponse(null, \"Not found\", 404);\napiResponse(null, \"Unauthorized\", 401);\n?>',
    'php', 4, 1, 1, 1
);

-- ============================================================================
-- Link snippets to tags
-- ============================================================================
INSERT INTO `snippet_tags` (`snippet_id`, `tag_id`) VALUES
(1, 1), (1, 2), (1, 3),   -- PDO snippet → php, mysql, pdo
(2, 1), (2, 4), (2, 5),   -- File Upload → php, security, helper
(3, 1), (3, 9), (3, 5);   -- API Response → php, api, helper

-- ============================================================================
-- Version history for sample snippets
-- ============================================================================
INSERT INTO `snippet_versions` (`snippet_id`, `code`, `version`, `change_note`, `created_by`) 
SELECT `id`, `code`, 1, 'Initial version', `user_id` FROM `snippets`;

-- ============================================================================
-- DONE! You can now login:
--   Admin  →  admin / admin123
--   Member →  demo  / demo123
-- ============================================================================
