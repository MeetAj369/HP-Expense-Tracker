<?php
/**
 * HP Personal Expense Tracker — HTML <head> Include
 * includes/header.php
 * ============================================================
 * Sets up the complete HTML document head.
 * Expected variables set by the including page:
 *   $page_title  (string) — used in <title> tag
 *   $extra_css   (array)  — additional CSS file paths (optional)
 */

// Ensure session and constants are available
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/auth.php';
}

$title = ($page_title ?? 'Dashboard') . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HP Personal Expense Tracker — Understand your money in seconds.">
    <meta name="author" content="HP Personal Expense Tracker">

    <!-- CSRF token available to all JavaScript on this page -->
    <?php if (isLoggedIn()): ?>
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <?php endif; ?>

    <title><?= e($title) ?></title>

    <!-- ── Google Fonts: Inter ──────────────────────────── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- ── Bootstrap 5 CSS ──────────────────────────────── -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- ── Bootstrap Icons ──────────────────────────────── -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- ── HP Custom CSS ────────────────────────────────── -->
    <link href="<?= BASE_URL ?>/assets/css/style.css"      rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/components.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css"  rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/responsive.css" rel="stylesheet">

    <?php
    // Extra CSS files for specific pages (e.g., analytics page loads chart-specific overrides)
    if (!empty($extra_css) && is_array($extra_css)):
        foreach ($extra_css as $cssFile):
    ?>
    <link href="<?= BASE_URL . '/' . e($cssFile) ?>" rel="stylesheet">
    <?php
        endforeach;
    endif;
    ?>

    <!-- Prevent Flash of Wrong Theme: apply theme before page renders -->
    <script>
        (function(){
            var t = localStorage.getItem('hp_theme') || 'light';
            if (t === 'system') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
