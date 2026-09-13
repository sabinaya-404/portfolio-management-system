<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session array values
$_SESSION = [];

// Delete the actual session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session on server
session_destroy();

header("Location: login.php");
exit;