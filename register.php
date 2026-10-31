<?php
/**
 * HP Personal Expense Tracker — Register Page
 * register.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

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
    <meta name="description" content="Create your HP Personal Expense Tracker account">
    <title>Create Account — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css"      rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/components.css" rel="stylesheet">
    <script>
        (function(){ var t = localStorage.getItem('hp_theme')||'light'; if(t==='system') t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'; document.documentElement.setAttribute('data-theme',t); })();
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
            <h2>Start your financial<br>journey today.</h2>
            <p>Create a free account and gain complete control over your personal finances in minutes.</p>
        </div>
        <ul class="auth-features">
            <li><i class="bi bi-check-circle-fill"></i> Free to use — no hidden charges</li>
            <li><i class="bi bi-check-circle-fill"></i> All data stays private &amp; secure</li>
            <li><i class="bi bi-check-circle-fill"></i> Rich analytics from day one</li>
            <li><i class="bi bi-check-circle-fill"></i> Budgets, goals &amp; insights</li>
            <li><i class="bi bi-check-circle-fill"></i> Works on all devices</li>
        </ul>
        <div class="auth-panel-footer">
            <p>© <?= date('Y') ?> HP Personal Expense Tracker · v<?= APP_VERSION ?></p>
        </div>
    </div>

    <!-- ── Right Form Panel ───────────────────────────── -->
    <div class="auth-panel-right">
        <div class="auth-form-container">

            <div class="mobile-brand d-lg-none">
                <div class="mobile-logo-mark">HP</div>
                <div class="mobile-brand-text">HP Personal Expense Tracker</div>
            </div>

            <div class="auth-form-header">
                <h1>Create account ✨</h1>
                <p>Set up your HP account in under a minute.</p>
            </div>

            <div id="authAlert" class="alert d-none mb-4" role="alert"></div>

            <form id="registerForm" novalidate autocomplete="on">

                <!-- Full Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text"
                               class="form-control form-control-icon"
                               id="name"
                               name="name"
                               placeholder="Harsh Patel"
                               autocomplete="name"
                               minlength="2"
                               required>
                    </div>
                    <div class="invalid-feedback">Please enter your full name (at least 2 characters).</div>
                </div>

                <!-- Email -->
                <div class="mb-3">
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
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password"
                               class="form-control form-control-icon pe-5"
                               id="password"
                               name="password"
                               placeholder="Minimum 8 characters"
                               autocomplete="new-password"
                               minlength="8"
                               required>
                        <button type="button" class="btn-password-toggle" id="togglePassword" aria-label="Show password">
                            <i class="bi bi-eye" id="togglePwdIcon"></i>
                        </button>
                    </div>
                    <!-- Password strength -->
                    <div class="pw-strength-bar mt-2">
                        <div class="pw-strength-fill" id="pwStrengthFill"></div>
                    </div>
                    <div class="pw-strength-label" id="pwStrengthLabel">Enter a password</div>
                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password"
                               class="form-control form-control-icon"
                               id="confirmPassword"
                               name="confirm_password"
                               placeholder="Re-enter your password"
                               autocomplete="new-password"
                               required>
                    </div>
                    <div class="invalid-feedback" id="confirmPwdFeedback">Passwords do not match.</div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-auth" id="registerBtn">
                    <span id="registerBtnText">Create Account</span>
                    <span class="spinner-border spinner-border-sm d-none" id="registerSpinner"></span>
                    <i class="bi bi-arrow-right ms-1" id="registerArrow"></i>
                </button>
            </form>

            <p class="auth-switch-text">
                Already have an account?
                <a href="<?= BASE_URL ?>/login.php">Sign in <i class="bi bi-arrow-right"></i></a>
            </p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {

    // ── Password visibility toggle ────────────────────────
    $('#togglePassword').on('click', function () {
        const pwd  = $('#password');
        const icon = $('#togglePwdIcon');
        if (pwd.attr('type') === 'password') {
            pwd.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            pwd.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // ── Password strength indicator ───────────────────────
    $('#password').on('input', function () {
        const val = $(this).val();
        const fill  = $('#pwStrengthFill');
        const label = $('#pwStrengthLabel');

        if (!val) {
            fill.attr('class', 'pw-strength-fill').css('width', '0%');
            label.text('Enter a password').css('color', '');
            return;
        }

        let score = 0;
        if (val.length >= 8)                          score++;
        if (/[A-Z]/.test(val))                        score++;
        if (/[0-9]/.test(val))                        score++;
        if (/[^a-zA-Z0-9]/.test(val))                score++;

        const levels = [
            { cls: 'weak',   label: 'Weak',   color: 'var(--danger)'  },
            { cls: 'fair',   label: 'Fair',   color: 'var(--warning)' },
            { cls: 'good',   label: 'Good',   color: 'var(--info)'    },
            { cls: 'strong', label: 'Strong', color: 'var(--success)' },
        ];
        const lvl = levels[Math.max(0, score - 1)];
        fill.attr('class', 'pw-strength-fill ' + lvl.cls);
        label.text(lvl.label + ' password').css('color', lvl.color);
    });

    // ── Form submission ───────────────────────────────────
    $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        // Password match check
        const pwd     = $('#password').val();
        const confirm = $('#confirmPassword').val();
        if (pwd !== confirm) {
            $('#confirmPassword')[0].setCustomValidity('no-match');
            $('#confirmPwdFeedback').text('Passwords do not match.');
            this.classList.add('was-validated');
            return;
        } else {
            $('#confirmPassword')[0].setCustomValidity('');
        }

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        setLoading(true);
        hideAlert();

        $.ajax({
            url:      '<?= BASE_URL ?>/api/auth/register.php',
            method:   'POST',
            dataType: 'json',
            data: {
                name:             $('#name').val().trim(),
                email:            $('#email').val().trim(),
                password:         pwd,
                confirm_password: confirm
            },
            success: function (resp) {
                if (resp.status === 'success') {
                    $('#registerBtnText').text('Redirecting...');
                    window.location.href = resp.redirect || '<?= BASE_URL ?>/dashboard.php';
                } else {
                    showAlert('danger', resp.message || 'Registration failed. Please try again.');
                    setLoading(false);
                }
            },
            error: function () {
                showAlert('danger', 'Connection error. Please try again.');
                setLoading(false);
            }
        });
    });

    // Clear custom validity when user retypes confirm password
    $('#confirmPassword').on('input', function () {
        this.setCustomValidity('');
    });

    function setLoading(on) {
        $('#registerBtn').prop('disabled', on);
        $('#registerSpinner').toggleClass('d-none', !on);
        $('#registerArrow').toggleClass('d-none', on);
        if (on) $('#registerBtnText').text('Creating account...');
        else     $('#registerBtnText').text('Create Account');
    }
    function showAlert(type, msg) {
        $('#authAlert').removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + type)
            .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>' + msg);
    }
    function hideAlert() { $('#authAlert').addClass('d-none'); }
});
</script>
</body>
</html>
