<?php
require_once "includes/auth.php";
require_once "config/database.php";

$type = (string) ($_GET["type"] ?? "");
if (!in_array($type, ["ipo", "news"], true)) {
    $type = "";
}

$query = "SELECT id, type, title, content, publication_date, source_url
          FROM ipo_news WHERE status = 'published'";
$params = [];
if ($type !== "") {
    $query .= " AND type = ?";
    $params[] = $type;
}
$query .= " ORDER BY publication_date DESC, id DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param("s", $params[0]);
}
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "IPO & News";
$page_category = "MARKET UPDATES";
$page_heading = "IPO & News";
$active_page = "ipo";
require_once "includes/header.php";
?>
<section class="dashboard-card" aria-labelledby="ipo-news-heading">
    <div class="card-header">
        <div><p class="holdings-eyebrow">Published updates</p><h2 id="ipo-news-heading">IPO & News</h2></div>
        <form method="get"><label class="visually-hidden" for="content-type">Filter by type</label><select id="content-type" name="type" onchange="this.form.submit()"><option value="">All updates</option><option value="ipo" <?= $type === "ipo" ? "selected" : "" ?>>IPO</option><option value="news" <?= $type === "news" ? "selected" : "" ?>>News</option></select></form>
    </div>
    <?php if (!$entries): ?>
        <div class="empty-state"><h3>No published updates</h3><p>Published IPO and news information will appear here when an administrator adds it.</p></div>
    <?php else: ?>
        <?php foreach ($entries as $entry): ?>
            <article class="content-entry">
                <div class="content-entry-header"><div><span class="status-badge"><?= htmlspecialchars(strtoupper($entry["type"]), ENT_QUOTES, "UTF-8") ?></span><h3><?= htmlspecialchars($entry["title"], ENT_QUOTES, "UTF-8") ?></h3></div><time datetime="<?= htmlspecialchars($entry["publication_date"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars(date("M j, Y", strtotime($entry["publication_date"])), ENT_QUOTES, "UTF-8") ?></time></div>
                <p class="content-entry-body"><?= nl2br(htmlspecialchars($entry["content"], ENT_QUOTES, "UTF-8")) ?></p>
                <?php if (!empty($entry["source_url"])): ?><p><a class="view-link" href="<?= htmlspecialchars($entry["source_url"], ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener noreferrer">View source</a></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require_once "includes/footer.php"; ?>