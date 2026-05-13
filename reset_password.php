<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$email = '';

if (empty($token)) {
    header("Location: landing.php");
    exit;
}

// Validate token
$stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();
$stmt->close();

if (!$reset) {
    $error = 'Invalid or expired reset link.';
} elseif (date('Y-m-d H:i:s') > $reset['expires_at']) {
    $error = 'Reset link has expired (1 hour limit). Please request a new one.';
} else {
    $email = $reset['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        
        // Update password
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        
        if ($update->execute()) {
            // Delete used token
            $delete = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $delete->bind_param("s", $token);
            $delete->execute();
            
            $success = 'Password reset successfully! You can now login with your new password.';
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Ethiopian Food Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="landing-wrapper">
    <div class="auth-cards" style="max-width: 500px; margin: 50px auto;">
        <div class="auth-card">
            <h2>🔑 Reset Password</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <a href="landing.php" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">Go to Login</a>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <a href="forgot_password.php" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">Request New Reset Link</a>
            <?php else: ?>
                <p style="margin-bottom: 20px;">Enter your new password for: <strong><?= htmlspecialchars($email) ?></strong></p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="password">New Password (min. 6 characters)</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Reset Password</button>
                </form>
                
                <p style="text-align: center; margin-top: 20px;">
                    <a href="landing.php">← Back to Login</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>