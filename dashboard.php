<?php
/**
 * HP Personal Expense Tracker — Dashboard
 * dashboard.php
 * ============================================================
 * The main financial overview page.
 * All numbers are loaded dynamically via AJAX from
 * api/dashboard/summary.php — nothing is hardcoded.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
requireAuth();

$current_page   = 'dashboard';
$page_title     = 'Dashboard';
$page_nav_title = 'Dashboard';
$include_charts = true;
$page_scripts   = ['assets/js/dashboard.js'];

$firstName  = currentUserFirstName();
$greeting   = getGreeting();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── App Layout ─────────────────────────────────────── -->
<div class="app-layout">

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<!-- ── Main Wrapper ───────────────────────────────────── -->
<div class="main-wrapper">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════
     PAGE CONTENT
     ══════════════════════════════════════════════════════ -->
<div class="page-content">

    <!-- ── Page Header ──────────────────────────────────── -->
    <div class="page-header stagger-children">
        <div>
            <h1 class="greeting-title" id="greetingTitle">
                <?= e($greeting) ?>, <?= e($firstName) ?> 👋
            </h1>
            <p class="greeting-date"><?= date('l, F j, Y') ?></p>
        </div>

        <div class="page-header-actions d-flex flex-wrap gap-2">
            <!-- Date range selector -->
            <div class="dropdown date-range-selector">
                <button class="btn btn-outline-primary dropdown-toggle" type="button"
                        id="dateRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-calendar3 me-1"></i>
                    <span id="dateRangeLabel">This Month</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dateRangeDropdown">
                    <li><a class="dropdown-item date-range-item active" href="#" data-range="this_month">
                        <i class="bi bi-calendar me-2"></i>This Month</a></li>
                    <li><a class="dropdown-item date-range-item" href="#" data-range="last_month">
                        <i class="bi bi-calendar-minus me-2"></i>Last Month</a></li>
                    <li><a class="dropdown-item date-range-item" href="#" data-range="last_3_months">
                        <i class="bi bi-calendar-range me-2"></i>Last 3 Months</a></li>
                    <li><a class="dropdown-item date-range-item" href="#" data-range="this_year">
                        <i class="bi bi-calendar2 me-2"></i>This Year</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item date-range-item" href="#" data-range="custom">
                        <i class="bi bi-calendar4-range me-2"></i>Custom Range</a></li>
                </ul>
            </div>

            <!-- Add Transaction -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="bi bi-plus-lg me-1"></i> Add Transaction
            </button>
        </div>
    </div>

    <!-- ── Summary Cards ────────────────────────────────── -->
    <div class="row g-3 mb-4 stagger-children" id="summaryCardsRow">

        <!-- Total Balance -->
        <div class="col-6 col-xl-3">
            <div class="summary-card summary-card--balance">
                <div class="summary-card__icon-wrap">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="summary-card__content">
                    <p class="summary-card__label">Total Balance</p>
                    <h3 class="summary-card__amount" id="kpiBalance">
                        <span class="skeleton d-inline-block" style="width:100px;height:28px;"></span>
                    </h3>
                    <p class="summary-card__trend" id="kpiBalanceTrend">
                        <span class="trend-neutral text-muted" style="font-size:0.75rem;">Loading…</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Income -->
        <div class="col-6 col-xl-3">
            <div class="summary-card summary-card--income">
                <div class="summary-card__icon-wrap">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="summary-card__content">
                    <p class="summary-card__label">Total Income</p>
                    <h3 class="summary-card__amount" id="kpiIncome">
                        <span class="skeleton d-inline-block" style="width:100px;height:28px;"></span>
                    </h3>
                    <p class="summary-card__trend" id="kpiIncomeTrend">
                        <span class="trend-neutral text-muted" style="font-size:0.75rem;">Loading…</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="col-6 col-xl-3">
            <div class="summary-card summary-card--expense">
                <div class="summary-card__icon-wrap">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="summary-card__content">
                    <p class="summary-card__label">Total Expenses</p>
                    <h3 class="summary-card__amount" id="kpiExpense">
                        <span class="skeleton d-inline-block" style="width:100px;height:28px;"></span>
                    </h3>
                    <p class="summary-card__trend" id="kpiExpenseTrend">
                        <span class="trend-neutral text-muted" style="font-size:0.75rem;">Loading…</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Savings Rate -->
        <div class="col-6 col-xl-3">
            <div class="summary-card summary-card--savings">
                <div class="summary-card__icon-wrap">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <div class="summary-card__content">
                    <p class="summary-card__label">Savings Rate</p>
                    <h3 class="summary-card__amount" id="kpiSavings">
                        <span class="skeleton d-inline-block" style="width:80px;height:28px;"></span>
                    </h3>
                    <p class="summary-card__trend" id="kpiSavingsTrend">
                        <span class="trend-neutral text-muted" style="font-size:0.75rem;">Loading…</span>
                    </p>
                </div>
            </div>
        </div>
    </div><!-- /summary cards -->

    <!-- ── Charts Row ───────────────────────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Spending by Category (Doughnut) -->
        <div class="col-lg-4">
            <div class="hp-card h-100">
                <div class="hp-card-header">
                    <div>
                        <h6 class="hp-card-title">Spending by Category</h6>
                        <div class="hp-card-subtitle" id="categoryChartSubtitle">Loading…</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrap chart-wrap--md">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div id="categoryLegend" class="chart-legend mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Income vs Expenses (Bar) -->
        <div class="col-lg-8">
            <div class="hp-card h-100">
                <div class="hp-card-header">
                    <div>
                        <h6 class="hp-card-title">Income vs Expenses</h6>
                        <div class="hp-card-subtitle">Last 6 months</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrap chart-wrap--lg">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Lower Row ────────────────────────────────────── -->
    <div class="row g-3">

        <!-- Recent Transactions -->
        <div class="col-lg-7">
            <div class="hp-card">
                <div class="hp-card-header">
                    <h6 class="hp-card-title">Recent Transactions</h6>
                    <a href="<?= BASE_URL ?>/transactions.php"
                       class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div id="recentTransactionsContainer">
                    <!-- Skeleton loading state -->
                    <ul class="tx-list" id="recentTxList">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <li class="tx-item">
                            <div class="skeleton" style="width:38px;height:38px;border-radius:8px;flex-shrink:0;"></div>
                            <div class="flex-1" style="flex:1;">
                                <div class="skeleton skeleton-row" style="width:60%;height:14px;margin-bottom:6px;"></div>
                                <div class="skeleton skeleton-row" style="width:40%;height:11px;margin-bottom:0;"></div>
                            </div>
                            <div class="skeleton" style="width:70px;height:18px;border-radius:4px;"></div>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right column: Budgets + Goals -->
        <div class="col-lg-5">

            <!-- Budget Overview -->
            <div class="hp-card mb-3">
                <div class="hp-card-header">
                    <h6 class="hp-card-title">Budget Overview</h6>
                    <a href="<?= BASE_URL ?>/budgets.php"
                       class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body" id="budgetOverviewContainer">
                    <div class="skeleton-row skeleton mb-2"></div>
                    <div class="skeleton-row skeleton mb-2"></div>
                    <div class="skeleton skeleton-row" style="width:70%"></div>
                </div>
            </div>

            <!-- Savings Goals -->
            <div class="hp-card">
                <div class="hp-card-header">
                    <h6 class="hp-card-title">Savings Goals</h6>
                    <a href="<?= BASE_URL ?>/goals.php"
                       class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body" id="goalsContainer">
                    <div class="skeleton-row skeleton mb-2"></div>
                    <div class="skeleton skeleton-row" style="width:80%"></div>
                </div>
            </div>

        </div>
    </div><!-- /lower row -->

</div><!-- /.page-content -->
</div><!-- /.main-wrapper -->
</div><!-- /.app-layout -->

<!-- ══════════════════════════════════════════════════════
     ADD TRANSACTION MODAL
     ══════════════════════════════════════════════════════ -->
<div class="modal fade add-tx-modal" id="addTransactionModal" tabindex="-1"
     aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionModalLabel">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Add Transaction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Type Selector -->
                <div class="type-selector mb-4" id="txTypeSelector" role="group"
                     aria-label="Transaction type">
                    <button type="button" class="type-selector-btn active-expense"
                            data-type="expense" id="typeExpenseBtn">
                        <i class="bi bi-arrow-down-circle"></i> Expense
                    </button>
                    <button type="button" class="type-selector-btn"
                            data-type="income" id="typeIncomeBtn">
                        <i class="bi bi-arrow-up-circle"></i> Income
                    </button>
                    <button type="button" class="type-selector-btn"
                            data-type="transfer" id="typeTransferBtn">
                        <i class="bi bi-arrow-left-right"></i> Transfer
                    </button>
                </div>

                <form id="addTransactionForm" novalidate>
                    <input type="hidden" id="txType" name="type" value="expense">

                    <!-- Amount (large, prominent) -->
                    <div class="mb-4">
                        <label for="txAmount" class="form-label form-section-label">Amount</label>
                        <div class="amount-input-wrap">
                            <span class="amount-currency-label">₹</span>
                            <input type="number"
                                   class="form-control amount-input-big"
                                   id="txAmount"
                                   name="amount"
                                   placeholder="0.00"
                                   min="0.01"
                                   step="0.01"
                                   required>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="txTitle" class="form-label">
                            <span class="expense-label">Expense Title</span>
                            <span class="income-label d-none">Income Source</span>
                            <span class="transfer-label d-none">Transfer Note</span>
                        </label>
                        <input type="text" class="form-control" id="txTitle"
                               name="title" placeholder="e.g. Lunch at Cafe" required>
                    </div>

                    <!-- Category (hidden for transfer) -->
                    <div class="row g-3 mb-3" id="categoryRow">
                        <div class="col-6">
                            <label for="txCategory" class="form-label">Category</label>
                            <select class="form-select" id="txCategory" name="category_id">
                                <option value="">Select category</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="txAccount" class="form-label">Account</label>
                            <select class="form-select" id="txAccount" name="account_id">
                                <option value="">Select account</option>
                            </select>
                        </div>
                    </div>

                    <!-- Transfer: From / To accounts -->
                    <div class="row g-3 mb-3 d-none" id="transferRow">
                        <div class="col-6">
                            <label for="txFromAccount" class="form-label">From Account</label>
                            <select class="form-select" id="txFromAccount" name="account_id">
                                <option value="">From</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="txToAccount" class="form-label">To Account</label>
                            <select class="form-select" id="txToAccount" name="to_account_id">
                                <option value="">To</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date & Merchant (for expense) -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="txDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="txDate"
                                   name="transaction_date"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6 expense-only">
                            <label for="txMerchant" class="form-label">Merchant <small class="text-muted">(optional)</small></label>
                            <input type="text" class="form-control" id="txMerchant"
                                   name="merchant" placeholder="Shop / Vendor">
                        </div>
                    </div>

                    <!-- Payment method (hidden for transfer) -->
                    <div class="mb-3" id="paymentMethodRow">
                        <label class="form-label">Payment Method</label>
                        <div class="payment-chips" id="paymentChips">
                            <div class="skeleton" style="height:32px;width:60px;border-radius:999px;"></div>
                            <div class="skeleton" style="height:32px;width:50px;border-radius:999px;"></div>
                            <div class="skeleton" style="height:32px;width:80px;border-radius:999px;"></div>
                        </div>
                        <input type="hidden" id="txPaymentMethod" name="payment_method_id">
                    </div>

                    <!-- Note -->
                    <div class="mb-3">
                        <label for="txNote" class="form-label">Note <small class="text-muted">(optional)</small></label>
                        <textarea class="form-control" id="txNote" name="note"
                                  rows="2" placeholder="Any additional notes…"></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTxBtn">
                    <i class="bi bi-check2 me-1"></i> Save Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
