-- ============================================================
-- HP Personal Expense Tracker — Database Schema
-- Version 2.0
-- ============================================================
-- Run this file once to set up all tables.
-- All money columns use DECIMAL(12,2) for precision.
-- All tables use InnoDB for foreign key support.
-- UTF-8 (utf8mb4) supports all Unicode characters.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hp_expense_tracker`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hp_expense_tracker`;

-- ── 1. USERS ──────────────────────────────────────────────
-- Stores registered user accounts.
-- password_hash stores a bcrypt hash, never plaintext.
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)     NOT NULL,
    `email`         VARCHAR(150)     NOT NULL,
    `password_hash` VARCHAR(255)     NOT NULL,
    `avatar`        VARCHAR(255)         NULL DEFAULT NULL,
    `currency`      CHAR(3)          NOT NULL DEFAULT 'INR',
    `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. ACCOUNTS / WALLETS ─────────────────────────────────
-- Represents cash, bank accounts, wallets, credit cards, etc.
-- current_balance is kept in sync whenever a transaction is added/edited/deleted.
-- Opening balance is recorded separately for audit history.
CREATE TABLE IF NOT EXISTS `accounts` (
    `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED     NOT NULL,
    `name`             VARCHAR(100)     NOT NULL,
    `type`             ENUM('cash','bank','credit','debit','wallet','other') NOT NULL DEFAULT 'bank',
    `opening_balance`  DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `current_balance`  DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `currency`         CHAR(3)          NOT NULL DEFAULT 'INR',
    `color`            VARCHAR(20)          NULL DEFAULT '#4c6fff',
    `icon`             VARCHAR(50)          NULL DEFAULT 'bi-wallet2',
    `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_accounts_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. CATEGORIES ─────────────────────────────────────────
-- System defaults have user_id = NULL and is_default = 1.
-- User-created categories have their user_id set.
-- Shared queries: WHERE (user_id = ? OR user_id IS NULL)
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED         NULL DEFAULT NULL,
    `name`       VARCHAR(100)     NOT NULL,
    `type`       ENUM('expense','income','both') NOT NULL DEFAULT 'expense',
    `icon`       VARCHAR(50)          NULL DEFAULT 'bi-tag',
    `color`      VARCHAR(20)          NULL DEFAULT '#6c757d',
    `is_default` TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`   (`user_id`),
    KEY `idx_type`      (`type`),
    CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. PAYMENT METHODS ────────────────────────────────────
-- System defaults have user_id = NULL.
-- Users can add custom payment methods.
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED         NULL DEFAULT NULL,
    `name`       VARCHAR(100)     NOT NULL,
    `icon`       VARCHAR(50)          NULL DEFAULT 'bi-credit-card',
    `is_default` TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_pmethods_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. TRANSACTIONS ───────────────────────────────────────
