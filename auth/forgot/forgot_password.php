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
                    
                    if ($_SESSION['reset_attempts'] > 3) {
                        $error = "Too many reset attempts. Please try again later.";
                    } else {
                        // Create reset link instead of code
                        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $reset_token;
                        
                        // Send email
                        $mail = new PHPMailer(true);
                        
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'byaruhangaisamelk@gmail.com';
                            $mail->Password   = 'jctz chkz ckxf lckx';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            $mail->SMTPDebug  = 0; // Change to 2 for debugging

                            // Recipients
                            $mail->setFrom('byaruhangaisamelk@gmail.com', 'BisureChat Support');
                            $mail->addAddress($email);
                            $mail->addReplyTo('support@bisurechat.org', 'Support Team');

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
            background-color: #F0F2F5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            background-color: var(--text-light);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background-color: var(--primary-dark);
            color: var(--text-light);
            padding: 25px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 24px;
            font-weight: 500;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: var(--text-light);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .auth-footer {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            border-top: 1px solid var(--border-color);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        @media (max-width: 480px) {
            .auth-container {
                border-radius: 0;
            }
            
            body {
                padding: 0;
                background-color: var(--text-light);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Reset Your Password</h1>
        </div>
        
        <div class="auth-body">
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to login
            </a>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
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
        
        <div class="auth-footer">
            © <?php echo date('Y'); ?> BisureChat. All rights reserved.
        </div>
    </div>
    
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