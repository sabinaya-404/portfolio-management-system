<?php
require_once "includes/auth.php";
require_once "config/database.php";

// Fetch user's accounts
$stmt = $conn->prepare(
    "SELECT id, account_name, account_holder, broker_name, boid, created_at
     FROM demat_accounts
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC"
);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$demat_count = $result->num_rows;

// Check for session flash messages
$flash_success = $_SESSION['flash_success'] ?? "";
$flash_error   = $_SESSION['flash_error'] ?? "";
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$page_title    = "My Demat Accounts";
$page_category = "PORTFOLIO";
$page_heading  = "Demat Management";
$active_page   = "demat";

require_once "includes/header.php";
?>

<!-- Flash Alerts -->
<?php if (!empty($flash_success)): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flash_success) ?></div>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
    <div class="alert alert-error" role="alert" aria-live="polite"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>

<!-- Top Toolbar: Search & Add Button -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; flex-wrap: wrap;">
    <div style="flex: 1; max-width: 360px; position: relative;">
        <input 
            type="text" 
            id="dematSearchInput"
            name="demat_search"
            aria-label="Search Demat accounts"
            autocomplete="off"
            placeholder="Search by name, broker, or BOID…"
            style="width: 100%; padding: 9px 12px 9px 36px; background: #ffffff; border: 1px solid var(--border-dark); border-radius: 6px; font-size: 13px;"
        >
        <svg aria-hidden="true" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8" stroke-width="2"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/>
        </svg>
    </div>

    <a href="add_demat.php" class="primary-button">+ Link Demat Account</a>
</div>

<?php if ($demat_count === 0): ?>
    <div class="empty-state">
        <h4>No Demat accounts connected</h4>
        <p>Link your 16-digit BOID to begin tracking your shares and investment portfolio.</p>
        <a href="add_demat.php" class="primary-button">+ Link First Account</a>
    </div>
<?php else: ?>
    <div class="accounts-grid" id="dematGrid">
        <?php while ($demat = $result->fetch_assoc()): ?>
            <div class="demat-card-tile demat-search-item" data-search="<?= strtolower(htmlspecialchars($demat['account_name'] . ' ' . $demat['account_holder'] . ' ' . $demat['broker_name'] . ' ' . $demat['boid'])) ?>">
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
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int)$demat["id"] ?>">
                            <button type="submit" class="action-link-danger" aria-label="Delete <?= htmlspecialchars($demat["account_name"]) ?> Demat account">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Hidden message when search has 0 results -->
    <div id="noSearchResults" class="empty-state" style="display: none; margin-top: 16px;">
        <h4>No matching accounts found</h4>
        <p>Try searching with a different account name, broker, or BOID.</p>
    </div>
<?php endif; ?>
<?php $result->free(); $stmt->close(); ?>

<!-- Vanilla JS Instant Search -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("dematSearchInput");
    const cards = Array.from(document.querySelectorAll(".demat-search-item"), card => ({
        element: card,
        searchData: card.getAttribute("data-search") || ""
    }));
    const noResults = document.getElementById("noSearchResults");
    let filterFrame = null;

    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const query = this.value.toLowerCase().trim();

            if (filterFrame !== null) {
                cancelAnimationFrame(filterFrame);
            }

            filterFrame = requestAnimationFrame(function() {
                let visibleCount = 0;

                cards.forEach(card => {
                    const isVisible = card.searchData.includes(query);
                    card.element.style.display = isVisible ? "" : "none";
                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (noResults) {
                    noResults.style.display = (visibleCount === 0 && cards.length > 0) ? "block" : "none";
                }
                filterFrame = null;
            });
        });
    }
});
</script>

<?php require_once "includes/footer.php"; ?>