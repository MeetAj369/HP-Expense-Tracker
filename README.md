# 💰 HP Personal Expense Tracker — Financial Ledger, Budget Intelligence & Analytics

[![Live Demo](https://img.shields.io/badge/Live_Hosted_Platform-HP_Expense_Tracker-22c55e?style=for-the-badge&logo=googlechrome&logoColor=white)](https://meetaj369.github.io/HP-Expense-Tracker/)
[![PHP](https://img.shields.io/badge/PHP-7.4%20--%208.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-hp__expense__tracker-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-Charts%20&%20Ledger-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![CSS3](https://img.shields.io/badge/Styling-Custom%20Responsive%20CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge)]()
[![Author](https://img.shields.io/badge/Developer-Meet%20Chauhan-6366f1?style=for-the-badge&logo=github)](https://github.com/MeetAj369)

> 🌐 **Live Interactive Web App**: [https://meetaj369.github.io/HP-Expense-Tracker/](https://meetaj369.github.io/HP-Expense-Tracker/)  
> Access the live ledger, budget meters, and spending analytics on any browser, mobile phone (Android / iOS), or PC worldwide!

---

## 📌 Executive Summary

**HP Personal Expense Tracker** is a comprehensive financial intelligence and personal accounting web application engineered by **Meet Chauhan**. Designed to replace manual spreadsheets, it empowers users to monitor income streams, categorize cash outflows, enforce category budget limits, track recurring subscriptions, and visualize financial growth across multiple bank accounts and digital wallets.

---

## ✨ Key Capabilities & Modules

### 1. 📊 Interactive Financial Dashboard
* Real-time financial health KPIs: **Total Net Worth**, **Monthly Income**, **Monthly Expenses**, and **Net Monthly Savings Rate**.
* Dynamic date range filtering: *This Month*, *Last Month*, *This Quarter*, and *Year to Date*.
* Income vs. Expense visual comparison and interactive category spending breakdown.

### 2. 💳 Multi-Account & Wallet Management
* Track balances across diverse account types: **Cash in Hand**, **Savings Bank Accounts**, **Digital Wallets (Paytm/Google Pay)**, and **Credit Cards**.
* Automatic balance reconciliation on every recorded transaction.

### 3. 🎯 Budget Intelligence & Threshold Alerts
* Set custom monthly budget caps per spending category (Food & Dining, Rent & Utilities, Transport, Shopping, Entertainment).
* Visual progress meters warning users when approaching 80% and 100% of budgeted limits to prevent overspending.

### 4. 🔄 Recurring Bills & Subscriptions
* Track active subscriptions (Netflix, Spotify, GitHub Pro, Cloud Hosting) with automated renewal countdowns and billing cycle alerts.

### 5. 🎯 Savings Goals Tracker
* Set financial targets with target dates (Emergency Fund, New Laptop, Vacation).
* Automatic calculation of completion percentages and monthly savings velocity.

### 6. 📜 Complete Transaction Ledger & Export
* Comprehensive filterable transaction history by date, category, account, and payment method.
* Export transactions to CSV and printable financial statement format.

---

## 🗄️ Database Architecture (`database/schema.sql`)

The system is powered by a normalized InnoDB schema comprising **11 tables**:

| Table | Purpose |
|---|---|
| `users` | User credentials with BCrypt hashing and default currency (INR) |
| `accounts` | Bank accounts, cash balances, credit limits, and digital wallets |
| `categories` | Spending and income classification taxonomy (icon, color, type) |
| `payment_methods` | Payment channels (UPI, Net Banking, Debit Card, Cash) |
| `transactions` | Core financial ledger recording amounts, dates, notes, and receipts |
| `budgets` | Category budget caps, allocated limits, and consumption metrics |
| `recurring_transactions` | Scheduled monthly/weekly bills and automated transaction logs |
| `subscriptions` | Active recurring software & utility subscriptions |
| `savings_goals` | Long-term target funds with goal progress meters |
| `notifications` | Over-budget alerts, subscription due reminders, and milestone notifications |
| `transaction_splits` | Multi-category bill splitting for shared expenses |

---

## 🚀 Installation & Local Setup

### Prerequisites
* **Web Server**: Apache (via WAMP, XAMPP, or LAMP)
* **PHP**: Version 7.4 to 8.2+ with PDO MySQL extension
* **Database**: MySQL 5.7+ / MariaDB 10.3+

### Step-by-Step Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/MeetAj369/HP-Expense-Tracker.git
   ```
   Place the project folder inside your web server's document root (`C:\wamp\www\HP Expense-Tracker` or `C:\xampp\htdocs\HP Expense-Tracker`).

2. **Import Database Schema**:
   * Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Create a new database named `hp_expense_tracker`.
   * Import `database/schema.sql`, then optionally import `database/seed.sql` for sample data.

3. **Configure Database Settings**:
   * Verify credentials in `config/db.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'hp_expense_tracker');
     ```

4. **Run the Application**:
   * Open in your browser:
     ```
     http://localhost/HP%20Expense-Tracker/
     ```

---

## 👨‍💻 Developer & Author

**Meet Chauhan**
* **Portfolio**: [meetaj369.github.io/Meet-Portfolio](https://meetaj369.github.io/Meet-Portfolio/)
* **GitHub**: [@MeetAj369](https://github.com/MeetAj369)
* **LinkedIn**: [linkedin.com/in/meet-chauhan-41b183375](https://linkedin.com/in/meet-chauhan-41b183375)
* **Education**: BCA Undergraduate, Charutar Vidya Mandal University (CVMU), Anand, Gujarat

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
