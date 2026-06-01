<?php
/**
 * BUSure Chat - Send Group Message Handler
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $user_id = $_SESSION['user_id'];
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $message_text = trim($_POST['message'] ?? '');

    if (!$conversation_id || empty($message_text)) {
        throw new Exception('Missing required fields');
    }

    $stmt = $conn->prepare("
        INSERT INTO messages (conversation_id, sender_id, message_type, message_text) 
        VALUES (?, ?, 'text', ?)
    ");
    $stmt->bind_param("iis", $conversation_id, $user_id, $message_text);
    $stmt->execute();
    $message_id = $stmt->insert_id;
    $stmt->close();

    $response['success'] = true;
    $response['message_id'] = $message_id;
    $response['message'] = 'Message sent';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;