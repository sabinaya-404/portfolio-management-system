<?php
require_once __DIR__ . "/includes/session.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#006f37">
    <meta name="description" content="Portfolio Manager — Organize Demat accounts, holdings, and saved investment totals in one understandable system. Built for Nepalese investors.">

    <title>Portfolio Manager</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<!-- ================= HEADER ================= -->

<header class="site-header" role="banner">
    <div class="header-inner">
        <a href="#home" class="logo" aria-label="Portfolio Manager home">
            <span class="logo-mark" aria-hidden="true">P</span>
            <span>Portfolio Manager</span>
        </a>

        <nav class="nav" role="navigation" aria-label="Primary navigation">
            <ul class="nav-links" id="siteNav">
                <li><a href="#home" aria-current="page">Home</a></li>
                <li><a href="#features">What you can organize</a></li>
                <li><a href="#how-it-works">How it works</a></li>
                <li><a href="#about">About</a></li>
            </ul>

            <div class="nav-actions">
                <a href="login.php" class="btn">Sign in</a>
                <a href="register.php" class="btn btn-primary">Create account</a>
            </div>
        </nav>

        <button class="menu-toggle" id="menuBtn" type="button" aria-expanded="false" aria-controls="siteNav" aria-label="Toggle navigation">
            <svg class="menu-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <svg class="menu-icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>
</header>

<!-- ================= MAIN CONTENT ================= -->

<main id="main-content" class="landing-page" role="main">

<!-- ================= HERO ================= -->

<section class="hero" id="home" aria-labelledby="hero-heading">
    <div class="container hero-inner">

        <div class="hero-copy">
            <span class="hero-badge">Nepal Investment Ledger</span>

            <h1 id="hero-heading">A clearer record of what you own.</h1>

            <p class="hero-lead">Organize Demat accounts, holdings, and saved investment totals in one understandable system.</p>

            <div class="hero-cta">
                <a href="register.php" class="btn btn-primary btn-block">Create account</a>
                <a href="login.php" class="btn btn-block">Sign in</a>
            </div>

            <div class="hero-trust" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>No live market data — your records only</span>
            </div>
        </div>

        <!-- Ledger Preview Panel -->
        <aside class="ledger-preview" aria-label="Portfolio record preview (illustrative data)">
            <div class="ledger-card">
                <div class="ledger-header">
                    <div class="ledger-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Portfolio Record
                        <span class="ledger-badge">Illustrative data</span>
                    </div>
                    <div class="ledger-dots" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <div class="ledger-body">
                    <div class="ledger-summary">
                        <div class="ledger-summary-item">
                            <span class="ledger-summary-label">Demat Account</span>
                            <span class="ledger-summary-value mono">Main BOID</span>
                        </div>
                        <div class="ledger-summary-item">
                            <span class="ledger-summary-label">Holdings</span>
                            <span class="ledger-summary-value mono">3 records</span>
                        </div>
                        <div class="ledger-summary-item">
                            <span class="ledger-summary-label">Total Invested</span>
                            <span class="ledger-summary-value mono">NPR 2,45,500</span>
                        </div>
                        <div class="ledger-summary-item">
                            <span class="ledger-summary-label">Saved Total</span>
                            <span class="ledger-summary-value mono">NPR 2,45,500</span>
                        </div>
                    </div>

                    <div class="ledger-holdings">
                        <div class="ledger-holdings-header">
                            <span>Company</span>
                            <span>Qty</span>
                            <span>Invested</span>
                        </div>

                        <div class="ledger-row">
                            <span class="ledger-row-name">NABIL</span>
                            <span class="ledger-row-qty mono">20</span>
                            <span class="ledger-row-value mono">NPR 2,00,000</span>
                        </div>

                        <div class="ledger-row">
                            <span class="ledger-row-name">NIFRA</span>
                            <span class="ledger-row-qty mono">50</span>
                            <span class="ledger-row-value mono">NPR 50,000</span>
                        </div>

                        <div class="ledger-row">
                            <span class="ledger-row-name">HIDCL</span>
                            <span class="ledger-row-qty mono">30</span>
                            <span class="ledger-row-value mono">NPR 15,000</span>
                        </div>
                    </div>

                    <div class="ledger-total">
                        <span class="ledger-total-label">Total Invested</span>
                        <span class="ledger-total-value mono">2,45,500<span class="ledger-currency"> NPR</span></span>
                    </div>
                </div>
            </div>
        </aside>

    </div>
</section>

<!-- ================= SECTION: WHAT YOU CAN ORGANIZE ================= -->

