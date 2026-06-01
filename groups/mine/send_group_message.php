<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../config/db.php';

$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $message = trim($_POST['message']);
    
    // Validate input
    if (!$group_id) {
        echo json_encode(['success' => false, 'message' => 'Group ID is required']);
        exit();
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit();
    }
    
    // Check if user is a member of this group
    $stmt = $conn->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $current_user_id);
    $stmt->execute();
    $membership_result = $stmt->get_result();
    $is_member = $membership_result->num_rows > 0;
    $stmt->close();
    
    if (!$is_member) {
        echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
        exit();
    }
    
    // Insert message into database
    $stmt = $conn->prepare("INSERT INTO group_messages (group_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $group_id, $current_user_id, $message);
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        
        // Get sender info for response
        $stmt2 = $conn->prepare("SELECT username FROM offices WHERE office_id = ?");
        $stmt2->bind_param("i", $current_user_id);
        $stmt2->execute();
        $sender_result = $stmt2->get_result();
        $sender = $sender_result->fetch_assoc();
        $stmt2->close();
        
        // Get message with timestamp
        $stmt3 = $conn->prepare("SELECT * FROM group_messages WHERE message_id = ?");
        $stmt3->bind_param("i", $message_id);
        $stmt3->execute();
        $message_result = $stmt3->get_result();
        $message_data = $message_result->fetch_assoc();
        $stmt3->close();
        
        echo json_encode([
            'success' => true, 
            'message' => [
                'message_id' => $message_id,
                'group_id' => $group_id,
                'sender_id' => $current_user_id,
                'sender_name' => $sender['username'],
                'message' => $message,
                'timestamp' => $message_data['timestamp']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error sending message']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}