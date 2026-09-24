<?php
require_once "includes/session.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

require_once "config/database.php";

$message = "";
$message_type = "error";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? null)) {
        $message = "Your session expired or the request was invalid. Please try again.";
    } else {
        $name             = trim($_POST["name"] ?? "");
        $email            = trim($_POST["email"] ?? "");
        $password         = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $message = "Please fill in all required fields.";
        } elseif (mb_strlen($name) > 100) {
            $message = "Full name cannot exceed 100 characters.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (strlen($email) > 150) {
            $message = "Email address cannot exceed 150 characters.";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters long.";
        } elseif (strlen($password) > 72) {
            $message = "Password cannot exceed 72 characters.";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                $message = "An account with this email already exists.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashedPassword);

                if ($stmt->execute()) {
                    $message = "Registration successful! You can now log in.";
                    $message_type = "success";
                } else {
                    $message = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
            $check->close();
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
    <title>Create Account | Portfolio Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>
<main id="main-content" class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon" style="margin: 0 auto 12px; width: 42px; height: 42px; font-size: 20px;">P</div>
            <h1>Create an Account</h1>
            <p>Start tracking your Demat portfolios today</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" role="<?= $message_type === 'success' ? 'status' : 'alert' ?>" aria-live="polite">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100" placeholder="John Doe" required autocomplete="name">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="150" placeholder="name@example.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" maxlength="72" placeholder="At least 6 characters" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" maxlength="72" placeholder="Repeat your password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </main>
</div>

</body>
</html>