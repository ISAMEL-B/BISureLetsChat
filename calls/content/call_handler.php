<?php
/**
 * BUSure Chat - Call Handler API
 * Inserts/updates call records in the database
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

    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'];

    switch ($action) {
        
        // ✅ Called when a call STARTS (caller initiates)
        case 'start_call':
            $receiver_id = intval($_POST['receiver_id'] ?? 0);
            $call_type = $_POST['call_type'] === 'video' ? 'video' : 'voice';
            
            if (!$receiver_id) throw new Exception('Receiver ID required');
            
            $stmt = $conn->prepare("
                INSERT INTO calls (caller_id, receiver_id, call_type, status, started_at) 
                VALUES (?, ?, ?, 'ringing', NOW())
            ");
            $stmt->bind_param("iis", $current_user_id, $receiver_id, $call_type);
            $stmt->execute();
            $call_id = $stmt->insert_id;
            $stmt->close();
            
            $response['success'] = true;
            $response['call_id'] = $call_id;
            $response['message'] = 'Call started';
            break;

        // ✅ Called when receiver ANSWERS
        case 'answer_call':
            $call_id = intval($_POST['call_id'] ?? 0);
            if (!$call_id) throw new Exception('Call ID required');
            
            $stmt = $conn->prepare("
                UPDATE calls SET status = 'answered', started_at = NOW() WHERE id = ? AND receiver_id = ?
            ");
            $stmt->bind_param("ii", $call_id, $current_user_id);
            $stmt->execute();
            $stmt->close();
            
            $response['success'] = true;
            $response['message'] = 'Call answered';
            break;

        // ✅ Called when receiver DECLINES
        case 'decline_call':
            $call_id = intval($_POST['call_id'] ?? 0);
            if (!$call_id) throw new Exception('Call ID required');
            
            $stmt = $conn->prepare("
                UPDATE calls SET status = 'declined', ended_at = NOW() WHERE id = ? AND receiver_id = ?
            ");
            $stmt->bind_param("ii", $call_id, $current_user_id);
            $stmt->execute();
            $stmt->close();
            
            $response['success'] = true;
            $response['message'] = 'Call declined';
            break;

        // ✅ Called when EITHER party ends the call
        case 'end_call':
            $call_id = intval($_POST['call_id'] ?? 0);
            $call_duration = intval($_POST['duration'] ?? 0); // in seconds
            
            if (!$call_id) throw new Exception('Call ID required');
            
            $stmt = $conn->prepare("
                UPDATE calls SET status = 'ended', ended_at = NOW() WHERE id = ?
            ");
            $stmt->bind_param("i", $call_id);
            $stmt->execute();
            $stmt->close();
            
            $response['success'] = true;
            $response['message'] = 'Call ended';
            break;

        // ✅ Called when call is MISSED (no answer)
        case 'missed_call':
            $call_id = intval($_POST['call_id'] ?? 0);
            if (!$call_id) throw new Exception('Call ID required');
            
            $stmt = $conn->prepare("
                UPDATE calls SET status = 'missed', ended_at = NOW() WHERE id = ?
            ");
            $stmt->bind_param("i", $call_id);
            $stmt->execute();
            $stmt->close();
            
            $response['success'] = true;
            $response['message'] = 'Call marked as missed';
            break;

        default:
            throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Call Handler Error: " . $e->getMessage());
}

echo json_encode($response);
exit;