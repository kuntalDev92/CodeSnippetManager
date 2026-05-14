<?php
/**
 * ============================================================================
 * Application Configuration
 * ============================================================================
 * 
 * Central configuration file for the Code Snippet Manager application.
 * Contains all global settings, paths, and constants.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

// ============================================================================
// Base Path Definition
// ============================================================================
define('BASE_PATH', dirname(__DIR__) . '/');

// ============================================================================
// Application Settings
// ============================================================================
define('APP_NAME', 'Code Snippet Manager');
define('APP_VERSION', '1.0.0');

/**
 * Auto-detect APP_URL based on the current request
 * This makes the app truly plug-and-play without manual URL configuration
 * 
 * NOTE: If auto-detection fails, uncomment and set manually:
 * define('APP_URL', 'http://localhost/snippet-manager');
 */
if (!defined('APP_URL')) {
    // Determine protocol
    $protocol = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $protocol = 'https';
    }
    
    // Get host
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // Get the base path of the application
    // We use BASE_PATH to find where the app is installed relative to document root
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $basePath = realpath(BASE_PATH);
    
    if ($docRoot && $basePath && strpos($basePath, $docRoot) === 0) {
        // App is in a subdirectory of document root
        $appPath = str_replace($docRoot, '', $basePath);
        $appPath = str_replace('\\', '/', $appPath); // Windows compatibility
        $appPath = rtrim($appPath, '/');
    } else {
        // Fallback: try to detect from script name
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        // Handle AJAX requests from ajax/ subdirectory
        if (strpos($scriptPath, '/ajax') !== false) {
            $scriptPath = dirname($scriptPath);
        }
        $appPath = rtrim($scriptPath, '/');
        if ($appPath === '/' || $appPath === '\\') {
            $appPath = '';
        }
    }
    
    define('APP_URL', $protocol . '://' . $host . $appPath);
}

// Set to false on live/production server for security
// When false: errors are logged but not displayed to users
define('APP_DEBUG', true);

// ============================================================================
// Session Configuration
// ============================================================================
define('SESSION_NAME', 'snippet_manager_session');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// ============================================================================
// Pagination Settings
// ============================================================================
define('ITEMS_PER_PAGE', 12);

// ============================================================================
// Upload Settings
// ============================================================================
define('UPLOAD_DIR', BASE_PATH . 'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_AVATAR_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ============================================================================
// Supported Programming Languages
// ============================================================================
define('SUPPORTED_LANGUAGES', [
    'php'        => 'PHP',
    'javascript' => 'JavaScript',
    'html'       => 'HTML',
    'css'        => 'CSS',
    'sql'        => 'SQL',
    'python'     => 'Python',
    'bash'       => 'Bash/Shell',
    'json'       => 'JSON',
    'xml'        => 'XML',
    'yaml'       => 'YAML',
    'markdown'   => 'Markdown',
    'typescript' => 'TypeScript',
    'java'       => 'Java',
    'csharp'     => 'C#',
    'ruby'       => 'Ruby',
    'go'         => 'Go',
    'rust'       => 'Rust',
    'plaintext'  => 'Plain Text',
]);

// ============================================================================
// Error Reporting (based on debug mode)
// ============================================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ============================================================================
// Timezone
// ============================================================================
// Set to your local timezone. Common values:
//   'Asia/Kolkata'       (India IST +5:30)
//   'America/New_York'   (US Eastern)
//   'Europe/London'      (UK GMT/BST)
//   'Asia/Dubai'         (UAE GST +4)
//   'UTC'                (Coordinated Universal Time)
// Full list: https://www.php.net/manual/en/timezones.php
// ============================================================================
define('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(APP_TIMEZONE);

// ============================================================================
// Auto-load required files
// ============================================================================
require_once BASE_PATH . 'config/database.php';
require_once BASE_PATH . 'classes/Session.php';
require_once BASE_PATH . 'classes/Auth.php';
require_once BASE_PATH . 'classes/User.php';
require_once BASE_PATH . 'classes/Snippet.php';
require_once BASE_PATH . 'classes/Category.php';
require_once BASE_PATH . 'classes/Tag.php';
require_once BASE_PATH . 'classes/Favorite.php';
require_once BASE_PATH . 'classes/Share.php';
require_once BASE_PATH . 'classes/ActivityLog.php';
require_once BASE_PATH . 'helpers/functions.php';

// ============================================================================
// Initialize Session
// ============================================================================
Session::start();
