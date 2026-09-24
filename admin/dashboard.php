<?php
require_once "../includes/admin-auth.php";

$company_count = 0;
$active_company_count = 0;
$count_result = $conn->query(
    "SELECT COUNT(*) AS total, SUM(status = 'active') AS active_total
     FROM companies"
);
if ($count_result) {
    $counts = $count_result->fetch_assoc();
    $company_count = (int) ($counts["total"] ?? 0);
    $active_company_count = (int) ($counts["active_total"] ?? 0);
}

$page_title = "Admin Dashboard";
$page_category = "ADMIN";
$page_heading = "Admin Dashboard";
$active_page = "admin";

require_once "../includes/header.php";
?>

<section class="summary-grid" aria-label="Company summary">
    <div class="summary-card">
        <span>TOTAL COMPANIES</span>
        <strong><?= number_format($company_count) ?></strong>
        <small>Companies stored in the system</small>
    </div>
    <div class="summary-card">
        <span>ACTIVE COMPANIES</span>
        <strong><?= number_format($active_company_count) ?></strong>
        <small>Available for new holdings</small>
    </div>
    <div class="summary-card">
        <span>INACTIVE COMPANIES</span>
        <strong><?= number_format($company_count - $active_company_count) ?></strong>
        <small>Existing data remains preserved</small>
    </div>
</section>

<section class="dashboard-card" aria-labelledby="admin-actions-heading">
    <div class="card-header">
        <h2 id="admin-actions-heading">Company management</h2>
    </div>
    <p class="muted-cell" style="margin-bottom: 16px;">
        Add listed companies, update their details, or deactivate them without removing existing portfolio holdings.
    </p>
    <a href="companies.php" class="primary-button">Manage Companies</a>
    <a href="news.php" class="primary-button" style="margin-left: 8px;">Manage IPO & News</a>
    <a href="users.php" class="primary-button" style="margin-left: 8px;">Manage Users</a>
    <a href="reports.php" class="primary-button" style="margin-left: 8px;">View Reports</a>
</section>

<?php require_once "../includes/footer.php"; ?>
