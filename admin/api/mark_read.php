<?php
/**
 * BUSure Chat - Admin Mark Message Read API
 * ✅ Updated for busure_lets_chat schema (uses message_reads table)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

session_start();

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid message ID']));
}

$messageId = intval($_GET['id']);

try {
    // ✅ FIXED: Check if message exists
    $check = $conn->prepare("SELECT id FROM messages WHERE id = ? AND is_deleted = 0");
    $check->bind_param("i", $messageId);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Message not found']));
    }
    $check->close();
    
    // ✅ FIXED: Insert into message_reads for ALL users (marks as read for everyone)
    $stmt = $conn->prepare("INSERT IGNORE INTO message_reads (message_id, user_id) SELECT ?, id FROM users");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $inserted = $stmt->affected_rows;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Message marked as read',
        'read_by' => $inserted . ' users'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();