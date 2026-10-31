<?php
/**
 * HP Personal Expense Tracker — One-Click Setup Page
 * setup.php
 * ============================================================
 * IMPORTANT: Delete this file (or restrict it) after first setup.
 *
 * This page runs the schema.sql, seed.sql, and creates the demo user.
 * Access via: http://localhost/HP%20Expense-Tracker/setup.php
 *
 * Steps:
 *   1. Connect to MySQL (no database selected yet)
 *   2. Create database hp_expense_tracker
 *   3. Execute schema.sql (creates all 11 tables)
 *   4. Execute seed.sql  (default categories + payment methods)
 *   5. Create demo user  (hp@demo.com / demo123)
 *   6. Optionally seed demo transactions (realistic 3-month history)
 */

// ── Security guard (disable after first run) ──────────────
// Uncomment to disable: if(true){die('<h2>Setup already completed.</h2>');}

define('BASE_URL', '/HP Expense-Tracker');  // For links
define('CURRENCY', '₹');
define('APP_NAME',    'HP Personal Expense Tracker');
define('APP_VERSION', '2.0');

// ── Config (must match config/db.php) ─────────────────────
$DB_HOST    = 'localhost';
$DB_PORT    = '3306';
$DB_USER    = 'root';
$DB_PASS    = '';
$DB_NAME    = 'hp_expense_tracker';
$DB_CHARSET = 'utf8mb4';

// ── Track setup steps ─────────────────────────────────────
$steps  = [];
$errors = [];
$demoSeeded = false;

