<?php
/**
 * ============================================================================
 * Code Snippet Manager — Installer
 * ============================================================================
 * 
 * One-time installation script that:
 * 1. Tests database connection
 * 2. Creates all tables (runs database.sql)
 * 3. Creates the admin user with properly hashed password
 * 4. Inserts default categories, tags, and sample snippets
 * 
 * ⚠️  DELETE THIS FILE AFTER INSTALLATION FOR SECURITY!
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

// ============================================================================
// Database Configuration — UPDATE THESE VALUES
// ============================================================================
$dbConfig = [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'snippet_manager',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

// ============================================================================
// Admin Account Configuration — CHANGE THE PASSWORD!
// ============================================================================
$adminConfig = [
    'username'  => 'admin',
    'email'     => 'admin@snippetmanager.com',
    'password'  => 'admin123',        // ← Change this!
    'full_name' => 'Administrator',
];

// ============================================================================
// Process Installation
// ============================================================================
$messages = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Override config from form values
    $dbConfig['host']     = trim($_POST['db_host'] ?? $dbConfig['host']);
    $dbConfig['port']     = (int)($_POST['db_port'] ?? $dbConfig['port']);
    $dbConfig['dbname']   = trim($_POST['db_name'] ?? $dbConfig['dbname']);
    $dbConfig['username'] = trim($_POST['db_user'] ?? $dbConfig['username']);
    $dbConfig['password'] = $_POST['db_pass'] ?? $dbConfig['password'];

    $adminConfig['username']  = trim($_POST['admin_user'] ?? $adminConfig['username']);
    $adminConfig['email']     = trim($_POST['admin_email'] ?? $adminConfig['email']);
    $adminConfig['password']  = $_POST['admin_pass'] ?? $adminConfig['password'];
    $adminConfig['full_name'] = trim($_POST['admin_name'] ?? $adminConfig['full_name']);

    try {
        // Step 1: Connect to MySQL server (without database)
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['charset']);
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $messages[] = ['success', '✅ Connected to MySQL server successfully.'];

        // Step 2: Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbConfig['dbname']}`");
        $messages[] = ['success', "✅ Database `{$dbConfig['dbname']}` is ready."];

        // Step 3: Create tables
        // -- users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
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
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- categories table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
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
                INDEX `idx_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- snippets table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `snippets` (
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
                INDEX `idx_user` (`user_id`),
                INDEX `idx_category` (`category_id`),
                INDEX `idx_public` (`is_public`),
                FULLTEXT INDEX `idx_search` (`title`, `description`, `code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- tags table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `tags` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `slug` VARCHAR(50) NOT NULL UNIQUE,
                `color` VARCHAR(7) DEFAULT '#8b5cf6',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- snippet_tags pivot table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `snippet_tags` (
                `snippet_id` INT UNSIGNED NOT NULL,
                `tag_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`snippet_id`, `tag_id`),
                FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- favorites table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `favorites` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `snippet_id` INT UNSIGNED NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_favorite` (`user_id`, `snippet_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`snippet_id`) REFERENCES `snippets`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- shared_snippets table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `shared_snippets` (
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
                FOREIGN KEY (`shared_with`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- snippet_versions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `snippet_versions` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // -- activity_log table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `activity_log` (
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
                INDEX `idx_action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $messages[] = ['success', '✅ All 9 database tables created successfully.'];

        // Step 4: Create admin user with PROPER password hash
        $hashedPassword = password_hash($adminConfig['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Check if admin already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$adminConfig['username']]);

        if ($checkStmt->fetch()) {
            // Update existing admin password
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, email = ?, full_name = ?, role = 'admin' WHERE username = ?");
            $updateStmt->execute([$hashedPassword, $adminConfig['email'], $adminConfig['full_name'], $adminConfig['username']]);
            $messages[] = ['success', '✅ Admin user updated with new password hash.'];
        } else {
            // Insert new admin user
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'admin')"
            );
            $insertStmt->execute([
                $adminConfig['username'],
                $adminConfig['email'],
                $hashedPassword,
                $adminConfig['full_name'],
            ]);
            $messages[] = ['success', '✅ Admin user created successfully.'];
        }

        $adminId = 1;

        // Step 5: Insert default categories
        $catCheck = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($catCheck == 0) {
            $pdo->exec("
                INSERT INTO `categories` (`name`, `slug`, `description`, `color`, `icon`, `sort_order`, `created_by`) VALUES
                ('Database', 'database', 'MySQL, PDO, and database-related snippets', '#ef4444', 'database', 1, {$adminId}),
                ('Authentication', 'authentication', 'Login, registration, and auth snippets', '#f97316', 'shield', 2, {$adminId}),
                ('File Handling', 'file-handling', 'File upload, download, and manipulation', '#eab308', 'file', 3, {$adminId}),
                ('API', 'api', 'REST API and cURL related snippets', '#22c55e', 'globe', 4, {$adminId}),
                ('String Manipulation', 'string-manipulation', 'String processing and formatting', '#3b82f6', 'type', 5, {$adminId}),
                ('Array Operations', 'array-operations', 'Array sorting, filtering, and manipulation', '#8b5cf6', 'list', 6, {$adminId}),
                ('Email', 'email', 'Email sending and template snippets', '#ec4899', 'mail', 7, {$adminId}),
                ('Security', 'security', 'Encryption, sanitization, and security', '#14b8a6', 'lock', 8, {$adminId}),
                ('Utilities', 'utilities', 'Helper functions and utilities', '#6366f1', 'tool', 9, {$adminId}),
                ('OOP Patterns', 'oop-patterns', 'Design patterns and OOP concepts', '#a855f7', 'code', 10, {$adminId})
            ");
            $messages[] = ['success', '✅ 10 default categories inserted.'];
        }

        // Step 6: Insert default tags
        $tagCheck = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
        if ($tagCheck == 0) {
            $pdo->exec("
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
                ('validation', 'validation', '#E11D48')
            ");
            $messages[] = ['success', '✅ 10 default tags inserted.'];
        }

        // Step 7: Insert sample snippets for admin
        $snippetCheck = $pdo->query("SELECT COUNT(*) FROM snippets")->fetchColumn();
        if ($snippetCheck == 0) {
            // Sample snippet 1: PDO Connection
            $pdo->exec("
                INSERT INTO snippets (title, slug, description, code, language, category_id, user_id, is_public, version) VALUES
                ('PDO Database Connection', 'pdo-database-connection', 'Secure PDO database connection with error handling and UTF-8 support.', '<?php\nclass Database {\n    private static ?PDO \$conn = null;\n\n    public static function connect(): PDO {\n        if (self::\$conn === null) {\n            \$dsn = \"mysql:host=localhost;dbname=mydb;charset=utf8mb4\";\n            self::\$conn = new PDO(\$dsn, \"root\", \"\", [\n                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n            ]);\n        }\n        return self::\$conn;\n    }\n}\n\n\$db = Database::connect();\n\$stmt = \$db->prepare(\"SELECT * FROM users WHERE id = ?\");\n\$stmt->execute([1]);\n\$user = \$stmt->fetch();\n?>', 'php', 1, 1, 1, 1)
            ");

            // Sample snippet 2: File Upload
            $pdo->exec("
                INSERT INTO snippets (title, slug, description, code, language, category_id, user_id, is_public, version) VALUES
                ('Secure File Upload', 'secure-file-upload', 'Handle file uploads with type validation and unique naming.', '<?php\nfunction uploadFile(array \$file, string \$dir = \"uploads/\"): ?string {\n    \$allowed = [\"image/jpeg\", \"image/png\", \"image/gif\", \"application/pdf\"];\n    \n    if (\$file[\"error\"] !== UPLOAD_ERR_OK) return null;\n    if (\$file[\"size\"] > 5 * 1024 * 1024) return null;\n    \n    \$finfo = new finfo(FILEINFO_MIME_TYPE);\n    if (!in_array(\$finfo->file(\$file[\"tmp_name\"]), \$allowed)) return null;\n    \n    \$ext = pathinfo(\$file[\"name\"], PATHINFO_EXTENSION);\n    \$name = bin2hex(random_bytes(16)) . \".\" . \$ext;\n    \n    if (!is_dir(\$dir)) mkdir(\$dir, 0755, true);\n    move_uploaded_file(\$file[\"tmp_name\"], \$dir . \$name);\n    \n    return \$name;\n}\n?>', 'php', 3, 1, 1, 1)
            ");

            // Sample snippet 3: API Response Helper
            $pdo->exec("
                INSERT INTO snippets (title, slug, description, code, language, category_id, user_id, is_public, version) VALUES
                ('JSON API Response Helper', 'json-api-response', 'Standardized JSON response function for REST APIs.', '<?php\nfunction apiResponse(mixed \$data = null, string \$message = \"Success\", int \$code = 200): void {\n    http_response_code(\$code);\n    header(\"Content-Type: application/json; charset=utf-8\");\n    \n    echo json_encode([\n        \"success\" => \$code >= 200 && \$code < 300,\n        \"message\" => \$message,\n        \"data\"    => \$data,\n        \"code\"    => \$code,\n    ], JSON_UNESCAPED_UNICODE);\n    \n    exit;\n}\n\n// Usage:\napiResponse([\"user\" => \$user], \"User found\", 200);\napiResponse(null, \"Not found\", 404);\n?>', 'php', 4, 1, 1, 1)
            ");

            // Link snippets to tags
            $pdo->exec("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES (1,1),(1,2),(1,3),(2,1),(2,4),(3,1),(3,9)");

            // Save initial versions
            $pdo->exec("
                INSERT INTO snippet_versions (snippet_id, code, version, change_note, created_by) 
                SELECT id, code, 1, 'Initial version', user_id FROM snippets
            ");

            $messages[] = ['success', '✅ 3 sample snippets with tags inserted.'];
        }

        // Step 8: Create or fix demo member user (password: demo123)
        $memberHash = password_hash('demo123', PASSWORD_BCRYPT, ['cost' => 12]);
        $memberCheck = $pdo->prepare("SELECT id FROM users WHERE username = 'demo'");
        $memberCheck->execute();
        if ($memberCheck->fetch()) {
            // User exists (from database.sql import) — fix the password hash
            $pdo->prepare("UPDATE users SET password = ? WHERE username = 'demo'")
                ->execute([$memberHash]);
            $messages[] = ['success', '✅ Demo member password hash updated (demo / demo123).'];
        } else {
            // Create fresh
            $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'member')")
                ->execute(['demo', 'demo@snippetmanager.com', $memberHash, 'Demo User']);
            $messages[] = ['success', '✅ Demo member user created (demo / demo123).'];
        }

        // Step 9: Create uploads directory
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
            file_put_contents($uploadsDir . '/.gitkeep', '');
            $messages[] = ['success', '✅ Uploads directory created.'];
        }

        $success = true;
        $messages[] = ['success', '🎉 Installation complete! You can now log in.'];

    } catch (PDOException $e) {
        $messages[] = ['error', '❌ Database Error: ' . $e->getMessage()];
    } catch (Exception $e) {
        $messages[] = ['error', '❌ Error: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Code Snippet Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0f23, #1a1a3e); min-height: 100vh; }
        .install-card { max-width: 700px; margin: 40px auto; border-radius: 16px; border: 1px solid rgba(99,102,241,0.2); box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        .install-card .card-header { background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.1)); border-bottom: 1px solid rgba(99,102,241,0.15); text-align: center; padding: 2rem; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="install-card card bg-dark">
            <div class="card-header">
                <i class="bi bi-code-slash fs-1 text-primary d-block mb-2"></i>
                <h2 class="fw-bold mb-1">Code Snippet Manager</h2>
                <p class="text-muted mb-0">Installation Wizard v1.0.0</p>
            </div>

            <div class="card-body p-4">
                <!-- Messages -->
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= $msg[0] === 'success' ? 'success' : 'danger' ?> py-2 small">
                        <?= htmlspecialchars($msg[1]) ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($success): ?>
                    <!-- Success State -->
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h3 class="fw-bold mt-3">Installation Successful!</h3>
                        <p class="text-muted">Your Code Snippet Manager is ready to use.</p>

                        <div class="alert alert-warning mt-3 text-start">
                            <strong>⚠️ Security:</strong>
                            <ul class="mb-0 mt-1">
                                <li><strong>DELETE this install.php file immediately!</strong></li>
                                <li>Change the admin password after first login.</li>
                                <li>Set <code>APP_DEBUG</code> to <code>false</code> in production.</li>
                            </ul>
                        </div>

                        <div class="mt-4 text-start">
                            <p class="fw-semibold text-center mb-3">Login Credentials:</p>
                            <table class="table table-dark table-sm table-bordered">
                                <thead><tr><th>Role</th><th>Username</th><th>Password</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-primary">Admin</span></td>
                                        <td><code><?= htmlspecialchars($adminConfig['username']) ?></code></td>
                                        <td><code><?= htmlspecialchars($adminConfig['password']) ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-secondary">Member</span></td>
                                        <td><code>demo</code></td>
                                        <td><code>demo123</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3 text-start small">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Sample data included:</strong> 10 categories, 10 tags, 3 sample snippets, and a demo member account are pre-loaded so you can explore all features immediately.
                        </div>

                        <a href="login.php" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Installation Form -->
                    <form method="POST">
                        <h5 class="fw-bold mb-3"><i class="bi bi-database me-2"></i>Database Configuration</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label small">Host</label>
                                <input type="text" name="db_host" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($dbConfig['host']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Port</label>
                                <input type="number" name="db_port" class="form-control bg-dark border-secondary text-light"
                                       value="<?= $dbConfig['port'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Database Name</label>
                                <input type="text" name="db_name" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($dbConfig['dbname']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Username</label>
                                <input type="text" name="db_user" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($dbConfig['username']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Password</label>
                                <input type="password" name="db_pass" class="form-control bg-dark border-secondary text-light"
                                       value="" placeholder="Enter DB password">
                            </div>
                        </div>

                        <hr class="border-secondary">

                        <h5 class="fw-bold mb-3"><i class="bi bi-person-gear me-2"></i>Admin Account</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small">Username</label>
                                <input type="text" name="admin_user" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($adminConfig['username']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Full Name</label>
                                <input type="text" name="admin_name" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($adminConfig['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" name="admin_email" class="form-control bg-dark border-secondary text-light"
                                       value="<?= htmlspecialchars($adminConfig['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Password</label>
                                <input type="password" name="admin_pass" class="form-control bg-dark border-secondary text-light"
                                       value="admin123" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-gear me-2"></i>Install Now
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card-footer text-center py-3 border-secondary">
                <small class="text-muted">Code Snippet Manager v1.0.0 — Installation Wizard</small>
            </div>
        </div>
    </div>
</body>
</html>
