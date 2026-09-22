<?php
require_once "includes/session.php";

// If user is already logged in, go straight to dashboard; otherwise go to login
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;