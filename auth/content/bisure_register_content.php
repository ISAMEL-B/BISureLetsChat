<?php
/**
 * BUSure Chat - Register Content Handler
 * Processes AJAX registration requests
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/db.php';

$response = ['success' => false, 'errors' => [], 'message' => ''];

try {
    
    // ============================================
    // CHECK REQUEST METHOD
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // ============================================
    // CHECK DATABASE CONNECTION
    // ============================================
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed.');
    }

    // ============================================
    // SANITIZE & VALIDATE INPUTS
    // ============================================
    $fullname         = trim($_POST['fullname'] ?? '');
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validate Full Name ---
    if (empty($fullname)) {
        $response['errors']['fullname'] = 'Full name is required.';
    } elseif (strlen($fullname) < 2) {
        $response['errors']['fullname'] = 'Full name must be at least 2 characters.';
    } elseif (strlen($fullname) > 150) {
        $response['errors']['fullname'] = 'Full name must not exceed 150 characters.';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $fullname)) {
        $response['errors']['fullname'] = 'Full name contains invalid characters.';
    }

    // --- Validate Username ---
    if (empty($username)) {
        $response['errors']['username'] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $response['errors']['username'] = 'Username must be at least 3 characters.';
    } elseif (strlen($username) > 50) {
        $response['errors']['username'] = 'Username must not exceed 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response['errors']['username'] = 'Username can only contain letters, numbers, and underscores.';
    }

    // --- Validate Email ---
    if (empty($email)) {
        $response['errors']['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Please enter a valid email address.';
    } elseif (strlen($email) > 255) {
        $response['errors']['email'] = 'Email must not exceed 255 characters.';
    }

    // --- Validate Phone (Optional but validate format if provided) ---
    if (!empty($phone)) {
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $response['errors']['phone'] = 'Please enter a valid phone number (10-15 digits).';
        }
    }

    // --- Validate Password ---
    if (empty($password)) {
        $response['errors']['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 255) {
        $response['errors']['password'] = 'Password must not exceed 255 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $response['errors']['password'] = 'Password must contain at least one special character.';
    }

    // --- Validate Confirm Password ---
    if (empty($confirm_password)) {
        $response['errors']['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $response['errors']['confirm_password'] = 'Passwords do not match.';
    }

    // --- Return Early if Validation Fails ---
    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }

    // ============================================
    // CHECK FOR DUPLICATES
    // ============================================
    $stmt = $conn->prepare(
        "SELECT username, email, phone 
         FROM users 
         WHERE username = ? OR email = ? OR phone = ? 
         LIMIT 1"
    );
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed.');
    }

    $stmt->bind_param("sss", $username, $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (strcasecmp($row['username'], $username) === 0) {
            $response['errors']['username'] = 'This username is already taken.';
        }
        if (strcasecmp($row['email'], $email) === 0) {
            $response['errors']['email'] = 'An account with this email already exists.';
        }
        if (!empty($phone) && $row['phone'] === $phone) {
            $response['errors']['phone'] = 'This phone number is already registered.';
        }
    }
    $stmt->close();

    // --- Return if Duplicates Found ---
    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }

    // ============================================
    // GENERATE UUID
    // ============================================
    // Using PHP's built-in UUID v4 generator (or fallback)
    $uuid = generateUUID();

    // ============================================
    // HASH PASSWORD
    // ============================================
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // ============================================
    // INSERT NEW USER
    // ============================================
    $stmt = $conn->prepare(
        "INSERT INTO users (uuid, fullname, username, email, phone, password_hash, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        throw new Exception('Failed to prepare insert statement.');
    }

    $stmt->bind_param(
        "ssssss",
        $uuid,
        $fullname,
        $username,
        $email,
        $phone,  // Can be NULL if not provided
        $hashed_password
    );

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        
        // ============================================
        // CREATE DEFAULT USER SETTINGS
        // ============================================
        $settings_stmt = $conn->prepare(
            "INSERT INTO user_settings (user_id) VALUES (?)"
        );
        if ($settings_stmt) {
            $settings_stmt->bind_param("i", $new_user_id);
            $settings_stmt->execute();
            $settings_stmt->close();
        }

        // ============================================
        // GENERATE VERIFICATION TOKEN (Optional)
        // ============================================
        $verification_token = bin2hex(random_bytes(32));
        
        $verify_stmt = $conn->prepare(
            "INSERT INTO email_verifications (user_id, verification_token, created_at) 
             VALUES (?, ?, NOW())"
        );
        if ($verify_stmt) {
            $verify_stmt->bind_param("is", $new_user_id, $verification_token);
            $verify_stmt->execute();
            $verify_stmt->close();
        }

        // ============================================
        // SEND WELCOME EMAIL (Optional)
        // ============================================
        // Uncomment if you have mail service set up
        /*
        require_once __DIR__ . '/../../mail/Mailer.php';
        $mailer = new Mailer();
        $mailer->sendWelcomeEmail($email, $fullname, $verification_token);
        */

        $response['success'] = true;
        $response['message'] = 'Account created successfully! You can now login.';
        // $response['message'] = 'Account created! Please check your email to verify your account.';

    } else {
        throw new Exception('Failed to create account. Please try again.');
    }

} catch (Exception $e) {
    // Log the actual error
    error_log("Register Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    $response['success'] = false;
    $response['message'] = 'A server error occurred. Please try again later.';
    
    // Show actual error in development
    // $response['message'] = $e->getMessage(); // Uncomment for debugging
}

// ============================================
// CLOSE CONNECTIONS & OUTPUT RESPONSE
// ============================================
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

// ============================================
// HELPER FUNCTION: Generate UUID v4
// ============================================
function generateUUID() {
    // PHP 7+ method
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);  // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);  // Variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    // Fallback for older PHP versions
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}