<section class="features section" id="features" aria-labelledby="features-heading">
    <div class="container">
        <header class="section-header">
            <h2 id="features-heading">What you can organize</h2>
            <p>Portfolio Manager is a record-keeping system. It does not fetch live prices, calculate returns, or track profit/loss. You enter and save your own numbers.</p>
        </header>

        <div class="feature-grid">
            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h3>Demat Accounts</h3>
                <p>Add and manage multiple Demat accounts with broker details, BOID, and account holder information.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <h3>Holdings & Totals</h3>
                <p>Record stock holdings per Demat with quantity and purchase price. See saved investment totals per account and overall.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h3>IPO / News Review</h3>
                <p>Save IPO applications and news items for later review. Mark status as pending, allotted, or rejected.</p>
            </article>
        </div>
    </div>
</section>

<!-- ================= SECTION: HOW THE RECORD WORKS ================= -->

<section class="how-it-works section" id="how-it-works" aria-labelledby="how-heading">
    <div class="container">
        <header class="section-header">
            <h2 id="how-heading">How the record works</h2>
            <p>Three steps from sign-up to an organized ledger.</p>
        </header>

        <ol class="steps">
            <li class="step" data-step="1">
                <div class="step-content">
                    <h3>Create account</h3>
                    <p>Register with your email. Verify and sign in to access your private ledger.</p>
                </div>
            </li>

            <li class="step" data-step="2">
                <div class="step-content">
                    <h3>Add Demat accounts</h3>
                    <p>Enter each Demat's broker, BOID, and holder name. One login, many accounts.</p>
                </div>
            </li>

            <li class="step" data-step="3">
                <div class="step-content">
                    <h3>Record holdings</h3>
                    <p>Add stock symbols, quantities, and purchase prices. Totals calculate automatically from your entries.</p>
                </div>
            </li>
        </ol>
    </div>
</section>

<!-- ================= SECTION: PROJECT CONTEXT ================= -->

<section class="about section" id="about" aria-labelledby="about-heading">
    <div class="container about-grid">
        <div class="about-content">
            <h2 id="about-heading">Project context</h2>

            <p class="about-lead">Portfolio Manager is a BCA academic project built with PHP 8, MariaDB, and vanilla JavaScript. It runs on a standard LAMP stack (XAMPP).</p>

            <div class="about-detail">
                <h3>What this project is</h3>
                <ul>
                    <li>A personal investment ledger for Nepali Demat holders</li>
                    <li>Session-based auth with CSRF protection and login rate limiting</li>
                    <li>Admin panel for user/account oversight</li>
                    <li>Server-rendered pages with minimal client-side JS</li>
                </ul>
            </div>

            <div class="about-detail">
                <h3>What this project is not</h3>
                <ul>
                    <li>No live market data, price feeds, or real-time quotes</li>
                    <li>No profit/loss, return, or gain/loss calculations</li>
                    <li>No brokerage integration or trade execution</li>
                    <li>No financial advice or portfolio optimization</li>
                </ul>
            </div>
        </div>

        <aside class="about-specs" aria-label="Technical specifications">
            <div class="spec-card">
                <span class="spec-label">Stack</span>
                <span class="spec-value">PHP 8 / MariaDB / vanilla JS</span>
            </div>
            <div class="spec-card">
                <span class="spec-label">Auth</span>
                <span class="spec-value">Session + CSRF + rate limiting</span>
            </div>
            <div class="spec-card">
                <span class="spec-label">Theme</span>
                <span class="spec-value">Light / Dark (system or manual)</span>
            </div>
            <div class="spec-card">
                <span class="spec-label">Accessibility</span>
                <span class="spec-value">WCAG 2.1 AA target</span>
            </div>
            <div class="spec-card">
                <span class="spec-label">License</span>
                <span class="spec-value">Academic project</span>
            </div>
        </aside>
    </div>
</section>

<!-- ================= CTA SECTION ================= -->

<section class="cta section" id="get-started" aria-labelledby="cta-heading">
    <div class="container">
        <div class="cta-card">
            <h2 id="cta-heading">Ready to organize your records?</h2>
            <p>Create your Portfolio Manager account and start building your investment ledger today.</p>
            <div class="cta-actions">
                <a href="register.php" class="btn btn-primary btn-block">Create account</a>
                <a href="login.php" class="btn btn-block">Sign in</a>
            </div>
        </div>
    </div>
</section>

</main>

<!-- ================= FOOTER ================= -->

<footer class="site-footer" role="contentinfo">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo-mark" aria-hidden="true">P</span>
            <span>Portfolio Manager</span>
        </div>

        <p class="footer-copyright">&copy; 2026 Portfolio Manager. All rights reserved.</p>

        <ul class="footer-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">What you can organize</a></li>
            <li><a href="#how-it-works">How it works</a></li>
            <li><a href="#about">About</a></li>
        </ul>
    </div>
</footer>

<script src="assets/js/landing.js" defer></script>

</body>
</html>