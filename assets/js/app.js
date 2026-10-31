/**
 * HP Personal Expense Tracker — Global JavaScript
 * app.js  |  Version 2.0
 * ================================================================
 * Responsibilities:
 *   - Theme management (dark / light / system) with localStorage
 *   - Toast notification system (replaces all alert() calls)
 *   - Currency formatter (en-IN locale for lakhs/crores)
 *   - AJAX setup (CSRF token, error interception)
 *   - Sidebar collapse toggle
 *   - Global helpers used across all pages
 * ================================================================
 */

/* ── Theme Management ─────────────────────────────────────── */
const HPTheme = (() => {
    const STORAGE_KEY = 'hp_theme';
    const THEMES = ['light', 'dark', 'system'];

    /**
     * Returns 'light' or 'dark' (resolves 'system' to actual preference).
     */
    function resolveTheme(theme) {
        if (theme === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return THEMES.includes(theme) ? theme : 'light';
    }

    /**
     * Applies the theme to the <html> element.
     * Also updates the theme toggle button icon.
     */
    function applyTheme(theme) {
        const resolved = resolveTheme(theme);
        document.documentElement.setAttribute('data-theme', resolved);
        localStorage.setItem(STORAGE_KEY, theme);  // Store user's choice (may be 'system')

        // Update toggle icon
        const icon = document.getElementById('themeToggleIcon');
        if (icon) {
            icon.className = resolved === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        }
        const label = document.getElementById('themeToggleLabel');
        if (label) {
            label.textContent = resolved === 'dark' ? 'Light Mode' : 'Dark Mode';
        }
    }

    /**
     * Toggle between light and dark (ignores 'system' once user toggles).
     */
    function toggle() {
        const current = resolveTheme(localStorage.getItem(STORAGE_KEY) || 'light');
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    /**
     * Initialise: read from localStorage and apply immediately.
     * Called on DOMContentLoaded.
     */
    function init() {
        const stored = localStorage.getItem(STORAGE_KEY) || 'light';
        applyTheme(stored);

        // Listen for system preference changes (if user chose 'system')
        window.matchMedia('(prefers-color-scheme: dark)')
              .addEventListener('change', () => {
                  if (localStorage.getItem(STORAGE_KEY) === 'system') {
                      applyTheme('system');
                  }
              });
    }

    return { init, toggle, applyTheme };
})();

/* ── Toast Notification System ───────────────────────────── */
/**
 * HP.toast.show(options)
 *
 * Replaces all native alert() / confirm() calls.
 * Options: { type, title, message, duration }
 *   type:     'success' | 'error' | 'warning' | 'info'
 *   title:    Short heading (optional — auto-set from type if omitted)
 *   message:  The notification text
 *   duration: Auto-dismiss ms (default 4000, 0 = no auto-dismiss)
 */
const HPToast = (() => {
    const DEFAULTS = {
        success: { title: 'Success',  icon: 'bi-check-circle-fill' },
        error:   { title: 'Error',    icon: 'bi-exclamation-triangle-fill' },
        warning: { title: 'Warning',  icon: 'bi-exclamation-circle-fill' },
        info:    { title: 'Info',     icon: 'bi-info-circle-fill' },
    };

    function ensureContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    function show({ type = 'info', title = '', message = '', duration = 4000 } = {}) {
        const defaults = DEFAULTS[type] || DEFAULTS.info;
        const effectiveTitle = title || defaults.title;

        const container = ensureContainer();

        const toast = document.createElement('div');
        toast.className = `hp-toast ${type}`;
        toast.innerHTML = `
            <div class="hp-toast-icon"><i class="bi ${defaults.icon}"></i></div>
            <div class="hp-toast-body">
                <div class="hp-toast-title">${effectiveTitle}</div>
                <div class="hp-toast-message">${message}</div>
            </div>
            <button class="hp-toast-close" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        `;

        // Close button handler
        toast.querySelector('.hp-toast-close').addEventListener('click', () => dismiss(toast));

        container.appendChild(toast);

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }

        return toast;
    }

    function dismiss(toast) {
        toast.style.animation = 'fadeOut 0.25s ease forwards';
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
        // Fallback in case animation doesn't fire
        setTimeout(() => toast.remove(), 350);
    }

    // Convenience methods
    const success = (msg, title) => show({ type: 'success', message: msg, title });
    const error   = (msg, title) => show({ type: 'error',   message: msg, title });
    const warning = (msg, title) => show({ type: 'warning', message: msg, title });
    const info    = (msg, title) => show({ type: 'info',    message: msg, title });

    return { show, success, error, warning, info };
})();

/* ── Currency Formatter ──────────────────────────────────── */
/**
 * Formats a number as Indian Rupee with proper lakh/crore notation.
 * E.g.: 125000 → "₹1,25,000.00"
 *       -4500  → "-₹4,500.00"
 */
function formatCurrency(amount, showSign = false) {
    const num = parseFloat(amount) || 0;
    const abs = Math.abs(num);

    const formatted = abs.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const symbol = '₹';
    if (showSign && num < 0) return `−${symbol}${formatted}`;
    if (showSign && num > 0) return `+${symbol}${formatted}`;
    return `${symbol}${formatted}`;
}

/**
 * Formats a percentage.
 * E.g.: 45.123 → "45.1%"
 */
function formatPercent(value, decimals = 1) {
    return parseFloat(value).toFixed(decimals) + '%';
}

/**
 * Returns a relative date string.
 * E.g.: "Today", "Yesterday", "3 days ago", "15 Jul"
 */
function formatRelativeDate(dateString) {
    const date  = new Date(dateString);
    const today = new Date();
    today.setHours(0,0,0,0);
    date.setHours(0,0,0,0);

    const diffDays = Math.round((today - date) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays  <= 6) return `${diffDays} days ago`;

    return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}

/* ── jQuery AJAX Global Setup ────────────────────────────── */
/**
 * This adds the CSRF token to every $.post / $.ajax call automatically.
 * The token is embedded in a <meta> tag by header.php.
 */
$(document).ready(function () {

    // ── Theme ──────────────────────────────────────────────
    HPTheme.init();

    // ── CSRF token setup ──────────────────────────────────
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    $.ajaxSetup({
        // Attach CSRF token to all POST requests
        beforeSend: function (xhr, settings) {
            if (settings.type && settings.type.toUpperCase() === 'POST') {
                if (typeof settings.data === 'string') {
                    settings.data += (settings.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(csrfToken);
                } else if (settings.data instanceof FormData) {
                    settings.data.append('csrf_token', csrfToken);
                } else {
                    settings.data = settings.data || {};
                    settings.data.csrf_token = csrfToken;
                }
            }
        }
    });

    // ── Global AJAX error handler ─────────────────────────
    $(document).ajaxError(function (event, xhr) {
        if (xhr.status === 401) {
            // Session expired — redirect to login
            const resp = xhr.responseJSON;
            const redirect = (resp && resp.redirect) ? resp.redirect : '/HP Expense-Tracker/login.php';
            HPToast.warning('Session expired. Redirecting to login...');
            setTimeout(() => { window.location.href = redirect; }, 1500);
        } else if (xhr.status === 403) {
            HPToast.error('You do not have permission to perform this action.');
        } else if (xhr.status >= 500) {
            HPToast.error('Server error. Please try again later.');
        }
    });

    // ── Sidebar collapse toggle ───────────────────────────
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');

    if (sidebar && toggleBtn) {
        // Restore collapsed state from localStorage
        if (localStorage.getItem('hp_sidebar_collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('hp_sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // ── Theme toggle button ───────────────────────────────
    $('#themeToggleBtn').on('click', function () {
        HPTheme.toggle();
    });

    // ── Mobile menu button → Bootstrap Offcanvas ──────────
    $('#mobileMenuBtn').on('click', function () {
        const offcanvas = document.getElementById('mobileNavOffcanvas');
        if (offcanvas) {
            const bs = bootstrap.Offcanvas.getOrCreateInstance(offcanvas);
            bs.show();
        }
    });

    // ── Confirm delete dialogs ────────────────────────────
    /**
     * Any element with data-confirm="message" and data-delete-url="url"
     * will show a Bootstrap modal confirm before deleting.
     */
    $(document).on('click', '[data-confirm]', function (e) {
        e.preventDefault();
        const msg = $(this).data('confirm') || 'Are you sure?';
        const url = $(this).data('delete-url');
        const onConfirm = $(this).data('on-confirm');

        // Use a simple Bootstrap modal if present, else native confirm
        if ($('#confirmModal').length) {
            $('#confirmModalBody').text(msg);
            $('#confirmModalOk').off('click').on('click', function () {
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                if (url) {
                    $.post(url, {}, function (resp) {
                        if (resp.status === 'success') {
                            HPToast.success(resp.message || 'Deleted successfully.');
                            if (typeof window.refreshCurrentPage === 'function') {
                                window.refreshCurrentPage();
                            }
                        } else {
                            HPToast.error(resp.message || 'Delete failed.');
                        }
                    }, 'json');
                }
                if (onConfirm && typeof window[onConfirm] === 'function') {
                    window[onConfirm]();
                }
            });
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        } else {
            if (window.confirm(msg) && url) {
                $.post(url, {}, function (resp) {
                    if (resp.status === 'success') {
                        HPToast.success(resp.message || 'Deleted successfully.');
                    } else {
                        HPToast.error(resp.message || 'Delete failed.');
                    }
                }, 'json');
            }
        }
    });

    // ── Loading state helpers ─────────────────────────────
    /**
     * Usage:
     *   const restore = HPLoader.btn($('#myBtn'));
     *   // ... async work ...
     *   restore();
     */
    window.HPLoader = {
        btn: function ($btn) {
            const originalHtml = $btn.html();
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Loading...');
            return function () {
                $btn.prop('disabled', false).html(originalHtml);
            };
        }
    };

    // ── Number animation (count-up effect) ────────────────
    /**
     * Animates a numeric element from 0 to the target value.
     * Usage: animateNumber($('#totalBalance'), 27450.00, '₹');
     */
    window.animateNumber = function ($el, target, prefix = '', suffix = '') {
        const duration = 800;   // ms
        const steps    = 40;
        const stepTime = duration / steps;
        let current    = 0;
        const increment = target / steps;

        const timer = setInterval(function () {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            $el.text(prefix + current.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + suffix);
        }, stepTime);
    };

    // ── Tooltip initialisation ────────────────────────────
    // Initialise all Bootstrap tooltips on the page
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

});

/* ── Global: fade-out animation for toasts ───────────────── */
const style = document.createElement('style');
style.textContent = `
@keyframes fadeOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(20px); }
}
`;
document.head.appendChild(style);
