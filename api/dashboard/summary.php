<?php
/**
 * HP Personal Expense Tracker — Dashboard Summary API
 * api/dashboard/summary.php
 * ============================================================
 * Accepts: GET/POST { range: 'this_month'|'last_month'|'last_3_months'|'this_year'|'custom',
 *                     date_from: 'YYYY-MM-DD', date_to: 'YYYY-MM-DD' }
 * Returns: JSON with all dashboard KPIs, recent transactions, category breakdown, budgets, goals.
 *
 * CRITICAL: All queries are scoped to currentUserId() — the server session.
 *           Never uses user_id from $_GET or $_POST.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAuth();

$userId = currentUserId();
$pdo    = getPDO();

// ── Date range parsing ────────────────────────────────────
$range    = $_REQUEST['range']     ?? 'this_month';
$dateFrom = $_REQUEST['date_from'] ?? '';
$dateTo   = $_REQUEST['date_to']   ?? '';

$now   = new DateTime();
$year  = (int) $now->format('Y');
$month = (int) $now->format('m');

switch ($range) {
    case 'last_month':
        $d = new DateTime('first day of last month');
        $dateFrom = $d->format('Y-m-01');
        $dateTo   = $d->format('Y-m-t');
        break;
    case 'last_3_months':
        $dateFrom = (new DateTime('-2 months'))->format('Y-m-01');
        $dateTo   = $now->format('Y-m-t');
        break;
    case 'this_year':
        $dateFrom = "$year-01-01";
        $dateTo   = "$year-12-31";
        break;
    case 'custom':
        // Use provided dates; fallback to this month if invalid
        if (!$dateFrom || !$dateTo) {
            $dateFrom = "$year-$month-01";
            $dateTo   = $now->format('Y-m-t');
        }
        break;
    default: // this_month
        $dateFrom = "$year-$month-01";
        $dateTo   = $now->format('Y-m-t');
        $range    = 'this_month';
        break;
}

// Previous period (same duration, one period back) for comparison %
$from = new DateTime($dateFrom);
$to   = new DateTime($dateTo);
$duration   = $from->diff($to)->days + 1;
$prevDateTo   = (clone $from)->modify('-1 day')->format('Y-m-d');
$prevDateFrom = (clone $from)->modify("-{$duration} days")->format('Y-m-d');

// ── Helper: single aggregate query ───────────────────────
function aggQuery(PDO $pdo, int $userId, string $type, string $from, string $to): float
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0)
         FROM transactions
         WHERE user_id = ?
           AND type = ?
           AND transaction_date BETWEEN ? AND ?"
    );
    $stmt->execute([$userId, $type, $from, $to]);
    return (float) $stmt->fetchColumn();
}

try {
    // ── 1. Current period totals ──────────────────────────
    $totalIncome  = aggQuery($pdo, $userId, 'income',  $dateFrom, $dateTo);
    $totalExpense = aggQuery($pdo, $userId, 'expense', $dateFrom, $dateTo);
    $balance      = $totalIncome - $totalExpense;
    $savingsRate  = $totalIncome > 0
                    ? round(($balance / $totalIncome) * 100, 1)
                    : 0;

    // ── 2. Previous period totals (for % change) ──────────
    $prevIncome  = aggQuery($pdo, $userId, 'income',  $prevDateFrom, $prevDateTo);
    $prevExpense = aggQuery($pdo, $userId, 'expense', $prevDateFrom, $prevDateTo);

    $incomeChange  = $prevIncome  > 0 ? round((($totalIncome  - $prevIncome)  / $prevIncome)  * 100, 1) : null;
    $expenseChange = $prevExpense > 0 ? round((($totalExpense - $prevExpense) / $prevExpense) * 100, 1) : null;

    // ── 3. Recent transactions (last 8) ───────────────────
    $stmt = $pdo->prepare(
        "SELECT t.id, t.type, t.title, t.amount, t.transaction_date, t.merchant,
                c.name  AS category_name,  c.icon  AS category_icon,  c.color AS category_color,
                a.name  AS account_name
         FROM transactions t
         LEFT JOIN categories     c ON c.id = t.category_id
         LEFT JOIN accounts       a ON a.id = t.account_id
         WHERE t.user_id = ?
         ORDER BY t.transaction_date DESC, t.created_at DESC
         LIMIT 8"
    );
    $stmt->execute([$userId]);
    $recentTx = $stmt->fetchAll();

    // ── 4. Expense by category (current period) ───────────
    $stmt = $pdo->prepare(
        "SELECT c.name, c.icon, c.color,
                COALESCE(SUM(t.amount), 0) AS total
         FROM transactions t
         JOIN categories c ON c.id = t.category_id
         WHERE t.user_id = ?
           AND t.type = 'expense'
           AND t.transaction_date BETWEEN ? AND ?
         GROUP BY c.id, c.name, c.icon, c.color
         ORDER BY total DESC
         LIMIT 6"
    );
    $stmt->execute([$userId, $dateFrom, $dateTo]);
    $categoryData = $stmt->fetchAll();

    // ── 5. Monthly income vs expense (last 6 months) ──────
    $stmt = $pdo->prepare(
        "SELECT
             DATE_FORMAT(transaction_date, '%Y-%m') AS month_key,
             DATE_FORMAT(transaction_date, '%b %Y') AS month_label,
             type,
             COALESCE(SUM(amount), 0) AS total
         FROM transactions
         WHERE user_id = ?
           AND type IN ('income','expense')
           AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY month_key, month_label, type
         ORDER BY month_key ASC"
    );
    $stmt->execute([$userId]);
    $monthlyRaw = $stmt->fetchAll();

    // Pivot monthly data into { months, income, expense } arrays
    $monthlyMap = [];
    foreach ($monthlyRaw as $row) {
        $k = $row['month_key'];
        if (!isset($monthlyMap[$k])) {
            $monthlyMap[$k] = ['label' => $row['month_label'], 'income' => 0, 'expense' => 0];
        }
        $monthlyMap[$k][$row['type']] = (float)$row['total'];
    }
    $monthlyChartData = array_values($monthlyMap);

    // ── 6. Budget status (current month) ─────────────────
    $curMonth = date('n');
    $curYear  = date('Y');
    $stmt = $pdo->prepare(
        "SELECT b.id, b.amount AS budget_limit,
                c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                COALESCE(
                    (SELECT SUM(t.amount)
                     FROM transactions t
                     WHERE t.user_id = b.user_id
                       AND t.category_id = b.category_id
                       AND t.type = 'expense'
                       AND MONTH(t.transaction_date) = b.month
                       AND YEAR(t.transaction_date)  = b.year),
                    0
                ) AS spent
         FROM budgets b
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.user_id = ?
           AND b.month = ?
           AND b.year  = ?
         ORDER BY (spent / b.amount) DESC
         LIMIT 5"
    );
    $stmt->execute([$userId, $curMonth, $curYear]);
    $budgets = $stmt->fetchAll();

    // ── 7. Savings goals ─────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT id, name, target_amount, current_amount, target_date, icon, color
         FROM savings_goals
         WHERE user_id = ?
         ORDER BY (current_amount / target_amount) DESC
         LIMIT 4"
    );
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();

    // ── 8. Transaction count ──────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?"
    );
    $stmt->execute([$userId, $dateFrom, $dateTo]);
    $txCount = (int)$stmt->fetchColumn();

    // ── 9. Top payment methods ────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT pm.name, pm.icon,
                COUNT(*) AS tx_count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type IN ('income','expense') AND transaction_date BETWEEN ? AND ?), 1) AS pct
         FROM transactions t
         JOIN payment_methods pm ON pm.id = t.payment_method_id
         WHERE t.user_id = ?
           AND t.type IN ('income','expense')
           AND t.transaction_date BETWEEN ? AND ?
         GROUP BY pm.id, pm.name, pm.icon
         ORDER BY tx_count DESC
         LIMIT 4"
    );
    $stmt->execute([$userId, $dateFrom, $dateTo, $userId, $dateFrom, $dateTo]);
    $paymentMethods = $stmt->fetchAll();

    // ── Compose response ──────────────────────────────────
    echo json_encode(apiSuccess('Summary loaded.', [
        'date_from'    => $dateFrom,
        'date_to'      => $dateTo,
        'range'        => $range,
        'total_income' => round($totalIncome,  2),
        'total_expense'=> round($totalExpense, 2),
        'balance'      => round($balance,      2),
        'savings_rate' => $savingsRate,
        'income_change'  => $incomeChange,
        'expense_change' => $expenseChange,
        'tx_count'       => $txCount,
        'recent_transactions' => $recentTx,
        'category_chart'      => $categoryData,
        'monthly_chart'       => $monthlyChartData,
        'budgets'             => $budgets,
        'goals'               => $goals,
        'payment_methods'     => $paymentMethods,
    ]));

} catch (PDOException $e) {
    error_log('[HP Dashboard] DB error: ' . $e->getMessage());
    die(json_encode(apiError('Failed to load dashboard data.', 500)));
}
