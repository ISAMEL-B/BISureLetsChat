<?php
/**
 * BUSure Chat - Admin Delete File API
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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid file ID']));
}

$fileId = intval($_GET['id']);

try {
    // ✅ FIXED: Get file info from messages table
    $stmt = $conn->prepare("SELECT id, attachment_path FROM messages WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'File not found']));
    }
    
    $file = $result->fetch_assoc();
    $stmt->close();
    
    // ✅ FIXED: Soft delete from messages table
    $updateStmt = $conn->prepare("UPDATE messages SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $fileId);
    $updateSuccess = $updateStmt->execute();
    $updateStmt->close();
    
    if (!$updateSuccess) {
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => 'Failed to delete file record']));
    }
    
    // Delete physical file if it exists
    $fileDeleted = true;
    if (!empty($file['attachment_path'])) {
        $filePath = __DIR__ . '/../../' . $file['attachment_path'];
        if (file_exists($filePath)) {
            $fileDeleted = unlink($filePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully',
        'physical_deleted' => $fileDeleted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();