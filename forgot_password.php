<?php
date_default_timezone_set("Asia/Kathmandu");
require_once "includes/session.php";
require_once "config/database.php";
require_once "includes/logger.php";

// Load email configuration
$mail_config = @require __DIR__ . '/config/mail.php';

// PHPMailer setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer autoloader
require 'vendor/autoload.php';

$message = "";
$message_type = "";
$reset_link = "";
$is_local_dev = in_array($_SERVER["HTTP_HOST"] ?? "", ["localhost", "127.0.0.1"], true)
    || in_array($_SERVER["SERVER_NAME"] ?? "", ["localhost", "127.0.0.1"], true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
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

                // Generate reset link
                $reset_link = "reset_password.php?token=" . urlencode($token);

                // Send email via Brevo (PHPMailer) or show link in dev
                if ($is_local_dev) {
                    // Development mode: Show link on page for testing
                    $message = "A reset token has been generated. For local development, use the link below to set your new password.";
                    $message_type = "success";
                } else {
                    // Production mode: Send actual email via Brevo
                    try {
                        $mail = new PHPMailer(true);

                        // Server settings - Using configured Brevo credentials
                        $mail->isSMTP();
                        $mail->Host       = $mail_config['host'] ?? 'smtp-relay.brevo.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $mail_config['username'] ?? '';
                        $mail->Password   = $mail_config['password'] ?? '';

                        // Handle secure connection setting
                        $secure = $mail_config['secure'] ?? 'STARTTLS';
                        if ($secure === 'STARTTLS') {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        } elseif ($secure === 'SMTPS') {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        } else {
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Default
                        }

                        $mail->Port       = $mail_config['port'] ?? 587;

                        // Recipients
                        $mail->setFrom($mail_config['from_email'] ?? '', $mail_config['from_name'] ?? 'Portfolio Management System');
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request - Portfolio Management System';

                        // Professional email body
                        $mail->Body = '
                        <html>
                        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                                <h2 style="color: #2563eb;">Password Reset Request</h2>
                                <p>Hello,</p>
                                <p>We received a request to reset your password for your Portfolio Management System account.</p>
                                <p>Click the button below to reset your password. This link will expire in 30 minutes for security:</p>
                                <div style="text-align: center; margin: 30px 0;">
                                    <a href="' . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . '"
                                       style="background-color: #2563eb; color: white; padding: 12px 28px;
                                              text-decoration: none; border-radius: 6px; font-weight: bold;
                                              display: inline-block;">
                                        Reset Your Password
                                    </a>
                                </div>
                                <p>If you did not request this password reset, please simply ignore this email.</p>
                                <p>This is an automated message - please do not reply to this email.</p>
                                <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
                                <p style="font-size: 0.9em; color: #666;">
                                    Portfolio Management System<br>
                                    <em>Securely managing your investments</em>
                                </p>
                            </div>
                        </body>
                        </html>
                        ';

                        // Plain text fallback for email clients that don't support HTML
                        $mail->AltBody = "
                        Password Reset Request - Portfolio Management System

                        Hello,

                        We received a request to reset your password for your Portfolio Management System account.

                        Please visit the following link to reset your password (this link will expire in 30 minutes):
                        " . $reset_link . "

                        If you did not request this password reset, please ignore this email.

                        This is an automated message - please do not reply to this email.

                        Portfolio Management System
                        ";

                        $mail->send();

                        $message = "If an account exists with that email, a password recovery request has been processed.";
                        $message_type = "success";
                    } catch (Exception $e) {
                        // Log the error (don't expose details to user for security)
                        error_log("Email sending failed: " . $mail->ErrorInfo);

                        // STILL show generic message - critical for security!
                        $message = "If an account exists with that email, a password recovery request has been processed.";
                        $message_type = "success";
                    }
                }
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