<?php
/**
 * BUSure Chat - Login Content Handler
 * Processes AJAX login requests
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed.');
    }

    $user     = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($user)) {
        $response['errors']['user'] = 'Please enter your username, email, or phone.';
    }
    if (empty($password)) {
        $response['errors']['password'] = 'Please enter your password.';
    }

    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }

    // Query matching your schema
    $stmt = $conn->prepare(
        "SELECT id, uuid, fullname, username, email, phone, 
                password_hash, profile_photo, is_verified 
         FROM users 
         WHERE username = ? OR email = ? OR phone = ? 
         LIMIT 1"
    );
    $stmt->bind_param("sss", $user, $user, $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            // Set session
            $_SESSION['user_id']       = $row['id'];
            $_SESSION['user_uuid']     = $row['uuid'];
            $_SESSION['fullname']      = $row['fullname'];
            $_SESSION['username']      = $row['username'];
            $_SESSION['email']         = $row['email'];
            $_SESSION['phone']         = $row['phone'];
            $_SESSION['profile_photo'] = $row['profile_photo'];
            $_SESSION['is_verified']   = $row['is_verified'];

            // Update online status
            $update = $conn->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
            $update->bind_param("i", $row['id']);
            $update->execute();

            session_regenerate_id(true);

            $response['success']  = true;
            $response['message']  = 'Welcome, ' . htmlspecialchars($row['fullname']) . '!';
            $response['redirect'] = '../chat/contacts';
        } else {
            $response['errors']['password'] = 'Incorrect password.';
        }
    } else {
        $response['errors']['user'] = 'No account found with that username, email, or phone.';
    }

} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    $response['message'] = 'A server error occurred. Please try again.';
}

echo json_encode($response);
exit;