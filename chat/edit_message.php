<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Set JSON content type for all responses
header('Content-Type: application/json');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred',
    'updated_message' => null,
    'timestamp' => null
];

try {
    // Verify POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Validate session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required");
    }

    // Get and validate input
    $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
    $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($message_id <= 0) {
        throw new Exception("Invalid message ID");
    }

    if (empty($message_text)) {
        throw new Exception("Message cannot be empty");
    }

    // Check if message exists and belongs to current user
    $check_query = "
        SELECT m.id, m.sender_id, m.message_text, m.is_deleted, m.conversation_id 
        FROM messages m 
        WHERE m.id = ?
    ";
    
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $message_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception("Message not found");
    }

    $message_data = $check_result->fetch_assoc();
    
    // Verify ownership
    if ($message_data['sender_id'] != $_SESSION['user_id']) {
        throw new Exception("You can only edit your own messages");
    }

    // Check if message is deleted
    if ($message_data['is_deleted'] == 1) {
        throw new Exception("Cannot edit a deleted message");
    }

    // Check if the message content actually changed
    if ($message_data['message_text'] === $message_text) {
        throw new Exception("No changes made to the message");
    }

    // Update the message
    $update_query = "
        UPDATE messages 
        SET message_text = ?, 
            is_edited = 1, 
            updated_at = NOW() 
        WHERE id = ? AND sender_id = ?
    ";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $update_stmt->bind_param("sii", $message_text, $message_id, $_SESSION['user_id']);
    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception("Failed to update the message");
    }

    // Get updated message details with sender information
    $select_query = "
        SELECT 
            m.id,
            m.message_text,
            m.is_edited,
            m.updated_at,
            m.created_at,
            m.message_type,
            m.attachment_path,
            u.fullname as sender_name,
            u.username as sender_username
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ";
    
    $select_stmt = $conn->prepare($select_query);
    if (!$select_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $select_stmt->bind_param("i", $message_id);
    $select_stmt->execute();
    $select_result = $select_stmt->get_result();
    $updated_message = $select_result->fetch_assoc();

    // Format timestamps
    $formatted_updated_at = $updated_message['updated_at'] ? date('h:i A | M d', strtotime($updated_message['updated_at'])) : null;
    $formatted_created_at = date('h:i A | M d', strtotime($updated_message['created_at']));

    // Prepare success response
    $response = [
        'status' => 'success',
        'message' => 'Message updated successfully',
        'updated_message' => [
            'id' => $updated_message['id'],
            'message_text' => $updated_message['message_text'],
            'is_edited' => true,
            'message_type' => $updated_message['message_type'],
            'attachment_path' => $updated_message['attachment_path'],
            'sender_name' => $updated_message['sender_name'],
            'sender_username' => $updated_message['sender_username'],
            'created_at' => $formatted_created_at,
            'updated_at' => $formatted_updated_at
        ],
        'timestamp' => $formatted_updated_at,
        'is_edited' => true
    ];

    // Close statements
    $check_stmt->close();
    $update_stmt->close();
    $select_stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
    
    // Send JSON response
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>