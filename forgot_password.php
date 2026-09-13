<?php
date_default_timezone_set("Asia/Kathmandu");
session_start();
require_once "config/database.php";

$message = "";
$message_type = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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

            $reset_link = "reset_password.php?token=" . urlencode($token);
            $message = "A reset token has been generated. Use the link below to set your new password.";
            $message_type = "success";
        } else {
            $message = "If an account exists with that email, a password reset link has been created.";
            $message_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon" style="margin: 0 auto 12px; width: 42px; height: 42px; font-size: 20px;">P</div>
            <h1>Reset Password</h1>
            <p>Enter your registered email to receive a reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reset_link)): ?>
            <div class="reset-link-box">
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 6px;">Click here to reset your password:</p>
                <a href="<?= htmlspecialchars($reset_link) ?>">Proceed to Reset Password →</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" required autocomplete="email">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            <a href="login.php">← Back to Sign In</a>
        </div>
    </div>
</div>

</body>
</html>