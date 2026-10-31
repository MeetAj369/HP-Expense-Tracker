<?php
/**
 * HP Personal Expense Tracker — Register API Endpoint
 * api/auth/register.php
 * ============================================================
 * Accepts: POST { name, email, password, confirm_password }
 * Returns: JSON { status, message, redirect }
 *
 * On success:
 *   1. Creates user record with bcrypt password hash
 *   2. Creates 3 default accounts (Cash, Bank, Wallet)
 *   3. Starts session immediately (user is logged in after registration)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

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

// ── Collect input ─────────────────────────────────────────
$name     = trim($_POST['name']             ?? '');
$email    = trim($_POST['email']            ?? '');
$password =      $_POST['password']         ?? '';
$confirm  =      $_POST['confirm_password'] ?? '';

// ── Server-side validation ────────────────────────────────
// (Always validate on the server even if the frontend already validates)
$errors = [];

if (strlen($name) < 2) {
    $errors[] = 'Full name must be at least 2 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    die(json_encode(apiError(implode(' ', $errors))));
}

// ── Database operations ───────────────────────────────────
try {
    $pdo = getPDO();

    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die(json_encode(apiError('This email address is already registered. Please sign in.')));
    }

    // Hash the password with bcrypt (PHP's default, cost factor 12)
    // password_hash() is salted automatically — never store plaintext
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Begin transaction — ensures user + accounts are created atomically
    $pdo->beginTransaction();

    // Insert user
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, currency) VALUES (?, ?, ?, 'INR')"
    );
    $stmt->execute([$name, $email, $hash]);
    $userId = (int)$pdo->lastInsertId();

    // Create 3 default accounts for the new user
    // These are starting accounts — user can add/edit/delete later.
    $accounts = [
        ['Cash Wallet',   'cash',   0.00,      '#10b981', 'bi-cash'],
        ['Bank Account',  'bank',   0.00,      '#0ea5e9', 'bi-bank'],
        ['Mobile Wallet', 'wallet', 0.00,      '#8b5cf6', 'bi-wallet'],
    ];

    $accStmt = $pdo->prepare(
        "INSERT INTO accounts (user_id, name, type, opening_balance, current_balance, color, icon)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($accounts as [$accName, $accType, $opening, $color, $icon]) {
        $accStmt->execute([$userId, $accName, $accType, $opening, $opening, $color, $icon]);
    }

    $pdo->commit();

    // ── Start session after successful registration ────────
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;

    echo json_encode(apiSuccess('Account created successfully! Welcome to HP!', [
        'redirect' => BASE_URL . '/dashboard.php',
        'name'     => $name,
    ]));

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[HP Register] DB error: ' . $e->getMessage());
    die(json_encode(apiError('Registration failed due to a server error. Please try again.', 500)));
}
