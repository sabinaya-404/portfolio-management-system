<?php
require_once "includes/auth.php";
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $demat_id = (int)($_POST["id"] ?? 0);

    if ($demat_id > 0) {
        $stmt = $conn->prepare("DELETE FROM demat_accounts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $demat_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: my_demat.php");
exit;