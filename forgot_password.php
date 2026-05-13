<?php
session_start();
require_once 'config/database.php';
require_once 'config/email_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        $check->close();
        
        if (!$user) {
            $error = 'No account found with this email address.';
        } else {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete old tokens
            $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete->bind_param("s", $email);
            $delete->execute();
            
            // Insert new token
            $insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $email, $token, $expires);
            
            if ($insert->execute()) {
                $reset_link = "http://localhost:8000/reset_password.php?token=" . $token;
                
                if (sendResetEmail($email, $user['fullname'], $reset_link)) {
                    $success = " Password reset link has been sent to your email!";
                } else {
                    $error = " Email failed to send. Please make sure your Gmail credentials are correct.";
                }
            } else {
                $error = "Failed to generate reset token.";
            }
            $insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="landing-wrapper">
    <div class="auth-cards" style="max-width: 500px; margin: 50px auto;">
        <div class="auth-card">
            <h2> Forgot Password?</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <p>Check your email inbox (and spam folder).</p>
                <a href="landing.php" class="btn btn-primary">Back to Login</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reset Email</button>
                </form>
                <p><a href="landing.php">← Back to Login</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>