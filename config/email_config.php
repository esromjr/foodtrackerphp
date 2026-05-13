<?php
// config/email_config.php

// Load PHPMailer classes
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    
    try {
        // === CHANGE THESE 2 LINES ===
        $your_gmail = 'esrotamerat@gmail.com';        // ← CHANGE THIS
        $your_app_password = 'tpec azfy ddku takn';   // ← CHANGE THIS (no spaces)
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $your_gmail;
        $mail->Password   = $your_app_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom($your_gmail, 'Ethiopian Food Tracker');
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - Ethiopian Food Tracker';
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #C0533B;'>🍲 Ethiopian Food Tracker</h2>
                <p>Hello <strong>$toName</strong>,</p>
                <p>Click the button below to reset your password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' style='background: #C0533B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Reset Password</a>
                </div>
                <p>This link expires in <strong>1 hour</strong>.</p>
                <p>If you didn't request this, ignore this email.</p>
                <hr>
                <small>Ethiopian Food Calorie Tracker - School Project</small>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Reset your password: $resetLink (Expires in 1 hour)";
        
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
?>