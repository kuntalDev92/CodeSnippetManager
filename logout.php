<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page.
 */
require_once __DIR__ . '/config/app.php';

$auth = new Auth();
$auth->logout();

header('Location: ' . APP_URL . '/login.php');
exit;
