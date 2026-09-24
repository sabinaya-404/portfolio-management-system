<?php
require_once "../includes/admin-auth.php";

$summary_result = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM demat_accounts) AS demat_total,
        (SELECT COUNT(*) FROM holdings) AS holdings_total,
        (SELECT COUNT(DISTINCT company_id) FROM holdings) AS represented_companies,
        (SELECT COALESCE(SUM(h.quantity * c.current_price), 0)
         FROM holdings AS h INNER JOIN companies AS c ON c.id = h.company_id) AS portfolio_value"
);
$summary = $summary_result ? $summary_result->fetch_assoc() : [];

$company_stmt = $conn->prepare(
    "SELECT c.symbol, c.company_name, SUM(h.quantity) AS quantity,
            SUM(h.quantity * c.current_price) AS market_value
     FROM holdings AS h
     INNER JOIN companies AS c ON c.id = h.company_id
     GROUP BY c.id, c.symbol, c.company_name
     ORDER BY market_value DESC, c.symbol ASC"
);
$company_stmt->execute();
$company_rows = $company_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$company_stmt->close();

$page_title = "Reports";
$page_category = "ADMIN";
$page_heading = "Portfolio Reports";
$active_page = "admin-reports";
require_once "../includes/header.php";
?>
<section class="summary-grid" aria-label="Current database totals">
    <div class="summary-card"><span>TOTAL USERS</span><strong><?= number_format((int) ($summary["users_total"] ?? 0)) ?></strong><small>Current registered users</small></div>
    <div class="summary-card"><span>DEMAT ACCOUNTS</span><strong><?= number_format((int) ($summary["demat_total"] ?? 0)) ?></strong><small>Current linked accounts</small></div>
    <div class="summary-card"><span>HOLDINGS</span><strong><?= number_format((int) ($summary["holdings_total"] ?? 0)) ?></strong><small>Current recorded positions</small></div>
    <div class="summary-card"><span>PORTFOLIO VALUE</span><strong>Rs. <?= number_format((float) ($summary["portfolio_value"] ?? 0), 2) ?></strong><small>Calculated from current company prices</small></div>
</section>
<section class="dashboard-card" aria-labelledby="report-heading">
    <div class="card-header"><div><p class="holdings-eyebrow">Current database state</p><h2 id="report-heading">Company portfolio summary</h2></div><span class="muted-cell"><?= number_format((int) ($summary["represented_companies"] ?? 0)) ?> companies represented</span></div>
    <?php if (!$company_rows): ?><div class="empty-state"><h3>No holdings to report</h3><p>Company summaries will appear after users record holdings.</p></div><?php else: ?><div class="holdings-table-wrap"><table class="holdings-table"><thead><tr><th scope="col">SYMBOL</th><th scope="col">COMPANY</th><th scope="col" class="numeric-cell">QUANTITY</th><th scope="col" class="numeric-cell">MARKET VALUE</th></tr></thead><tbody><?php foreach ($company_rows as $row): ?><tr><th scope="row"><?= htmlspecialchars($row["symbol"], ENT_QUOTES, "UTF-8") ?></th><td><?= htmlspecialchars($row["company_name"], ENT_QUOTES, "UTF-8") ?></td><td class="numeric-cell"><?= number_format((int) $row["quantity"]) ?></td><td class="numeric-cell">Rs. <?= number_format((float) $row["market_value"], 2) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php require_once "../includes/footer.php"; ?>