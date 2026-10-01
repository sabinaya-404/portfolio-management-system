# Portfolio Management System

A simple web application for managing Demat accounts, stock holdings, and portfolio information.

This project was created as a BCA college project using procedural PHP, MySQL/MariaDB, HTML, CSS, and vanilla JavaScript. It is intended for local use with XAMPP.

## Features

### User features

- User registration and login
- Logout and session-based authentication
- Password reset
- Multiple Demat account management
- Add, edit, view, and delete holdings
- Purchase price and current market value tracking
- Portfolio profit/loss calculations
- Company directory with search and status filtering
- Published IPO and financial news
- Light and dark theme toggle

### Admin features

Administrators can:

- Manage companies
- Activate or deactivate companies
- Manage IPO and news entries
- Publish or unpublish content
- View and manage user roles
- View portfolio and system reports
- Import news from an RSS feed

## Technology stack

- PHP 8.x
- MySQL or MariaDB
- MySQLi
- HTML5
- CSS3
- Vanilla JavaScript
- Composer
- PHPMailer
- XAMPP

The project does not use a PHP framework or JavaScript framework.

## Security highlights

The application includes:

- Password hashing with PHP's password functions
- Prepared MySQLi statements
- Session hardening settings
- CSRF protection for state-changing forms
- Ownership checks for user Demat accounts and holdings
- Database-backed administrator authorization
- Login rate limiting
- Expiring, hashed password-reset tokens
- Escaped output for values displayed in HTML

This is a college project for local development and demonstration. It should not be treated as a production financial service.

## Project structure

```text
/
├── admin/                  Administrator pages
├── assets/
│   ├── css/                Stylesheets
│   └── js/                 Browser-side JavaScript
├── config/                 Local application configuration
├── cron/                   Background job scripts
├── database/               Local database files
├── demat/                  Demat-related page files
├── includes/               Shared authentication, session, logging, and layout helpers
├── portfolio/              Portfolio-related page files
├── sql/                    SQL scripts and migrations
├── vendor/                 Composer dependencies
├── dashboard.php           User dashboard
├── holdings.php            Holdings and portfolio page
├── my_demat.php            User Demat accounts
├── companies.php           Company directory
├── ipo-news.php            IPO and news listing
├── login.php               Login page
└── register.php            Registration page
```

## Database

The application uses these main tables:

- `users`
- `demat_accounts`
- `holdings`
- `companies`
- `password_resets`
- `ipo_news`
- `rate_limits`

The database stores user accounts, Demat details, holdings, company prices, IPO/news content, password-reset tokens, and login rate-limit records.

The current working copy may contain a local development export at:

```text
database/portfolio_management.sql
```

This file is untracked and is not included in the GitHub repository. A database export or schema must be supplied separately when setting up a fresh clone. The export may also contain sample development data.

## Local setup with XAMPP

### Requirements

- Windows
- XAMPP with Apache and MySQL/MariaDB
- PHP 8.x
- Composer

### Installation

1. Place the project inside the XAMPP web directory:

   ```text
   C:\xampp\htdocs\portfolio-management-system
   ```

2. Start **Apache** and **MySQL** in the XAMPP Control Panel.

3. Create a database named:

   ```text
   portfolio_management
   ```

4. Obtain a database export or schema separately and import it into the database. The GitHub clone does not include `database/portfolio_management.sql`.

5. Copy the database configuration example:

   ```text
   config/database.example.php
   ```

   to:

   ```text
   config/database.php
   ```

6. Set the local database connection values. The usual XAMPP defaults are:

   ```text
   Host:     localhost
   Database: portfolio_management
   Username: root
   Password: empty
   ```

7. For password-reset email configuration, copy:

   ```text
   config/mail.example.php
   ```

   to:

   ```text
   config/mail.php
   ```

   Then enter the required SMTP settings.

8. Install Composer dependencies if necessary:

   ```text
   composer install
   ```

9. Open the application:

   ```text
   http://localhost/portfolio-management-system/
   ```

10. Register a user account.

To test administrator features, change the user's `role` value to `admin` in the local database.

## Local configuration files

These files contain local values and should not be committed:

```text
config/database.php
config/mail.php
database/portfolio_management.sql
.env
```

Example files are provided for configuration reference:

```text
config/database.example.php
config/mail.example.php
.env.example
```

## Development checks

Run PHP syntax validation from the project root:

```text
C:\xampp\php\php.exe -l <file>.php
```

Examples:

```text
C:\xampp\php\php.exe -l login.php
C:\xampp\php\php.exe -l holdings.php
C:\xampp\php\php.exe -l includes\rate_limit.php
```

Composer dependencies can be installed with:

```text
composer install
```

The project does not currently include a formal automated test suite or CI workflow. Browser workflows and database behavior should be tested manually using a local XAMPP installation.

## Development status

The main user, Demat, holdings, company, IPO/news, administrator, and reporting features are implemented.

The project remains under development. RSS automation and some additional validation and workflow testing still need improvement. It is suitable for local demonstration and college project evaluation, but it is not production-ready.
