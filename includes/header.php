<?php
$page_title = $page_title ?? 'Portfolio Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Portfolio Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">

<div class="dashboard-layout">

    <!-- Include Reusable Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="dashboard-main">

        <!-- Top Header Bar -->
        <header class="dashboard-header">
            <div>
                <p class="page-label"><?= htmlspecialchars($page_category ?? 'OVERVIEW') ?></p>
                <h1><?= htmlspecialchars($page_heading ?? $page_title) ?></h1>
            </div>

            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($current_user_name, 0, 1)) ?>
                </div>
                <div class="user-info">
                    <strong><?= htmlspecialchars($current_user_name) ?></strong>
                    <span><?= ucfirst(htmlspecialchars($current_user_role)) ?></span>
                </div>
            </div>
        </header>