<?php
/**
 * HP Personal Expense Tracker — Accounts List API
 * api/accounts/list.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

try {
    $stmt = getPDO()->prepare(
        "SELECT id, name, type, current_balance, opening_balance, color, icon, is_active
         FROM accounts
         WHERE user_id = ? AND is_active = 1
         ORDER BY name ASC"
    );
    $stmt->execute([currentUserId()]);
    echo json_encode(apiSuccess('OK', $stmt->fetchAll()));
} catch (PDOException $e) {
    error_log('[HP Accounts] ' . $e->getMessage());
    echo json_encode(apiError('Failed to load accounts.', 500));
}
