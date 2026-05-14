<?php
/**
 * ============================================================================
 * Favorites Page
 * ============================================================================
 * @package  CodeSnippetManager
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

// Redirect to main listing with favorites filter
header('Location: ' . APP_URL . '/index.php?view=favorites');
exit;
