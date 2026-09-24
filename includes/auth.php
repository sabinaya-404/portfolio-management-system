<?php
 require_once __DIR__ . "/session.php";
 // redirects login
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
    $current_user_id   = (int)$_SESSION["user_id"];
    $current_user_name = $_SESSION["user_name"] ?? "User";
    $current_user_role = $_SESSION["role"] ?? "user";