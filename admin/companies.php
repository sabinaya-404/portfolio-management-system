<?php
require_once "../includes/admin-auth.php";

$message = "";
$message_type = "error";
$editing_company = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_token = (string) ($_POST["csrf_token"] ?? "");
    if (!hash_equals((string) $_SESSION["csrf_token"], $csrf_token)) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
        $action = (string) ($_POST["action"] ?? "");
        $company_id = filter_var($_POST["company_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if ($action === "toggle" && $company_id !== false && $company_id !== null) {
            $toggle_stmt = $conn->prepare(
                "UPDATE companies
                 SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END
                 WHERE id = ?"
            );
            $toggle_stmt->bind_param("i", $company_id);
            $message_type = $toggle_stmt->execute() && $toggle_stmt->affected_rows === 1 ? "success" : "error";
            $message = $message_type === "success" ? "Company status updated." : "Company was not found.";
            $toggle_stmt->close();
        } elseif (in_array($action, ["add", "edit"], true)) {
            $symbol = strtoupper(trim((string) ($_POST["symbol"] ?? "")));
            $company_name = trim((string) ($_POST["company_name"] ?? ""));
            $sector = trim((string) ($_POST["sector"] ?? ""));
            $price = filter_var($_POST["current_price"] ?? null, FILTER_VALIDATE_FLOAT);

            if (
                $symbol === "" || !preg_match("/^[A-Z0-9.-]{1,20}$/", $symbol)
                || $company_name === "" || strlen($company_name) > 150
                || $sector === "" || strlen($sector) > 100
                || $price === false || $price < 0
            ) {
                $message = "Enter a valid symbol, company name, sector, and non-negative price.";
            } elseif ($action === "add") {
                $insert_stmt = $conn->prepare(
                    "INSERT INTO companies (symbol, company_name, sector, current_price, status)
                     VALUES (?, ?, ?, ?, 'active')"
                );
                $insert_stmt->bind_param("sssd", $symbol, $company_name, $sector, $price);
                if ($insert_stmt->execute()) {
                    $message = "Company added successfully.";
                    $message_type = "success";
                } else {
                    $message = "Unable to add company. The symbol may already exist.";
                }
                $insert_stmt->close();
            } elseif ($company_id !== false && $company_id !== null) {
                $update_stmt = $conn->prepare(
                    "UPDATE companies
                     SET symbol = ?, company_name = ?, sector = ?, current_price = ?
                     WHERE id = ?"
                );
                $update_stmt->bind_param("sssdi", $symbol, $company_name, $sector, $price, $company_id);
                if ($update_stmt->execute()) {
                    $message = "Company updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Unable to update company. The symbol may already exist.";
                }
                $update_stmt->close();
            } else {
                $message = "A valid company is required for editing.";
            }
        } else {
            $message = "Invalid company action.";
        }
    }
}

$search = trim((string) ($_GET["search"] ?? ""));
$status = (string) ($_GET["status"] ?? "");
if (!in_array($status, ["active", "inactive"], true)) {
    $status = "";
}

