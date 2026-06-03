<?php
/**
 * BUSure Chat - Admin Delete Message API
 * ✅ Updated for busure_lets_chat schema
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

session_start();

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid message ID']));
}

$messageId = intval($_POST['id']);

try {
    // ✅ FIXED: Get message info from messages table
    $stmt = $conn->prepare("SELECT id, attachment_path FROM messages WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Message not found']));
    }
    
    $message = $result->fetch_assoc();
    $stmt->close();
    
    // ✅ FIXED: Soft delete the message
    $updateStmt = $conn->prepare("UPDATE messages SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $messageId);
    $updateStmt->execute();
    $deleted = $updateStmt->affected_rows > 0;
    $updateStmt->close();
    
    if (!$deleted) {
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => 'Failed to delete message']));
    }
    
    // If it had a file attachment, optionally delete the physical file
    $fileDeleted = false;
    if (!empty($message['attachment_path'])) {
        $filePath = __DIR__ . '/../../' . $message['attachment_path'];
        if (file_exists($filePath)) {
            $fileDeleted = unlink($filePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Message deleted successfully',
        'file_deleted' => $fileDeleted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();