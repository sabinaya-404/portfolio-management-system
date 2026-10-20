<?php
require_once __DIR__ . "/session.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$login_path = basename(dirname((string) ($_SERVER["SCRIPT_NAME"] ?? ""))) === "admin"
    ? "../login.php"
    : "login.php";

$invalidate_session = static function () use ($login_path): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    header("Location: " . $login_path);
    exit;
};

$current_user_id = (int) $_SESSION["user_id"];
$user_stmt = null;

try {
    $user_stmt = $conn->prepare(
        "SELECT id, name, role, account_status FROM users WHERE id = ? LIMIT 1"
    );

    if (!$user_stmt) {
        $invalidate_session();
    }

    $user_stmt->bind_param("i", $current_user_id);
    if (!$user_stmt->execute()) {
        $invalidate_session();
    }

    $user_result = $user_stmt->get_result();
    $current_user = $user_result ? $user_result->fetch_assoc() : null;
    $user_stmt->close();
} catch (mysqli_sql_exception $e) {
    if ($user_stmt instanceof mysqli_stmt) {
        $user_stmt->close();
    }
    $invalidate_session();
}

if (!$current_user || $current_user["account_status"] !== "active") {
    $invalidate_session();
}

$current_user_id = (int) $current_user["id"];
$current_user_name = $current_user["name"];
$current_user_role = $current_user["role"];