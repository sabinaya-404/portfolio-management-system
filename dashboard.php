<?php
require_once "includes/auth.php";
require_once "config/database.php";

// 1. Fetch user's Demat accounts
$stmt = $conn->prepare(
    "SELECT id, account_name, account_holder, broker_name, boid
     FROM demat_accounts
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$demat_accounts = $stmt->get_result();
$demat_count = $demat_accounts->num_rows;
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

<!-- Welcome Banner -->
<div class="dashboard-card" style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to right, #1e40af, #2563eb); color: #ffffff; border: none;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">Welcome back, <?= htmlspecialchars($current_user_name) ?></h2>
        <p style="color: #bfdbfe; font-size: 13px;">Manage your Demat portfolios, track market assets, and monitor financial news in one place.</p>
    </div>
    <div>
        <a href="add_demat.php" class="btn-primary" style="background: #ffffff; color: #1e40af !important; border: 1px solid #ffffff;">+ Link Demat</a>
    </div>
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
        <span>MARKET STATUS</span>
        <strong style="color: #15803d; font-size: 18px; display: flex; align-items: center; gap: 6px;">
            <span style="width: 10px; height: 10px; background: #22c55e; border-radius: 50%; display: inline-block;"></span>
            Market Active
        </strong>
        <small>Standard Trading Hours</small>
    </div>
</section>

<!-- Main Dashboard Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">

    <!-- Left: Demat Accounts Summary Table -->
    <div class="dashboard-card" style="margin-bottom: 0;">
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
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); height: 36px;">
                            <th>ACCOUNT</th>
                            <th>HOLDER</th>
                            <th>BROKER</th>
                            <th>BOID</th>
                            <th style="text-align: right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($acc = $demat_accounts->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid var(--border); height: 48px;">
                                <td><strong><?= htmlspecialchars($acc["account_name"]) ?></strong></td>
                                <td style="color: var(--text-muted);"><?= htmlspecialchars($acc["account_holder"]) ?></td>
                                <td><?= htmlspecialchars($acc["broker_name"]) ?></td>
                                <td><span class="boid-badge"><?= htmlspecialchars($acc["boid"]) ?></span></td>
                                <td style="text-align: right;">
                                    <a href="holdings.php?demat_id=<?= (int)$acc["id"] ?>" class="view-link">Portfolio →</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: Quick Navigation & Services -->
    <div class="dashboard-card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="add_demat.php" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--bg-surface-secondary); border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--text-main);">
                <div>
                    <strong style="display: block; font-size: 13px;">+ Link Demat Account</strong>
                    <span style="font-size: 11.5px; color: var(--text-muted);">Add broker & BOID details</span>
                </div>
                <span style="color: var(--text-muted); font-size: 14px;">→</span>
            </a>

            <a href="companies.php" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--bg-surface-secondary); border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--text-main);">
                <div>
                    <strong style="display: block; font-size: 13px;">Browse Companies</strong>
                    <span style="font-size: 11.5px; color: var(--text-muted);">View listed stocks & prices</span>
                </div>
                <span style="color: var(--text-muted); font-size: 14px;">→</span>
            </a>

            <a href="ipo-news.php" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--bg-surface-secondary); border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--text-main);">
                <div>
                    <strong style="display: block; font-size: 13px;">IPO News & Notices</strong>
                    <span style="font-size: 11.5px; color: var(--text-muted);">Check upcoming issues</span>
                </div>
                <span style="color: var(--text-muted); font-size: 14px;">→</span>
            </a>
        </div>
    </div>

</div>

<?php require_once "includes/footer.php"; ?>