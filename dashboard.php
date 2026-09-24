<?php
require_once "includes/auth.php";
require_once "config/database.php";

// 1. Fetch user's Demat accounts
$stmt = $conn->prepare(
    "SELECT id, account_name, account_holder, broker_name, boid,
            (SELECT COUNT(*) FROM demat_accounts WHERE user_id = ?) AS total_count
     FROM demat_accounts
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC
     LIMIT 10"
);
$stmt->bind_param("ii", $current_user_id, $current_user_id);
$stmt->execute();
$demat_accounts = $stmt->get_result();
$demat_rows = $demat_accounts->fetch_all(MYSQLI_ASSOC);
$demat_count = $demat_rows ? (int)$demat_rows[0]["total_count"] : 0;
$stmt->close();

// 2. Fetch company and market stats
$company_count_res = $conn->query("SELECT COUNT(*) AS total FROM companies WHERE status = 'active'");
$total_active_companies = $company_count_res->fetch_assoc()['total'] ?? 0;

$page_title    = "Dashboard";
$page_category = "OVERVIEW";
$page_heading  = "Investor Dashboard";
$active_page   = "dashboard";

require_once "includes/header.php";
?>

<div class="dashboard-intro">
    <div>
        <p class="holdings-eyebrow">Portfolio workspace</p>
        <h2>Current account overview</h2>
        <p class="intro-copy">Keep your linked accounts and recorded positions organized in one place.</p>
    </div>
    <a href="add_demat.php" class="btn-primary">Link Demat account</a>
</div>

<!-- Summary Metrics -->
<section class="summary-grid">
    <div class="summary-card">
        <span>LINKED DEMAT ACCOUNTS</span>
        <strong><?= $demat_count ?></strong>
        <small><?= $demat_count > 0 ? 'Accounts active and synced' : 'No accounts linked yet' ?></small>
    </div>

    <div class="summary-card">
        <span>LISTED COMPANIES</span>
        <strong><?= number_format($total_active_companies) ?></strong>
        <small>Active securities tracked</small>
    </div>

    <div class="summary-card">
        <span>PORTFOLIO VIEW</span>
        <strong>Current state</strong>
        <small>Values reflect recorded holdings and listed prices</small>
    </div>
</section>

<!-- Main Dashboard Grid -->
<div class="dashboard-columns">

    <!-- Left: Demat Accounts Summary Table -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Linked Demat Accounts (<?= $demat_count ?>)</h3>
            <a href="my_demat.php" class="view-link">Manage All →</a>
        </div>

        <?php if ($demat_count === 0): ?>
            <div class="empty-state">
                <h4>No Demat accounts linked yet</h4>
                <p>Add your 16-digit Beneficial Owner ID to start organizing your investments.</p>
                <a href="add_demat.php" class="primary-button">+ Link New Demat</a>
            </div>
        <?php else: ?>
            <div class="holdings-table-wrap">
                <table class="holdings-table">
                    <thead>
                        <tr>
                            <th scope="col">ACCOUNT</th>
                            <th scope="col">HOLDER</th>
                            <th scope="col">BROKER</th>
                            <th scope="col">BOID</th>
                            <th scope="col" class="numeric-cell">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demat_rows as $acc): ?>
                            <tr>
                                <th scope="row"><?= htmlspecialchars($acc["account_name"]) ?></th>
                                <td class="muted-cell"><?= htmlspecialchars($acc["account_holder"]) ?></td>
                                <td><?= htmlspecialchars($acc["broker_name"]) ?></td>
                                <td><span class="boid-badge"><?= htmlspecialchars($acc["boid"]) ?></span></td>
                                <td class="numeric-cell">
                                    <a href="holdings.php?demat_id=<?= (int)$acc["id"] ?>" class="view-link">Portfolio →</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: Quick Navigation & Services -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>

        <div class="quick-actions">
            <a href="add_demat.php"><strong>Link Demat account</strong><span>Add broker and BOID details</span><b aria-hidden="true">→</b></a>
            <a href="companies.php"><strong>Browse companies</strong><span>Review listed companies and prices</span><b aria-hidden="true">→</b></a>
            <a href="ipo-news.php"><strong>Read IPO &amp; news</strong><span>View published market updates</span><b aria-hidden="true">→</b></a>
        </div>
    </div>

</div>

<?php require_once "includes/footer.php"; ?>