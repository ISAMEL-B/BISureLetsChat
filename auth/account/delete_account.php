<?php
/**
 * BUSure Chat - Delete Account Page
 * Handles account deletion with AJAX and smooth UX
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';
$current_user_id = $_SESSION['user_id'];

// ============================================
// HANDLE AJAX DELETION REQUEST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    // Verify credentials BEFORE destroying session
    $query = "SELECT id, password_hash FROM users WHERE (email = ? OR username = ?) AND id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
    $stmt->bind_param("ssi", $identifier, $identifier, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        exit;
    }

    $user_id = (int)$user['id'];

    // Delete all user data
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        "message_reactions" => "DELETE FROM message_reactions WHERE user_id = $user_id",
        "message_reads" => "DELETE FROM message_reads WHERE user_id = $user_id",
        "archived_chats" => "DELETE FROM archived_chats WHERE user_id = $user_id",
        "block_reasons" => "DELETE FROM block_reasons WHERE user_id = $user_id OR blocked_by = $user_id",
        "contacts" => "DELETE FROM contacts WHERE user_id = $user_id OR contact_user_id = $user_id",
        "email_logs" => "DELETE FROM email_logs WHERE sender_id = $user_id OR recipient_user_id = $user_id",
        "email_verifications" => "DELETE FROM email_verifications WHERE user_id = $user_id",
        "password_resets" => "DELETE FROM password_resets WHERE user_id = $user_id",
        "inquiries" => "DELETE FROM inquiries WHERE user_id = $user_id",
        "group_members" => "DELETE FROM group_members WHERE user_id = $user_id",
        "conversation_participants" => "DELETE FROM conversation_participants WHERE user_id = $user_id",
        "messages" => "DELETE FROM messages WHERE sender_id = $user_id",
        "calls" => "DELETE FROM calls WHERE caller_id = $user_id OR receiver_id = $user_id",
        "conversations" => "DELETE FROM conversations WHERE created_by = $user_id",
        "groups_chat" => "DELETE FROM groups_chat WHERE created_by = $user_id",
        "user_settings" => "DELETE FROM user_settings WHERE user_id = $user_id",
        "users" => "DELETE FROM users WHERE id = $user_id",
    ];

    $all_successful = true;
    $failed_table = '';
    foreach ($tables as $name => $sql) {
        if (!$conn->query($sql)) {
            $all_successful = false;
            $failed_table = $name;
            error_log("Delete Account Error [table: $name]: " . $conn->error);
            break;
        }
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if ($all_successful) {
        // ✅ Send success response BEFORE destroying session
        echo json_encode([
            'success' => true, 
            'message' => 'Your account has been permanently deleted.',
            'redirect' => '../register.php?deleted=1'
        ]);
        
        // Now destroy session (after response sent)
        session_destroy();
        exit;
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete account data. Please try again.'
        ]);
        exit;
    }
}
?>