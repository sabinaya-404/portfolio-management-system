<?php
require_once __DIR__ . "/session.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

$admin_stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
if (!$admin_stmt) {
    header("Location: ../dashboard.php");
    exit;
}

$admin_stmt->bind_param("i", $current_user_id);
$admin_stmt->execute();
$admin_user = $admin_stmt->get_result()->fetch_assoc();
$admin_stmt->close();

if (!$admin_user || $admin_user["role"] !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

$current_user_role = "admin";
$base_path = "../";
$asset_path = "../assets/";