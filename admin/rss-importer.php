<?php
require_once "../includes/admin-auth.php";
require_once "../includes/rss-import.php";

$message = "";
$message_type = "error";
$importResults = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
        $action = (string) ($_POST["action"] ?? "");

        if ($action === "import_feed") {
            // Get current admin user ID from session
            $adminUserId = $_SESSION["user_id"] ?? null;
            if ($adminUserId === null) {
                $message = "Unable to identify admin user. Please log in again.";
            } else {
                // Get feeds to import
                $feeds = getConfiguredRssFeeds();

                // If specific feeds selected, only import those
                if (!empty($_POST['feeds'])) {
                    $selectedFeeds = array_map('trim', $_POST['feeds']);
                    $feeds = array_values(array_intersect($feeds, $selectedFeeds));
                }

                if (empty($feeds)) {
                    $message = "No feeds selected for import.";
                } else {
                    // Import each feed
                    $totalImported = 0;
                    $totalSkipped = 0;
                    $allErrors = [];

                    foreach ($feeds as $feedUrl) {
                        $results = importRssFeed($conn, $feedUrl, $adminUserId);
                        $totalImported += $results['imported'];
                        $totalSkipped += $results['skipped'];
                        $allErrors = array_merge($allErrors, $results['errors']);
                    }

                    if (empty($allErrors)) {
                        $message_type = "success";
                        $message = "Import completed: {$totalImported} new items imported, {$totalSkipped} duplicates skipped.";
                    } else {
                        $message_type = "error";
                        $message = "Import completed with errors: {$totalImported} new items imported, {$totalSkipped} duplicates skipped. " .
                                  count($allErrors) . " error(s) occurred.";
                    }

                    $importResults = [
                        'feeds_processed' => count($feeds),
                        'total_imported' => $totalImported,
                        'total_skipped' => $totalSkipped,
                        'errors' => $allErrors
                    ];
                }
            }
        }
    }
}

// Get available feeds for the form
$feeds = getConfiguredRssFeeds();

$page_title = "RSS Feed Importer";
$page_category = "ADMIN";
$page_heading = "Import RSS Feeds";
$active_page = "admin-rss-importer";
require_once "../includes/header.php";
?>
<?php if ($message !== ""): ?><div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" role="<?= $message_type === 'success' ? 'status' : 'alert' ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<section class="dashboard-card open-panel admin-section" aria-labelledby="rss-import-heading">
    <div class="card-header">
        <h2 id="rss-import-heading">Import RSS Feeds</h2>
        <p class="form-help">Import news items from configured RSS feeds. Duplicate items (same article from any feed) will be automatically skipped to avoid duplicate content in public news feed.</p>
    </div>
    <form method="post" action="rss-importer.php" class="admin-form rss-import-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
        <input type="hidden" name="action" value="import_feed">

        <div class="form-group">
            <label for="feeds">Select Feeds to Import</label>
            <div>
                <?php foreach ($feeds as $index => $feedUrl): ?>
                    <div class="feed-checkbox">
                        <input type="checkbox" name="feeds[]" value="<?= htmlspecialchars($feedUrl, ENT_QUOTES, "UTF-8") ?>" id="feed-<?= $index ?>" checked>
                        <label for="feed-<?= $index ?>"><?= htmlspecialchars($feedUrl, ENT_QUOTES, "UTF-8") ?></label>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($feeds)): ?>
                    <p class="form-help">No feeds configured. Please contact administrator.</p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="primary-button">Import Selected Feeds</button>
    </form>

    <?php if ($importResults !== null): ?>
        <div class="dashboard-card open-panel import-results-panel" aria-labelledby="import-results-heading">
            <div class="card-header">
                <h2 id="import-results-heading">Import Results</h2>
            </div>
            <div class="import-results">
                <p><strong>Feeds Processed:</strong> <?= htmlspecialchars($importResults['feeds_processed']) ?></p>
                <p><strong>New Items Imported:</strong> <span class="import-count"><?= htmlspecialchars($importResults['total_imported']) ?></span></p>
                <p><strong>Duplicates Skipped:</strong> <span class="skip-count"><?= htmlspecialchars($importResults['total_skipped']) ?></span></p>

                <?php if (!empty($importResults['errors'])): ?>
                    <div class="import-errors">
                        <h3>Errors Encountered:</h3>
                        <ul>
                            <?php foreach ($importResults['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="dashboard-card open-panel admin-section" aria-labelledby="rss-info-heading">
    <div class="card-header">
        <h2 id="rss-info-heading">About RSS Importing</h2>
    </div>
    <div class="rss-info">
        <p>This tool imports news items from RSS feeds into the Portfolio Management System.</p>
        <ul>
            <li><strong>Duplicate Prevention:</strong> Items are identified by their source URL only. The same article will not be imported twice, regardless of which feed it comes from.</li>
            <li><strong>Source Attribution:</strong> Imported items store which RSS feed they originated from for analytics.</li>
            <li><strong>Content Handling:</strong> Only the title, description/summary, and original article link are stored. Users can click "View source" to read the full article on the original website.</li>
            <li><strong>Coexistence:</strong> RSS-imported items appear alongside manually created news in the public news feed.</li>
            <li><strong>Audit Trail:</strong> Imported items show which admin user triggered the import.</li>
        </ul>
        <p><em>Currently configured feeds:</em>
<?php foreach ($feeds as $index => $feed): ?>
    <?php if ($index > 0) echo ", "; ?>
    <code><?= htmlspecialchars($feed) ?></code>
<?php endforeach; ?>
    </div>
</section>

<?php require_once "../includes/footer.php"; ?>