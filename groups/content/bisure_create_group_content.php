<?php
/**
 * BUSure Chat - Create Group Handler
 * Processes AJAX group creation
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated.');
    }

    $current_user_id = $_SESSION['user_id'];
    $group_name = trim($_POST['group_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $members = json_decode($_POST['members'] ?? '[]', true);

    // Validate
    if (empty($group_name)) {
        throw new Exception('Group name is required.');
    }

    if (empty($members) || !is_array($members)) {
        throw new Exception('At least one member is required.');
    }

    // Handle group image upload
    $group_photo = null;
    if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
        $filename = 'group_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destination = $upload_dir . $filename;

        // Validate image
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
            throw new Exception('Invalid image format. Allowed: jpg, png, gif, webp.');
        }

        if ($_FILES['group_image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Image must be less than 5MB.');
        }

        if (move_uploaded_file($_FILES['group_image']['tmp_name'], $destination)) {
            $group_photo = $filename;
        }
    }

    // Start transaction
    $conn->begin_transaction();

    // 1. Create conversation
    $stmt = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('group', ?)");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $conversation_id = $stmt->insert_id;
    $stmt->close();

    if (!$conversation_id) {
        throw new Exception('Failed to create conversation.');
    }

    // 2. Create group
    $stmt = $conn->prepare("
        INSERT INTO groups_chat (conversation_id, group_name, group_photo, description, created_by) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssi", $conversation_id, $group_name, $group_photo, $description, $current_user_id);
    $stmt->execute();
    $group_id = $stmt->insert_id;
    $stmt->close();

    if (!$group_id) {
        throw new Exception('Failed to create group.');
    }

    // 3. Add creator as admin
    $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
    $stmt->bind_param("ii", $group_id, $current_user_id);
    $stmt->execute();
    $stmt->close();

    // 4. Add creator to conversation participants
    $stmt = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $conversation_id, $current_user_id);
    $stmt->execute();
    $stmt->close();

    // 5. Add selected members
    $stmt_member = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt_participant = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");

    foreach ($members as $member_id) {
        $member_id = (int)$member_id;
        
        // Skip if same as creator
        if ($member_id === $current_user_id) continue;

        // Add to group members
        $stmt_member->bind_param("ii", $group_id, $member_id);
        $stmt_member->execute();

        // Add to conversation participants
        $stmt_participant->bind_param("ii", $conversation_id, $member_id);
        $stmt_participant->execute();
    }

    $stmt_member->close();
    $stmt_participant->close();

    // Commit transaction
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Group created successfully!';
    $response['group_id'] = $group_id;
    $response['conversation_id'] = $conversation_id;

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }

    $response['message'] = $e->getMessage();
    error_log("Create Group Error: " . $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response);
exit;