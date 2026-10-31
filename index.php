<?php
/**
 * HP Personal Expense Tracker — Entry Point
 * index.php
 * ============================================================
 * Smart redirect:
 *   Logged in  → dashboard.php
 *   Logged out → login.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
