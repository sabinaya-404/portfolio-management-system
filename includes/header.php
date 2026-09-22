<?php
$page_title = $page_title ?? 'Portfolio Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f6f9">
    <title><?= htmlspecialchars($page_title) ?> | Portfolio Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Instant Theme Loader (Prevents White Flash in Dark Mode) -->
    <script>
        try {
            const savedTheme = localStorage.getItem("portfolio_theme");
            if (savedTheme === "dark" || savedTheme === "light") {
                document.documentElement.setAttribute("data-theme", savedTheme);
            }
        } catch (error) {
            // Use the default light theme when storage is unavailable.
        }
    </script>
</head>
<body class="dashboard-page">

<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="dashboard-layout">

    <!-- Reusable Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main id="main-content" class="dashboard-main">

        <!-- Top Header Bar -->
        <header class="dashboard-header">
            <div>
                <p class="page-label"><?= htmlspecialchars($page_category ?? 'OVERVIEW') ?></p>
                <h1><?= htmlspecialchars($page_heading ?? $page_title) ?></h1>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <!-- Dark / Light Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle Theme">
                    <svg id="themeIconSun" aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg id="themeIconMoon" aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span id="themeToggleText">Dark</span>
                </button>

                <!-- User Profile Pill -->
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user_name, 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <strong><?= htmlspecialchars($current_user_name) ?></strong>
                        <span><?= ucfirst(htmlspecialchars($current_user_role)) ?></span>
                    </div>
                </div>
            </div>
        </header>