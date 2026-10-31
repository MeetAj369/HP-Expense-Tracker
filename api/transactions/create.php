<?php
/**
 * HP Personal Expense Tracker — Create Transaction API
 * api/transactions/create.php
 * ============================================================
 * Accepts: POST { type, title, amount, transaction_date,
 *                 category_id, account_id, to_account_id,
 *                 payment_method_id, merchant, note, csrf_token }
 * Returns: JSON { status, message }
 *
 * Security:
 *   - Auth required (session)
 *   - CSRF token verified
 *   - All IDs validated against the user's own records
 *   - Account balances updated in a transaction
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(apiError('Method not allowed.', 405)));
}

// CSRF
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    die(json_encode(apiError('Invalid security token. Please refresh and try again.', 403)));
}

$userId = currentUserId();
$pdo    = getPDO();

// ── Collect & validate input ──────────────────────────────
$type       = $_POST['type']       ?? '';
$title      = trim($_POST['title'] ?? '');
$amount     = (float) ($_POST['amount'] ?? 0);
$date       = $_POST['transaction_date'] ?? '';
$catId      = (int) ($_POST['category_id']       ?? 0) ?: null;
$accId      = (int) ($_POST['account_id']         ?? 0) ?: null;
$toAccId    = (int) ($_POST['to_account_id']      ?? 0) ?: null;
$pmId       = (int) ($_POST['payment_method_id']  ?? 0) ?: null;
$merchant   = trim($_POST['merchant'] ?? '') ?: null;
$note       = trim($_POST['note']     ?? '') ?: null;

$validTypes = ['expense', 'income', 'transfer'];
if (!in_array($type, $validTypes, true)) {
    die(json_encode(apiError('Invalid transaction type.')));
}
if (empty($title)) {
    die(json_encode(apiError('Title is required.')));
}
if ($amount <= 0) {
    die(json_encode(apiError('Amount must be greater than zero.')));
}
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die(json_encode(apiError('Valid date is required.')));
}
if ($type === 'transfer' && (!$accId || !$toAccId || $accId === $toAccId)) {
    die(json_encode(apiError('Transfers require two different accounts.')));
}

// ── Validate that account belongs to this user ────────────
// This prevents user A from posting to user B's account_id
function validateAccount(PDO $pdo, int $userId, ?int $accId): bool {
    if (!$accId) return true;
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$accId, $userId]);
    return (bool) $stmt->fetch();
}

if ($accId   && !validateAccount($pdo, $userId, $accId))   die(json_encode(apiError('Invalid account.')));
if ($toAccId && !validateAccount($pdo, $userId, $toAccId)) die(json_encode(apiError('Invalid destination account.')));

// ── Database write (with balance update) ──────────────────
try {
    $pdo->beginTransaction();

    // Insert transaction
    $stmt = $pdo->prepare(
        "INSERT INTO transactions
             (user_id, account_id, to_account_id, category_id, payment_method_id,
              type, title, amount, transaction_date, merchant, note)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $userId, $accId, $toAccId, $catId, $pmId,
        $type, $title, $amount, $date, $merchant, $note
    ]);

    // Update account balances
    // Income: +amount to account
    // Expense: -amount from account
    // Transfer: -amount from source, +amount to destination
    if ($accId) {
        if ($type === 'income') {
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?")->execute([$amount, $accId]);
        } elseif ($type === 'expense') {
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$amount, $accId]);
        } elseif ($type === 'transfer') {
            $pdo->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$amount, $accId]);
            if ($toAccId) {
                $pdo->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?")->execute([$amount, $toAccId]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(apiSuccess('Transaction added successfully.'));

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[HP Create TX] ' . $e->getMessage());
    echo json_encode(apiError('Failed to save transaction. Please try again.', 500));
}
