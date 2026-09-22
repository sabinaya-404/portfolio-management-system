<?php
date_default_timezone_set("Asia/Kathmandu");
require_once "includes/session.php";
require_once "config/database.php";

$message = "";
$message_type = "";

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

            $message = "If an account exists with that email, a reset request has been recorded. This local build does not deliver reset links; configure trusted email delivery before using password recovery in production.";
            $message_type = "success";
        } else {
            $message = "If an account exists with that email, a reset request has been recorded. This local build does not deliver reset links; configure trusted email delivery before using password recovery in production.";
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
    <meta name="theme-color" content="#f4f6f9">
    <title>Forgot Password | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon" style="margin: 0 auto 12px; width: 42px; height: 42px; font-size: 20px;">P</div>
            <h1>Reset Password</h1>
            <p>Enter your registered email to receive a reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" role="<?= $message_type === 'success' ? 'status' : 'alert' ?>" aria-live="polite">
                <?= htmlspecialchars($message) ?>
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
    </main>
</div>

</body>
</html>