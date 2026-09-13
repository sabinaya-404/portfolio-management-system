<?php
 //start session
 if (session_status() === PHP_SESSION_NONE) {
    session_start();
 }
 // redirects login
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
    // automatically generates a CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $current_user_id   = (int)$_SESSION["user_id"];
    $current_user_name = $_SESSION["user_name"] ?? "User";
    $current_user_role = $_SESSION["role"] ?? "user";