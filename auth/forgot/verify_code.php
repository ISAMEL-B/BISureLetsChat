<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once __DIR__ . '/../../config/db.php';

$error = '';

// Check if session has the reset email
if (!isset($_SESSION['reset_email'])) {
    echo '<script>alert("Session expired. Please request a new password reset."); window.location.href = "forgot_password.php";</script>';
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reset_code = trim($_POST['reset_code'] ?? '');
    
    if (empty($reset_code)) {
        $error = "Please enter the reset code.";
    } else {
        try {
            // Get user ID from email
            $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->bind_param('s', $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $error = "User not found.";
            } else {
                $user = $user_result->fetch_assoc();
                $user_id = $user['id'];
                
                // Check if the reset token/code matches and is not expired
                // Since we're using tokens in password_resets table, we check there
                $token_stmt = $conn->prepare("
                    SELECT id, reset_token 
                    FROM password_resets 
                    WHERE user_id = ? 
                    AND reset_token = ? 
                    AND used_at IS NULL 
                    AND expires_at > NOW()
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $token_stmt->bind_param('is', $user_id, $reset_code);
                $token_stmt->execute();
                $token_result = $token_stmt->get_result();
                
                if ($token_result->num_rows > 0) {
                    $token_data = $token_result->fetch_assoc();
                    
                    // Store token in session for the reset password page
                    $_SESSION['reset_token'] = $reset_code;
                    $_SESSION['reset_verified'] = true;
                    
                    // Redirect to password reset form
                    echo '<script>window.location.href = "reset_password.php?token=' . urlencode($reset_code) . '";</script>';
                    exit;
                } else {
                    $error = "Invalid code or the code has expired. Please request a new code.";
                    
                    // Check if user has any valid tokens
                    $check_stmt = $conn->prepare("
                        SELECT COUNT(*) as valid_count 
                        FROM password_resets 
                        WHERE user_id = ? 
                        AND used_at IS NULL 
                        AND expires_at > NOW()
                    ");
                    $check_stmt->bind_param('i', $user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check_data = $check_result->fetch_assoc();
                    
                    if ($check_data['valid_count'] == 0) {
                        $error .= " All reset codes for this account have expired.";
                    }
                    $check_stmt->close();
                }
                $token_stmt->close();
            }
            $user_stmt->close();
            
        } catch (Exception $e) {
            error_log("Verification Error: " . $e->getMessage());
            $error = "An error occurred. Please try again.";
        }
    }
}

// If we already have a token in the URL and it's valid, redirect directly
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $user_stmt->bind_param('s', $email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            
            $token_stmt = $conn->prepare("
                SELECT id 
                FROM password_resets 
                WHERE user_id = ? 
                AND reset_token = ? 
                AND used_at IS NULL 
                AND expires_at > NOW()
                LIMIT 1
            ");
            $token_stmt->bind_param('is', $user['id'], $token);
            $token_stmt->execute();
            
            if ($token_stmt->get_result()->num_rows > 0) {
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_verified'] = true;
                echo '<script>window.location.href = "reset_password.php?token=' . urlencode($token) . '";</script>';
                exit;
            }
            $token_stmt->close();
        }
        $user_stmt->close();
    } catch (Exception $e) {
        // Silently fail and show the form
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code | BisureChat</title>
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
            letter-spacing: 3px;
            font-family: monospace;
            font-size: 20px;
            text-align: center;
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
        
        .alert-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.2);
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
        
        .code-input-container {
            position: relative;
        }
        
        .resend-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .resend-link:hover {
            text-decoration: underline;
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
            <h1>Verify Reset Code</h1>
        </div>
        
        <div class="auth-body">
            <a href="forgot_password.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                A verification code has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
            
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                Please enter the reset code from your email.
            </p>
            
            <form method="POST" id="verifyForm">
                <div class="form-group">
                    <label for="reset_code">Reset Code</label>
                    <div class="code-input-container">
                        <input type="text" 
                               id="reset_code" 
                               name="reset_code" 
                               class="form-control" 
                               placeholder="Enter code"
                               maxlength="64"
                               required
                               autocomplete="off">
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-check-circle"></i> Verify Code
                </button>
            </form>
            
            <div style="text-align: center;">
                <a href="forgot_password.php" class="resend-link">
                    <i class="fas fa-redo"></i> Request new code
                </a>
            </div>
        </div>
        
        <div class="auth-footer">
            © <?php echo date('Y'); ?> BisureChat. All rights reserved.
        </div>
    </div>
    
    <script>
        // Auto-focus on the code input
        document.getElementById('reset_code').focus();
        
        // Prevent double submission
        document.getElementById('verifyForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        });
        
        // Allow only hex characters if using hex code
        document.getElementById('reset_code').addEventListener('input', function(e) {
            // Remove any non-hex characters if using hex code
            // this.value = this.value.replace(/[^0-9a-fA-F]/g, '');
        });
    </script>
</body>
</html>