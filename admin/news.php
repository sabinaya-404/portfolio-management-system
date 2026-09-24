<?php
require_once "../includes/admin-auth.php";

$message = "";
$message_type = "error";
$editing_entry = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
        $action = (string) ($_POST["action"] ?? "");
        $entry_id = filter_var($_POST["entry_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($action === "delete" && $entry_id !== false && $entry_id !== null) {
            $stmt = $conn->prepare("DELETE FROM ipo_news WHERE id = ?");
            $stmt->bind_param("i", $entry_id);
            $message_type = $stmt->execute() && $stmt->affected_rows === 1 ? "success" : "error";
            $message = $message_type === "success" ? "Entry deleted." : "Entry was not found.";
            $stmt->close();
        } elseif (in_array($action, ["add", "edit"], true)) {
            $entry_type = (string) ($_POST["type"] ?? "");
            $title = trim((string) ($_POST["title"] ?? ""));
            $content = trim((string) ($_POST["content"] ?? ""));
            $publication_date = (string) ($_POST["publication_date"] ?? "");
            $source_url = trim((string) ($_POST["source_url"] ?? ""));
            $status = (string) ($_POST["status"] ?? "unpublished");
            $date_valid = DateTime::createFromFormat("Y-m-d", $publication_date);
            $url_valid = $source_url === "" || (filter_var($source_url, FILTER_VALIDATE_URL) && preg_match("/^https?:\/\//i", $source_url));
            if (!in_array($entry_type, ["ipo", "news"], true) || $title === "" || mb_strlen($title) > 200 || $content === "" || !$date_valid || $date_valid->format("Y-m-d") !== $publication_date || !$url_valid || mb_strlen($source_url) > 500 || !in_array($status, ["published", "unpublished"], true)) {
                $message = "Enter valid content details, publication date, and source URL.";
            } elseif ($action === "add") {
                $stmt = $conn->prepare("INSERT INTO ipo_news (created_by, type, title, content, publication_date, source_url, status) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?)");
                $stmt->bind_param("issssss", $current_user_id, $entry_type, $title, $content, $publication_date, $source_url, $status);
                $message_type = $stmt->execute() ? "success" : "error";
                $message = $message_type === "success" ? "Entry saved." : "Unable to save the entry.";
                $stmt->close();
            } elseif ($entry_id !== false && $entry_id !== null) {
                $stmt = $conn->prepare("UPDATE ipo_news SET type = ?, title = ?, content = ?, publication_date = ?, source_url = NULLIF(?, ''), status = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $entry_type, $title, $content, $publication_date, $source_url, $status, $entry_id);
                $message_type = $stmt->execute() ? "success" : "error";
                $message = $message_type === "success" ? "Entry updated." : "Unable to update the entry.";
                $stmt->close();
            } else {
                $message = "A valid entry is required for editing.";
            }
        } else {
            $message = "Invalid content action.";
        }
    }
}

if (isset($_GET["edit"])) {
    $edit_id = filter_var($_GET["edit"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($edit_id !== false && $edit_id !== null) {
        $stmt = $conn->prepare("SELECT id, type, title, content, publication_date, source_url, status FROM ipo_news WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $editing_entry = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$list_stmt = $conn->prepare("SELECT id, type, title, publication_date, status FROM ipo_news ORDER BY publication_date DESC, id DESC");
$list_stmt->execute();
$entries = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();

$page_title = "Manage IPO & News";
$page_category = "ADMIN";
$page_heading = "Manage IPO & News";
$active_page = "admin";
require_once "../includes/header.php";
?>
<?php if ($message !== ""): ?><div class="alert <?= $message_type === "success" ? "alert-success" : "alert-error" ?>" role="<?= $message_type === "success" ? "status" : "alert" ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<section class="dashboard-card" aria-labelledby="content-form-heading">
    <div class="card-header"><h2 id="content-form-heading"><?= $editing_entry ? "Edit entry" : "Add entry" ?></h2><?php if ($editing_entry): ?><a href="news.php" class="view-link">Cancel</a><?php endif; ?></div>
    <form method="post" class="auth-card" style="max-width: none; box-shadow: none; padding: 0;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"><input type="hidden" name="action" value="<?= $editing_entry ? "edit" : "add" ?>"><?php if ($editing_entry): ?><input type="hidden" name="entry_id" value="<?= (int) $editing_entry["id"] ?>"><?php endif; ?>
        <div class="form-group"><label for="type">Type</label><select id="type" name="type" required><option value="ipo" <?= ($editing_entry["type"] ?? "") === "ipo" ? "selected" : "" ?>>IPO</option><option value="news" <?= ($editing_entry["type"] ?? "news") === "news" ? "selected" : "" ?>>News</option></select></div>
        <div class="form-group"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= htmlspecialchars($editing_entry["title"] ?? "", ENT_QUOTES, "UTF-8") ?>"></div>
        <div class="form-group"><label for="content">Description / content</label><textarea id="content" name="content" rows="6" required><?= htmlspecialchars($editing_entry["content"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea></div>
        <div class="form-group"><label for="publication_date">Publication date</label><input id="publication_date" name="publication_date" type="date" required value="<?= htmlspecialchars($editing_entry["publication_date"] ?? date("Y-m-d"), ENT_QUOTES, "UTF-8") ?>"></div>
        <div class="form-group"><label for="source_url">Source URL <span class="muted-cell">(optional)</span></label><input id="source_url" name="source_url" type="url" maxlength="500" value="<?= htmlspecialchars($editing_entry["source_url"] ?? "", ENT_QUOTES, "UTF-8") ?>"></div>
        <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="unpublished" <?= ($editing_entry["status"] ?? "") === "unpublished" ? "selected" : "" ?>>Unpublished</option><option value="published" <?= ($editing_entry["status"] ?? "") === "published" ? "selected" : "" ?>>Published</option></select></div>
        <button type="submit" class="primary-button"><?= $editing_entry ? "Save changes" : "Save entry" ?></button>
    </form>
</section>
<section class="dashboard-card" aria-labelledby="content-list-heading"><div class="card-header"><h2 id="content-list-heading">All entries</h2><span class="muted-cell"><?= number_format(count($entries)) ?> entries</span></div>
<?php if (!$entries): ?><div class="empty-state"><h3>No entries yet</h3><p>Add an IPO or news update to publish it for authenticated users.</p></div><?php else: ?><div class="holdings-table-wrap"><table class="holdings-table"><thead><tr><th>TYPE</th><th>TITLE</th><th>DATE</th><th>STATUS</th><th>ACTIONS</th></tr></thead><tbody>
<?php foreach ($entries as $entry): ?><tr><td><?= htmlspecialchars(strtoupper($entry["type"]), ENT_QUOTES, "UTF-8") ?></td><th scope="row"><?= htmlspecialchars($entry["title"], ENT_QUOTES, "UTF-8") ?></th><td><?= htmlspecialchars($entry["publication_date"], ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(ucfirst($entry["status"]), ENT_QUOTES, "UTF-8") ?></td><td><a class="action-link" href="news.php?edit=<?= (int) $entry["id"] ?>">Edit</a><form method="post" style="display:inline; margin-left:10px;"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="entry_id" value="<?= (int) $entry["id"] ?>"><button type="submit" class="action-link-danger">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php require_once "../includes/footer.php"; ?>