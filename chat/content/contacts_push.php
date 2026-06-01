<?php

require_once '../../register/config/db.php';
require_once 'src/vendor/autoload.php'; // Composer WebSocket Client

use WebSocket\Client;

// Accept CLI argument or HTTP GET parameter for user_id
if (php_sapi_name() !== 'cli' && !isset($_GET['user_id'])) {
    http_response_code(400);
    exit("Missing user_id");
}

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) ($argv[1] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    exit("Invalid user ID");
}

// Make user_id available to contact fetch script (if needed)
$_GET['user_id'] = $userId;

// Include contact fetch, expected to return mysqli_result or false
$result = include 'contact_fetch.php';

if (!$result || !method_exists($result, 'fetch_assoc')) {
    http_response_code(500);
    exit("❌ Failed to fetch contacts.");
}

// Build contacts array for pushing
$contacts = [];
while ($row = $result->fetch_assoc()) {
    $profilePath = $row['profile_picture'] ?? '';
    $row['profile_picture_exists'] = !empty($profilePath) && file_exists($profilePath);
    $row['is_online'] = rand(0, 1); // TODO: Replace with real status logic

    $sentTime = strtotime($row['last_sent_time'] ?? '0');
    $recvTime = strtotime($row['last_received_time'] ?? '0');

    if ($sentTime > $recvTime) {
        $row['last_message'] = $row['last_sent_message'] ?? '';
        $row['last_message_time'] = date('h:i A', $sentTime);
    } else {
        $row['last_message'] = $row['last_received_message'] ?? '';
        $row['last_message_time'] = date('h:i A', $recvTime);
    }

    $contacts[] = $row;
}

// Push contacts via WebSocket
try {
    $ws = new Client("ws://localhost:8081");
    $ws->send(json_encode([
        'type' => 'contacts_update',
        'user_id' => $userId,
        'contacts' => $contacts
    ]));
    $ws->close();

    echo "✅ Contacts pushed to user $userId";
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ Failed to push: " . $e->getMessage();
}
