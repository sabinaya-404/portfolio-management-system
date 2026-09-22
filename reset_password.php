<?php
date_default_timezone_set("Asia/Kathmandu");
require_once "includes/session.php";
require_once "config/database.php";

$message = "";
$message_type = "";
$token = $_GET["token"] ?? "";
$reset = null;

if (empty($token)) {
    $message = "Invalid password reset link.";
    $message_type = "error";
} else {
    $token_hash   = hash("sha256", $token);
    $current_time = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > ? LIMIT 1");
    $stmt->bind_param("ss", $token_hash, $current_time);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        $message = "This password reset link is invalid or has expired.";
        $message_type = "error";
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $password         = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if (strlen($password) < 6) {
            $message = "Password must be at least 6 characters long.";
            $message_type = "error";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match.";
            $message_type = "error";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $user_id = (int)$reset["user_id"];

            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $user_id);
            $stmt->execute();
            $stmt->close();

            // Invalidate token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE id = ?");
            $stmt->bind_param("i", $reset["id"]);
            $stmt->execute();
            $stmt->close();

            $message = "Your password has been reset successfully! You can now log in.";
            $message_type = "success";
            $reset = null;
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
    <title>Set New Password | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon" style="margin: 0 auto 12px; width: 42px; height: 42px; font-size: 20px;">P</div>
            <h1>Create New Password</h1>
            <p>Set a secure password for your account</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" role="<?= $message_type === 'success' ? 'status' : 'alert' ?>" aria-live="polite">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reset)): ?>
            <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your new password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Update Password
                </button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            <a href="login.php">Back to Sign In</a>
        </div>
    </main>
</div>

</body>
</html>