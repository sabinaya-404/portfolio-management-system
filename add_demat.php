<?php
require_once "includes/auth.php";
require_once "config/database.php";

$message = "";
$account_name = "";
$account_holder = "";
$broker_name = "";
$boid = "";

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
        $check = $conn->prepare("SELECT id FROM demat_accounts WHERE boid = ? LIMIT 1");
        $check->bind_param("s", $boid);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = "A Demat account with this BOID already exists.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO demat_accounts (user_id, account_name, account_holder, broker_name, boid)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $current_user_id, $account_name, $account_holder, $broker_name, $boid);
            
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Demat account '{$account_name}' linked successfully!";
                header("Location: my_demat.php");
                exit;
            } else {
                $message = "Failed to add Demat account. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

$page_title    = "Add Demat Account";
$page_category = "MY DEMAT";
$page_heading  = "Link Demat Account";
$active_page   = "demat";

require_once "includes/header.php";
?>

<div class="page-back">
    <a href="my_demat.php" class="view-link">← Back to My Demat</a>
</div>

<div class="form-narrow">
    <div class="auth-card">
        <h3 class="form-heading">Demat Account Details</h3>
        <p class="form-intro">
            Provide your depository participant and 16-digit Beneficial Owner Identification number.
        </p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-error" role="alert" aria-live="polite"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="add_demat.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="account_name">Account Nickname</label>
                <input type="text" id="account_name" name="account_name" value="<?= htmlspecialchars($account_name) ?>" placeholder="e.g. Personal Portfolio" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="account_holder">Account Holder Name</label>
                <input type="text" id="account_holder" name="account_holder" value="<?= htmlspecialchars($account_holder) ?>" placeholder="Full name registered with DP" autocomplete="name" required>
            </div>

            <div class="form-group">
                <label for="broker_name">Broker / Depository Participant (DP)</label>
                <input type="text" id="broker_name" name="broker_name" value="<?= htmlspecialchars($broker_name) ?>" placeholder="e.g. Naasa Securities (Broker 58)" autocomplete="organization" required>
            </div>

            <div class="form-group">
                <label for="boid">16-Digit BOID</label>
                <input type="text" id="boid" name="boid" maxlength="16" pattern="[0-9]{16}" inputmode="numeric" value="<?= htmlspecialchars($boid) ?>" placeholder="1301234567890123" autocomplete="off" required>
                <small class="form-help">
                    Consists of 8-digit DP ID + 8-digit Client ID.
                </small>
            </div>

            <div class="form-actions">
                <a href="my_demat.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Link Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>