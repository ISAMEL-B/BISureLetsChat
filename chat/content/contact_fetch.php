<?php
/**
 * contact_fetch.php
 * Fetches user contacts with their last conversation details and unread counts
 * Returns JSON for AJAX calls
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
require_once __DIR__ . '/../../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

/**
 * Format timestamp intelligently
 */
function formatMessageTime($timestamp) {
    if (!$timestamp) return '';
    
    $message_time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $now = time();
    $diff = $now - $message_time;
    
    if ($diff < 60) {
        return 'Just now';
    }
    
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    }
    
    if ($diff < 39600) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    
    $message_date = date('Y-m-d', $message_time);
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($message_date === $yesterday) {
        return 'Yesterday';
    }
    
    if ($message_time > strtotime('-7 days')) {
        return date('l', $message_time);
    }
    
    return date('m/d/Y', $message_time);
}

/**
 * Check if user is online
 */
function isUserOnline($last_seen) {
    if (!$last_seen) return false;
    return (time() - strtotime($last_seen)) < 300;
}

// AJAX Request Handling
try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    /**
     * MAIN QUERY:
     * 1. Find all private conversations where current user participates
     * 2. Get the other participant's user details
     * 3. Get the latest message in each conversation
     * 4. Count unread messages (messages NOT read by current user)
     */
    $query = "
        SELECT 
            u.id,
            u.fullname,
            u.username,
            u.profile_photo,
            u.is_online,
            u.last_seen,
            u.status_message,
            cp.conversation_id,
            c.conversation_type,
            last_msg.message_text as last_message,
            last_msg.created_at as last_message_time,
            last_msg.sender_id as last_sender_id,
            last_msg.message_type as last_message_type,
            COALESCE(unread_counts.unread_count, 0) as unread_count
        FROM conversation_participants cp
        INNER JOIN conversations c ON cp.conversation_id = c.id
        INNER JOIN conversation_participants other_cp ON cp.conversation_id = other_cp.conversation_id 
            AND other_cp.user_id != ?
        INNER JOIN users u ON other_cp.user_id = u.id
        LEFT JOIN (
            -- Subquery to get the latest message for each conversation
            SELECT 
                m1.conversation_id,
                m1.message_text,
                m1.created_at,
                m1.sender_id,
                m1.message_type
            FROM messages m1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) as max_created
                FROM messages
                WHERE is_deleted = 0
                GROUP BY conversation_id
            ) m2 ON m1.conversation_id = m2.conversation_id 
                AND m1.created_at = m2.max_created
            WHERE m1.is_deleted = 0
        ) last_msg ON cp.conversation_id = last_msg.conversation_id
        LEFT JOIN (
            -- Subquery to count unread messages for current user
            -- Unread = messages where:
            -- - sender is NOT current user (someone else sent it)
            -- - message is NOT deleted
            -- - message has NOT been read by current user (not in message_reads)
            SELECT 
                m.conversation_id,
                COUNT(*) as unread_count
            FROM messages m
            WHERE m.sender_id != ?
            AND m.is_deleted = 0
            AND m.id NOT IN (
                SELECT mr.message_id 
                FROM message_reads mr 
                WHERE mr.user_id = ?
            )
            GROUP BY m.conversation_id
        ) unread_counts ON cp.conversation_id = unread_counts.conversation_id
        WHERE cp.user_id = ?
        AND c.conversation_type = 'private'
    ";
    
    $params = [$current_user_id, $current_user_id, $current_user_id, $current_user_id];
    $types = "iiii";
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (u.fullname LIKE ? OR u.username LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    // Add ordering - conversations with messages first, then by most recent, then alphabetically
    $query .= " ORDER BY last_msg.created_at IS NULL ASC, last_msg.created_at DESC, u.fullname ASC";
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    // Debug: Log the query with parameters (comment out in production)
    // error_log("Contact fetch query: " . $query);
    // error_log("Params: " . json_encode($params));
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contacts = [];
    $online_count = 0;
    $seen_user_ids = []; // Track seen users to prevent duplicates
    
    while ($row = $result->fetch_assoc()) {
        $contact_id = $row['id'];
        
        // Skip if we've already added this user (safety check)
        if (in_array($contact_id, $seen_user_ids)) {
            continue;
        }
        $seen_user_ids[] = $contact_id;
        
        $is_online = isUserOnline($row['last_seen']);
        
        if ($is_online) {
            $online_count++;
        }
        
        // Determine last message details
        $last_message = $row['last_message'] ?? '';
        $last_sender_id = $row['last_sender_id'] ?? null;
        $is_sent_by_me = ($last_sender_id == $current_user_id);
        
        // Format message preview based on message type
        $message_preview = '';
        
        if (!empty($last_message)) {
            switch ($row['last_message_type']) {
                case 'image':
                    $message_preview = '📷 Image';
                    break;
                case 'video':
                    $message_preview = '🎥 Video';
                    break;
                case 'voice':
                    $message_preview = '🎤 Voice message';
                    break;
                case 'file':
                    $message_preview = '📎 File';
                    break;
                default:
                    $message_preview = $last_message;
            }
            
            // Add "You: " prefix if current user sent the last message
            if ($is_sent_by_me) {
                $message_preview = 'You: ' . $message_preview;
            }
        }
        
        // Truncate long messages for preview
        if (mb_strlen($message_preview) > 50) {
            $message_preview = mb_substr($message_preview, 0, 47) . '...';
        }
        
        // Check if profile photo exists
        $profile_photo_url = '';
        if (!empty($row['profile_photo'])) {
            // Check multiple possible paths relative to this file
            $possible_paths = [
                __DIR__ . '/../../uploads/profiles/' . $row['profile_photo'],
                __DIR__ . '/../uploads/profiles/' . $row['profile_photo'],
                __DIR__ . '/uploads/profiles/' . $row['profile_photo']
            ];
            
            foreach ($possible_paths as $photo_path) {
                if (file_exists($photo_path)) {
                    $profile_photo_url = '../uploads/profiles/' . $row['profile_photo'];
                    break;
                }
            }
        }
        
        $contacts[] = [
            'id' => (int)$contact_id,
            'fullname' => htmlspecialchars($row['fullname'] ?? $row['username'] ?? 'Unknown'),
            'username' => htmlspecialchars($row['username'] ?? ''),
            'profile_photo' => $profile_photo_url,
            'is_online' => $is_online,
            'last_seen' => $row['last_seen'],
            'status_message' => htmlspecialchars($row['status_message'] ?? 'Available'),
            'conversation_id' => (int)$row['conversation_id'],
            'conversation_type' => $row['conversation_type'],
            'last_message' => htmlspecialchars($message_preview),
            'last_message_time' => $row['last_message_time'] ? formatMessageTime($row['last_message_time']) : '',
            'last_message_raw' => $row['last_message_time'],
            'is_last_message_sent' => $is_sent_by_me,
            'unread_count' => (int)$row['unread_count'],
            'initials' => strtoupper(mb_substr($row['fullname'] ?? $row['username'] ?? 'U', 0, 1))
        ];
    }
    
    $response = [
        'status' => 'success',
        'data' => [
            'contacts' => $contacts,
            'online_count' => $online_count,
            'total' => count($contacts),
            'page' => $page,
            'has_more' => count($contacts) >= $limit
        ]
    ];
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => 'Failed to fetch contacts: ' . $e->getMessage()
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;