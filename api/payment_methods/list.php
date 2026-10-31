<?php
/**
 * HP Personal Expense Tracker — Payment Methods List API
 * api/payment_methods/list.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

try {
    $stmt = getPDO()->prepare(
        "SELECT id, name, icon, is_default
         FROM payment_methods
         WHERE user_id = ? OR user_id IS NULL
         ORDER BY is_default DESC, name ASC"
    );
    $stmt->execute([currentUserId()]);
    echo json_encode(apiSuccess('OK', $stmt->fetchAll()));
} catch (PDOException $e) {
    error_log('[HP Payment Methods] ' . $e->getMessage());
    echo json_encode(apiError('Failed to load payment methods.', 500));
}
