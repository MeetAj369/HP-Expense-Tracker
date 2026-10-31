<?php
/**
 * HP Personal Expense Tracker — Sidebar Navigation Include
 * includes/sidebar.php
 * ============================================================
 * Renders the fixed sidebar on desktop and the Bootstrap
 * Offcanvas navigation on mobile.
 *
 * Expected variables (set by the including page):
 *   $current_page  (string) — e.g., 'dashboard', 'transactions'
 */
$currentPage = $current_page ?? '';
$userName    = currentUserName();
$userEmail   = $_SESSION['user_email'] ?? '';
$userInitials = strtoupper(substr($userName, 0, 1));
// Use first two letters if name has two parts
$nameParts = explode(' ', $userName);
if (count($nameParts) >= 2) {
    $userInitials = strtoupper($nameParts[0][0] . $nameParts[1][0]);
}

/**
 * nav_link() — Helper to render a sidebar link.
 * Marks it as 'active' when $currentPage matches.
 */
function nav_link(string $href, string $icon, string $label, string $page, string $currentPage, string $badge = ''): void
{
    $active = ($currentPage === $page) ? 'active' : '';
    $b = '';
    if ($badge) {
        $b = '<span class="sidebar-nav-badge nav-badge">' . e($badge) . '</span>';
    }
    echo '<li class="nav-item">';
    echo '<a href="' . BASE_URL . '/' . e($href) . '" class="nav-link ' . $active . '">';
    echo '<i class="bi ' . e($icon) . '"></i>';
    echo '<span>' . e($label) . '</span>';
    echo $b;
    echo '</a></li>';
}

/**
 * Shared sidebar inner HTML — used for both the fixed desktop sidebar
 * and the mobile Offcanvas sidebar.
 */
$renderSidebarInner = function () use ($currentPage, $userName, $userEmail, $userInitials) {
    ?>
    <!-- Brand / Logo -->
    <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-brand">
        <div class="sidebar-logo-mark">HP</div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">HP</div>
            <div class="sidebar-brand-sub">Personal Expense Tracker</div>
        </div>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav flex-column">

            <!-- Primary -->
            <?php nav_link('dashboard.php',     'bi-house-door',           'Dashboard',       'dashboard',     $currentPage); ?>
            <?php nav_link('transactions.php',  'bi-arrow-left-right',     'Transactions',    'transactions',  $currentPage); ?>
            <?php nav_link('expenses.php',      'bi-arrow-down-circle',    'Expenses',        'expenses',      $currentPage); ?>
            <?php nav_link('income.php',        'bi-arrow-up-circle',      'Income',          'income',        $currentPage); ?>
            <?php nav_link('accounts.php',      'bi-wallet2',              'Accounts',        'accounts',      $currentPage); ?>

            <div class="sidebar-section-label">Planning</div>
            <?php nav_link('budgets.php',       'bi-pie-chart',            'Budgets',         'budgets',       $currentPage); ?>
            <?php nav_link('recurring.php',     'bi-repeat',               'Recurring',       'recurring',     $currentPage); ?>
            <?php nav_link('subscriptions.php', 'bi-credit-card-2-front',  'Subscriptions',   'subscriptions', $currentPage); ?>
            <?php nav_link('goals.php',         'bi-piggy-bank',           'Savings Goals',   'goals',         $currentPage); ?>

            <div class="sidebar-section-label">Insights</div>
            <?php nav_link('analytics.php',     'bi-bar-chart-line',       'Analytics',       'analytics',     $currentPage); ?>
            <?php nav_link('reports.php',       'bi-file-earmark-bar-graph','Reports',        'reports',       $currentPage); ?>

            <div class="sidebar-section-label">Settings</div>
            <?php nav_link('categories.php',    'bi-tag',                  'Categories',      'categories',    $currentPage); ?>
            <?php nav_link('payment_methods.php','bi-credit-card',         'Payment Methods', 'payment_methods',$currentPage); ?>
            <?php nav_link('profile.php',       'bi-person-circle',        'Profile',         'profile',       $currentPage); ?>

        </ul>
    </nav>

    <!-- User profile footer -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= e($userInitials) ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($userName) ?></div>
            <div class="sidebar-user-email"><?= e($userEmail) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/api/auth/logout.php" class="sidebar-logout-btn"
           title="Sign Out" data-bs-toggle="tooltip" data-bs-placement="right">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
    <?php
};
?>

<!-- ── Desktop Sidebar (fixed, visible on lg+) ─────────── -->
<aside id="appSidebar" class="sidebar">
    <!-- Collapse toggle button -->
    <button id="sidebarToggleBtn" class="sidebar-toggle d-none d-lg-flex"
            title="Collapse sidebar" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-left" id="sidebarToggleIcon"></i>
    </button>
    <?php $renderSidebarInner(); ?>
</aside>

<!-- ── Mobile Offcanvas Sidebar (visible on < lg) ─────── -->
<div class="offcanvas offcanvas-start offcanvas-sidebar d-lg-none"
     tabindex="-1"
     id="mobileNavOffcanvas"
     aria-labelledby="mobileNavOffcanvasLabel">
    <div class="offcanvas-header">
        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Close menu">
        </button>
    </div>
    <div class="d-flex flex-column h-100">
        <?php $renderSidebarInner(); ?>
    </div>
</div>
