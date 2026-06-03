<?php
/**
 * BUSure Chat - Admin Clear Logs API
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$type = $_POST['type'] ?? 'error';
$logFile = $type === 'access' 
    ? __DIR__ . '/../../logs/access.log' 
    : __DIR__ . '/../../logs/errors.log';

try {
    if (file_exists($logFile)) {
        // Backup before clearing
        $backup = $logFile . '.' . date('Y-m-d_His') . '.bak';
        copy($logFile, $backup);
        
        // Clear the log
        file_put_contents($logFile, '');
        
        echo json_encode(['success' => true, 'message' => 'Log cleared successfully', 'backup' => basename($backup)]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Log file does not exist']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}