-- ============================================================
-- HP Personal Expense Tracker — Default Seed Data
-- ============================================================
-- These are system-wide defaults (user_id = NULL).
-- They appear for ALL users, alongside their custom categories.
-- INSERT IGNORE prevents errors on re-run.
-- ============================================================

USE `hp_expense_tracker`;

-- ── Default Expense Categories ────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `user_id`, `name`, `type`, `icon`, `color`, `is_default`) VALUES
(1,  NULL, 'Food',          'expense', 'bi-cup-hot',            '#ef4444', 1),
(2,  NULL, 'Transport',     'expense', 'bi-car-front',          '#f97316', 1),
(3,  NULL, 'Shopping',      'expense', 'bi-bag',                '#8b5cf6', 1),
(4,  NULL, 'Bills',         'expense', 'bi-receipt',            '#0ea5e9', 1),
(5,  NULL, 'Education',     'expense', 'bi-book',               '#6366f1', 1),
(6,  NULL, 'Health',        'expense', 'bi-heart-pulse',        '#ec4899', 1),
(7,  NULL, 'Entertainment', 'expense', 'bi-film',               '#f59e0b', 1),
(8,  NULL, 'Rent',          'expense', 'bi-house',              '#64748b', 1),
(9,  NULL, 'Groceries',     'expense', 'bi-cart',               '#10b981', 1),
(10, NULL, 'Travel',        'expense', 'bi-airplane',           '#0ea5e9', 1),
(11, NULL, 'Subscriptions', 'expense', 'bi-tv',                 '#7c3aed', 1),
(12, NULL, 'Personal Care', 'expense', 'bi-person-heart',       '#db2777', 1),
(13, NULL, 'Other',         'expense', 'bi-three-dots',         '#6c757d', 1);

-- ── Default Income Categories ─────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `user_id`, `name`, `type`, `icon`, `color`, `is_default`) VALUES
(14, NULL, 'Salary',        'income',  'bi-briefcase',          '#10b981', 1),
(15, NULL, 'Freelance',     'income',  'bi-laptop',             '#0ea5e9', 1),
(16, NULL, 'Business',      'income',  'bi-shop',               '#f59e0b', 1),
(17, NULL, 'Gift',          'income',  'bi-gift',               '#ec4899', 1),
(18, NULL, 'Interest',      'income',  'bi-bank',               '#6366f1', 1),
(19, NULL, 'Allowance',     'income',  'bi-cash-stack',         '#8b5cf6', 1),
(20, NULL, 'Other Income',  'income',  'bi-plus-circle',        '#6c757d', 1);

-- ── Default Payment Methods ───────────────────────────────
INSERT IGNORE INTO `payment_methods` (`id`, `user_id`, `name`, `icon`, `is_default`) VALUES
(1,  NULL, 'Cash',           'bi-cash',                  1),
(2,  NULL, 'UPI',            'bi-phone',                 1),
(3,  NULL, 'Debit Card',     'bi-credit-card',           1),
(4,  NULL, 'Credit Card',    'bi-credit-card-2-front',   1),
(5,  NULL, 'Bank Transfer',  'bi-bank',                  1),
(6,  NULL, 'Net Banking',    'bi-globe',                 1),
(7,  NULL, 'Mobile Wallet',  'bi-wallet',                1),
(8,  NULL, 'Cheque',         'bi-envelope',              1),
(9,  NULL, 'Auto Debit',     'bi-arrow-repeat',          1),
(10, NULL, 'Other',          'bi-three-dots',            1);
