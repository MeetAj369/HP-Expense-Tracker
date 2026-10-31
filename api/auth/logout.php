<?php
/**
 * HP Personal Expense Tracker — Logout Endpoint
 * api/auth/logout.php
 * ============================================================
 * Destroys the session completely and redirects to login.
 * Must be accessed via a direct link or POST — never AJAX redirect.
 */
require_once __DIR__ . '/../../config/db.php';

// Same session configuration as used on login
ini_set('session.cookie_httponly', '1');
session_name('HP_EXPENSE_SESS');
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Clear all session data
$_SESSION = [];

// 2. Destroy the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroy the server-side session
session_destroy();

// 4. Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit;