-- The core table. Stores expenses, income, and transfers.
-- Transfers use BOTH account_id (from) and to_account_id (to).
-- Transfers must NOT be included in income or expense totals.
-- Authoritative calculation rule:
--   Total Expense = SUM(amount) WHERE type='expense' AND user_id=?
--   Total Income  = SUM(amount) WHERE type='income'  AND user_id=?
--   Balance       = Total Income - Total Expense
CREATE TABLE IF NOT EXISTS `transactions` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED     NOT NULL,
    `account_id`        INT UNSIGNED         NULL DEFAULT NULL,
    `to_account_id`     INT UNSIGNED         NULL DEFAULT NULL,  -- Transfers only
    `category_id`       INT UNSIGNED         NULL DEFAULT NULL,
    `payment_method_id` INT UNSIGNED         NULL DEFAULT NULL,
    `type`              ENUM('expense','income','transfer') NOT NULL,
    `title`             VARCHAR(200)     NOT NULL,
    `amount`            DECIMAL(12,2)    NOT NULL,
    `transaction_date`  DATE             NOT NULL,
    `merchant`          VARCHAR(200)         NULL DEFAULT NULL,
    `note`              TEXT                 NULL DEFAULT NULL,
    `receipt_path`      VARCHAR(500)         NULL DEFAULT NULL,
    `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`          (`user_id`),
    KEY `idx_type`             (`type`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_account_id`       (`account_id`),
    KEY `idx_category_id`      (`category_id`),
    CONSTRAINT `fk_tx_user`       FOREIGN KEY (`user_id`)           REFERENCES `users`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_tx_account`    FOREIGN KEY (`account_id`)        REFERENCES `accounts`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_tx_to_account` FOREIGN KEY (`to_account_id`)     REFERENCES `accounts`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_tx_category`   FOREIGN KEY (`category_id`)       REFERENCES `categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tx_pmethod`    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. BUDGETS ────────────────────────────────────────────
-- Monthly budgets. category_id = NULL means an overall monthly budget.
-- The UNIQUE constraint prevents duplicate budgets per user/category/month/year.
CREATE TABLE IF NOT EXISTS `budgets` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED     NOT NULL,
    `category_id` INT UNSIGNED         NULL DEFAULT NULL,
    `amount`      DECIMAL(12,2)    NOT NULL,
    `month`       TINYINT UNSIGNED NOT NULL,    -- 1–12
    `year`        SMALLINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_budget` (`user_id`, `category_id`, `month`, `year`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_budgets_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. RECURRING TRANSACTIONS ─────────────────────────────
-- Templates for automatically-repeating income or expenses.
-- next_date tells the system (or user) when the next occurrence is due.
CREATE TABLE IF NOT EXISTS `recurring_transactions` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED     NOT NULL,
    `account_id`        INT UNSIGNED         NULL DEFAULT NULL,
    `category_id`       INT UNSIGNED         NULL DEFAULT NULL,
    `payment_method_id` INT UNSIGNED         NULL DEFAULT NULL,
    `type`              ENUM('expense','income') NOT NULL,
    `title`             VARCHAR(200)     NOT NULL,
    `amount`            DECIMAL(12,2)    NOT NULL,
    `frequency`         ENUM('daily','weekly','monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
    `start_date`        DATE             NOT NULL,
    `next_date`         DATE             NOT NULL,
    `end_date`          DATE                 NULL DEFAULT NULL,
    `is_active`         TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`  (`user_id`),
    KEY `idx_next_date` (`next_date`),
    KEY `idx_is_active` (`is_active`),
    CONSTRAINT `fk_recur_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. SUBSCRIPTIONS ──────────────────────────────────────
-- Dedicated subscription tracking (Netflix, Spotify, etc.)
-- Separate from recurring transactions for a dedicated Subscription Center view.
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`            INT UNSIGNED     NOT NULL,
    `account_id`         INT UNSIGNED         NULL DEFAULT NULL,
    `category_id`        INT UNSIGNED         NULL DEFAULT NULL,
    `service_name`       VARCHAR(200)     NOT NULL,
    `amount`             DECIMAL(12,2)    NOT NULL,
    `billing_cycle`      ENUM('weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
    `next_billing_date`  DATE             NOT NULL,
    `status`             ENUM('active','paused','cancelled') NOT NULL DEFAULT 'active',
    `created_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`          (`user_id`),
    KEY `idx_next_billing_date` (`next_billing_date`),
    CONSTRAINT `fk_subs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 9. SAVINGS GOALS ──────────────────────────────────────
-- Tracks progress toward financial goals like Emergency Fund, Phone, Travel, etc.
-- current_amount is updated manually by the user.
CREATE TABLE IF NOT EXISTS `savings_goals` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED     NOT NULL,
    `name`           VARCHAR(200)     NOT NULL,
    `target_amount`  DECIMAL(12,2)    NOT NULL,
    `current_amount` DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `target_date`    DATE                 NULL DEFAULT NULL,
    `icon`           VARCHAR(50)          NULL DEFAULT 'bi-piggy-bank',
    `color`          VARCHAR(20)          NULL DEFAULT '#4c6fff',
    `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 10. NOTIFICATIONS ─────────────────────────────────────
-- In-app notifications for budget alerts, due payments, etc.
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED     NOT NULL,
    `title`      VARCHAR(200)     NOT NULL,
    `message`    TEXT             NOT NULL,
    `type`       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `is_read`    TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_read` (`is_read`),
    CONSTRAINT `fk_notifs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 11. TRANSACTION SPLITS (optional, Phase 20) ───────────
-- Allows splitting an expense among multiple people.
CREATE TABLE IF NOT EXISTS `transaction_splits` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `transaction_id` INT UNSIGNED     NOT NULL,
    `person_name`    VARCHAR(100)     NOT NULL,
    `amount`         DECIMAL(12,2)    NOT NULL,
    `is_paid`        TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`),
    CONSTRAINT `fk_splits_tx` FOREIGN KEY (`transaction_id`)
        REFERENCES `transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
