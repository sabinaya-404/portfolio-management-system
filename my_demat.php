<?php
require_once "includes/auth.php";
require_once "config/database.php";

$stmt = $conn->prepare(
    "SELECT id, account_name, account_holder, broker_name, boid, created_at
     FROM demat_accounts
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title    = "My Demat Accounts";
$page_category = "PORTFOLIO";
$page_heading  = "Demat Management";
$active_page   = "demat";

require_once "includes/header.php";
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <p style="color: var(--text-muted); font-size: 13px;">Manage and monitor your linked Demat accounts across different brokers.</p>
    </div>
    <a href="add_demat.php" class="primary-button">+ Link Demat Account</a>
</div>

<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <h4>No Demat accounts connected</h4>
        <p>Link your 16-digit BOID to begin tracking your shares and investment portfolio.</p>
        <a href="add_demat.php" class="primary-button">+ Link First Account</a>
    </div>
<?php else: ?>
    <div class="accounts-grid">
        <?php while ($demat = $result->fetch_assoc()): ?>
            <div class="demat-card-tile">
                <div>
                    <div class="demat-tile-header">
                        <div>
                            <h3><?= htmlspecialchars($demat["account_name"]) ?></h3>
                            <span style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($demat["broker_name"]) ?></span>
                        </div>
                        <span class="status-badge">Active</span>
                    </div>

                    <div class="demat-info-row">
                        <span>Account Holder:</span>
                        <strong><?= htmlspecialchars($demat["account_holder"]) ?></strong>
                    </div>

                    <div class="demat-info-row">
                        <span>BOID:</span>
                        <div class="boid-badge"><?= htmlspecialchars($demat["boid"]) ?></div>
                    </div>
                </div>

                <div class="demat-tile-footer">
                    <a href="holdings.php?demat_id=<?= (int)$demat["id"] ?>" class="view-link">View Portfolio →</a>
                    
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <a href="edit_demat.php?id=<?= (int)$demat["id"] ?>" class="action-link">Edit</a>
                        
                        <form method="POST" action="delete_demat.php" style="display:inline;" onsubmit="return confirm('Delete this Demat account? All associated holdings records will be removed.');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= (int)$demat["id"] ?>">
                            <button type="submit" class="action-link-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php
$stmt->close();
require_once "includes/footer.php";
?>