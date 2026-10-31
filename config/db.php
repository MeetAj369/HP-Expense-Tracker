<?php
/**
 * HP Personal Expense Tracker — Database Configuration
 * =====================================================
 * This file defines all application-wide constants and
 * the PDO database connection factory.
 *
 * SECURITY: Never expose this file to the browser.
 *           Protected by config/.htaccess
 */

// ── Application Constants ─────────────────────────────────
define('APP_NAME',    'HP Personal Expense Tracker');
define('APP_SHORT',   'HP');
define('APP_VERSION', '2.0');
define('BASE_URL',    '/HP Expense-Tracker');   // Root URL — adjust if deployed elsewhere
define('CURRENCY',    '₹');
define('CURRENCY_CODE', 'INR');
define('UPLOADS_DIR', __DIR__ . '/../uploads/receipts/');

// ── Database Connection Constants ─────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'hp_expense_tracker');
define('DB_USER',    'root');
define('DB_PASS',    '');                       // Empty by default on WAMP
define('DB_CHARSET', 'utf8mb4');

/**
 * getPDO() — Returns a PDO instance (Singleton pattern).
 *
 * Using a singleton means we only create ONE database connection
 * per request, which is efficient and avoids connection leaks.
 *
 * PDO settings:
 *  - ERRMODE_EXCEPTION  → Throws exceptions on SQL errors (caught below)
 *  - FETCH_ASSOC        → Returns rows as named arrays, e.g. $row['amount']
 *  - EMULATE_PREPARES   → FALSE means real server-side prepared statements
 *                         (prevents all SQL injection attacks)
 */
function getPDO(): PDO
{
    static $pdo = null;   // Stays alive for the lifetime of this request

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the real error server-side; never expose it to the browser
            error_log('[HP Tracker] DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            // Check if this is an AJAX request
            if (
                isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                die(json_encode([
                    'status'  => 'error',
                    'message' => 'A database error occurred. Please try again later.'
                ]));
            }
            die('<h3 style="font-family:sans-serif;color:#c00;padding:2rem">Database connection failed. Check config/db.php settings.</h3>');
        }
    }

    return $pdo;
}

/**
 * Formats a float as Indian Rupee string.
 * Example: 27450.00  →  "₹27,450.00"
 *
 * Uses number_format which produces standard Western format.
 * For true Indian lakh/crore notation, use the JS formatter on frontend.
 */
function formatCurrency(float $amount): string
{
    return CURRENCY . number_format($amount, 2);
}

/**
 * Sanitizes a string for safe HTML output.
 * Always use this before echoing user-supplied text.
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Returns a standardized JSON success response array.
 */
function apiSuccess(string $message = 'OK', array $data = []): array
{
    return ['status' => 'success', 'message' => $message, 'data' => $data];
}

/**
 * Returns a standardized JSON error response array.
 */
function apiError(string $message, int $httpCode = 400): array
{
    http_response_code($httpCode);
    return ['status' => 'error', 'message' => $message];
}
