<?php
/**
 * HP Personal Expense Tracker — Login API Endpoint
 * api/auth/login.php
 * ============================================================
 * Accepts: POST { email, password }
 * Returns: JSON { status, message, redirect }
 *
 * Security:
 *   - PDO prepared statement (no SQL injection possible)
 *   - password_verify() (constant-time bcrypt check)
 *   - session_regenerate_id() (session fixation prevention)
 *   - Generic error messages (no email enumeration)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

// Session setup (must be before any output in production)
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode',  '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('HP_EXPENSE_SESS');
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Method guard ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(apiError('Method not allowed.')));
}

// ── Input collection & basic validation ──────────────────
$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    die(json_encode(apiError('Email and password are required.')));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(apiError('Please enter a valid email address.')));
}

// ── Database lookup ───────────────────────────────────────
// We use a prepared statement — the email is a bound parameter,
// never concatenated into the SQL string, preventing SQL injection.
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[HP Login] DB error: ' . $e->getMessage());
    die(json_encode(apiError('A server error occurred. Please try again.', 500)));
}

// ── Credential verification ───────────────────────────────
// We use the SAME generic error for both "user not found" and "wrong password"
// to prevent email enumeration (an attacker cannot tell which it is).
if (!$user || !password_verify($password, $user['password_hash'])) {
    die(json_encode(apiError('Invalid email or password. Please try again.')));
}

// ── Session creation ──────────────────────────────────────
// session_regenerate_id(true) creates a new session ID and deletes the old one.
// This prevents Session Fixation attacks.
session_regenerate_id(true);
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['name'];
$_SESSION['user_email'] = $user['email'];

// ── Success response ──────────────────────────────────────
echo json_encode(apiSuccess('Login successful.', [
    'redirect' => BASE_URL . '/dashboard.php',
    'name'     => $user['name'],
]));
