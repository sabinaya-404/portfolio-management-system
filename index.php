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

    <title>PORTFOLIO MANAGER</title>

    <link rel="stylesheet" href="assets/css/landing.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->

<header class="navbar">
    <div class="nav-container">

        <a href="#home" class="logo">
            <span class="logo-icon">↗</span>
            <span>PORTFOLIO MANAGER</span>
        </a>

        <nav class="nav-links" id="siteNav" aria-label="Primary navigation">
            <a href="#home" class="active">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#about">About</a>
        </nav>

        <div class="nav-actions">
            <a href="login.php" class="login">Log in</a>
            <a href="register.php" class="btn btn-small">
                Get Started <span>→</span>
            </a>
           <div class="avatar">
    S
</div>

        <button class="menu-btn" id="menuBtn" type="button" aria-expanded="false" aria-controls="siteNav">
            <span class="menu-icon" aria-hidden="true">☰</span>
            <span class="sr-only">Toggle navigation</span>
        </button>

        </div>
    </div>
</header>


<!-- ================= HERO ================= -->

<section class="hero" id="home">

    <div class="hero-container">

        <!-- LEFT SIDE -->

        <div class="hero-left">

            <div class="hero-heading">
                <h1>
                    Manage Your<br>
                    Investments.
                </h1>

                <h2>All in One Place.</h2>
            </div>

            <p class="hero-description">
                Track your portfolio, manage multiple Demat accounts,
                monitor investment activities and keep your investments
                organized through one simple platform.
            </p>

            <!-- Laptop illustration -->

            <img src="assets/images/laptop.png"  class="laptop-image" alt="Laptop-image">

            <div class="hero-buttons">
                <a href="register.php" class="btn">
                    Get Started <span>→</span>
                </a>

                <a href="#features" class="btn btn-outline">
                    Explore Features
                </a>
            </div>

        </div>


        <!-- RIGHT SIDE -->

        <div class="hero-right">

            <!-- Portfolio Card -->

            <div class="dashboard-card">

                <div class="card-header">
                    <div class="profile-title">
                        <span class="round-icon">PM</span>
                        <strong>My Portfolio</strong>
                    </div>

                    <span class="today">▣ Today</span>
                </div>

                <div class="portfolio-value-box">

                    <div>
                        <small>TOTAL PORTFOLIO VALUE</small>
                        <h2>NPR 2,78,450<span>.00</span></h2>
                    </div>

                    <div class="profit-badge">
                        +13.40% ↗
                    </div>

                </div>


                <!-- Chart -->

                <div class="chart-container">

                    <svg viewBox="0 0 500 160" preserveAspectRatio="none">

                        <path
                            d="M0 140
                            C40 130 50 105 85 115
                            C120 125 135 80 170 95
                            C205 110 220 70 250 78
                            C285 88 300 48 330 60
                            C360 70 380 28 405 42
                            C435 60 450 15 500 25
                            L500 160 L0 160 Z"
                            fill="var(--primary-light)"
                            fill-opacity=".72"
                        />

                        <path
                            d="M0 140
                            C40 130 50 105 85 115
                            C120 125 135 80 170 95
                            C205 110 220 70 250 78
                            C285 88 300 48 330 60
                            C360 70 380 28 405 42
                            C435 60 450 15 500 25"
                            fill="none"
                            stroke="var(--primary)"
                            stroke-width="3"
                        />

                    </svg>

                </div>


                <!-- Holdings -->

                <div class="table-title">
                    <span>TOP HOLDINGS</span>
                    <span>CURRENT VALUE</span>
                </div>

                <div class="holding-row">

                    <div class="holding-name">
                        <span class="stock-icon">▥</span>

                        <div>
                            <strong>NABIL</strong>
                            <small>20 Shares @ NPR 100</small>
                        </div>
                    </div>

                    <strong>NPR 28,000</strong>

                </div>


                <div class="holding-row">

                    <div class="holding-name">
                        <span class="stock-icon">▦</span>

                        <div>
                            <strong>NIFRA</strong>
                            <small>50 Shares @ NPR 100</small>
                        </div>
                    </div>

                    <strong>NPR 32,500</strong>

                </div>


                <div class="holding-row">

                    <div class="holding-name">
                        <span class="stock-icon">◉</span>

                        <div>
                            <strong>HIDCL</strong>
                            <small>30 Shares @ NPR 100</small>
                        </div>
                    </div>

                    <strong>NPR 15,200</strong>

                </div>

            </div>


            <!-- Demat Card -->

            <div class="dashboard-card demat-card">

                <div class="card-header">

                    <div class="profile-title">
                        <span class="round-icon">PM</span>
                        <strong>Demat Accounts</strong>
                    </div>

                    <a href="login.php">View All →</a>

                </div>


                <div class="demat-total">

                    <small>TOTAL MANAGED DEMAT ACCOUNTS</small>

                    <h2>16</h2>

                    <a href="login.php">View All →</a>

                </div>


                <div class="table-title">
                    <span>TOP DEMATS</span>
                    <span>CURRENT VALUE</span>
                </div>


                <div class="holding-row">

                    <div class="holding-name">

                        <span class="stock-icon">PT</span>

                        <div>
                            <strong>Pawan Thapa</strong>
                            <small>20 Shares</small>
                        </div>

                    </div>

                    <strong>NPR 210,000</strong>

                </div>


                <div class="holding-row">

                    <div class="holding-name">

                        <span class="stock-icon">BS</span>

                        <div>
                            <strong>Balen Shah</strong>
                            <small>19 Shares</small>
                        </div>

                    </div>

                    <strong>NPR 200,500</strong>

                </div>


                <div class="holding-row">

                    <div class="holding-name">

                        <span class="stock-icon">SK</span>

                        <div>
                            <strong>Sabinaya Khadka</strong>
                            <small>23 Shares</small>
                        </div>

                    </div>

                    <strong>NPR 99,750</strong>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= FEATURES ================= -->

