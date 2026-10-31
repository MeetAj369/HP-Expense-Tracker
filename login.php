<?php
/**
 * HP Personal Expense Tracker — Login Page
 * login.php
 * ============================================================
 * Split layout: branded dark left panel + clean login form right.
 * All form submission is via AJAX (no page reload).
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to HP Personal Expense Tracker">
    <title>Sign In — <?= APP_NAME ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css"      rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/components.css" rel="stylesheet">

    <script>
        // Apply theme before render to avoid flash
        (function(){
            var t = localStorage.getItem('hp_theme') || 'light';
            if (t === 'system') t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body class="auth-body">

<div class="auth-wrapper">

    <!-- ── Left Branded Panel ─────────────────────────── -->
    <div class="auth-panel-left">

        <div class="auth-brand">
            <div class="auth-logo-mark">HP</div>
            <div class="auth-brand-text">
                <div class="auth-brand-name">HP Personal</div>
                <div class="auth-brand-sub">Expense Tracker</div>
            </div>
        </div>

        <div class="auth-tagline">
            <h2>Understand your<br>money in seconds.</h2>
            <p>The smart, modern way to track your income, expenses, and savings — all in one place.</p>
        </div>

        <ul class="auth-features">
            <li><i class="bi bi-check-circle-fill"></i> Track all expenses &amp; income</li>
            <li><i class="bi bi-check-circle-fill"></i> Set budgets &amp; savings goals</li>
            <li><i class="bi bi-check-circle-fill"></i> Visual analytics &amp; charts</li>
            <li><i class="bi bi-check-circle-fill"></i> Manage recurring &amp; subscriptions</li>
            <li><i class="bi bi-check-circle-fill"></i> Secure, private &amp; fully yours</li>
        </ul>

        <div class="auth-panel-footer">
            <p>© <?= date('Y') ?> HP Personal Expense Tracker · v<?= APP_VERSION ?></p>
        </div>
    </div>

    <!-- ── Right Form Panel ───────────────────────────── -->
    <div class="auth-panel-right">
        <div class="auth-form-container">

            <!-- Mobile-only brand mark -->
            <div class="mobile-brand d-lg-none">
                <div class="mobile-logo-mark">HP</div>
                <div class="mobile-brand-text">HP Personal Expense Tracker</div>
            </div>

            <div class="auth-form-header">
                <h1>Welcome back 👋</h1>
                <p>Sign in to your HP account to continue.</p>
            </div>

            <!-- Error / success alert -->
            <div id="authAlert" class="alert d-none mb-4" role="alert"></div>

            <!-- Login form -->
            <form id="loginForm" novalidate autocomplete="on">

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email"
                               class="form-control form-control-icon"
                               id="email"
                               name="email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                    </div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="#" class="form-link" tabindex="-1">Forgot password?</a>
                    </div>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password"
                               class="form-control form-control-icon pe-5"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               autocomplete="current-password"
                               required>
                        <button type="button" class="btn-password-toggle" id="togglePassword" aria-label="Show password">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password is required.</div>
                </div>

                <!-- Remember me -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe" style="font-size:0.875rem;">
                            Remember me on this device
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-auth" id="loginBtn">
                    <span id="loginBtnText">Sign In</span>
                    <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status"></span>
                    <i class="bi bi-arrow-right ms-1" id="loginArrow"></i>
                </button>
            </form>

            <!-- Demo credentials card -->
            <div class="demo-cred-card">
                <div class="demo-cred-label">Demo Account — Try it instantly</div>
                <div class="demo-cred-row">
                    <span><b>Email:</b> hp@demo.com</span>
                    <span><b>Password:</b> demo123</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="fillDemoBtn">
                    <i class="bi bi-magic me-2"></i>Use Demo Credentials
                </button>
            </div>

            <!-- Switch to register -->
            <p class="auth-switch-text">
                Don't have an account?
                <a href="<?= BASE_URL ?>/register.php">Create one <i class="bi bi-arrow-right"></i></a>
            </p>

        </div><!-- /.auth-form-container -->
    </div><!-- /.auth-panel-right -->
</div><!-- /.auth-wrapper -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {

    // ── Password visibility toggle ────────────────────────
    $('#togglePassword').on('click', function () {
        const pwd  = $('#password');
        const icon = $('#togglePasswordIcon');
        if (pwd.attr('type') === 'password') {
            pwd.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            pwd.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // ── Fill demo credentials ─────────────────────────────
    $('#fillDemoBtn').on('click', function () {
        $('#email').val('hp@demo.com').trigger('input');
        $('#password').val('demo123').trigger('input');
        $('#loginForm').removeClass('was-validated');
    });

    // ── Form submission ───────────────────────────────────
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        // HTML5 validation
        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        setLoading(true);
        hideAlert();

        $.ajax({
            url:      '<?= BASE_URL ?>/api/auth/login.php',
            method:   'POST',
            dataType: 'json',
            data: {
                email:    $('#email').val().trim(),
                password: $('#password').val()
            },
            success: function (resp) {
                if (resp.status === 'success') {
                    $('#loginBtnText').text('Redirecting...');
                    window.location.href = resp.redirect || '<?= BASE_URL ?>/dashboard.php';
                } else {
                    showAlert('danger', resp.message || 'Login failed. Please try again.');
                    setLoading(false);
                }
            },
            error: function () {
                showAlert('danger', 'Connection error. Please check your connection and try again.');
                setLoading(false);
            }
        });
    });

    // ── Helpers ───────────────────────────────────────────
    function setLoading(on) {
        $('#loginBtn').prop('disabled', on);
        $('#loginSpinner').toggleClass('d-none', !on);
        $('#loginArrow').toggleClass('d-none', on);
        if (on) $('#loginBtnText').text('Signing in...');
        else     $('#loginBtnText').text('Sign In');
    }

    function showAlert(type, msg) {
        $('#authAlert')
            .removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type)
            .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>' + msg);
    }

    function hideAlert() {
        $('#authAlert').addClass('d-none');
    }

});
</script>
</body>
</html>
