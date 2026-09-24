<?php
require_once "includes/auth.php";
require_once "config/database.php";

$demat_id = (int)($_GET["id"] ?? 0);
if ($demat_id <= 0) {
    header("Location: my_demat.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM demat_accounts WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $demat_id, $current_user_id);
$stmt->execute();
$demat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$demat) {
    $_SESSION['flash_error'] = "Account not found.";
    header("Location: my_demat.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $account_name   = trim((string) ($_POST["account_name"] ?? ""));
    $account_holder = trim((string) ($_POST["account_holder"] ?? ""));
    $broker_name    = trim((string) ($_POST["broker_name"] ?? ""));
    $boid           = trim((string) ($_POST["boid"] ?? ""));

    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired. Refresh the page and try again.";
    } elseif (
        $account_name === "" || $account_holder === "" || $broker_name === "" || $boid === ""
        || mb_strlen($account_name) > 100 || mb_strlen($account_holder) > 100
        || mb_strlen($broker_name) > 100
    ) {
        $message = "Please fill in all fields.";
    } elseif (!preg_match('/^[0-9]{16}$/', $boid)) {
        $message = "BOID must be exactly 16 numeric digits.";
    } else {
        $check = $conn->prepare("SELECT id FROM demat_accounts WHERE boid = ? AND id != ? LIMIT 1");
        $check->bind_param("si", $boid, $demat_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = "Another Demat account with this BOID already exists.";
        } else {
            $update = $conn->prepare(
                "UPDATE demat_accounts 
                 SET account_name = ?, account_holder = ?, broker_name = ?, boid = ?
                 WHERE id = ? AND user_id = ?"
            );
            $update->bind_param("ssssii", $account_name, $account_holder, $broker_name, $boid, $demat_id, $current_user_id);
            
            if ($update->execute()) {
                $_SESSION['flash_success'] = "Demat account '{$account_name}' updated successfully!";
                header("Location: my_demat.php");
                exit;
            } else {
                $message = "Failed to update Demat account.";
            }
            $update->close();
        }
        $check->close();
    }
}

$page_title    = "Edit Demat Account";
$page_category = "MY DEMAT";
$page_heading  = "Edit Account";
$active_page   = "demat";

require_once "includes/header.php";
?>

<div class="page-back">
    <a href="my_demat.php" class="view-link">← Back to My Demat</a>
</div>

<div class="form-narrow">
    <div class="auth-card">
        <h3 class="form-heading">Modify Demat Details</h3>
        <p class="form-intro">
            Update information for <strong><?= htmlspecialchars($demat['account_name']) ?></strong>.
        </p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-error" role="alert" aria-live="polite"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_demat.php?id=<?= $demat_id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="account_name">Account Nickname</label>
                <input type="text" id="account_name" name="account_name" value="<?= htmlspecialchars($_POST['account_name'] ?? $demat['account_name']) ?>" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="account_holder">Account Holder Name</label>
                <input type="text" id="account_holder" name="account_holder" value="<?= htmlspecialchars($_POST['account_holder'] ?? $demat['account_holder']) ?>" autocomplete="name" required>
            </div>

            <div class="form-group">
                <label for="broker_name">Broker / Depository Participant (DP)</label>
                <input type="text" id="broker_name" name="broker_name" value="<?= htmlspecialchars($_POST['broker_name'] ?? $demat['broker_name']) ?>" autocomplete="organization" required>
            </div>

            <div class="form-group">
                <label for="boid">16-Digit BOID</label>
                <input type="text" id="boid" name="boid" maxlength="16" pattern="[0-9]{16}" inputmode="numeric" value="<?= htmlspecialchars($_POST['boid'] ?? $demat['boid']) ?>" autocomplete="off" required>
            </div>

            <div class="form-actions">
                <a href="my_demat.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>