// ── Run setup when form is submitted ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seedDemo = !empty($_POST['seed_demo']);

    // Step 1: Connect to MySQL server (without selecting a database)
    try {
        $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;charset=$DB_CHARSET";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $steps[] = ['ok', 'Connected to MySQL server successfully.'];
    } catch (PDOException $e) {
        $errors[] = 'Cannot connect to MySQL: ' . $e->getMessage();
        $steps[]  = ['error', 'MySQL connection failed: ' . $e->getMessage()];
        goto renderPage;
    }

    // Step 2: Create database
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$DB_NAME`");
        $steps[] = ['ok', "Database `$DB_NAME` created (or already exists)."];
    } catch (PDOException $e) {
        $errors[] = 'Database creation failed: ' . $e->getMessage();
        $steps[]  = ['error', 'Database creation failed: ' . $e->getMessage()];
        goto renderPage;
    }

    // Helper: execute a SQL file (split by semicolons, ignoring comments)
    function executeSqlFile(PDO $pdo, string $path): array {
        if (!file_exists($path)) return [false, "File not found: $path"];
        $sql  = file_get_contents($path);
        // Remove single-line comments
        $sql  = preg_replace('/--[^\n]*\n/u', "\n", $sql);
        // Remove block comments
        $sql  = preg_replace('/\/\*.*?\*\//su', '', $sql);
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($stmts as $stmt) {
            if (empty($stmt)) continue;
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore "already exists" errors (table/key already present)
                if (strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    return [false, $e->getMessage()];
                }
            }
        }
        return [true, ''];
    }

    // Step 3: Schema
    [$ok, $msg] = executeSqlFile($pdo, __DIR__ . '/database/schema.sql');
    if ($ok) {
        $steps[] = ['ok', 'All 11 database tables created successfully.'];
    } else {
        $errors[] = "Schema error: $msg";
        $steps[]  = ['error', "Schema failed: $msg"];
        goto renderPage;
    }

    // Step 4: Seed
    [$ok, $msg] = executeSqlFile($pdo, __DIR__ . '/database/seed.sql');
    if ($ok) {
        $steps[] = ['ok', '13 expense categories, 7 income categories, and 10 payment methods inserted.'];
    } else {
        $steps[] = ['warning', "Seed data partial (safe to ignore if already seeded): $msg"];
    }

    // Step 5: Create demo user
    $demoEmail = 'hp@demo.com';
    $demoPass  = password_hash('demo123', PASSWORD_BCRYPT, ['cost' => 12]);
    $demoName  = 'Harsh Patel (Demo)';
    try {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$demoEmail]);
        $existingUser = $chk->fetch();

        if ($existingUser) {
            $demoUserId = $existingUser['id'];
            $steps[] = ['ok', "Demo user already exists (email: $demoEmail)."];
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, currency) VALUES (?, ?, ?, 'INR')"
            );
            $ins->execute([$demoName, $demoEmail, $demoPass]);
            $demoUserId = (int) $pdo->lastInsertId();

            // Create default accounts for demo user
            $accs = [
                ['Cash Wallet',   'cash',   5000.00,  '#10b981', 'bi-cash'],
                ['HDFC Bank',     'bank',   45000.00, '#0ea5e9', 'bi-bank'],
                ['PhonePe',       'wallet', 2500.00,  '#8b5cf6', 'bi-wallet'],
                ['ICICI Credit',  'credit', 0.00,     '#ef4444', 'bi-credit-card-2-front'],
            ];
            $accStmt = $pdo->prepare(
                "INSERT INTO accounts (user_id, name, type, opening_balance, current_balance, color, icon) VALUES (?,?,?,?,?,?,?)"
            );
            $accIds = [];
            foreach ($accs as [$n, $t, $ob, $c, $i]) {
                $accStmt->execute([$demoUserId, $n, $t, $ob, $ob, $c, $i]);
                $accIds[] = (int)$pdo->lastInsertId();
            }
            $steps[] = ['ok', "Demo user created: $demoEmail / demo123"];
        }
    } catch (PDOException $e) {
        $errors[] = 'Demo user creation failed: ' . $e->getMessage();
        $steps[]  = ['error', 'Demo user creation failed: ' . $e->getMessage()];
        goto renderPage;
    }

    // Step 6: Seed demo transactions (optional)
    if ($seedDemo && !empty($demoUserId)) {
        try {
            // Get category IDs
            $catStmt = $pdo->prepare("SELECT id, name, type FROM categories WHERE user_id IS NULL");
            $catStmt->execute();
            $cats = [];
            foreach ($catStmt->fetchAll() as $c) { $cats[$c['name']] = $c['id']; }

            // Get account IDs for demo user
            $accStmt2 = $pdo->prepare("SELECT id, name FROM accounts WHERE user_id = ?");
            $accStmt2->execute([$demoUserId]);
            $demoAccounts = [];
            foreach ($accStmt2->fetchAll() as $a) { $demoAccounts[$a['name']] = $a['id']; }

            // 90 days of demo transactions
            $txStmt = $pdo->prepare(
                "INSERT INTO transactions (user_id, account_id, category_id, payment_method_id, type, title, amount, transaction_date, merchant)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $now = new DateTime();
            $demoTx = [];

            // Salary — 1st of each of last 3 months
            for ($i = 2; $i >= 0; $i--) {
                $d = (new DateTime())->modify("-$i months")->format('Y-m-01');
                $demoTx[] = [$demoUserId, $demoAccounts['HDFC Bank'] ?? null, $cats['Salary'] ?? null, 5, 'income', 'Monthly Salary', 35000.00, $d, 'Employer Ltd'];
            }
            // Freelance income
            $demoTx[] = [$demoUserId, $demoAccounts['HDFC Bank'] ?? null, $cats['Freelance'] ?? null, 6, 'income', 'Web Design Project', 8500.00, (new DateTime('-45 days'))->format('Y-m-d'), 'Client Corp'];
            $demoTx[] = [$demoUserId, $demoAccounts['PhonePe'] ?? null, $cats['Freelance'] ?? null, 2, 'income', 'Logo Design', 2500.00, (new DateTime('-12 days'))->format('Y-m-d'), 'Startup X'];

            // Regular expenses
            $expenseTemplates = [
                [$cats['Food'] ?? null, 'Swiggy Food Order', 350, 2, 'Swiggy'],
                [$cats['Food'] ?? null, 'Lunch at Café', 220, 1, 'Café Coffee Day'],
                [$cats['Transport'] ?? null, 'Uber Cab', 180, 2, 'Uber'],
                [$cats['Transport'] ?? null, 'Petrol', 1800, 1, 'HP Petrol Pump'],
                [$cats['Groceries'] ?? null, 'BigBasket Order', 1200, 2, 'BigBasket'],
                [$cats['Groceries'] ?? null, 'DMart Groceries', 2400, 3, 'DMart'],
                [$cats['Entertainment'] ?? null, 'Netflix Subscription', 649, 4, 'Netflix'],
                [$cats['Entertainment'] ?? null, 'Movie Tickets', 600, 4, 'PVR Cinemas'],
                [$cats['Shopping'] ?? null, 'Amazon Purchase', 1800, 4, 'Amazon'],
                [$cats['Shopping'] ?? null, 'Zara T-Shirt', 999, 4, 'Zara'],
                [$cats['Health'] ?? null, 'Gym Membership', 1500, 3, 'Gold\'s Gym'],
                [$cats['Health'] ?? null, 'Pharmacy', 340, 1, 'Apollo Pharmacy'],
                [$cats['Education'] ?? null, 'Udemy Course', 499, 4, 'Udemy'],
                [$cats['Bills'] ?? null, 'Electricity Bill', 1200, 9, 'MSEB'],
                [$cats['Bills'] ?? null, 'Mobile Recharge', 239, 2, 'Jio'],
                [$cats['Rent'] ?? null, 'Room Rent', 8000, 5, 'Landlord'],
            ];

            // Generate ~50 transactions over past 90 days
            for ($day = 90; $day >= 1; $day -= rand(1,4)) {
                $tpl = $expenseTemplates[array_rand($expenseTemplates)];
                [$catId, $title, $baseAmt, $pmId, $merchant] = $tpl;
                $variance = $baseAmt * (rand(-15, 15) / 100);
                $amount   = round(max(50, $baseAmt + $variance), 0);
                $date     = (new DateTime())->modify("-{$day} days")->format('Y-m-d');
                $accId    = ($pmId == 1 || $pmId == 2)
                            ? ($demoAccounts['PhonePe'] ?? $demoAccounts['Cash Wallet'] ?? null)
                            : ($demoAccounts['HDFC Bank'] ?? null);
                $demoTx[] = [$demoUserId, $accId, $catId, $pmId, 'expense', $title, $amount, $date, $merchant];
            }

            foreach ($demoTx as $tx) {
                $txStmt->execute($tx);
            }

            // Add some budgets for demo user
            $curMonth = date('n');
            $curYear  = date('Y');
            $budgets = [
                [$cats['Food'] ?? null, 5000],
                [$cats['Shopping'] ?? null, 3000],
                [$cats['Entertainment'] ?? null, 2000],
                [$cats['Transport'] ?? null, 4000],
                [null, 25000],  // Overall budget
            ];
            $budgStmt = $pdo->prepare(
                "INSERT IGNORE INTO budgets (user_id, category_id, amount, month, year) VALUES (?,?,?,?,?)"
            );
            foreach ($budgets as [$bCatId, $bAmt]) {
                $budgStmt->execute([$demoUserId, $bCatId, $bAmt, $curMonth, $curYear]);
            }

            // Add savings goals
            $goals = [
                ['Emergency Fund', 100000, 35000, '2027-03-31', 'bi-shield-check', '#10b981'],
                ['New Phone',       40000,  12000, '2026-12-31', 'bi-phone',        '#8b5cf6'],
                ['Goa Trip',        25000,   8500, '2027-01-15', 'bi-airplane',     '#0ea5e9'],
            ];
            $goalStmt = $pdo->prepare(
                "INSERT INTO savings_goals (user_id, name, target_amount, current_amount, target_date, icon, color) VALUES (?,?,?,?,?,?,?)"
            );
            foreach ($goals as [$gName, $target, $current, $targetDate, $icon, $color]) {
                $goalStmt->execute([$demoUserId, $gName, $target, $current, $targetDate, $icon, $color]);
            }

            $steps[] = ['ok', 'Demo data seeded: ~55 transactions, 5 budgets, 3 savings goals.'];
            $demoSeeded = true;

        } catch (PDOException $e) {
            $steps[] = ['warning', 'Demo data seeding failed (safe to retry): ' . $e->getMessage()];
        }
    }

    $steps[] = ['ok', '✅ Setup complete! You can now sign in.'];
}

renderPage:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .setup-card { background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); overflow: hidden; max-width: 620px; width: 100%; }
        .setup-header { background: #1a1d2e; padding: 32px 40px; }
        .setup-logo { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .logo-mark { width: 44px; height: 44px; background: #4c6fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; font-size: 0.875rem; }
        .setup-header h1 { color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0 0 6px; }
        .setup-header p { color: rgba(255,255,255,0.6); margin: 0; font-size: 0.9375rem; }
        .setup-body { padding: 36px 40px; }
        .step-item { display: flex; gap: 12px; margin-bottom: 10px; font-size: 0.875rem; }
        .step-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.6875rem; margin-top: 1px; }
        .step-ok      .step-icon { background: #dcfce7; color: #16a34a; }
        .step-error   .step-icon { background: #fee2e2; color: #dc2626; }
        .step-warning .step-icon { background: #fef9c3; color: #ca8a04; }
        .btn-setup { background: #4c6fff; color: #fff; border: none; padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 700; width: 100%; cursor: pointer; transition: all 0.2s; }
        .btn-setup:hover { background: #3b5bdb; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(76,111,255,0.3); }
        .warn-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 0.875rem; color: #92400e; }
        .form-check-label { font-size: 0.9rem; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <div class="setup-logo">
            <div class="logo-mark">HP</div>
            <div>
                <div style="font-weight:700;color:#fff;line-height:1.2;">HP Personal</div>
                <div style="font-size:0.75rem;color:rgba(255,255,255,0.55);">Expense Tracker — Setup</div>
            </div>
        </div>
        <h1>Database Setup Wizard</h1>
        <p>This will create all tables and seed default data. Run once only.</p>
    </div>

    <div class="setup-body">

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Setup failed.</strong> See details below.
        </div>
        <?php endif; ?>

        <?php if (!empty($steps)): ?>
        <!-- Step results -->
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Setup Log</h6>
            <?php foreach ($steps as [$type, $msg]): ?>
            <div class="step-item step-<?= $type ?>">
                <div class="step-icon">
                    <?php if ($type === 'ok'): ?><i class="bi bi-check"></i>
                    <?php elseif ($type === 'error'): ?><i class="bi bi-x"></i>
                    <?php else: ?><i class="bi bi-exclamation"></i><?php endif; ?>
                </div>
                <div><?= htmlspecialchars($msg) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($errors)): ?>
        <!-- Success — show login link -->
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Setup complete!</strong> Your database is ready.
        </div>
        <hr class="divider">
        <div class="text-center">
            <a href="<?= BASE_URL ?>/login.php" class="btn-setup d-inline-block" style="text-decoration:none;width:auto;padding:12px 32px;">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Go to Login Page
            </a>
            <?php if ($demoSeeded): ?>
            <p class="text-muted mt-3" style="font-size:0.8125rem;">
                Demo credentials: <strong>hp@demo.com</strong> / <strong>demo123</strong>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>

        <!-- Pre-run: setup form -->
        <div class="warn-box">
            <i class="bi bi-shield-exclamation me-2"></i>
            <strong>Security Notice:</strong> Delete or restrict access to
            <code>setup.php</code> after first run to prevent unauthorized re-setup.
        </div>

        <div class="mb-4">
            <h6 class="fw-bold mb-1">This will:</h6>
            <ul class="text-muted mb-0" style="font-size:0.875rem;line-height:2;">
                <li>Create database <code>hp_expense_tracker</code></li>
                <li>Create 11 tables (users, transactions, budgets, goals…)</li>
                <li>Insert default categories &amp; payment methods</li>
                <li>Create demo user: <strong>hp@demo.com</strong> / <strong>demo123</strong></li>
            </ul>
        </div>

        <form method="post">
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="seedDemo" name="seed_demo" value="1" checked>
                <label class="form-check-label" for="seedDemo">
                    <strong>Seed demo data</strong> —
                    <span class="text-muted">~55 realistic transactions, budgets &amp; goals for demo account</span>
                </label>
            </div>
            <button type="submit" class="btn-setup">
                <i class="bi bi-play-circle me-2"></i>
                Run Database Setup
            </button>
        </form>

        <?php endif; ?>

    </div><!-- /.setup-body -->
</div><!-- /.setup-card -->

</body>
</html>
