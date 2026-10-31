<?php
/**
 * HP Personal Expense Tracker — Authentication & Session Guard
 * ============================================================
 * Include this on EVERY protected page and API endpoint.
 *
 * IMPORTANT: config/db.php must be included before this file,
 *            because we use the BASE_URL constant for redirects.
 *
 * Security hardening applied:
 *  - HttpOnly cookie: JS cannot read the session cookie
 *  - Strict mode: Server rejects externally supplied session IDs
 *  - SameSite=Lax: Mitigates CSRF on cross-site navigations
 *  - Custom session name: avoids fingerprinting the stack
 */

// ── Secure Session Settings (must be set BEFORE session_start) ──
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode',  '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('HP_EXPENSE_SESS');   // Custom name, not the default PHPSESSID

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * requireAuth() — Terminates execution if no valid session exists.
 *
 * Behaviour differs based on request type:
 *  - AJAX request  → Returns HTTP 401 JSON  (JS can handle it gracefully)
 *  - Regular page  → Redirects to login.php (standard web behaviour)
 */
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            die(json_encode([
                'status'   => 'error',
                'message'  => 'Session expired. Please log in again.',
                'redirect' => BASE_URL . '/login.php'
            ]));
        }

        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * isLoggedIn() — Returns true if the user has an active session.
 * Used on login/register pages to redirect away if already logged in.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * currentUserId() — The ONLY authoritative way to get the current user.
 *
 * CRITICAL SECURITY RULE:
 *   NEVER trust $_POST['user_id'] or $_GET['user_id'] from the browser.
 *   Always use this function. It reads from the server-side session only.
 */
function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * currentUserName() — Returns the logged-in user's display name.
 */
function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * currentUserFirstName() — Returns just the first word of the user's name.
 * Used in greetings: "Good morning, Harsh" instead of "Harsh Patel".
 */
function currentUserFirstName(): string
{
    $name = currentUserName();
    return explode(' ', trim($name))[0];
}

/**
 * csrfToken() — Returns (or creates) the CSRF token for this session.
 *
 * CSRF (Cross-Site Request Forgery) protection:
 *   A unique random token is stored in the session. Every state-changing
 *   form and AJAX request must include this token. The server verifies it
 *   before processing. A forged request from another site cannot know the token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * verifyCsrf() — Validates the submitted CSRF token against the session.
 *
 * Uses hash_equals() to prevent timing attacks.
 * Returns false if either token is empty or they don't match.
 */
function verifyCsrf(string $submittedToken): bool
{
    return !empty($_SESSION['csrf_token']) &&
           !empty($submittedToken) &&
           hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * getGreeting() — Returns a time-appropriate greeting string.
 * Times are based on server's local clock (IST for this project).
 */
function getGreeting(): string
{
    $hour = (int) date('H');
    if ($hour >= 5  && $hour < 12) return 'Good morning';
    if ($hour >= 12 && $hour < 17) return 'Good afternoon';
    if ($hour >= 17 && $hour < 21) return 'Good evening';
    return 'Good night';
}
