<?php
date_default_timezone_set("Asia/Kathmandu");
require_once "includes/session.php";
require_once "config/database.php";

$message = "";
$message_type = "";
$reset_link = "";
$is_local_dev = in_array($_SERVER["HTTP_HOST"] ?? "", ["localhost", "127.0.0.1"], true)
    || in_array($_SERVER["SERVER_NAME"] ?? "", ["localhost", "127.0.0.1"], true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired or the request was invalid. Please try again.";
        $message_type = "error";
    } else {
        $email = trim($_POST["email"] ?? "");

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                $user_id    = (int)$user["id"];
                $token      = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);
                $expires_at = date("Y-m-d H:i:s", time() + (30 * 60));

                // Clean previous tokens
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                // Insert new token
                $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $token_hash, $expires_at);
                $stmt->execute();
                $stmt->close();

                if ($is_local_dev) {
                    $reset_link = "reset_password.php?token=" . urlencode($token);
                    $message = "A reset token has been generated. For local development, use the link below to set your new password.";
                } else {
                    $message = "If an account exists with that email, a password recovery request has been processed.";
                }
                $message_type = "success";
            } else {
                $message = "If an account exists with that email, a password recovery request has been processed.";
                $message_type = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f6f9">
    <title>Forgot Password | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon">P</div>
            <h1>Reset Password</h1>
            <p>Enter your registered email to receive a reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" role="<?= $message_type === 'success' ? 'status' : 'alert' ?>" aria-live="polite">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reset_link) && $is_local_dev): ?>
            <div class="reset-link-box">
                <span class="status status-unpublished">Development Mode</span>
                <p class="form-help">Development reset link:</p>
                <a href="<?= htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') ?>" class="view-link">Reset your password →</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="150" placeholder="name@example.com" required autocomplete="email">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            <a href="login.php">← Back to Sign In</a>
        </div>
    </main>
</div>

</body>
</html>