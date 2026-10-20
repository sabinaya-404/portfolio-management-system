<?php
require_once "includes/session.php";
require_once "includes/rate_limit.php";
require_once "includes/logger.php";

// If already logged in, redirect to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

require_once "config/database.php";

// Rate limiting configuration
$ip_address = get_client_ip();
$endpoint = "login";
$max_attempts = 5; // Five failed attempts before lockout
$window_seconds = 300; // Counting window
$lockout_seconds = 1200; // 20-minute lockout

$message = "";
$rate_limited = is_rate_limited($ip_address, $endpoint, $max_attempts, $window_seconds, $lockout_seconds);
if ($rate_limited) {
    http_response_code(429); // Too Many Requests
    $message = "Too many login attempts. Please try again later.";
}

if (!$rate_limited && $_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");

    if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired or the request was invalid. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role, account_status FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!password_verify($password, $user["password"])) {
                // Record failed attempt for rate limiting.
                $failed_attempts = record_failed_attempt($ip_address, $endpoint, $window_seconds, $max_attempts, $lockout_seconds);
            } elseif ($user["account_status"] === "disabled") {
                $message = "Your account has been disabled by the admin.";
            } else {
                // Clear rate limit on successful login
                clear_rate_limit($ip_address, $endpoint);

                session_regenerate_id(true);
                $_SESSION["user_id"]   = (int)$user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["role"]      = $user["role"];

                header("Location: dashboard.php");
                exit;
            }
        } else {
            // Record failed attempt for rate limiting (even if user doesn't exist).
            $failed_attempts = record_failed_attempt($ip_address, $endpoint, $window_seconds, $max_attempts, $lockout_seconds);
        }

        if (isset($failed_attempts)) {
            if ($failed_attempts >= $max_attempts) {
                $message = "Login attempts from this IP are temporarily restricted for 20 minutes.";
            } else {
                $remaining_attempts = $max_attempts - $failed_attempts;
                $message = "Invalid email or password. {$remaining_attempts} attempts remaining before temporary lockout.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f6f9">
    <title>Login | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon">P</div>
            <h1>Portfolio Manager</h1>
            <p>Access your investment & Demat portfolio</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-error" role="alert" aria-live="polite">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <div class="auth-password-heading">
                    <label for="password">Password</label>
                </div>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Create an account</a>
        </div>
    </div>
</main>

</body>
</html>