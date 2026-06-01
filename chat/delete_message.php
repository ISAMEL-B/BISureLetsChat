<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Set JSON content type for all responses
header('Content-Type: application/json');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred',
    'deleted_id' => null,
    'deleted_at' => null
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

    // Get and validate input - support both old and new parameter names
    $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
    
    // Optional: Allow permanent delete for message owners
    $permanent_delete = isset($_POST['permanent']) && $_POST['permanent'] === 'true';
    
    if ($message_id <= 0) {
        throw new Exception("Invalid message ID");
    }

    // Check if message exists and belongs to current user
    $check_query = "
        SELECT 
            m.id, 
            m.sender_id, 
            m.attachment_path, 
            m.is_deleted,
            m.message_type,
            m.conversation_id,
            c.conversation_type,
            cp.role
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        LEFT JOIN group_members gm ON (
            c.conversation_type = 'group' 
            AND c.id IN (
                SELECT conversation_id 
                FROM groups_chat 
                WHERE conversation_id = c.id
            )
            AND gm.group_id = (
                SELECT id 
                FROM groups_chat 
                WHERE conversation_id = c.id
            )
            AND gm.user_id = ?
        )
        LEFT JOIN conversation_participants cp ON (
            c.conversation_type = 'private' 
            AND cp.conversation_id = c.id 
            AND cp.user_id = ?
        )
        WHERE m.id = ?
    ";
    
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $user_id = $_SESSION['user_id'];
    $check_stmt->bind_param("iii", $user_id, $user_id, $message_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        throw new Exception("Message not found");
    }

    $message_data = $check_result->fetch_assoc();
    
    // Check if message is already deleted
    if ($message_data['is_deleted'] == 1) {
        throw new Exception("Message is already deleted");
    }

    // Verify permissions:
    // 1. Message owner can always delete their messages
    // 2. Group admins can delete any message in their groups
    $can_delete = false;
    $delete_type = 'soft'; // Default to soft delete
    
    if ($message_data['sender_id'] == $user_id) {
        // Message owner
        $can_delete = true;
        if ($permanent_delete) {
            $delete_type = 'permanent';
        }
    } elseif ($message_data['conversation_type'] === 'group' && $message_data['role'] === 'admin') {
        // Group admin
        $can_delete = true;
        $delete_type = 'permanent'; // Admins can permanently delete
    }
    
    if (!$can_delete) {
        throw new Exception("You don't have permission to delete this message");
    }

    // Handle file deletion if attachment exists and permanent delete is requested
    if ($delete_type === 'permanent' && !empty($message_data['attachment_path'])) {
        if (file_exists($message_data['attachment_path'])) {
            if (!unlink($message_data['attachment_path'])) {
                // Log error but continue with deletion
                error_log("Failed to delete file: " . $message_data['attachment_path']);
            }
        }
    }

    // Perform the deletion
    $deleted_at = date('Y-m-d H:i:s');
    
    if ($delete_type === 'permanent') {
        // Permanent delete - remove from database
        $delete_query = "DELETE FROM messages WHERE id = ? AND (sender_id = ? OR EXISTS (
            SELECT 1 FROM groups_chat gc
            JOIN conversations c ON gc.conversation_id = c.id
            JOIN group_members gm ON gc.id = gm.group_id
            WHERE c.id = (SELECT conversation_id FROM messages WHERE id = ?)
            AND gm.user_id = ? AND gm.role = 'admin'
        ))";
        
        $delete_stmt = $conn->prepare($delete_query);
        if (!$delete_stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $delete_stmt->bind_param("iiii", $message_id, $user_id, $message_id, $user_id);
        $delete_stmt->execute();
        
        if ($delete_stmt->affected_rows === 0) {
            throw new Exception("Failed to delete the message");
        }
        
        $delete_stmt->close();
        
    } else {
        // Soft delete - just mark as deleted
        $soft_delete_query = "
            UPDATE messages 
            SET is_deleted = 1, 
                message_text = 'This message was deleted',
                attachment_path = NULL,
                updated_at = NOW()
            WHERE id = ? AND sender_id = ?
        ";
        
        $soft_delete_stmt = $conn->prepare($soft_delete_query);
        if (!$soft_delete_stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $soft_delete_stmt->bind_param("ii", $message_id, $user_id);
        $soft_delete_stmt->execute();
        
        if ($soft_delete_stmt->affected_rows === 0) {
            throw new Exception("Failed to delete the message");
        }
        
        $soft_delete_stmt->close();
    }

    // Prepare success response
    $response = [
        'status' => 'success',
        'message' => $delete_type === 'permanent' ? 'Message permanently deleted' : 'Message deleted successfully',
        'deleted_id' => $message_id,
        'deleted_at' => $deleted_at,
        'delete_type' => $delete_type
    ];

    $check_stmt->close();

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