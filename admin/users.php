<?php
require_once "../includes/admin-auth.php";

$message = "";
$message_type = "error";
$search = trim((string) ($_GET["search"] ?? ""));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
        $user_id = filter_var($_POST["user_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $role = (string) ($_POST["role"] ?? "");
        if (($user_id === false || $user_id === null) || !in_array($role, ["user", "admin"], true)) {
            $message = "A valid user and role are required.";
        } else {
            $conn->begin_transaction();
            try {
                $count_result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' FOR UPDATE");
                $admin_count = (int) ($count_result->fetch_assoc()["total"] ?? 0);
                $target_stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $target_stmt->bind_param("i", $user_id);
                $target_stmt->execute();
                $target = $target_stmt->get_result()->fetch_assoc();
                $target_stmt->close();

                if (!$target) {
                    throw new RuntimeException("User was not found.");
                }
                if ($target["role"] === "admin" && $role === "user" && $admin_count <= 1) {
                    throw new RuntimeException("The last administrator cannot be removed.");
                }

                $update_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $update_stmt->bind_param("si", $role, $user_id);
                if (!$update_stmt->execute()) {
                    throw new RuntimeException("Unable to update the user role.");
                }
                $update_stmt->close();
                $conn->commit();
                $message_type = "success";
                $message = "User role updated.";
            } catch (RuntimeException $error) {
                $conn->rollback();
                $message = $error->getMessage();
            }
        }
    }
}

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
$active_page = "admin";
require_once "../includes/header.php";
?>
<?php if ($message !== ""): ?><div class="alert <?= $message_type === "success" ? "alert-success" : "alert-error" ?>" role="<?= $message_type === "success" ? "status" : "alert" ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<section class="dashboard-card" aria-labelledby="users-heading">
    <div class="card-header"><h2 id="users-heading">Users</h2><form method="get"><label class="visually-hidden" for="user-search">Search users</label><input id="user-search" name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" placeholder="Name or email"><button type="submit" class="primary-button">Search</button></form></div>
    <?php if (!$users): ?><div class="empty-state"><h3>No users found</h3></div><?php else: ?><div class="holdings-table-wrap"><table class="holdings-table"><thead><tr><th>NAME</th><th>EMAIL</th><th>ROLE</th><th>JOINED</th><th>ACTION</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?><tr><th scope="row"><?= htmlspecialchars($user["name"], ENT_QUOTES, "UTF-8") ?></th><td><?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(ucfirst($user["role"]), ENT_QUOTES, "UTF-8") ?></td><td><?= htmlspecialchars(date("M j, Y", strtotime($user["created_at"])), ENT_QUOTES, "UTF-8") ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"><input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>"><label class="visually-hidden" for="role-<?= (int) $user["id"] ?>">Role for <?= htmlspecialchars($user["name"], ENT_QUOTES, "UTF-8") ?></label><select id="role-<?= (int) $user["id"] ?>" name="role"><option value="user" <?= $user["role"] === "user" ? "selected" : "" ?>>User</option><option value="admin" <?= $user["role"] === "admin" ? "selected" : "" ?>>Admin</option></select><button type="submit" class="action-link">Save</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php require_once "../includes/footer.php"; ?>