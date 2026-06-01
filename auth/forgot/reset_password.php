<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once __DIR__ . '/../../config/db.php';

// Check if the reset token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo '<script>alert("Invalid reset link. Please request a new password reset."); window.location.href = "../forgot/forgot_password.php";</script>';
    exit;
}

$reset_token = $_GET['token'];
$error = '';
$success = '';

// Validate token and check if it's still valid
try {
    $stmt = $conn->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at, u.email 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.reset_token = ? 
        AND pr.used_at IS NULL 
        AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param('s', $reset_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<script>alert("Invalid or expired reset link. Please request a new password reset."); window.location.href = "../forgot/forgot_password.php";</script>';
        exit;
    }
    
    $reset_data = $result->fetch_assoc();
    $user_id = $reset_data['user_id'];
    $email = $reset_data['email'];
    $reset_id = $reset_data['id'];
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo '<script>alert("An error occurred. Please try again later."); window.location.href = "../forgot/forgot_password.php";</script>';
    exit;
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate password length
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param('si', $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Mark the reset token as used
                $mark_used_stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
                $mark_used_stmt->bind_param('i', $reset_id);
                $mark_used_stmt->execute();
                $mark_used_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                // Clear session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_attempts']);
                
                echo '<script>
                    alert("Your password has been successfully reset. Please login with your new password.");
                    window.location.href = "../login.php";
                </script>';
                exit;
            } else {
                $conn->rollback();
                $error = "Failed to reset password. Please try again.";
            }
            
            $update_stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Password Reset Error: " . $e->getMessage());
            $error = "An error occurred while resetting your password. Please try again.";
        }
    }
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
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 42px;
            cursor: pointer;
            color: var(--text-secondary);
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
        
        .password-requirements {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }
        
        .password-requirements li {
            margin: 3px 0;
        }
        
        .password-requirements li i {
            margin-right: 5px;
            font-size: 10px;
        }
        
        .password-requirements .valid {
            color: var(--success-color);
        }
        
        .password-requirements .invalid {
            color: var(--text-secondary);
        }
        
        .auth-footer {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            border-top: 1px solid var(--border-color);
        }
        
        .shake {
            animation: shake 0.5s;
            border-color: var(--error-color) !important;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 90% { transform: translateX(-5px); }
            20%, 80% { transform: translateX(5px); }
            30%, 50%, 70% { transform: translateX(-5px); }
            40%, 60% { transform: translateX(5px); }
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
            <h1>Set New Password</h1>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                Enter your new password for <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="form-control" placeholder="Enter new password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                    <div class="password-requirements" id="passwordRequirements">
                        <ul>
                            <li id="lengthCheck"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="uppercaseCheck"><i class="fas fa-circle"></i> One uppercase letter</li>
                            <li id="lowercaseCheck"><i class="fas fa-circle"></i> One lowercase letter</li>
                            <li id="numberCheck"><i class="fas fa-circle"></i> One number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="form-control" placeholder="Confirm new password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-lock"></i> Reset Password
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            © <?php echo date('Y'); ?> BisureChat. All rights reserved.
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Real-time password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const lengthCheck = document.getElementById('lengthCheck');
        const uppercaseCheck = document.getElementById('uppercaseCheck');
        const lowercaseCheck = document.getElementById('lowercaseCheck');
        const numberCheck = document.getElementById('numberCheck');
        
        newPassword.addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            if (password.length >= 8) {
                lengthCheck.classList.add('valid');
                lengthCheck.classList.remove('invalid');
                lengthCheck.querySelector('i').classList.add('fa-check-circle');
                lengthCheck.querySelector('i').classList.remove('fa-circle');
            } else {
                lengthCheck.classList.remove('valid');
                lengthCheck.classList.add('invalid');
                lengthCheck.querySelector('i').classList.remove('fa-check-circle');
                lengthCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                uppercaseCheck.classList.add('valid');
                uppercaseCheck.classList.remove('invalid');
                uppercaseCheck.querySelector('i').classList.add('fa-check-circle');
                uppercaseCheck.querySelector('i').classList.remove('fa-circle');
            } else {
                uppercaseCheck.classList.remove('valid');
                uppercaseCheck.classList.add('invalid');
                uppercaseCheck.querySelector('i').classList.remove('fa-check-circle');
                uppercaseCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                lowercaseCheck.classList.add('valid');
                lowercaseCheck.classList.remove('invalid');
                lowercaseCheck.querySelector('i').classList.add('fa-check-circle');
                lowercaseCheck.querySelector('i').classList.remove('fa-circle');
            } else {
                lowercaseCheck.classList.remove('valid');
                lowercaseCheck.classList.add('invalid');
                lowercaseCheck.querySelector('i').classList.remove('fa-check-circle');
                lowercaseCheck.querySelector('i').classList.add('fa-circle');
            }
            
            // Check number
            if (/\d/.test(password)) {
                numberCheck.classList.add('valid');
                numberCheck.classList.remove('invalid');
                numberCheck.querySelector('i').classList.add('fa-check-circle');
                numberCheck.querySelector('i').classList.remove('fa-circle');
            } else {
                numberCheck.classList.remove('valid');
                numberCheck.classList.add('invalid');
                numberCheck.querySelector('i').classList.remove('fa-check-circle');
                numberCheck.querySelector('i').classList.add('fa-circle');
            }
        });
        
        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            let hasError = false;
            
            // Reset all errors
            newPassword.classList.remove('shake');
            confirmPassword.classList.remove('shake');
            
            // Validate password requirements
            if (newPass.length < 8 || !/[A-Z]/.test(newPass) || !/[a-z]/.test(newPass) || !/\d/.test(newPass)) {
                newPassword.classList.add('shake');
                hasError = true;
            }
            
            // Check if passwords match
            if (newPass !== confirmPass) {
                confirmPassword.classList.add('shake');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                setTimeout(() => {
                    newPassword.classList.remove('shake');
                    confirmPassword.classList.remove('shake');
                }, 500);
            } else {
                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            }
        });
    </script>
</body>
</html>