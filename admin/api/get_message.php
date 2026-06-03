<?php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    http_response_code(403);

    echo json_encode([
        'error' => 'Unauthorized access'
    ]);

    exit;
}

$messageId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$messageId) {

    http_response_code(400);

    echo json_encode([
        'error' => 'Invalid message ID'
    ]);

    exit;
}

try {

    $sql = "
        SELECT
            m.id,
            m.message_type,
            m.message_text,
            m.attachment_path,
            m.is_edited,
            m.created_at,
            m.updated_at,

            u1.fullname AS sender_name,
            u1.username AS sender_username,

            u2.fullname AS receiver_name,
            u2.username AS receiver_username,

            CASE
                WHEN mr.message_id IS NOT NULL
                THEN 1
                ELSE 0
            END AS is_read

        FROM messages m

        LEFT JOIN users u1
            ON m.sender_id = u1.id

        LEFT JOIN conversation_participants cp
            ON m.conversation_id = cp.conversation_id
            AND cp.user_id != m.sender_id

        LEFT JOIN users u2
            ON cp.user_id = u2.id

        LEFT JOIN message_reads mr
            ON m.id = mr.message_id

        WHERE m.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'i',
        $messageId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        http_response_code(404);

        echo json_encode([
            'error' => 'Message not found'
        ]);

        exit;
    }

    $message = $result->fetch_assoc();

    echo json_encode([
        'id'                => (int)$message['id'],
        'message_type'      => $message['message_type'],
        'message_text'      => $message['message_text'],
        'attachment_path'   => $message['attachment_path'],
        'is_edited'         => (int)$message['is_edited'],
        'created_at'        => $message['created_at'],
        'updated_at'        => $message['updated_at'],
        'sender_name'       => $message['sender_name'],
        'sender_username'   => $message['sender_username'],
        'receiver_name'     => $message['receiver_name'],
        'receiver_username' => $message['receiver_username'],
        'is_read'           => (int)$message['is_read']
    ]);

} catch (Throwable $e) {

    error_log(
        'get_message.php: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'error' => 'ERR-UQZ11X23'
    ]);
}

exit;