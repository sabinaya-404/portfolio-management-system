<?php
require_once "includes/auth.php";
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $account_name   = trim($_POST["account_name"] ?? "");
    $account_holder = trim($_POST["account_holder"] ?? "");
    $broker_name    = trim($_POST["broker_name"] ?? "");
    $boid           = trim($_POST["boid"] ?? "");

    if (empty($account_name) || empty($account_holder) || empty($broker_name) || empty($boid)) {
        $message = "Please fill in all fields.";
    } elseif (!preg_match('/^[0-9]{16}$/', $boid)) {
        $message = "BOID must be exactly 16 numeric digits.";
    } else {
        $check = $conn->prepare("SELECT id FROM demat_accounts WHERE boid = ?");
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
                header("Location: my_demat.php");
                exit;
            } else {
                $message = "Failed to add Demat account.";
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

<div style="margin-bottom: 20px;">
    <a href="my_demat.php" class="view-link">← Back to My Demat</a>
</div>

<div style="max-width: 560px; margin: 0 auto;">
    <div class="auth-card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Demat Account Details</h3>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
            Provide your depository participant and 16-digit Beneficial Owner Identification number.
        </p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="add_demat.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label for="account_name">Account Nickname</label>
                <input type="text" id="account_name" name="account_name" placeholder="e.g. Personal Portfolio" required>
            </div>

            <div class="form-group">
                <label for="account_holder">Account Holder Name</label>
                <input type="text" id="account_holder" name="account_holder" placeholder="Full name registered with DP" required>
            </div>

            <div class="form-group">
                <label for="broker_name">Broker / Depository Participant (DP)</label>
                <input type="text" id="broker_name" name="broker_name" placeholder="e.g. Naasa Securities (Broker 58)" required>
            </div>

            <div class="form-group">
                <label for="boid">16-Digit BOID</label>
                <input type="text" id="boid" name="boid" maxlength="16" pattern="[0-9]{16}" placeholder="1301234567890123" required>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">
                    Consists of 8-digit DP ID + 8-digit Client ID.
                </small>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="my_demat.php" class="btn-primary" style="background: var(--bg-surface-secondary); color: var(--text-main) !important; border: 1px solid var(--border-dark);">Cancel</a>
                <button type="submit" class="btn-primary" style="flex: 1; justify-content: center;">Link Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>