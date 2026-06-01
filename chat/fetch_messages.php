<?php
session_start();
header('Content-Type: application/json');

// Database configuration
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get POST data
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
$contact_id = isset($_POST['contactId']) ? intval($_POST['contactId']) : 0;
$message_type = isset($_POST['message_type']) ? $_POST['message_type'] : 'text';
$reply_to_id = isset($_POST['reply_to_id']) ? intval($_POST['reply_to_id']) : null;
$attachment_path = isset($_POST['attachment_path']) ? $_POST['attachment_path'] : null;

// Validate inputs
if (empty($message_text) && empty($attachment_path)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
    exit();
}

if ($contact_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid contact ID.']);
    exit();
}

// Check if user is not sending to themselves
if ($current_user_id === $contact_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot send message to yourself.']);
    exit();
}

// Validate contact exists
$contact_check_sql = "SELECT id, fullname, username FROM users WHERE id = ?";
$contact_stmt = $conn->prepare($contact_check_sql);
$contact_stmt->bind_param("i", $contact_id);
$contact_stmt->execute();
$contact_result = $contact_stmt->get_result();

if ($contact_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Contact not found.']);
    $contact_stmt->close();
    $conn->close();
    exit();
}

$contact_data = $contact_result->fetch_assoc();
$contact_stmt->close();

// Validate message type
$allowed_types = ['text', 'image', 'video', 'file', 'voice'];
if (!in_array($message_type, $allowed_types)) {
    $message_type = 'text'; // Default to text if invalid type
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if a private conversation already exists between these two users
    $conversation_sql = "
        SELECT cp1.conversation_id 
        FROM conversation_participants cp1
        JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
        JOIN conversations c ON cp1.conversation_id = c.id
        WHERE cp1.user_id = ? 
        AND cp2.user_id = ? 
        AND c.conversation_type = 'private'
        LIMIT 1
    ";
    
    $conv_stmt = $conn->prepare($conversation_sql);
    $conv_stmt->bind_param("ii", $current_user_id, $contact_id);
    $conv_stmt->execute();
    $conv_result = $conv_stmt->get_result();
    
    if ($conv_result->num_rows > 0) {
        // Conversation exists, get the ID
        $conversation = $conv_result->fetch_assoc();
        $conversation_id = $conversation['conversation_id'];
    } else {
        // Create new conversation
        $create_conv_sql = "INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)";
        $create_conv_stmt = $conn->prepare($create_conv_sql);
        $create_conv_stmt->bind_param("i", $current_user_id);
        
        if (!$create_conv_stmt->execute()) {
            throw new Exception("Failed to create conversation.");
        }
        
        $conversation_id = $conn->insert_id;
        $create_conv_stmt->close();
        
        // Add both users as participants
        $add_participants_sql = "INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)";
        $add_participants_stmt = $conn->prepare($add_participants_sql);
        $add_participants_stmt->bind_param("iiii", $conversation_id, $current_user_id, $conversation_id, $contact_id);
        
        if (!$add_participants_stmt->execute()) {
            throw new Exception("Failed to add participants.");
        }
        $add_participants_stmt->close();
    }
    $conv_stmt->close();
    
    // Insert the message
    $insert_sql = "
        INSERT INTO messages (conversation_id, sender_id, message_type, message_text, attachment_path) 
        VALUES (?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iisss", $conversation_id, $current_user_id, $message_type, $message_text, $attachment_path);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to send message: " . $stmt->error);
    }
    
    $message_id = $conn->insert_id;
    $stmt->close();
    
    // If this is a reply, you might want to add reply tracking
    if ($reply_to_id) {
        // Verify the replied message exists in the same conversation
        $reply_check_sql = "SELECT id FROM messages WHERE id = ? AND conversation_id = ?";
        $reply_check_stmt = $conn->prepare($reply_check_sql);
        $reply_check_stmt->bind_param("ii", $reply_to_id, $conversation_id);
        $reply_check_stmt->execute();
        $reply_result = $reply_check_stmt->get_result();
        
        if ($reply_result->num_rows === 0) {
            // Reply message not found, but we'll still send the message
            // You could add a reply_to column to messages table if needed
        }
        $reply_check_stmt->close();
    }
    
    // Get the full message details for the response
    $message_details_sql = "
        SELECT 
            m.id,
            m.conversation_id,
            m.sender_id,
            m.message_type,
            m.message_text,
            m.attachment_path,
            m.is_edited,
            m.is_deleted,
            m.created_at,
            u.fullname as sender_name,
            u.username as sender_username,
            u.profile_photo as sender_photo
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ";
    
    $details_stmt = $conn->prepare($message_details_sql);
    $details_stmt->bind_param("i", $message_id);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();
    $message_data = $details_result->fetch_assoc();
    $details_stmt->close();
    
    // Format the timestamp
    $message_data['formatted_time'] = date('h:i A | M d', strtotime($message_data['created_at']));
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'message_id' => $message_id,
            'conversation_id' => $conversation_id,
            'message' => $message_data,
            'contact' => [
                'id' => $contact_data['id'],
                'fullname' => $contact_data['fullname'],
                'username' => $contact_data['username']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to send message: ' . $e->getMessage()
    ]);
}

$conn->close();
?>