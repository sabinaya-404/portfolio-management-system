# Portfolio Management System

A small procedural PHP 8.x and MySQL/MariaDB application for organizing user-owned Demat accounts and holdings. It is designed for local development with XAMPP and uses MySQLi, server-rendered HTML, CSS, and vanilla JavaScript.

## Implemented scope

- User registration, login, logout, session protection, CSRF protection, and password reset tokens.
- Multiple user-owned Demat accounts with add, edit, list, and delete workflows.
- Holdings per Demat account with ownership checks, add/edit/delete actions, and calculated market value.
- Authenticated company directory with search and status filtering.
- Server-authorized administrator dashboard and company management, including activation/deactivation without deleting holdings.
- Administrator IPO/news management with published-only authenticated user viewing.
- Basic administrator user role management with last-admin protection.
- Read-only current-state portfolio reports.
- Responsive shared layout with accessible navigation, alerts, tables, skip links, and light/dark theme support.

## Database

Import `database/portfolio_management.sql` into MySQL or MariaDB, then copy `config/database.example.php` to `config/database.php` and set the local connection values. The schema contains `users`, `demat_accounts`, `holdings`, `companies`, `password_resets`, and `ipo_news`; foreign keys and unique constraints preserve ownership and data integrity.

The SQL dump includes one minimal `ipo_news` table. It stores the entry type (`ipo` or `news`), title, content, publication date, optional source URL, publication status, administrator creator, and timestamps. Its `created_by` foreign key references `users.id`; no separate editorial, notification, or external-content tables are used.

## Local development

1. Start Apache and MySQL in XAMPP.
2. Import the SQL dump and configure the database connection.
3. Open `http://localhost/portfolio-management-system/`.
4. Create a user account. To test administrator authorization, set that user's existing `role` column to `admin` directly in the database.

Validate PHP syntax from the repository root with:

```text
C:\xampp\php\php.exe -l <file>.php
```
