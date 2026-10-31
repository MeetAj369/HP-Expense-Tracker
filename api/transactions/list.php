<?php
/**
 * HP Personal Expense Tracker — List Transactions API
 * api/transactions/list.php
 * ============================================================
 * Accepts: GET { type, category_id, account_id,
 *               date_from, date_to, search,
 *               sort, order, page, per_page }
 * Returns: JSON { status, data: { items[], total, pages, current_page } }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

$userId = currentUserId();
$pdo    = getPDO();

// ── Input ─────────────────────────────────────────────────
$type       = $_GET['type']        ?? '';
$catId      = (int) ($_GET['category_id']  ?? 0) ?: null;
$accId      = (int) ($_GET['account_id']   ?? 0) ?: null;
$dateFrom   = $_GET['date_from']   ?? '';
$dateTo     = $_GET['date_to']     ?? '';
$search     = trim($_GET['search'] ?? '');
$sort       = in_array($_GET['sort'] ?? '', ['transaction_date','amount','title','created_at'])
              ? ($_GET['sort'] ?? 'transaction_date') : 'transaction_date';
$order      = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page       = max(1, (int) ($_GET['page']     ?? 1));
$perPage    = min(100, max(5, (int) ($_GET['per_page'] ?? 20)));

// ── Build WHERE clauses ───────────────────────────────────
$where  = ['t.user_id = ?'];
$params = [$userId];

if (in_array($type, ['expense', 'income', 'transfer'], true)) {
    $where[] = 't.type = ?';
    $params[] = $type;
}
if ($catId) {
    $where[] = 't.category_id = ?';
    $params[] = $catId;
}
if ($accId) {
    $where[] = '(t.account_id = ? OR t.to_account_id = ?)';
    $params[] = $accId;
    $params[] = $accId;
}
if ($dateFrom) {
    $where[] = 't.transaction_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[] = 't.transaction_date <= ?';
    $params[] = $dateTo;
}
if ($search) {
    $where[] = '(t.title LIKE ? OR t.merchant LIKE ? OR t.note LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

try {
    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t $whereSQL");
    $countStmt->execute($params);
    $total   = (int) $countStmt->fetchColumn();
    $pages   = (int) ceil($total / $perPage);
    $offset  = ($page - 1) * $perPage;

    // Fetch page
    $dataStmt = $pdo->prepare(
        "SELECT t.id, t.type, t.title, t.amount, t.transaction_date, t.merchant, t.note,
                c.id AS category_id, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                a.id AS account_id,  a.name AS account_name,
                pm.name AS payment_method_name
         FROM transactions t
         LEFT JOIN categories     c  ON c.id  = t.category_id
         LEFT JOIN accounts       a  ON a.id  = t.account_id
         LEFT JOIN payment_methods pm ON pm.id = t.payment_method_id
         $whereSQL
         ORDER BY t.$sort $order
         LIMIT $perPage OFFSET $offset"
    );
    $dataStmt->execute($params);
    $items = $dataStmt->fetchAll();

    echo json_encode(apiSuccess('OK', [
        'items'        => $items,
        'total'        => $total,
        'pages'        => $pages,
        'current_page' => $page,
        'per_page'     => $perPage,
    ]));

} catch (PDOException $e) {
    error_log('[HP List TX] ' . $e->getMessage());
    echo json_encode(apiError('Failed to load transactions.', 500));
}
