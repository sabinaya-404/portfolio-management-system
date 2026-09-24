<?php
require_once "includes/auth.php";
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $demat_id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $_SESSION["flash_error"] = "Your session expired. Refresh the page and try again.";
    } elseif ($demat_id !== false && $demat_id !== null) {
        $stmt = $conn->prepare("DELETE FROM demat_accounts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $demat_id, $current_user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash_success'] = "Demat account deleted successfully.";
        } else {
            $_SESSION['flash_error'] = "Could not delete the Demat account.";
        }
        $stmt->close();
    } else {
        $_SESSION["flash_error"] = "A valid Demat account is required.";
    }
}

header("Location: my_demat.php");
exit;