<section class="features-section" id="features">

    <div class="section-heading">

        <span class="eyebrow">
            OUR POWERFUL FEATURES
        </span>

        <h2>
            Everything You Need to<br>
            <span>Manage Your Portfolio</span>
        </h2>

        <p>
            Keep your investments, Demat accounts and IPO applications
            organized in one convenient platform.
        </p>

    </div>


    <div class="feature-grid">

        <div class="feature-card">

            <div class="feature-icon">⌁</div>

            <h3>Portfolio Management</h3>

            <p>
                Monitor your investments and view your overall
                portfolio from a single dashboard.
            </p>

            <a href="login.php">Learn more →</a>

        </div>


        <div class="feature-card">

            <div class="feature-icon">▣</div>

            <h3>Demat Management</h3>

            <p>
                Manage multiple Demat accounts and keep your
                investment information organized.
            </p>

            <a href="login.php">Learn more →</a>

        </div>


        <div class="feature-card">

            <div class="feature-icon">▤</div>

            <h3>IPO Management</h3>

            <p>
                Stay informed about IPOs and be ready to
                invest in every single day.
            </p>

            <a href="login.php">Learn more →</a>

        </div>

    </div>

</section>


<!-- ================= CENTRALIZED PLATFORM ================= -->

<section class="central-section">

    <div class="central-container">

        <div class="central-text">

            <span class="eyebrow">
                ONE CENTRALIZED PLATFORM
            </span>

            <h2>
                See Your Investments<br>
                Clearly
            </h2>

            <p>
                Bring your Demat accounts, IPO applications and
                portfolio information together in one centralized dashboard.
            </p>


            <ul class="check-list">

                <li>Manage multiple Demat accounts</li>
                <li>Track investment holdings</li>
                <li>Monitor IPO applications</li>
                <li>View overall portfolio</li>

            </ul>


            <a href="login.php" class="btn">
                Explore Dashboard →
            </a>

        </div>


        <!-- Dashboard -->

        <div class="big-dashboard">

            <div class="stats-grid">

                <div>
                    <small>TOTAL PORTFOLIO</small>
                    <strong>NPR 2,78,450</strong>
                </div>

                <div>
                    <small>TOTAL INVESTED</small>
                    <strong>NPR 2,45,500</strong>
                </div>

                <div>
                    <small>OVERALL GAIN</small>
                    <strong class="green">+13.4%</strong>
                </div>

                <div>
                    <small>ACTIVE IPOS</small>
                    <strong>2 Applied</strong>
                </div>

            </div>


            <div class="investment-table">

                <div class="investment-header">

                    <span>STOCK / SCRIP</span>
                    <span>UNITS</span>
                    <span>CURRENT PRICE</span>
                    <span>TOTAL VALUE</span>
                    <span>RETURN</span>

                </div>


                <div class="investment-row">

                    <strong>NABIL</strong>
                    <span>20</span>
                    <span>NPR 1,400</span>
                    <span>NPR 28,000</span>
                    <span class="green">+8.2%</span>

                </div>


                <div class="investment-row">

                    <strong>NIFRA</strong>
                    <span>50</span>
                    <span>NPR 650</span>
                    <span>NPR 32,500</span>
                    <span class="green">+16.0%</span>

                </div>


                <div class="investment-row">

                    <strong>HIDCL</strong>
                    <span>30</span>
                    <span>NPR 506</span>
                    <span>NPR 15,180</span>
                    <span class="green">+11.4%</span>

                </div>

            </div>


            <div class="casba">

                <div class="casba-heading">

                    <strong>Recent CASBA Applications</strong>

                    <span>C-ASBA Verified</span>

                </div>


                <div class="casba-row">

                    <span>
                        <i class="yellow-dot"></i>
                        Upper Trishuli 3B Hydropower
                    </span>

                    <span class="pending">
                        Pending Allotment
                    </span>

                </div>


                <div class="casba-row">

                    <span>
                        <i class="green-dot"></i>
                        Reliable Nepal Life Insurance
                    </span>

                    <span class="allotted">
                        Allotted (10 Units)
                    </span>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="how-section" id="how-it-works">

    <div class="section-heading">

        <span class="eyebrow">
            HOW IT WORKS?
        </span>

        <h2>
            Start Managing Your Investments<br>
            Easily
        </h2>

    </div>


    <div class="steps">

        <div class="step-card">

            <div class="step-number">01</div>

            <h3>Add Your Demat Accounts</h3>

            <p>
                Register and create your Portfolio Master account
                and register your multiple Demat accounts.
            </p>

        </div>


        <div class="step-card">

            <div class="step-number">02</div>

            <h3>Add Your Stock Holdings</h3>

            <p>
                Add your stock holdings in each Demat and organize
                your whole investment information.
            </p>

        </div>


        <div class="step-card">

            <div class="step-number">03</div>

            <h3>Track Your Portfolio</h3>

            <p>
                Monitor your holdings, IPO applications and overall
                portfolio health from one master dashboard.
            </p>

        </div>

    </div>

