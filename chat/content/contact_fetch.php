<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$currentUser = $_SESSION['user_id'] ?? null;

if (!$currentUser) {
    http_response_code(401);
    exit('User not authenticated');
}

$sql = "
    SELECT
        id,
        fullname,
        username,
        profile_photo,
        is_online,
        last_seen
    FROM users
    WHERE id != ?
    ORDER BY fullname ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param("i", $currentUser);

$stmt->execute();

$result = $stmt->get_result();

return $result;