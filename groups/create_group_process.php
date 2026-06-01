<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../../../register/config/db.php';

$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $members = json_decode($_POST['members']);
    
    // Validate input
    if (empty($group_name)) {
        echo json_encode(['success' => false, 'message' => 'Group name is required']);
        exit();
    }
    
    if (empty($members) || !is_array($members)) {
        echo json_encode(['success' => false, 'message' => 'At least one member is required']);
        exit();
    }
    
    // Add current user to members
    if (!in_array($current_user_id, $members)) {
        $members[] = $current_user_id;
    }
    
    // Handle group image upload
    $group_image_path = null;
    if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/group_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'group_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['group_image']['tmp_name'], $file_path)) {
            $group_image_path = $file_path;
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert group
        $stmt = $conn->prepare("INSERT INTO groups (group_name, created_by, group_image) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $group_name, $current_user_id, $group_image_path);
        $stmt->execute();
        $group_id = $conn->insert_id;
        $stmt->close();
        
        // Add members to group
        $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, ?)");
        
        // Add creator as admin
        $is_admin = 1;
        $stmt->bind_param("iii", $group_id, $current_user_id, $is_admin);
        $stmt->execute();
        
        // Add other members as non-admins
        $is_admin = 0;
        foreach ($members as $member_id) {
            if ($member_id != $current_user_id) {
                $stmt->bind_param("iii", $group_id, $member_id, $is_admin);
                $stmt->execute();
            }
        }
        
        $stmt->close();
        $conn->commit();
        
        echo json_encode(['success' => true, 'group_id' => $group_id, 'message' => 'Group created successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error creating group: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}