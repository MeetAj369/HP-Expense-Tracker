<?php
/**
 * HP Personal Expense Tracker — Top Navigation Bar Include
 * includes/navbar.php
 * ============================================================
 * The sticky top bar containing:
 *   - Mobile hamburger button
 *   - Current page title
 *   - Date / time display
 *   - Theme toggle
 *   - Notification bell
 *   - User avatar dropdown
 */
$userName    = currentUserName();
$userInitials = strtoupper(substr($userName, 0, 1));
$nameParts = explode(' ', $userName);
if (count($nameParts) >= 2) {
    $userInitials = strtoupper($nameParts[0][0] . $nameParts[1][0]);
}
$pageNavTitle = $page_nav_title ?? ($page_title ?? 'Dashboard');
?>
<header class="top-navbar">

    <!-- ── Mobile hamburger ─────────────────────────────── -->
    <button class="navbar-icon-btn mobile-menu-btn d-lg-none"
            id="mobileMenuBtn"
            aria-label="Open navigation menu"
            style="display:none">
        <i class="bi bi-list"></i>
    </button>

    <!-- ── Page title area ──────────────────────────────── -->
    <div class="page-title-area">
        <h2 class="page-title mb-0"><?= e($pageNavTitle) ?></h2>
    </div>

    <!-- ── Right-side actions ───────────────────────────── -->
    <div class="d-flex align-items-center gap-2">

        <!-- Date display (hidden on small screens) -->
        <span class="d-none d-md-block text-muted me-2" style="font-size:0.8125rem;font-weight:500;">
            <i class="bi bi-calendar3 me-1"></i>
            <?= date('D, d M Y') ?>
        </span>

        <!-- Theme Toggle -->
        <button class="navbar-icon-btn"
                id="themeToggleBtn"
                title="Toggle dark mode"
                data-bs-toggle="tooltip"
                data-bs-placement="bottom">
            <i class="bi bi-moon" id="themeToggleIcon"></i>
        </button>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="navbar-icon-btn"
                    id="notifDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Notifications"
                    aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <?php
                // Check for unread notifications
                try {
                    $pdo = getPDO();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                    $stmt->execute([currentUserId()]);
                    $unreadCount = (int)$stmt->fetchColumn();
                    if ($unreadCount > 0): ?>
                <span class="navbar-notification-badge"></span>
                <?php endif; } catch (Exception $e) { $unreadCount = 0; } ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:300px;max-width:340px;"
                aria-labelledby="notifDropdown">
                <li class="px-3 py-2 d-flex justify-content-between align-items-center">
                    <span style="font-weight:700;font-size:0.875rem;">Notifications</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-primary"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <?php
                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
                    );
                    $stmt->execute([currentUserId()]);
                    $notifications = $stmt->fetchAll();
                    if (empty($notifications)): ?>
                <li>
                    <div class="text-center py-3 text-muted" style="font-size:0.8125rem;">
                        <i class="bi bi-bell-slash d-block mb-1" style="font-size:1.5rem;"></i>
                        No notifications
                    </div>
                </li>
                    <?php else:
                        foreach ($notifications as $notif):
                            $typeIcon = [
                                'success' => 'bi-check-circle-fill text-success',
                                'warning' => 'bi-exclamation-circle-fill text-warning',
                                'danger'  => 'bi-exclamation-triangle-fill text-danger',
                                'info'    => 'bi-info-circle-fill text-info',
                            ][$notif['type']] ?? 'bi-info-circle-fill text-info';
                    ?>
                <li>
                    <a href="#" class="dropdown-item py-2 <?= $notif['is_read'] ? '' : 'fw-semibold' ?>">
                        <div class="d-flex gap-2 align-items-start">
                            <i class="bi <?= $typeIcon ?> mt-1 flex-shrink-0"></i>
                            <div>
                                <div style="font-size:0.8125rem;"><?= e($notif['title']) ?></div>
                                <div style="font-size:0.75rem;" class="text-muted"><?= e($notif['message']) ?></div>
                            </div>
                        </div>
                    </a>
                </li>
                    <?php endforeach; ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-center text-primary" href="#" style="font-size:0.8125rem;font-weight:600;">View all</a></li>
                <?php endif; } catch (Exception $e) { ?>
                <li><div class="text-center py-2 text-muted" style="font-size:0.8125rem;">No notifications</div></li>
                <?php } ?>
            </ul>
        </div>

        <!-- User Avatar Dropdown -->
        <div class="dropdown">
            <button class="navbar-user-avatar d-flex align-items-center justify-content-center"
                    id="userAvatarDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="User menu">
                <?= e($userInitials) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userAvatarDropdown">
                <li class="px-3 py-2">
                    <div style="font-weight:700;font-size:0.875rem;"><?= e($userName) ?></div>
                    <div style="font-size:0.75rem;" class="text-muted"><?= e($_SESSION['user_email'] ?? '') ?></div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                        <i class="bi bi-person me-2"></i>Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php#settings">
                        <i class="bi bi-gear me-2"></i>Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/api/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                    </a>
                </li>
            </ul>
        </div>

    </div><!-- /.d-flex -->
</header>
