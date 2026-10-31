<?php
/**
 * HP Personal Expense Tracker — Categories List API
 * api/categories/list.php
 * ============================================================
 * Accepts: GET { type: 'expense'|'income'|'both'|'' }
 * Returns: JSON list of default + user-created categories
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

$userId = currentUserId();
$type   = $_GET['type'] ?? '';

$validTypes = ['expense', 'income', 'both'];
$where  = ['(c.user_id = ? OR c.user_id IS NULL)'];
$params = [$userId];

if (in_array($type, $validTypes, true)) {
    $where[] = "(c.type = ? OR c.type = 'both')";
    $params[] = $type;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

try {
    $stmt = getPDO()->prepare(
        "SELECT id, name, type, icon, color, is_default, user_id
         FROM categories c
         $whereSQL
         ORDER BY is_default DESC, name ASC"
    );
    $stmt->execute($params);
    echo json_encode(apiSuccess('OK', $stmt->fetchAll()));
} catch (PDOException $e) {
    error_log('[HP Categories] ' . $e->getMessage());
    echo json_encode(apiError('Failed to load categories.', 500));
}