</section>


<!-- ================= ORGANIZED ================= -->

<section class="organized-section" id="about">

    <div class="organized-container">

        <div class="organized-text">

            <h2>
                Your Investments,<br>
                <span>Organized.</span>
            </h2>

            <p>
                InvestMaster simplifies investment portfolio management
                by bringing Demat accounts, IPO applications and portfolio
                information together in one centralized system.
            </p>

            <a href="register.php" class="btn">
                Start Managing →
            </a>

        </div>


        <div class="benefits">

            <div class="benefit">

                <div class="benefit-icon">↻</div>

                <div>
                    <h3>01 Simple</h3>

                    <p>
                        Easy-to-use interface for managing investments
                        without cumbersome spreadsheets.
                    </p>
                </div>

            </div>


            <div class="benefit">

                <div class="benefit-icon">⌘</div>

                <div>
                    <h3>02 Centralized</h3>

                    <p>
                        Keep multiple Demat accounts in one place
                        with unified allocation reports.
                    </p>
                </div>

            </div>


            <div class="benefit">

                <div class="benefit-icon">▣</div>

                <div>
                    <h3>03 Organized</h3>

                    <p>
                        View your investment information clearly with
                        clean tables and real-time summaries.
                    </p>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta-section" id="get-started">

    <div class="cta-content">

        <h2>
            Ready to Organize Your<br>
            Investments?
        </h2>

        <p>
            Start managing your portfolio with Portfolio Managers.
        </p>

        <a href="register.php" class="btn cta-btn">
            Get Started →
        </a>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer class="footer">

    <div class="footer-top">

        <div class="footer-brand">

            <div class="logo footer-logo">

                <span class="logo-icon">↗</span>

                <span>Portfolio Manager</span>

            </div>

            <p>
                Simple and centralized investment portfolio management
                for Nepalese investors.
            </p>

        </div>


        <div class="footer-links">

            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#about">About</a>

        </div>

    </div>


    <div class="footer-bottom">

        <span>
            © 2026 Portfolio Manager. All rights reserved.
        </span>

        <span>
            Designed for Nepalese IPO investors & BCA College Project.
        </span>

    </div>

</footer>

<script src="assets/js/landing.js" defer></script>

</body>
</html>
