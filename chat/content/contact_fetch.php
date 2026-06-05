<?php
/**
 * contact_fetch.php
 * Fetches user contacts with their last conversation details and unread counts
 * Includes "You" (logged-in user) sorted by time like other contacts
 * ✅ Proper tick logic: Sent/Delivered/Read based on last_seen and message_reads
 * Returns JSON for AJAX calls
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

function formatMessageTime($timestamp) {
    if (!$timestamp) return '';
    $message_time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $now = time();
    $diff = $now - $message_time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) { $minutes = floor($diff / 60); return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago'; }
    if ($diff < 39600) { $hours = floor($diff / 3600); return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago'; }
    $message_date = date('Y-m-d', $message_time);
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($message_date === $yesterday) return 'Yesterday';
    if ($message_time > strtotime('-7 days')) return date('l', $message_time);
    return date('m/d/Y', $message_time);
}

function isUserOnline($last_seen) {
    if (!$last_seen) return false;
    return (time() - strtotime($last_seen)) < 300;
}

/**
 * Determine tick status for the last message sent by current user
 * 
 * Tick Logic:
 * - 1 PURPLE tick (sent): Message sent but receiver hasn't been online since
 * - 2 PURPLE ticks (delivered): Receiver has been online after message was sent but hasn't read it
 * - 2 GREEN ticks (read): Message is in message_reads table (receiver opened the chat)
 */
