<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    ini_set("session.use_trans_sid", "0");
    ini_set("session.gc_maxlifetime", "1800");

    $is_https = !empty($_SERVER["HTTPS"])
        && strtolower((string) $_SERVER["HTTPS"]) !== "off";

    session_set_cookie_params([
        "lifetime" => 1800,
        "path" => "/",
        "secure" => $is_https,
        "httponly" => true,
        "samesite" => "Lax",
    ]);

    session_start();
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if (!function_exists("verify_csrf_token")) {
    function verify_csrf_token(?string $token): bool
    {
        return !empty($token)
            && !empty($_SESSION["csrf_token"])
            && hash_equals((string) $_SESSION["csrf_token"], (string) $token);
    }
}
