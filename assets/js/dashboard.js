/**
 * HP Personal Expense Tracker — Dashboard JavaScript
 * assets/js/dashboard.js
 * ================================================================
 * Handles:
 *   - Loading dashboard summary via AJAX
 *   - Animating KPI cards
 *   - Rendering Chart.js charts
 *   - Recent transactions list
 *   - Budget progress bars
 *   - Savings goals list
 *   - Add Transaction modal (type switching, form submission)
 *   - Date range selector
 * ================================================================
 */

// ── Chart.js instances (kept so we can destroy and re-create on refresh) ──
let categoryChartInstance    = null;
let incomeExpenseChartInstance = null;

// ── Current date range ────────────────────────────────────
let currentRange = 'this_month';

// ── Chart.js: use CSS variable colours ────────────────────
function getCssVar(name) {
    return getComputedStyle(document.documentElement)
           .getPropertyValue(name).trim();
}

// ── Load Dashboard ─────────────────────────────────────────
function loadDashboard(range) {
    currentRange = range || 'this_month';

    $.ajax({
        url:      'api/dashboard/summary.php',
        method:   'GET',
        dataType: 'json',
        data:     { range: currentRange },
        success:  function (resp) {
            if (resp.status === 'success') {
                const d = resp.data;
                updateKPICards(d);
                renderCategoryChart(d.category_chart);
                renderIncomeExpenseChart(d.monthly_chart);
                renderRecentTransactions(d.recent_transactions);
                renderBudgets(d.budgets);
                renderGoals(d.goals);
            } else {
                HPToast.error(resp.message || 'Failed to load dashboard data.');
            }
        },
        error: function () {
            HPToast.error('Could not connect to the server. Please refresh.');
        }
    });
}

// ── Update KPI Cards ──────────────────────────────────────
function updateKPICards(d) {
    // Balance
    $('#kpiBalance').html(formatCurrency(d.balance));
    $('#kpiBalanceTrend').html(makeTrendHtml(null, `${d.tx_count} transactions`));

    // Income
    $('#kpiIncome').html(formatCurrency(d.total_income));
    $('#kpiIncomeTrend').html(makeTrendHtml(d.income_change, 'vs last period'));

    // Expense
    $('#kpiExpense').html(formatCurrency(d.total_expense));
    // For expenses, a decrease is good (green), increase is bad (red) → invert
    $('#kpiExpenseTrend').html(makeTrendHtml(d.expense_change ? -d.expense_change : null, 'vs last period'));

    // Savings rate
    $('#kpiSavings').html(d.savings_rate + '%');
    const savingMsg = d.savings_rate >= 20 ? '🎉 Great savings!' :
                      d.savings_rate >= 10 ? '👍 Keep saving' : '⚠️ Low savings rate';
    $('#kpiSavingsTrend').html(`<span class="${d.savings_rate >= 20 ? 'trend-up' : d.savings_rate >= 10 ? 'text-muted' : 'trend-down'}">${savingMsg}</span>`);
}

function makeTrendHtml(change, label) {
    if (change === null || change === undefined) {
        return `<span class="trend-neutral text-muted">${label}</span>`;
    }
    const abs   = Math.abs(change).toFixed(1);
    const up    = change >= 0;
    const icon  = up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
    const cls   = up ? 'trend-up' : 'trend-down';
    return `<span class="${cls}"><i class="bi ${icon}"></i> ${abs}%</span>
            <span class="trend-label ms-1">${label}</span>`;
}

