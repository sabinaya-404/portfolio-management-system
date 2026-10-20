<?php
require_once __DIR__ . "/session.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/auth.php";

if ($current_user_role !== "admin") {
    header("Location: ../dashboard.php");
    exit;
}

$current_user_role = "admin";
$base_path = "../";
$asset_path = "../assets/";