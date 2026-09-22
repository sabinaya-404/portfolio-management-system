<?php
require_once "includes/auth.php";
require_once "config/database.php";

$demat_id = (int) ($_GET["demat_id"] ?? 0);
$demat = null;
$holdings = [];
$companies = [];
$total_value = 0.0;

$is_ajax = $_SERVER["REQUEST_METHOD"] === "POST"
    && strtolower($_SERVER["HTTP_ACCEPT"] ?? "") === "application/json";

function holdings_response(bool $success, string $message, array $payload = [], int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge(["success" => $success, "message" => $message], $payload));
    exit;
}

function holdings_redirect(int $demat_id, string $message, bool $success): void
{
    $_SESSION["holdings_flash"] = ["message" => $message, "success" => $success];
    header("Location: holdings.php?demat_id=" . $demat_id);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $posted_demat_id = filter_var($_POST["demat_id"] ?? 0, FILTER_VALIDATE_INT);
    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $csrf_token)) {
        $message = "Your session expired. Refresh the page and try again.";
        if ($is_ajax) {
            holdings_response(false, $message, [], 419);
        }
        holdings_redirect((int) $posted_demat_id, $message, false);
    }

    if ($posted_demat_id === false || $posted_demat_id <= 0) {
        $message = "A valid Demat account is required.";
        if ($is_ajax) {
            holdings_response(false, $message, [], 422);
        }
        holdings_redirect(0, $message, false);
    }

    $auth_stmt = $conn->prepare("SELECT id FROM demat_accounts WHERE id = ? AND user_id = ? LIMIT 1");
    $auth_stmt->bind_param("ii", $posted_demat_id, $current_user_id);
    $auth_stmt->execute();
    $authorized = (bool) $auth_stmt->get_result()->fetch_assoc();
    $auth_stmt->close();

    if (!$authorized) {
        $message = "Demat account not found.";
        if ($is_ajax) {
            holdings_response(false, $message, [], 404);
        }
        holdings_redirect(0, $message, false);
    }

    $quantity = filter_var($_POST["quantity"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if (in_array($action, ["add", "edit"], true) && ($quantity === false || $quantity === null)) {
        $message = "Quantity must be a whole number greater than zero.";
        if ($is_ajax) {
            holdings_response(false, $message, [], 422);
        }
        holdings_redirect((int) $posted_demat_id, $message, false);
    }

    if ($action === "add") {
        $company_id = filter_var($_POST["company_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($company_id === false || $company_id === null) {
            $message = "Select a valid company.";
            if ($is_ajax) {
                holdings_response(false, $message, [], 422);
            }
            holdings_redirect((int) $posted_demat_id, $message, false);
        }
        $mutation = $conn->prepare("INSERT INTO holdings (demat_id, company_id, quantity) VALUES (?, ?, ?)");
        $mutation->bind_param("iii", $posted_demat_id, $company_id, $quantity);
        $mutation->execute();
        $message = "Holding added.";
        $mutation->close();
    } elseif ($action === "edit") {
        $holding_id = filter_var($_POST["holding_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($holding_id === false || $holding_id === null) {
            $message = "A valid holding is required.";
            if ($is_ajax) {
                holdings_response(false, $message, [], 422);
            }
            holdings_redirect((int) $posted_demat_id, $message, false);
        }
        $mutation = $conn->prepare("UPDATE holdings SET quantity = ? WHERE id = ? AND demat_id = ?");
        $mutation->bind_param("iii", $quantity, $holding_id, $posted_demat_id);
        $mutation->execute();
        if ($mutation->affected_rows < 1) {
            $mutation->close();
            $message = "Holding not found or unchanged.";
            if ($is_ajax) {
                holdings_response(false, $message, [], 404);
            }
            holdings_redirect((int) $posted_demat_id, $message, false);
        }
        $message = "Holding updated.";
        $mutation->close();
    } elseif ($action === "delete") {
        $holding_id = filter_var($_POST["holding_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($holding_id === false || $holding_id === null) {
            $message = "A valid holding is required.";
            if ($is_ajax) {
                holdings_response(false, $message, [], 422);
            }
            holdings_redirect((int) $posted_demat_id, $message, false);
        }
        $mutation = $conn->prepare("DELETE FROM holdings WHERE id = ? AND demat_id = ?");
        $mutation->bind_param("ii", $holding_id, $posted_demat_id);
        $mutation->execute();
        if ($mutation->affected_rows < 1) {
            $mutation->close();
            $message = "Holding not found.";
            if ($is_ajax) {
                holdings_response(false, $message, [], 404);
            }
            holdings_redirect((int) $posted_demat_id, $message, false);
        }
        $message = "Holding deleted.";
        $mutation->close();
    } else {
        $message = "Unsupported holding action.";
        if ($is_ajax) {
            holdings_response(false, $message, [], 422);
        }
        holdings_redirect((int) $posted_demat_id, $message, false);
    }

    if ($is_ajax) {
        holdings_response(true, $message, ["reload" => true]);
    }
    holdings_redirect((int) $posted_demat_id, $message, true);
}

if ($demat_id > 0) {
    $stmt = $conn->prepare(
        "SELECT id, account_name, account_holder, broker_name, boid
         FROM demat_accounts
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("ii", $demat_id, $current_user_id);
    $stmt->execute();
    $demat = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($demat) {
        $holdings_stmt = $conn->prepare(
            "SELECT h.id, c.symbol, c.company_name, c.sector, c.current_price,
                    h.quantity, (h.quantity * c.current_price) AS market_value
             FROM holdings AS h
             INNER JOIN companies AS c ON c.id = h.company_id
             WHERE h.demat_id = ?
             ORDER BY c.symbol ASC"
        );
        $holdings_stmt->bind_param("i", $demat_id);
        $holdings_stmt->execute();
        $holdings = $holdings_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $holdings_stmt->close();

        foreach ($holdings as $holding) {
            $total_value += (float) $holding["market_value"];
        }

        $companies_stmt = $conn->prepare(
            "SELECT id, symbol, company_name
             FROM companies
             WHERE status = 'active'
             ORDER BY symbol ASC"
        );
        $companies_stmt->execute();
        $companies = $companies_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $companies_stmt->close();
    }
}

$flash = $_SESSION["holdings_flash"] ?? null;
unset($_SESSION["holdings_flash"]);

$page_title = "Holdings";
$page_category = "PORTFOLIO";
$page_heading = $demat ? "Holdings Overview" : "Portfolio Holdings";
$active_page = "holdings";

require_once "includes/header.php";
?>

<div id="holdingsLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>
<?php if ($flash): ?>
    <div class="alert <?= $flash["success"] ? "alert-success" : "alert-error" ?>" role="<?= $flash["success"] ? "status" : "alert" ?>" aria-live="polite">
        <?= htmlspecialchars($flash["message"]) ?>
    </div>
<?php endif; ?>

<div class="holdings-toolbar">
    <div>
        <p class="holdings-eyebrow">Selected account</p>
        <h2 class="holdings-title"><?= $demat ? htmlspecialchars($demat["account_name"]) : "Choose a Demat account" ?></h2>
        <?php if ($demat): ?>
            <p class="holdings-subtitle">
                <?= htmlspecialchars($demat["broker_name"]) ?> · BOID <?= htmlspecialchars($demat["boid"]) ?>
            </p>
        <?php else: ?>
            <p class="holdings-subtitle">Select an account from My Demat to review its recorded holdings.</p>
        <?php endif; ?>
    </div>
    <a href="my_demat.php" class="btn-primary holdings-toolbar-action">← My Demat</a>
</div>

<?php if (!$demat): ?>
    <section class="dashboard-card holdings-empty-card" aria-labelledby="holdings-empty-heading">
        <div class="holdings-empty-icon" aria-hidden="true">+</div>
        <h2 id="holdings-empty-heading">No Demat account selected</h2>
        <p>Choose a linked account to see its recorded securities and market value.</p>
        <a href="my_demat.php" class="primary-button">View My Demat Accounts</a>
    </section>
<?php else: ?>
    <section class="holdings-account-card dashboard-card" aria-labelledby="account-details-heading">
        <div>
            <p class="holdings-eyebrow">Account identity</p>
            <h2 id="account-details-heading"><?= htmlspecialchars($demat["account_name"]) ?></h2>
        </div>
        <dl class="holdings-account-details">
            <div>
                <dt>Account holder</dt>
                <dd><?= htmlspecialchars($demat["account_holder"]) ?></dd>
            </div>
            <div>
                <dt>Broker / DP</dt>
                <dd><?= htmlspecialchars($demat["broker_name"]) ?></dd>
            </div>
            <div>
                <dt>BOID</dt>
                <dd class="boid-badge"><?= htmlspecialchars($demat["boid"]) ?></dd>
            </div>
        </dl>
    </section>

    <section class="summary-grid holdings-summary-grid" aria-label="Holdings summary">
        <div class="summary-card">
            <span>SECURITIES HELD</span>
            <strong><?= number_format(count($holdings)) ?></strong>
            <small>Recorded positions in this account</small>
        </div>
        <div class="summary-card">
            <span>ESTIMATED MARKET VALUE</span>
            <strong>Rs. <?= number_format($total_value, 2) ?></strong>
            <small>Based on current listed prices</small>
        </div>
        <div class="summary-card">
            <span>ACCOUNT STATUS</span>
            <strong class="holdings-status">Active</strong>
            <small>Account access verified</small>
        </div>
    </section>

    <section class="dashboard-card holdings-table-card" aria-labelledby="holdings-table-heading">
        <div class="card-header">
            <div>
                <p class="holdings-eyebrow">Position register</p>
                <h2 id="holdings-table-heading">Recorded holdings</h2>
            </div>
            <a href="my_demat.php" class="view-link">Switch account →</a>
        </div>

        <form method="post" class="holdings-add-form" data-holdings-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="demat_id" value="<?= $demat_id ?>">
            <input type="hidden" name="action" value="add">
            <label for="company_id">Add security</label>
            <select id="company_id" name="company_id" required>
                <option value="">Select company</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= (int) $company["id"] ?>">
                        <?= htmlspecialchars($company["symbol"] . " — " . $company["company_name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="add_quantity">Quantity</label>
            <input id="add_quantity" name="quantity" type="number" min="1" step="1" inputmode="numeric" required>
            <button type="submit" class="primary-button">Add holding</button>
        </form>

        <?php if (!$holdings): ?>
            <div class="empty-state holdings-empty-state">
                <h3>No holdings recorded yet</h3>
                <p>This account is connected, but no securities have been added to its holdings.</p>
            </div>
        <?php else: ?>
            <div class="holdings-table-wrap">
                <table class="holdings-table">
                    <caption class="visually-hidden">Holdings for <?= htmlspecialchars($demat["account_name"]) ?></caption>
                    <thead>
                        <tr data-holding-row>
                            <th scope="col">SYMBOL</th>
                            <th scope="col">COMPANY</th>
                            <th scope="col">SECTOR</th>
                            <th scope="col" class="numeric-cell">QUANTITY</th>
                            <th scope="col" class="numeric-cell">PRICE</th>
                            <th scope="col" class="numeric-cell">MARKET VALUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holdings as $holding): ?>
                            <tr>
                                <th scope="row"><?= htmlspecialchars($holding["symbol"]) ?></th>
                                <td><?= htmlspecialchars($holding["company_name"]) ?></td>
                                <td class="muted-cell"><?= htmlspecialchars($holding["sector"]) ?></td>
                                <td class="numeric-cell"><?= number_format((int) $holding["quantity"]) ?></td>
                                <td class="numeric-cell">Rs. <?= number_format((float) $holding["current_price"], 2) ?></td>
                                <td class="numeric-cell"><strong>Rs. <?= number_format((float) $holding["market_value"], 2) ?></strong></td>
                                <td class="numeric-cell">
                                    <form method="post" class="holding-action-form" data-holdings-form>
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden" name="demat_id" value="<?= $demat_id ?>">
                                        <input type="hidden" name="holding_id" value="<?= (int) $holding["id"] ?>">
                                        <label class="visually-hidden" for="quantity-<?= (int) $holding["id"] ?>">Quantity for <?= htmlspecialchars($holding["symbol"]) ?></label>
                                        <input id="quantity-<?= (int) $holding["id"] ?>" name="quantity" type="number" min="1" step="1" inputmode="numeric" value="<?= (int) $holding["quantity"] ?>" required>
                                        <button type="submit" name="action" value="edit" class="action-link">Save</button>
                                        <button type="submit" name="action" value="delete" class="action-link-danger" data-delete-holding>Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const liveRegion = document.getElementById("holdingsLiveRegion");
        document.querySelectorAll("[data-holdings-form]").forEach(function (form) {
            form.addEventListener("submit", async function (event) {
                const submitter = event.submitter;
                if (submitter && submitter.hasAttribute("data-delete-holding")
                    && !window.confirm("Delete this holding? This action cannot be undone.")) {
                    event.preventDefault();
                    return;
                }
                if (!window.fetch) {
                    return;
                }
                event.preventDefault();
                if (submitter) {
                    submitter.disabled = true;
                }
                if (liveRegion) {
                    liveRegion.textContent = "Saving holding…";
                }
                const data = new FormData(form);
                if (submitter && submitter.name) {
                    data.set(submitter.name, submitter.value);
                }
                try {
                    const response = await fetch("holdings.php", {
                        method: "POST",
                        headers: { "Accept": "application/json" },
                        body: data
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || "Unable to save holding.");
                    }
                    if (liveRegion) {
                        liveRegion.textContent = result.message;
                    }
                    window.location.reload();
                } catch (error) {
                    if (liveRegion) {
                        liveRegion.textContent = error.message;
                    }
                    if (submitter) {
                        submitter.disabled = false;
                    }
                }
            });
        });
    });
    </script>
