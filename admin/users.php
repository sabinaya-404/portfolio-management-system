<?php
require_once "../includes/admin-auth.php";

$message = "";
$message_type = "error";
$search = trim((string) ($_GET["search"] ?? ""));


$query = "SELECT id, name, email, role, created_at FROM users";
$params = [];
if ($search !== "") {
    $query .= " WHERE name LIKE ? OR email LIKE ?";
    $term = "%" . $search . "%";
    $params = [$term, $term];
}
$query .= " ORDER BY created_at DESC, id DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param("ss", $params[0], $params[1]);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Manage Users";
$page_category = "ADMIN";
$page_heading = "Manage Users";
$active_page = "admin-users";
require_once "../includes/header.php";
?>
<?php if ($message !== ""): ?><div class="alert <?= $message_type === "success" ? "alert-success" : "alert-error" ?>" role="<?= $message_type === "success" ? "status" : "alert" ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<section class="dashboard-card open-panel admin-section" aria-labelledby="users-heading">
    <div class="card-header"><h2 id="users-heading">Users</h2><form method="get" class="filter-inline"><label class="visually-hidden" for="user-search">Search users</label><input id="user-search" name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" placeholder="Name or email"><button type="submit" class="primary-button">Search</button></form></div>
    <?php if (!$users): ?><div class="empty-state"><h3>No users found</h3><p>Try a different name or email search.</p></div><?php else: ?><div class="holdings-table-wrap"><table class="holdings-table"><thead><tr><th scope="col">NAME</th><th scope="col">EMAIL</th><th scope="col">ROLE</th><th scope="col">JOINED</th><th scope="col">ACTION</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?><tr><th scope="row"><?= htmlspecialchars($user["name"], ENT_QUOTES, "UTF-8") ?></th><td><?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(ucfirst($user["role"]), ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(date("M j, Y", strtotime($user["created_at"])), ENT_QUOTES, "UTF-8") ?></td><td></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php require_once "../includes/footer.php"; ?>