if (isset($_GET["edit"])) {
    $edit_id = filter_var($_GET["edit"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($edit_id !== false && $edit_id !== null) {
        $edit_stmt = $conn->prepare(
            "SELECT id, symbol, company_name, sector, current_price, status
             FROM companies WHERE id = ? LIMIT 1"
        );
        $edit_stmt->bind_param("i", $edit_id);
        $edit_stmt->execute();
        $editing_company = $edit_stmt->get_result()->fetch_assoc();
        $edit_stmt->close();
    }
}

$conditions = [];
$params = [];
$types = "";
if ($search !== "") {
    $conditions[] = "(company_name LIKE ? OR symbol LIKE ? OR sector LIKE ?)";
    $search_term = "%" . $search . "%";
    $params = [$search_term, $search_term, $search_term];
    $types = "sss";
}
if ($status !== "") {
    $conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$query = "SELECT id, symbol, company_name, sector, current_price, status FROM companies";
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY company_name ASC, symbol ASC";
$list_stmt = $conn->prepare($query);
if ($types !== "") {
    $bind_params = [$types];
    foreach ($params as $key => &$param) {
        $bind_params[] = &$param;
    }
    call_user_func_array([$list_stmt, "bind_param"], $bind_params);
}
$list_stmt->execute();
$companies = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();

$page_title = "Manage Companies";
$page_category = "ADMIN";
$page_heading = "Manage Companies";
$active_page = "admin-companies";

require_once "../includes/header.php";
?>

<?php if ($message !== ""): ?>
    <div class="alert <?= $message_type === "success" ? "alert-success" : "alert-error" ?>" role="<?= $message_type === "success" ? "status" : "alert" ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
    </div>
<?php endif; ?>

<section class="dashboard-card" aria-labelledby="company-form-heading">
    <div class="card-header">
        <h2 id="company-form-heading"><?= $editing_company ? "Edit company" : "Add company" ?></h2>
        <?php if ($editing_company): ?>
            <a href="companies.php" class="view-link">Cancel</a>
        <?php endif; ?>
    </div>
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
        <input type="hidden" name="action" value="<?= $editing_company ? "edit" : "add" ?>">
        <?php if ($editing_company): ?>
            <input type="hidden" name="company_id" value="<?= (int) $editing_company["id"] ?>">
        <?php endif; ?>
        <label for="symbol">Symbol</label>
        <label for="company_name">Company name</label>
        <label for="sector">Sector</label>
        <label for="current_price">Current price</label>
        <span aria-hidden="true"></span>
        <input id="symbol" name="symbol" maxlength="20" required value="<?= htmlspecialchars($editing_company["symbol"] ?? "", ENT_QUOTES, "UTF-8") ?>">
        <input id="company_name" name="company_name" maxlength="150" required value="<?= htmlspecialchars($editing_company["company_name"] ?? "", ENT_QUOTES, "UTF-8") ?>">
        <input id="sector" name="sector" maxlength="100" required value="<?= htmlspecialchars($editing_company["sector"] ?? "", ENT_QUOTES, "UTF-8") ?>">
        <input id="current_price" name="current_price" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) ($editing_company["current_price"] ?? "0.00"), ENT_QUOTES, "UTF-8") ?>">
        <button type="submit" class="primary-button"><?= $editing_company ? "Save changes" : "Add company" ?></button>
    </form>
</section>

<section class="dashboard-card" aria-labelledby="company-list-heading">
    <div class="card-header">
        <h2 id="company-list-heading">Existing companies</h2>
        <span class="muted-cell"><?= number_format(count($companies)) ?> result<?= count($companies) === 1 ? "" : "s" ?></span>
    </div>
    <form method="get" class="filter-bar">
        <label for="company-search">Search</label>
        <label for="company-status">Status</label>
        <span aria-hidden="true"></span>
        <input id="company-search" name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" placeholder="Name, symbol, or sector">
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
            <p>Try a different search or add a new company.</p>
        </div>
    <?php else: ?>
        <div class="holdings-table-wrap">
            <table class="holdings-table">
                <caption class="visually-hidden">Companies managed by administrators</caption>
                <thead>
                    <tr>
                        <th scope="col">SYMBOL</th>
                        <th scope="col">COMPANY</th>
                        <th scope="col">SECTOR</th>
                        <th scope="col" class="numeric-cell">PRICE</th>
                        <th scope="col">STATUS</th>
                        <th scope="col">ACTIONS</th>
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
                                <span class="status-badge <?= $company["status"] === "inactive" ? "status-danger" : "" ?>">
                                    <?= htmlspecialchars(ucfirst($company["status"]), ENT_QUOTES, "UTF-8") ?>
                                </span>
                            </td>
                            <td>
                                <a href="companies.php?edit=<?= (int) $company["id"] ?>" class="action-link">Edit</a>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="company_id" value="<?= (int) $company["id"] ?>">
                                    <button type="submit" class="action-link<?= $company["status"] === "active" ? "-danger" : "" ?>">
                                        <?= $company["status"] === "active" ? "Deactivate" : "Activate" ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once "../includes/footer.php"; ?>
