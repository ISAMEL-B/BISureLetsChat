<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Kampala');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../../plugins/PHPMailer/src/Exception.php';
require '../../plugins/PHPMailer/src/PHPMailer.php';
require '../../plugins/PHPMailer/src/SMTP.php';

// Database connection
require_once __DIR__ . '/../../config/db.php';

$email = '';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Automatically expire unused reset tokens
            $conn->query("UPDATE password_resets SET used_at = NOW() WHERE expires_at < NOW() AND used_at IS NULL");
            
            // Check if email exists in users table
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                
                // Generate cryptographically secure token (longer for better security)
                $reset_token = bin2hex(random_bytes(32)); // 64-character hex token
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store the reset token in password_resets table
                $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->bind_param('iss', $user_id, $reset_token, $expires_at);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_attempt_time'] = time();
                    
                    // Rate limiting - only allow 3 attempts per hour
                    if (!isset($_SESSION['reset_attempts'])) {
                        $_SESSION['reset_attempts'] = 1;
                    } else {
                        $_SESSION['reset_attempts']++;
                    }
                    
                    // Check if last reset was more than 1 hour ago
                    if (isset($_SESSION['reset_attempt_time']) && 
                        (time() - $_SESSION['reset_attempt_time']) > 3600) {
                        $_SESSION['reset_attempts'] = 1;
                        $_SESSION['reset_attempt_time'] = time();
                    }
                    
                    if ($_SESSION['reset_attempts'] > 100) {
                        $error = "Too many reset attempts. Please try again later.";
                    } else {
                        // Create reset link instead of code
                        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "bisureletschat/auth/forgot/reset-password.php?token=" . $reset_token;
                        
                        // Send email
                        $mail = new PHPMailer(true);
                        
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'byaruhangangaisamelk@gmail.com';
                            $mail->Password   = 'wrzc qckn fdgz relr';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            $mail->SMTPDebug  = 0; // Change to 2 for debugging

                            // Recipients
                            $mail->setFrom('byaruhangaisamelk@gmail.com', 'BisureChat Support');
                            $mail->addAddress($email);
                            $mail->addReplyTo('byaruhangaisamelk@gmail.com', 'Support Team');

                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Reset Your BisureChat Password';
                            
                            // Modern email template
                            $mail->Body = "
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                    <div style='background-color: #128C7E; padding: 20px; text-align: center;'>
                                        <h1 style='color: white; margin: 0;'>BisureChat</h1>
                                    </div>
                                    <div style='padding: 30px; background-color: #f9f9f9;'>
                                        <h2 style='color: #333;'>Password Reset Request</h2>
                                        <p>Hello,</p>
                                        <p>We received a request to reset your BisureChat password. Click the button below to reset it:</p>
                                        <div style='text-align: center; margin: 25px 0;'>
                                            <a href='$reset_link' style='display: inline-block; padding: 15px 25px; background-color: #075E54; color: white; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 5px;'>
                                                Reset Your Password
                                            </a>
                                        </div>
                                        <p>This link will expire in 15 minutes. If you didn't request this, please ignore this email.</p>
                                        <p style='margin-top: 30px;'>Thanks,<br>The BisureChat Team</p>
                                    </div>
                                    <div style='padding: 15px; text-align: center; background-color: #f0f0f0; font-size: 12px; color: #666;'>
                                        © ".date('Y')." BisureChat. All rights reserved.
                                    </div>
                                </div>
                            ";
                            
                            $mail->AltBody = "To reset your BisureChat password, please visit: $reset_link\nThis link expires in 15 minutes.";

                            $mail->send();
                            $success = "A password reset link has been sent to your email.";
                        } catch (Exception $e) {
                            error_log("Mailer Error: " . $mail->ErrorInfo);
                            $error = "We couldn't send the email. Please try again later.";
                        }
                    }
                } else {
                    $error = "Failed to generate reset token. Please try again.";
                }
                $insert_stmt->close();
            } else {
                // Don't reveal if email exists or not for security
                $success = "If an account exists with that email, a password reset link has been sent.";
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            $error = "A database error occurred. Please try again later.";
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | BisureChat</title>
    <link rel="icon" href="../../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #128C7E;
            --primary-dark: #075E54;
            --secondary-color: #25D366;
            --text-light: #FFFFFF;
            --text-dark: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --border-color: #DDDDDD;
            --error-color: #E74C3C;
            --success-color: #2ECC71;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #F0F2F5 0%, #E8F5F3 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main content wrapper */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1px 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 520px;
            background-color: var(--text-light);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 35px 30px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 26px;
            font-weight: 500;
            margin: 0;
        }
        
        .auth-header .icon {
            font-size: 52px;
            margin-bottom: 12px;
            display: block;
        }
        
        .auth-body {
            padding: 35px 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #FAFBFC;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(18, 140, 126, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
            letter-spacing: 0.3px;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #0a6b5e, #0e7d6e);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(18, 140, 126, 0.3);
        }
        
        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 16px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.08);
            color: #C0392B;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.08);
            color: #27AE60;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 24px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .info-text {
            margin-bottom: 24px;
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.7;
        }

        /* Footer */
        .site-footer {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid rgba(18, 140, 126, 0.1);
            padding: 24px 20px;
            text-align: center;
        }
        
        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .footer-security {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .footer-security i {
            color: var(--secondary-color);
            font-size: 18px;
        }
        
        .footer-features {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        
        .footer-feature {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            background: rgba(18, 140, 126, 0.05);
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid rgba(18, 140, 126, 0.1);
        }
        
        .footer-feature i {
            color: var(--secondary-color);
            font-size: 13px;
        }
        
        .footer-text {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .footer-help {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .footer-help a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-help a:hover {
            text-decoration: underline;
        }
        
        .footer-copyright {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(18, 140, 126, 0.1);
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Loading spinner animation */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============================================
           RESPONSIVE STYLES
           ============================================ */
        @media (min-width: 1200px) {
            .auth-container {
                max-width: 560px;
            }
            
            .auth-header {
                padding: 45px 35px;
            }
            
            .auth-header .icon {
                font-size: 60px;
            }
            
            .auth-header h1 {
                font-size: 28px;
            }
            
            .auth-body {
                padding: 40px 35px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1px 1px;
            }
            
            .site-footer {
                padding: 20px 15px;
            }
        }
        
        @media (max-width: 520px) {
            body {
                background: var(--text-light);
            }
            
            .main-content {
                padding: 2px 0;
            }
            
            .auth-container {
                border-radius: 0;
                box-shadow: none;
            }
            
            .auth-header {
                padding: 28px 20px;
            }
            
            .auth-header .icon {
                font-size: 40px;
                margin-bottom: 8px;
            }
            
            .auth-header h1 {
                font-size: 22px;
            }
            
            .auth-body {
                padding: 25px 20px;
            }
            
            .footer-features {
                gap: 8px;
            }
            
            .footer-feature {
                padding: 6px 10px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 380px) {
            .auth-header {
                padding: 22px 16px;
            }
            
            .auth-body {
                padding: 20px 16px;
            }
            
            .form-control {
                padding: 14px 16px;
                font-size: 15px;
            }
            
            .btn {
                padding: 14px;
                font-size: 15px;
            }
        }
        
        /* Landscape mode on mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .main-content {
                padding: 8px;
            }
            
            .auth-header {
                padding: 20px;
            }
            
            .auth-header .icon {
                font-size: 30px;
                margin-bottom: 4px;
            }
            
            .auth-header h1 {
                font-size: 18px;
            }
            
            .auth-body {
                padding: 16px 18px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .info-text {
                margin-bottom: 12px;
                font-size: 12px;
            }
            
            .site-footer {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Main content - centered form -->
    <div class="main-content">
        <div class="auth-container">
            <div class="auth-header">
                <span class="icon">🔐</span>
                <h1>Reset Your Password</h1>
            </div>
            
            <div class="auth-body">
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <p class="info-text">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
                
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="your@email.com" required 
                               value="<?php echo htmlspecialchars($email); ?>"
                               autocomplete="email">
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer section -->
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-security">
                <i class="fas fa-shield-alt"></i>
                <span>Your Security Matters</span>
            </div>
            <p class="footer-text">
                We take your account security seriously. The reset link will expire in 15 minutes and can only be used once. Never share your reset link with anyone.
            </p>
            <div class="footer-features">
                <div class="footer-feature">
                    <i class="fas fa-lock"></i>
                    <span>Encrypted</span>
                </div>
                <div class="footer-feature">
                    <i class="fas fa-clock"></i>
                    <span>15 Min Expiry</span>
                </div>
                <div class="footer-feature">
                    <i class="fas fa-user-lock"></i>
                    <span>Single Use</span>
                </div>
            </div>
            <p class="footer-help">
                Need help? Contact <a href="mailto:support@bisurechat.org">support@bisurechat.org</a>
            </p>
            <div class="footer-copyright">
                © <?php echo date('Y'); ?> BisureChat. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script>
        // Prevent double submission
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        });
    </script>
</body>
</html>