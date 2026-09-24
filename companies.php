<?php
require_once "includes/auth.php";
require_once "config/database.php";

$search = trim((string) ($_GET["search"] ?? ""));
$status = (string) ($_GET["status"] ?? "");
$allowed_statuses = ["active", "inactive"];

if (!in_array($status, $allowed_statuses, true)) {
    $status = "";
}

$conditions = [];
$params = [];
$types = "";

if ($search !== "") {
    $conditions[] = "(company_name LIKE ? OR symbol LIKE ? OR sector LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($status !== "") {
    $conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$query = "SELECT id, company_name, symbol, sector, current_price, status
          FROM companies";
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY company_name ASC, symbol ASC";

$stmt = $conn->prepare($query);
if ($types !== "") {
    $bind_params = [$types];
    foreach ($params as $key => &$param) {
        $bind_params[] = &$param;
    }
    call_user_func_array([$stmt, "bind_param"], $bind_params);
}
$stmt->execute();
$companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Companies";
$page_category = "MARKET";
$page_heading = "Listed Companies";
$active_page = "companies";

require_once "includes/header.php";
?>

<section class="dashboard-card" aria-labelledby="companies-heading">
    <div class="card-header">
        <div>
            <p class="holdings-eyebrow">Market directory</p>
            <h2 id="companies-heading">Companies</h2>
        </div>
        <span class="muted-cell"><?= number_format(count($companies)) ?> result<?= count($companies) === 1 ? "" : "s" ?></span>
    </div>

    <form method="get" class="filter-bar">
        <label for="company-search">Search companies</label>
        <label for="company-status">Status</label>
        <span aria-hidden="true"></span>
        <input
            id="company-search"
            name="search"
            type="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            placeholder="Name, symbol, or sector"
        >
        <select id="company-status" name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $status === "active" ? "selected" : "" ?>>Active</option>
            <option value="inactive" <?= $status === "inactive" ? "selected" : "" ?>>Inactive</option>
        </select>
        <button type="submit" class="primary-button">Filter</button>
    </form>

    <?php if (!$companies): ?>
        <div class="empty-state">
            <h4>No companies found</h4>
            <p>Try a different search term or status filter.</p>
        </div>
    <?php else: ?>
        <div class="holdings-table-wrap">
            <table class="holdings-table">
                <caption class="visually-hidden">Listed companies and current prices</caption>
                <thead>
                    <tr>
                        <th scope="col">SYMBOL</th>
                        <th scope="col">COMPANY</th>
                        <th scope="col">SECTOR</th>
                        <th scope="col" class="numeric-cell">CURRENT PRICE</th>
                        <th scope="col">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($company["symbol"], ENT_QUOTES, "UTF-8") ?></th>
                            <td><?= htmlspecialchars($company["company_name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td class="muted-cell"><?= htmlspecialchars($company["sector"], ENT_QUOTES, "UTF-8") ?></td>
                            <td class="numeric-cell">Rs. <?= number_format((float) $company["current_price"], 2) ?></td>
                            <td>
                                <span
                                    class="status-badge <?= $company["status"] === "inactive" ? "status-danger" : "" ?>"
                                >
                                    <?= htmlspecialchars(ucfirst($company["status"]), ENT_QUOTES, "UTF-8") ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once "includes/footer.php"; ?>