// ── Render Category Doughnut Chart ────────────────────────
function renderCategoryChart(data) {
    const canvas = document.getElementById('categoryChart');
    if (!canvas) return;

    if (categoryChartInstance) {
        categoryChartInstance.destroy();
        categoryChartInstance = null;
    }

    if (!data || data.length === 0) {
        canvas.parentElement.innerHTML = `
            <div class="empty-state py-4">
                <div class="empty-state-icon mx-auto" style="width:48px;height:48px;font-size:1.25rem;">
                    <i class="bi bi-pie-chart"></i>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.8125rem;">No expense data</p>
            </div>`;
        $('#categoryChartSubtitle').text('No data for this period');
        return;
    }

    const labels = data.map(d => d.name);
    const values = data.map(d => parseFloat(d.total));
    const colors = data.map(d => d.color || '#6c757d');

    const total = values.reduce((a, b) => a + b, 0);
    $('#categoryChartSubtitle').text(formatCurrency(total) + ' total');

    categoryChartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${formatCurrency(ctx.parsed)} (${((ctx.parsed/total)*100).toFixed(1)}%)`
                    }
                }
            }
        }
    });

    // Custom legend
    const legendHtml = data.map((d, i) => `
        <div class="chart-legend-item">
            <div class="chart-legend-dot" style="background:${colors[i]}"></div>
            <span>${d.name}</span>
            <span class="ms-auto fw-semibold" style="font-size:0.75rem;">${((values[i]/total)*100).toFixed(0)}%</span>
        </div>`).join('');
    $('#categoryLegend').html(legendHtml);
}

// ── Render Income vs Expense Bar Chart ────────────────────
function renderIncomeExpenseChart(data) {
    const canvas = document.getElementById('incomeExpenseChart');
    if (!canvas) return;

    if (incomeExpenseChartInstance) {
        incomeExpenseChartInstance.destroy();
        incomeExpenseChartInstance = null;
    }

    if (!data || data.length === 0) {
        canvas.parentElement.innerHTML = `<div class="empty-state py-4"><p class="text-muted">No data available</p></div>`;
        return;
    }

    const labels  = data.map(d => d.label);
    const incomes = data.map(d => d.income  || 0);
    const expenses= data.map(d => d.expense || 0);

    incomeExpenseChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Income',
                    data:  incomes,
                    backgroundColor: 'rgba(16,185,129,0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Expenses',
                    data:  expenses,
                    backgroundColor: 'rgba(239,68,68,0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: getCssVar('--text'),
                        font: { family: 'Inter', size: 12, weight: '600' },
                        boxWidth: 10,
                        boxHeight: 10,
                        borderRadius: 3,
                        useBorderRadius: true,
                        padding: 16,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: getCssVar('--text-muted'), font: { family:'Inter', size:11 } }
                },
                y: {
                    grid: { color: getCssVar('--border'), lineWidth: 1 },
                    ticks: {
                        color: getCssVar('--text-muted'),
                        font: { family:'Inter', size:11 },
                        callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                    }
                }
            }
        }
    });
}

// ── Render Recent Transactions ────────────────────────────
function renderRecentTransactions(txList) {
    if (!txList || txList.length === 0) {
        $('#recentTransactionsContainer').html(`
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
                <p class="empty-state-title">No transactions yet</p>
                <p class="empty-state-text">Start tracking your money by adding your first transaction.</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Transaction
                </button>
            </div>`);
        return;
    }

    const html = txList.map(tx => {
        const isExpense  = tx.type === 'expense';
        const isTransfer = tx.type === 'transfer';
        const amtCls  = isExpense ? 'amount-expense' : isTransfer ? 'amount-transfer' : 'amount-income';
        const amtSign = isExpense || isTransfer ? '−' : '+';
        const iconBg  = tx.category_color || (isExpense ? '#ef4444' : '#10b981');
        const iconName= tx.category_icon  || 'bi-circle';
        const badgeCls= `badge-${tx.type}`;
        const relDate  = formatRelativeDate(tx.transaction_date);

        return `
        <li class="tx-item">
            <div class="tx-icon" style="background:${iconBg}20;">
                <i class="bi ${iconName}" style="color:${iconBg};"></i>
            </div>
            <div class="tx-info">
                <div class="tx-title">${tx.title}</div>
                <div class="tx-meta">
                    ${tx.category_name ? `<span class="badge ${badgeCls} me-1" style="font-size:0.6rem">${tx.category_name}</span>` : ''}
                    ${tx.account_name  ? `<span class="text-muted">${tx.account_name}</span>` : ''}
                </div>
            </div>
            <div>
                <div class="tx-amount ${amtCls}">${amtSign}${formatCurrency(tx.amount)}</div>
                <div class="tx-date-badge">${relDate}</div>
            </div>
        </li>`;
    }).join('');

    $('#recentTransactionsContainer').html(`<ul class="tx-list">${html}</ul>`);
}

// ── Render Budget Overview ────────────────────────────────
function renderBudgets(budgets) {
    if (!budgets || budgets.length === 0) {
        $('#budgetOverviewContainer').html(`
            <div class="empty-state py-3">
                <div class="empty-state-icon" style="width:40px;height:40px;font-size:1rem;">
                    <i class="bi bi-pie-chart"></i>
                </div>
                <p class="empty-state-title mb-1" style="font-size:0.875rem;">No budgets set</p>
                <p class="empty-state-text" style="font-size:0.8125rem;">
                    <a href="budgets.php">Create a budget</a> to track your spending limits.
                </p>
            </div>`);
        return;
    }

    const html = budgets.map(b => {
        const spent  = parseFloat(b.spent);
        const limit  = parseFloat(b.budget_limit);
        const pct    = limit > 0 ? Math.min((spent / limit) * 100, 100) : 0;
        const overLimit = spent > limit;
        const fillCls = pct >= 100 ? 'exceeded' : pct >= 90 ? 'critical' : pct >= 80 ? 'warning' : 'normal';
        const iconBg  = b.category_color || '#6c757d';

        return `
        <div class="budget-progress-item">
            <div class="budget-progress-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="category-icon" style="background:${iconBg}20;width:26px;height:26px;font-size:0.75rem;">
                        <i class="bi ${b.category_icon || 'bi-tag'}" style="color:${iconBg};"></i>
                    </div>
                    <span class="budget-progress-label">${b.category_name || 'Overall'}</span>
                </div>
                <span class="budget-progress-amount ${overLimit ? 'text-danger fw-bold' : ''}">
                    ${formatCurrency(spent)} <span class="text-muted">/ ${formatCurrency(limit)}</span>
                </span>
            </div>
            <div class="budget-progress-bar">
                <div class="budget-progress-fill ${fillCls}" style="width:${pct.toFixed(1)}%"></div>
            </div>
            <div class="budget-progress-footer">
                <span>${pct.toFixed(0)}% used</span>
                <span class="${overLimit ? 'text-danger' : 'text-muted'}">
                    ${overLimit ? 'Over by ' + formatCurrency(spent - limit) : formatCurrency(limit - spent) + ' left'}
                </span>
            </div>
        </div>`;
    }).join('');

    $('#budgetOverviewContainer').html(html);
}

// ── Render Savings Goals ──────────────────────────────────
function renderGoals(goals) {
    if (!goals || goals.length === 0) {
        $('#goalsContainer').html(`
            <div class="empty-state py-3">
                <div class="empty-state-icon" style="width:40px;height:40px;font-size:1rem;">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <p class="empty-state-title mb-1" style="font-size:0.875rem;">No savings goals</p>
                <p class="empty-state-text" style="font-size:0.8125rem;">
                    <a href="goals.php">Create a goal</a> to start saving.
                </p>
            </div>`);
        return;
    }

    const html = goals.map(g => {
        const current = parseFloat(g.current_amount);
        const target  = parseFloat(g.target_amount);
        const pct     = target > 0 ? Math.min((current / target) * 100, 100) : 0;
        const color   = g.color || '#4c6fff';
        const daysLeft= g.target_date
            ? Math.max(0, Math.ceil((new Date(g.target_date) - new Date()) / 86400000))
            : null;

        return `
        <div class="goal-card">
            <div class="goal-icon" style="background:${color}20;">
                <i class="bi ${g.icon || 'bi-piggy-bank'}" style="color:${color};"></i>
            </div>
            <div class="goal-info">
                <div class="goal-name">${g.name}</div>
                <div class="goal-meta">
                    ${formatCurrency(current)} of ${formatCurrency(target)}
                    ${daysLeft !== null ? `· ${daysLeft}d left` : ''}
                </div>
                <div class="goal-bar">
                    <div class="goal-bar-fill" style="width:${pct.toFixed(1)}%;background:${color};"></div>
                </div>
            </div>
            <div class="goal-percent" style="color:${color};">${pct.toFixed(0)}%</div>
        </div>`;
    }).join('');

    $('#goalsContainer').html(html);
}

// ── Date Range Selector ───────────────────────────────────
const RANGE_LABELS = {
    this_month:   'This Month',
    last_month:   'Last Month',
    last_3_months:'Last 3 Months',
    this_year:    'This Year',
    custom:       'Custom Range',
};

$(document).on('click', '.date-range-item', function (e) {
    e.preventDefault();
    const range = $(this).data('range');
    if (range === 'custom') { return; } // TODO: Custom date picker in Phase 10
    $('.date-range-item').removeClass('active');
    $(this).addClass('active');
    $('#dateRangeLabel').text(RANGE_LABELS[range] || 'Custom');
    loadDashboard(range);
});

// ── Add Transaction Modal: Type Switching ─────────────────
$('#txTypeSelector').on('click', '.type-selector-btn', function () {
    const type = $(this).data('type');
    $('#txType').val(type);

    // Update button styles
    $('.type-selector-btn').removeClass('active-expense active-income active-transfer');
    $(this).addClass('active-' + type);

    // Show/hide fields based on type
    if (type === 'transfer') {
        $('#categoryRow').addClass('d-none');
        $('#transferRow').removeClass('d-none');
        $('#paymentMethodRow').addClass('d-none');
        $('.expense-only').hide();
        $('.expense-label, .income-label').addClass('d-none');
        $('.transfer-label').removeClass('d-none');
    } else if (type === 'income') {
        $('#categoryRow').removeClass('d-none');
        $('#transferRow').addClass('d-none');
        $('#paymentMethodRow').removeClass('d-none');
        $('.expense-only').hide();
        $('.expense-label, .transfer-label').addClass('d-none');
        $('.income-label').removeClass('d-none');
    } else {
        $('#categoryRow').removeClass('d-none');
        $('#transferRow').addClass('d-none');
        $('#paymentMethodRow').removeClass('d-none');
        $('.expense-only').show();
        $('.income-label, .transfer-label').addClass('d-none');
        $('.expense-label').removeClass('d-none');
    }

    // Reload categories for new type
    loadCategoriesForType(type);
});

// ── Load Categories for Type ──────────────────────────────
function loadCategoriesForType(type) {
    const catType = type === 'expense' ? 'expense' : type === 'income' ? 'income' : null;
    if (!catType) return;

    $.ajax({
        url: 'api/categories/list.php',
        method: 'GET',
        dataType: 'json',
        data: { type: catType },
        success: function (resp) {
            if (resp.status === 'success') {
                const opts = resp.data.map(c =>
                    `<option value="${c.id}">${c.name}</option>`
                ).join('');
                $('#txCategory').html('<option value="">Select category</option>' + opts);
            }
        }
    });
}

// ── Load Accounts ─────────────────────────────────────────
function loadAccounts() {
    $.ajax({
        url: 'api/accounts/list.php',
        method: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.status === 'success') {
                const opts = resp.data.map(a =>
                    `<option value="${a.id}">${a.name} (${formatCurrency(a.current_balance)})</option>`
                ).join('');
                const base = '<option value="">Select account</option>' + opts;
                $('#txAccount, #txFromAccount, #txToAccount').html(base);
            }
        }
    });
}

// ── Load Payment Methods ──────────────────────────────────
function loadPaymentMethods() {
    $.ajax({
        url: 'api/payment_methods/list.php',
        method: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.status === 'success') {
                const chips = resp.data.slice(0, 8).map((pm, i) => `
                    <div class="payment-chip ${i===0?'selected':''}"
                         data-id="${pm.id}" data-name="${pm.name}">
                        <i class="bi ${pm.icon || 'bi-credit-card'}"></i>
                        ${pm.name}
                    </div>`).join('');
                $('#paymentChips').html(chips);
                // Set first as default
                if (resp.data.length > 0) {
                    $('#txPaymentMethod').val(resp.data[0].id);
                }
            }
        }
    });
}

// Payment chip selection
$(document).on('click', '.payment-chip', function () {
    $('.payment-chip').removeClass('selected');
    $(this).addClass('selected');
    $('#txPaymentMethod').val($(this).data('id'));
});

// ── Add Transaction: modal open → load data ───────────────
$('#addTransactionModal').on('show.bs.modal', function () {
    loadCategoriesForType('expense');
    loadAccounts();
    loadPaymentMethods();
});

// ── Add Transaction: form submit ──────────────────────────
$('#saveTxBtn').on('click', function () {
    const form = document.getElementById('addTransactionForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const restore = HPLoader.btn($(this));

    $.ajax({
        url:      'api/transactions/create.php',
        method:   'POST',
        dataType: 'json',
        data:     $(form).serialize(),
        success:  function (resp) {
            if (resp.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
                form.reset();
                form.classList.remove('was-validated');
                HPToast.success(resp.message || 'Transaction added successfully.');
                loadDashboard(currentRange); // Refresh dashboard
            } else {
                HPToast.error(resp.message || 'Failed to save transaction.');
            }
        },
        error: function () {
            HPToast.error('Server error. Please try again.');
        },
        complete: restore
    });
});

// ── Reset modal on close ──────────────────────────────────
$('#addTransactionModal').on('hidden.bs.modal', function () {
    const form = document.getElementById('addTransactionForm');
    form.reset();
    form.classList.remove('was-validated');
    // Reset type to expense
    $('.type-selector-btn').removeClass('active-expense active-income active-transfer');
    $('#typeExpenseBtn').addClass('active-expense');
    $('#txType').val('expense');
    $('#categoryRow').removeClass('d-none');
    $('#transferRow').addClass('d-none');
    $('#paymentMethodRow').removeClass('d-none');
    $('.expense-only').show();
    $('.expense-label').removeClass('d-none');
    $('.income-label, .transfer-label').addClass('d-none');
});

// ── Init ──────────────────────────────────────────────────
$(document).ready(function () {
    loadDashboard('this_month');
});
