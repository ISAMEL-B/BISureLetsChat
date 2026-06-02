<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

$current_user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: delete_account.php');
        exit;
    }

    // Verify user credentials
    $query = "SELECT id, email, username, password_hash FROM users WHERE (email = ? OR username = ?) AND id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $identifier, $identifier, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid credentials. Please check your email/username.';
        header('Location: delete_account.php');
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['error'] = 'Invalid password. Please try again.';
        header('Location: delete_account.php');
        exit;
    }

    // Start transaction for safe deletion
    mysqli_begin_transaction($conn);

    try {
        $user_id = $user['id'];

        $tables = [
            "DELETE FROM archived_chats WHERE user_id = ?",
            "DELETE FROM contacts WHERE user_id = ? OR contact_user_id = ?",
            "DELETE FROM message_reads WHERE user_id = ?",
            "DELETE FROM message_reactions WHERE user_id = ?",
            "DELETE FROM email_logs WHERE sender_id = ? OR recipient_user_id = ?",
            "DELETE FROM email_verifications WHERE user_id = ?",
            "DELETE FROM password_resets WHERE user_id = ?",
            "DELETE FROM group_members WHERE user_id = ?",
            "DELETE FROM conversation_participants WHERE user_id = ?",
            "DELETE FROM messages WHERE sender_id = ?",
            "DELETE FROM conversations WHERE created_by = ?",
            "DELETE FROM groups_chat WHERE created_by = ?",
            "DELETE FROM calls WHERE caller_id = ? OR receiver_id = ?",
            "DELETE FROM user_settings WHERE user_id = ?",
        ];

        foreach ($tables as $sql) {
            $stmt = $conn->prepare($sql);
            if (strpos($sql, 'OR') !== false) {
                $stmt->bind_param("ii", $user_id, $user_id);
            } else {
                $stmt->bind_param("i", $user_id);
            }
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        mysqli_commit($conn);
        session_destroy();
        session_start();
        $_SESSION['success'] = 'Your account has been permanently deleted.';
        header('Location: ../register.php');
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = 'Failed to delete account. Please try again.';
        header('Location: delete_account.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Delete Account | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --da-primary: #128C7E;
            --da-primary-dark: #075E54;
            --da-secondary: #25D366;
            --da-bg: #e5ddd5;
            --da-card: #ffffff;
            --da-text: #2D3748;
            --da-text-secondary: #718096;
            --da-border: #E2E8F0;
            --da-hover: rgba(18, 140, 126, 0.04);
            --da-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --da-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --da-danger: #E74C3C;
            --da-danger-dark: #C0392B;
            --da-warning: #F39C12;
            --da-input-bg: #f9f9f9;
            --da-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --nav-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--da-bg);
            color: var(--da-text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* =============================================
           FULL-WIDTH GREEN HEADER (like contacts.php)
           ============================================= */
        .da-header {
            background: linear-gradient(135deg, var(--da-primary-dark), var(--da-primary));
            color: #FFFFFF;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 60px;
            width: 100%;
        }

        .da-sidebar-toggle {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            z-index: 2;
            color: inherit;
        }

        .da-header-title {
            font-size: 1.35rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
            text-align: center;
        }

        .da-pro-badge {
            background: #FFD700;
            color: #000;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .da-header-spacer {
            flex-shrink: 0;
            width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* =============================================
           MAIN CONTENT - FULL HEIGHT (like contacts.php)
           ============================================= */
        .da-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--da-card);
            min-height: calc(100vh - 60px);
            box-shadow: var(--da-shadow);
            transition: background-color 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        .da-content {
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .da-form-card {
            width: 100%;
            max-width: 480px;
            background: var(--da-card);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--da-shadow);
            border: 1px solid var(--da-border);
            transition: background 0.3s ease, border-color 0.3s ease;
            margin-top: 0.5rem;
        }

        .da-form-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e74c3c20, #c0392b20);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .da-form-icon i {
            font-size: 2rem;
            color: var(--da-danger);
        }

        .da-form-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--da-text);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .da-form-subtitle {
            font-size: 0.85rem;
            color: var(--da-text-secondary);
            text-align: center;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        /* Warning box */
        .da-warning-box {
            background: #FFF8E6;
            border-left: 4px solid var(--da-warning);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }

        .da-warning-box i {
            color: var(--da-warning);
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .da-warning-box p {
            font-size: 0.85rem;
            color: #856404;
            line-height: 1.5;
            margin: 0;
        }

        .da-warning-box p strong {
            color: #6d5308;
        }

        /* Messages */
        .da-message {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .da-message-error {
            background: #FDECEA;
            color: #C0392B;
            border-left: 4px solid #E74C3C;
        }

        .da-message-success {
            background: #E8F5E9;
            color: #1B5E20;
            border-left: 4px solid #25D366;
        }

        /* Form groups */
        .da-form-group {
            margin-bottom: 1.25rem;
        }

        .da-form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--da-text);
            margin-bottom: 0.4rem;
        }

        .da-input-wrapper {
            position: relative;
        }

        .da-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--da-text-secondary);
            font-size: 0.9rem;
            transition: var(--da-transition);
            z-index: 1;
            pointer-events: none;
        }

        .da-input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 2px solid var(--da-border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            background: var(--da-input-bg);
            color: var(--da-text);
            transition: var(--da-transition);
            outline: none;
        }

        .da-input:focus {
            border-color: var(--da-primary);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
            background: var(--da-card);
        }

        .da-input::placeholder {
            color: #a0a0a0;
        }

        .da-input-error {
            border-color: var(--da-danger) !important;
        }

        .da-input-error:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15) !important;
        }

        .da-validation-msg {
            font-size: 0.75rem;
            color: var(--da-danger);
            margin-top: 0.3rem;
            display: none;
        }

        /* Checkbox */
        .da-checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #fdf2f2;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
            transition: var(--da-transition);
        }

        .da-checkbox-group input[type="checkbox"] {
            margin-top: 0.15rem;
            width: 18px;
            height: 18px;
            accent-color: var(--da-danger);
            cursor: pointer;
            flex-shrink: 0;
        }

        .da-checkbox-label {
            font-size: 0.8rem;
            color: #721c24;
            line-height: 1.5;
            cursor: pointer;
            user-select: none;
        }

        /* Buttons */
        .da-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: var(--da-transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .da-btn-delete {
            background: var(--da-danger);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .da-btn-delete:hover {
            background: var(--da-danger-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .da-btn-delete:active {
            transform: translateY(0);
        }

        .da-btn-delete:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .da-btn-cancel {
            background: transparent;
            color: var(--da-text-secondary);
            border: 2px solid var(--da-border);
            margin-top: 0.75rem;
        }

        .da-btn-cancel:hover {
            background: var(--da-hover);
            border-color: var(--da-primary);
            color: var(--da-primary);
        }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode {
            --da-bg: #0B141A;
            --da-card: #1F2C33;
            --da-text: #E9EDEF;
            --da-text-secondary: #8696A0;
            --da-border: #2A3942;
            --da-hover: rgba(255, 255, 255, 0.04);
            --da-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            --da-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.5);
            --da-input-bg: #2A3942;
            background: var(--da-bg);
        }

        body.dark-mode .da-container {
            background-color: #1F2C33;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
        }

        body.dark-mode .da-form-card {
            background-color: #1F2C33;
            border-color: #2A3942;
        }

        body.dark-mode .da-input {
            background: var(--da-input-bg);
            border-color: #374248;
            color: var(--da-text);
        }

        body.dark-mode .da-input::placeholder {
            color: var(--da-text-secondary);
        }

        body.dark-mode .da-input:focus {
            border-color: #25D366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
            background: #2A3942;
        }

        body.dark-mode .da-warning-box {
            background: #2A1A00;
            border-left-color: #F39C12;
        }

        body.dark-mode .da-warning-box p {
            color: #F39C12;
        }

        body.dark-mode .da-warning-box p strong {
            color: #F7DC6F;
        }

        body.dark-mode .da-checkbox-group {
            background: #2A1A1A;
            border-color: #5C1A1A;
        }

        body.dark-mode .da-checkbox-label {
            color: #F5C6CB;
        }

        body.dark-mode .da-form-icon {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.2), rgba(192, 57, 43, 0.2));
        }

        body.dark-mode .da-message-error {
            background: #2A1A1A;
            color: #F5C6CB;
            border-left-color: #E74C3C;
        }

        body.dark-mode .da-message-success {
            background: #1A2A1A;
            color: #81C784;
            border-left-color: #25D366;
        }

        body.dark-mode .da-btn-cancel {
            border-color: #2A3942;
            color: #8696A0;
        }

        body.dark-mode .da-btn-cancel:hover {
            border-color: #25D366;
            color: #25D366;
            background: rgba(37, 211, 102, 0.08);
        }

        /* =============================================
           RESPONSIVE
           ============================================= */
        @media (max-width: 480px) {
            .da-header {
                padding: 14px 16px;
                min-height: 54px;
            }

            .da-header-title {
                font-size: 1.15rem;
            }

            .da-sidebar-toggle {
                margin-right: 8px;
            }

            .da-header-spacer {
                width: 40px;
            }

            .da-pro-badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }

            .da-content {
                padding: 1.5rem 1rem;
            }

            .da-form-card {
                padding: 1.5rem;
            }

            .da-form-icon {
                width: 60px;
                height: 60px;
            }

            .da-form-icon i {
                font-size: 1.6rem;
            }

            .da-form-title {
                font-size: 1.2rem;
            }

            .da-input {
                padding: 10px 12px 10px 36px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 360px) {
            .da-header {
                padding: 12px 12px;
            }

            .da-header-title {
                font-size: 1.05rem;
            }

            .da-sidebar-toggle {
                margin-right: 6px;
            }

            .da-header-spacer {
                width: 36px;
            }

            .da-content {
                padding: 1rem 0.75rem;
            }

            .da-form-card {
                padding: 1.25rem;
            }
        }

        @media (min-width: 768px) {
            .da-header {
                padding: 18px 24px;
            }

            .da-header-title {
                font-size: 1.5rem;
            }

            .da-content {
                padding: 2.5rem 2rem;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<!-- ✅ FULL-WIDTH GREEN HEADER -->
<div class="da-header">
    <div class="da-sidebar-toggle">
        <?php require_once __DIR__ . '/../../includes/cd_hamburger.php'; ?>
    </div>
    
    <div class="da-header-title">
        Delete Account <span class="da-pro-badge">PRO</span>
    </div>
    
    <div class="da-header-spacer"></div>
</div>

<!-- ✅ FULL-HEIGHT CONTAINER (like contacts.php) -->
<div class="da-container">
    <div class="da-content">
        <div class="da-form-card">
            <!-- Icon -->
            <div class="da-form-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2 class="da-form-title">Delete Your Account</h2>
            <p class="da-form-subtitle">
                This action is permanent and cannot be undone
            </p>

            <!-- Warning Box -->
            <div class="da-warning-box">
                <i class="fas fa-shield-alt"></i>
                <p>
                    <strong>Warning:</strong> All your messages, contacts, groups, call history, and account data will be permanently deleted from our servers. This action cannot be reversed.
                </p>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="da-message da-message-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="da-message da-message-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="delete_account.php" id="daDeleteForm" novalidate>
                <div class="da-form-group">
                    <label class="da-form-label" for="identifier">
                        <i class="fas fa-envelope"></i> Email or Username
                    </label>
                    <div class="da-input-wrapper">
                        <i class="fas fa-user da-input-icon"></i>
                        <input 
                            type="text" 
                            class="da-input" 
                            id="identifier" 
                            name="identifier" 
                            placeholder="Enter your email or username" 
                            required
                            autocomplete="email"
                        >
                    </div>
                    <div class="da-validation-msg" id="daIdentifierError">
                        Please enter a valid email or username
                    </div>
                </div>

                <div class="da-form-group">
                    <label class="da-form-label" for="password">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <div class="da-input-wrapper">
                        <i class="fas fa-lock da-input-icon"></i>
                        <input 
                            type="password" 
                            class="da-input" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required 
                            minlength="6"
                            autocomplete="current-password"
                        >
                    </div>
                    <div class="da-validation-msg" id="daPasswordError">
                        Password must be at least 6 characters
                    </div>
                </div>

                <!-- Confirmation Checkbox -->
                <div class="da-checkbox-group">
                    <input type="checkbox" id="confirmDelete" name="confirmDelete" required>
                    <label for="confirmDelete" class="da-checkbox-label">
                        I understand that this action cannot be undone and all my data will be permanently deleted from BisureChat servers.
                    </label>
                </div>
                <div class="da-validation-msg" id="daConfirmError" style="margin-top: -0.5rem; margin-bottom: 1rem;">
                    You must confirm this action
                </div>

                <!-- Delete Button -->
                <button type="submit" class="da-btn da-btn-delete" id="daDeleteBtn">
                    <i class="fas fa-trash-alt"></i>
                    <span>Delete Account Permanently</span>
                </button>

                <!-- Cancel Button -->
                <a href="my_profile.php" class="da-btn da-btn-cancel" style="display: flex; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Cancel & Go Back</span>
                </a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('daDeleteForm');
        const identifierInput = document.getElementById('identifier');
        const passwordInput = document.getElementById('password');
        const confirmCheckbox = document.getElementById('confirmDelete');
        const deleteBtn = document.getElementById('daDeleteBtn');

        function validateIdentifier() {
            const value = identifierInput.value.trim();
            const isValid = value.length >= 2;
            toggleError(identifierInput, 'daIdentifierError', isValid);
            return isValid;
        }

        function validatePassword() {
            const isValid = passwordInput.value.length >= 6;
            toggleError(passwordInput, 'daPasswordError', isValid);
            return isValid;
        }

        function validateConfirmation() {
            const isValid = confirmCheckbox.checked;
            const errorEl = document.getElementById('daConfirmError');
            errorEl.style.display = isValid ? 'none' : 'block';
            return isValid;
        }

        function toggleError(input, errorId, isValid) {
            const errorElement = document.getElementById(errorId);
            if (!isValid) {
                input.classList.add('da-input-error');
                errorElement.style.display = 'block';
            } else {
                input.classList.remove('da-input-error');
                errorElement.style.display = 'none';
            }
        }

        identifierInput.addEventListener('input', validateIdentifier);
        passwordInput.addEventListener('input', validatePassword);
        confirmCheckbox.addEventListener('change', validateConfirmation);
        identifierInput.addEventListener('blur', validateIdentifier);
        passwordInput.addEventListener('blur', validatePassword);

        form.addEventListener('submit', function(e) {
            const isIdentifierValid = validateIdentifier();
            const isPasswordValid = validatePassword();
            const isConfirmed = validateConfirmation();

            if (!isIdentifierValid || !isPasswordValid || !isConfirmed) {
                e.preventDefault();

                if (!isConfirmed) {
                    const checkboxGroup = confirmCheckbox.closest('.da-checkbox-group');
                    checkboxGroup.style.borderColor = '#E74C3C';
                    checkboxGroup.style.backgroundColor = '#fde8e8';
                    
                    const originalHTML = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Please confirm deletion</span>';
                    deleteBtn.style.background = '#F39C12';
                    
                    setTimeout(() => {
                        deleteBtn.innerHTML = originalHTML;
                        deleteBtn.style.background = '';
                        checkboxGroup.style.borderColor = '';
                        checkboxGroup.style.backgroundColor = '';
                    }, 2500);
                }
            } else {
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Deleting Account...</span>';
                deleteBtn.disabled = true;
            }
        });
    });
</script>
</body>
</html>