function determineTickStatus($conn, $message_timestamp, $receiver_id, $receiver_last_seen, $conversation_id, $current_user_id) {
    // If message not sent by me, no ticks
    if (!$message_timestamp) return ['is_read' => false, 'is_delivered' => false];
    
    // Check if message has been READ (exists in message_reads)
    $read_sql = "
        SELECT EXISTS(
            SELECT 1 FROM message_reads mr 
            INNER JOIN messages m ON mr.message_id = m.id
            WHERE m.conversation_id = ? 
            AND m.sender_id = ? 
            AND mr.user_id = ?
            AND m.created_at = (
                SELECT MAX(m2.created_at) FROM messages m2 
                WHERE m2.conversation_id = ? AND m2.sender_id = ? AND m2.is_deleted = 0
            )
        ) as is_read
    ";
    $read_stmt = $conn->prepare($read_sql);
    if ($read_stmt) {
        $read_stmt->bind_param("iiiii", $conversation_id, $current_user_id, $receiver_id, $conversation_id, $current_user_id);
        $read_stmt->execute();
        $is_read = (bool)$read_stmt->get_result()->fetch_assoc()['is_read'];
        $read_stmt->close();
    } else {
        $is_read = false;
    }
    
    // If read, return green ticks
    if ($is_read) {
        return ['is_read' => true, 'is_delivered' => true];
    }
    
    // Check if DELIVERED (receiver's last_seen is after message was sent)
    $is_delivered = false;
    if ($receiver_last_seen && $message_timestamp) {
        $message_time = strtotime($message_timestamp);
        $last_seen_time = strtotime($receiver_last_seen);
        // Receiver has been online after the message was sent
        $is_delivered = ($last_seen_time > $message_time);
    }
    
    return ['is_read' => false, 'is_delivered' => $is_delivered];
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    // Fetch current user
    $current_user_sql = "SELECT id, fullname, username, profile_photo, is_online, last_seen, status_message FROM users WHERE id = ? LIMIT 1";
    $current_user_stmt = $conn->prepare($current_user_sql);
    if ($current_user_stmt) {
        $current_user_stmt->bind_param("i", $current_user_id);
        $current_user_stmt->execute();
        $current_user_data = $current_user_stmt->get_result()->fetch_assoc();
        $current_user_stmt->close();
    } else {
        $current_user_data = null;
    }
    
    // Find or create self-conversation
    $self_conv_id = 0;
    $last_self_message = null;
    $last_self_message_time = null;
    
    $self_conv_sql = "SELECT c.id FROM conversations c INNER JOIN conversation_participants cp ON c.id = cp.conversation_id WHERE c.conversation_type = 'self' AND cp.user_id = ? LIMIT 1";
    $self_conv_stmt = $conn->prepare($self_conv_sql);
    if ($self_conv_stmt) {
        $self_conv_stmt->bind_param("i", $current_user_id);
        $self_conv_stmt->execute();
        $self_result = $self_conv_stmt->get_result();
        if ($self_result->num_rows > 0) {
            $self_conv_id = (int)$self_result->fetch_assoc()['id'];
        } else {
            $create_self = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('self', ?)");
            if ($create_self) {
                $create_self->bind_param("i", $current_user_id);
                $create_self->execute();
                $self_conv_id = (int)$create_self->insert_id;
                $create_self->close();
                $add_self = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
                if ($add_self) {
                    $add_self->bind_param("ii", $self_conv_id, $current_user_id);
                    $add_self->execute();
                    $add_self->close();
                }
            }
        }
        $self_conv_stmt->close();
    }
    
    if ($self_conv_id > 0) {
        $last_msg_sql = "SELECT message_text, message_type, created_at FROM messages WHERE conversation_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1";
        $last_msg_stmt = $conn->prepare($last_msg_sql);
        if ($last_msg_stmt) {
            $last_msg_stmt->bind_param("i", $self_conv_id);
            $last_msg_stmt->execute();
            $last_msg_result = $last_msg_stmt->get_result();
            if ($last_msg_result->num_rows > 0) {
                $last_self_message = $last_msg_result->fetch_assoc();
                $last_self_message_time = $last_self_message['created_at'];
            }
            $last_msg_stmt->close();
        }
    }
    
    // Build "You" entry
    $you_entry = null;
    if ($current_user_data) {
        $profile_photo_url = '';
        if (!empty($current_user_data['profile_photo'])) {
            $safe_photo = basename($current_user_data['profile_photo']);
            $safe_photo = preg_replace('/[^a-zA-Z0-9._-]/', '', $safe_photo);
            foreach ([
                __DIR__ . '/../../uploads/profiles/' . $safe_photo,
                __DIR__ . '/../uploads/profiles/' . $safe_photo,
                __DIR__ . '/uploads/profiles/' . $safe_photo
            ] as $photo_path) {
                if (file_exists($photo_path) && is_file($photo_path)) {
                    $profile_photo_url = '../uploads/profiles/' . $safe_photo;
                    break;
                }
            }
        }
        
        $user_fullname = htmlspecialchars($current_user_data['fullname'] ?? $current_user_data['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $user_username = htmlspecialchars($current_user_data['username'] ?? '', ENT_QUOTES, 'UTF-8');
        $user_status = htmlspecialchars($current_user_data['status_message'] ?? 'Available', ENT_QUOTES, 'UTF-8');
        
        $last_message_preview = '';
        $last_message_time = '';
        $last_message_raw = null;
        
        if ($last_self_message) {
            $last_message_raw = $last_self_message['created_at'];
            $last_message_time = formatMessageTime($last_self_message['created_at']);
            switch ($last_self_message['message_type']) {
                case 'image': $last_message_preview = '📷 Image'; break;
                case 'video': $last_message_preview = '🎥 Video'; break;
                case 'voice': $last_message_preview = '🎤 Voice message'; break;
                case 'file': $last_message_preview = '📎 File'; break;
                default: $last_message_preview = $last_self_message['message_text'];
            }
            if (mb_strlen($last_message_preview) > 50) {
                $last_message_preview = mb_substr($last_message_preview, 0, 47) . '...';
            }
        }
        
        $you_entry = [
            'id' => (int)$current_user_id,
            'fullname' => $user_fullname . ' (You)',
            'username' => $user_username,
            'profile_photo' => $profile_photo_url,
            'is_online' => false,
            'last_seen' => $current_user_data['last_seen'],
            'status_message' => $user_status,
            'conversation_id' => (int)$self_conv_id,
            'conversation_type' => 'self',
            'last_message' => $last_message_preview ? htmlspecialchars($last_message_preview, ENT_QUOTES, 'UTF-8') : '',
            'last_message_time' => $last_message_time,
            'last_message_raw' => $last_message_raw,
            'last_message_timestamp' => $last_self_message_time,
            'is_last_message_sent' => true,
            'is_last_message_read' => true,
            'is_last_message_delivered' => true,
            'unread_count' => 0,
            'initials' => strtoupper(mb_substr($user_fullname, 0, 1)),
            'is_self' => true
        ];
    }
    
    // Main query for private conversations
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
        INNER JOIN conversation_participants other_cp ON cp.conversation_id = other_cp.conversation_id AND other_cp.user_id != ?
        INNER JOIN users u ON other_cp.user_id = u.id
        LEFT JOIN (
            SELECT m1.conversation_id, m1.message_text, m1.created_at, m1.sender_id, m1.message_type
            FROM messages m1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) as max_created
                FROM messages WHERE is_deleted = 0 GROUP BY conversation_id
            ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_created
            WHERE m1.is_deleted = 0
        ) last_msg ON cp.conversation_id = last_msg.conversation_id
        LEFT JOIN (
            SELECT m.conversation_id, COUNT(*) as unread_count
            FROM messages m
            WHERE m.sender_id != ? AND m.is_deleted = 0
            AND m.id NOT IN (SELECT mr.message_id FROM message_reads mr WHERE mr.user_id = ?)
            GROUP BY m.conversation_id
        ) unread_counts ON cp.conversation_id = unread_counts.conversation_id
        WHERE cp.user_id = ? AND c.conversation_type = 'private'
    ";
    
    $params = [$current_user_id, $current_user_id, $current_user_id, $current_user_id];
    $types = "iiii";
    
    if (!empty($search)) {
        $query .= " AND (u.fullname LIKE ? OR u.username LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param; $params[] = $search_param;
        $types .= "ss";
    }
    
    $query .= " ORDER BY last_msg.created_at IS NULL ASC, last_msg.created_at DESC, u.fullname ASC LIMIT ? OFFSET ?";
    $params[] = $limit; $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception('Query preparation failed: ' . $conn->error);
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $online_count = 0;
    $all_contacts = [];
    if ($you_entry) $all_contacts[] = $you_entry;
    
    while ($row = $result->fetch_assoc()) {
        $contact_id = $row['id'];
        if ($contact_id == $current_user_id) continue;
        
        $is_online = isUserOnline($row['last_seen']);
        if ($is_online) $online_count++;
        
        $last_message = $row['last_message'] ?? '';
        $last_sender_id = $row['last_sender_id'] ?? null;
        $is_sent_by_me = ($last_sender_id == $current_user_id);
        
        $message_preview = '';
        if (!empty($last_message)) {
            switch ($row['last_message_type']) {
                case 'image': $message_preview = '📷 Image'; break;
                case 'video': $message_preview = '🎥 Video'; break;
                case 'voice': $message_preview = '🎤 Voice message'; break;
                case 'file': $message_preview = '📎 File'; break;
                default: $message_preview = $last_message;
            }
            if ($is_sent_by_me) $message_preview = 'You: ' . $message_preview;
        }
        if (mb_strlen($message_preview) > 50) $message_preview = mb_substr($message_preview, 0, 47) . '...';
        
        $profile_photo_url = '';
        if (!empty($row['profile_photo'])) {
            $safe_photo = basename($row['profile_photo']);
            $safe_photo = preg_replace('/[^a-zA-Z0-9._-]/', '', $safe_photo);
            foreach ([
                __DIR__ . '/../../uploads/profiles/' . $safe_photo,
                __DIR__ . '/../uploads/profiles/' . $safe_photo,
                __DIR__ . '/uploads/profiles/' . $safe_photo
            ] as $photo_path) {
                if (file_exists($photo_path) && is_file($photo_path)) {
                    $profile_photo_url = '../uploads/profiles/' . $safe_photo;
                    break;
                }
            }
        }
        
        // ✅ Determine tick status using the new function
        $tick_status = ['is_read' => false, 'is_delivered' => false];
        if ($is_sent_by_me && $row['last_message_time']) {
            $tick_status = determineTickStatus(
                $conn,
                $row['last_message_time'],
                $contact_id,
                $row['last_seen'],
                $row['conversation_id'],
                $current_user_id
            );
        }
        
        $all_contacts[] = [
            'id' => (int)$contact_id,
            'fullname' => htmlspecialchars($row['fullname'] ?? $row['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'username' => htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8'),
            'profile_photo' => $profile_photo_url,
            'is_online' => $is_online,
            'last_seen' => $row['last_seen'],
            'status_message' => htmlspecialchars($row['status_message'] ?? 'Available', ENT_QUOTES, 'UTF-8'),
            'conversation_id' => (int)$row['conversation_id'],
            'conversation_type' => htmlspecialchars($row['conversation_type'], ENT_QUOTES, 'UTF-8'),
            'last_message' => htmlspecialchars($message_preview, ENT_QUOTES, 'UTF-8'),
            'last_message_time' => $row['last_message_time'] ? formatMessageTime($row['last_message_time']) : '',
            'last_message_raw' => $row['last_message_time'],
            'last_message_timestamp' => $row['last_message_time'],
            'is_last_message_sent' => $is_sent_by_me,
            'is_last_message_read' => $tick_status['is_read'],       // ✅ Green ticks
            'is_last_message_delivered' => $tick_status['is_delivered'], // ✅ Purple double ticks
            'unread_count' => (int)$row['unread_count'],
            'initials' => strtoupper(mb_substr($row['fullname'] ?? $row['username'] ?? 'U', 0, 1)),
            'is_self' => false
        ];
    }
    
    // Sort by timestamp
    usort($all_contacts, function($a, $b) {
        $timeA = $a['last_message_timestamp'] ?? '0000-00-00 00:00:00';
        $timeB = $b['last_message_timestamp'] ?? '0000-00-00 00:00:00';
        $timeCompare = strcmp($timeB, $timeA);
        if ($timeCompare !== 0) return $timeCompare;
        return strcmp($a['fullname'], $b['fullname']);
    });
    
    $contacts = array_map(function($contact) {
        unset($contact['last_message_timestamp']);
        return $contact;
    }, $all_contacts);
    
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
    $response = ['status' => 'error', 'message' => 'Failed to fetch contacts: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;