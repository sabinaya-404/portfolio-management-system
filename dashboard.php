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
$allocation_stmt = $conn->prepare(
    "SELECT c.symbol, c.company_name, SUM(h.quantity * c.current_price) AS market_value
     FROM holdings AS h
     INNER JOIN demat_accounts AS d ON d.id = h.demat_id
     INNER JOIN companies AS c ON c.id = h.company_id
     WHERE d.user_id = ?
     GROUP BY c.id, c.symbol, c.company_name
     ORDER BY market_value DESC, c.symbol ASC"
);
$allocation_stmt->bind_param("i", $current_user_id);
$allocation_stmt->execute();
$allocation_rows = $allocation_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allocation_stmt->close();
$portfolio_value = 0.0;
foreach ($allocation_rows as $allocation_row) {
    $portfolio_value += (float) $allocation_row["market_value"];
}

$holdings_count_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_holdings
     FROM holdings AS h
     INNER JOIN demat_accounts AS d ON d.id = h.demat_id
     WHERE d.user_id = ?"
);
$holdings_count_stmt->bind_param("i", $current_user_id);
$holdings_count_stmt->execute();
$holding_count = (int) $holdings_count_stmt->get_result()->fetch_assoc()["total_holdings"];
$holdings_count_stmt->close();

$company_count_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_companies,
            SUM(status = 'active') AS active_companies
     FROM companies"
);
$company_count_stmt->execute();
$company_stats = $company_count_stmt->get_result()->fetch_assoc();
$company_count = (int) ($company_stats["total_companies"] ?? 0);
$active_company_count = (int) ($company_stats["active_companies"] ?? 0);
$company_count_stmt->close();

$news_stmt = $conn->prepare(
    "SELECT type, title, publication_date
     FROM ipo_news
     WHERE status = 'published'
     ORDER BY publication_date DESC, id DESC
     LIMIT 4"
);
$news_stmt->execute();
$recent_updates = $news_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$news_stmt->close();

$page_title    = "Dashboard";
$page_category = "OVERVIEW";
$page_heading  = "Portfolio";
$active_page   = "dashboard";

require_once "includes/header.php";
?>

<section class="portfolio-masthead">
    <div class="portfolio-value-block">
        <p class="section-kicker">Current portfolio value</p>
        <strong>Rs. <?= number_format($portfolio_value, 2) ?></strong>
        <span>Calculated from recorded holdings and current company prices</span>
    </div>
    <div class="masthead-index" aria-label="Portfolio index">
        <span>INDEX</span>
        <strong><?= str_pad((string) $demat_count, 2, "0", STR_PAD_LEFT) ?></strong>
        <small>linked accounts</small>
    </div>
</section>

<section class="summary-grid" aria-label="Portfolio summary">
    <div class="summary-card">
        <span>Portfolio value</span>
        <strong>Rs. <?= number_format($portfolio_value, 2) ?></strong>
        <small>Across <?= number_format($holding_count) ?> recorded positions</small>
    </div>
    <div class="summary-card">
        <span>Holdings</span>
        <strong><?= number_format($holding_count) ?></strong>
        <small>Active positions in linked accounts</small>
    </div>
    <div class="summary-card">
        <span>Companies</span>
        <strong><?= number_format($company_count) ?></strong>
        <small><?= number_format($active_company_count) ?> active</small>
    </div>
</section>

<div class="dashboard-columns dashboard-columns-featured">

    <!-- Left: Demat Accounts Summary Table -->
    <div class="dashboard-card allocation-panel">
        <div class="card-header">
            <div>
                <p class="section-kicker">Allocation</p>
                <h3>Where the portfolio sits</h3>
            </div>
            <span class="panel-index">01</span>
        </div>
        <?php if (!$allocation_rows): ?>
            <div class="empty-state">
                <h4>No portfolio allocation yet</h4>
                <p>Add a holding to see how recorded market value is distributed across companies.</p>
                <a href="holdings.php" class="primary-button">Add first holding</a>
            </div>
        <?php else: ?>
            <div class="allocation-list">
                <?php foreach ($allocation_rows as $allocation_row): ?>
                    <?php $allocation_percent = $portfolio_value > 0 ? ((float) $allocation_row["market_value"] / $portfolio_value) * 100 : 0; ?>
                    <div class="allocation-row">
                        <div class="allocation-label">
                            <strong><?= htmlspecialchars($allocation_row["symbol"]) ?></strong>
                            <span><?= htmlspecialchars($allocation_row["company_name"]) ?></span>
                        </div>
                        <div class="allocation-track" aria-label="<?= htmlspecialchars($allocation_row["company_name"]) ?> represents <?= number_format($allocation_percent, 1) ?> percent of portfolio">
                            <span style="width: <?= min(100, max(0, $allocation_percent)) ?>%;"></span>
                        </div>
                        <strong class="allocation-percent"><?= number_format($allocation_percent, 1) ?>%</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-card account-panel">
        <div class="card-header">
            <div>
                <p class="section-kicker">Accounts</p>
                <h3>Demat register</h3>
            </div>
            <a href="my_demat.php" class="view-link">All accounts</a>
        </div>
        <?php if ($demat_count === 0): ?>
            <div class="empty-state compact-empty">
                <h4>No Demat accounts</h4>
                <p>Link an account to begin.</p>
                <a href="add_demat.php" class="primary-button">Link account</a>
            </div>
        <?php else: ?>
            <div class="account-register">
                <?php foreach ($demat_rows as $account_index => $acc): ?>
                    <a class="account-register-row" href="holdings.php?demat_id=<?= (int) $acc["id"] ?>">
                        <span class="account-number"><?= str_pad((string) ($account_index + 1), 2, "0", STR_PAD_LEFT) ?></span>
                        <span class="account-register-main"><strong><?= htmlspecialchars($acc["account_name"]) ?></strong><small><?= htmlspecialchars($acc["broker_name"]) ?></small></span>
                        <span class="account-register-boid"><?= htmlspecialchars($acc["boid"]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-lower-grid">
    <section class="dashboard-card update-panel" aria-labelledby="updates-heading">
        <div class="card-header">
            <div><p class="section-kicker">Published information</p><h3 id="updates-heading">Recent updates</h3></div>
            <a href="ipo-news.php" class="view-link">Open feed</a>
        </div>
        <?php if (!$recent_updates): ?>
            <div class="empty-state compact-empty"><p>No published IPO or news updates yet.</p></div>
        <?php else: ?>
            <div class="update-list">
                <?php foreach ($recent_updates as $update): ?>
                    <a href="ipo-news.php" class="update-row">
                        <span class="update-type <?= $update["type"] === "ipo" ? "update-type-ipo" : "update-type-news" ?>"><?= htmlspecialchars(strtoupper($update["type"])) ?></span>
                        <strong><?= htmlspecialchars($update["title"]) ?></strong>
                        <time datetime="<?= htmlspecialchars($update["publication_date"]) ?>"><?= htmlspecialchars(date("M j", strtotime($update["publication_date"]))) ?></time>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <aside class="dashboard-card action-panel">
        <p class="section-kicker">Next move</p>
        <h3>Keep the register current.</h3>
        <p>Link an account or add a position whenever your records change.</p>
        <div class="action-stack">
            <a href="add_demat.php" class="primary-button">Add Demat</a>
            <a href="holdings.php" class="btn-secondary">Open holdings</a>
        </div>
    </aside>
</div>

<?php require_once "includes/footer.php"; ?>