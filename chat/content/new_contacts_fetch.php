<?php
/**
 * users_fetch.php
 * Fetches ALL users for starting new conversations
 * Shows user details, not conversation details
 */

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

function isUserOnline($last_seen) {
    if (!$last_seen) return false;
    return (time() - strtotime($last_seen)) < 300;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    // Fetch ALL users INCLUDING current user, with their details
    $query = "
        SELECT 
            u.id,
            u.fullname,
            u.username,
            u.profile_photo,
            u.phone,
            u.bio,
            u.status_message,
            u.is_online,
            u.last_seen,
            u.created_at,
            -- Check if conversation already exists (exclude self-conversation)
            CASE 
                WHEN u.id != ? THEN
                    (
                        SELECT c.id 
                        FROM conversations c
                        INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
                        INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = u.id
                        WHERE c.conversation_type = 'private'
                        LIMIT 1
                    )
                ELSE NULL
            END as existing_conversation_id,
            -- Check if in contacts (exclude self)
            CASE 
                WHEN u.id != ? THEN
                    (
                        SELECT COUNT(*) 
                        FROM contacts 
                        WHERE user_id = ? AND contact_user_id = u.id
                    )
                ELSE 0
            END as is_in_contacts,
            -- Flag to identify current user
            (u.id = ?) as is_current_user
        FROM users u
        WHERE 1=1
    ";
    
    $params = [$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id];
    $types = "iiiii";
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (u.fullname LIKE ? OR u.username LIKE ? OR u.phone LIKE ? OR u.status_message LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ssss";
    }
    
    // Order: current user first, then online users, then by name
    $query .= " ORDER BY (u.id = ?) DESC, u.is_online DESC, u.last_seen DESC, u.fullname ASC";
    $params[] = $current_user_id;
    $types .= "i";
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    $online_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $is_online = isUserOnline($row['last_seen']);
        
        if ($is_online) {
            $online_count++;
        }
        
        // Profile photo
        $profile_photo_url = '';
        if (!empty($row['profile_photo'])) {
            $possible_paths = [
                __DIR__ . '/../../uploads/profiles/' . $row['profile_photo'],
                __DIR__ . '/../uploads/profiles/' . $row['profile_photo'],
            ];
            
            foreach ($possible_paths as $photo_path) {
                if (file_exists($photo_path)) {
                    $profile_photo_url = '../uploads/profiles/' . $row['profile_photo'];
                    break;
                }
            }
        }
        
        // Format last seen
        $last_seen_text = '';
        if ($row['last_seen']) {
            $last_seen_time = strtotime($row['last_seen']);
            $diff = time() - $last_seen_time;
            
            if ($diff < 60) {
                $last_seen_text = 'Just now';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                $last_seen_text = $minutes . ' min ago';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                $last_seen_text = $hours . 'h ago';
            } else {
                $last_seen_text = date('M d', $last_seen_time);
            }
        }
        
        // Determine what to show as subtitle
        $subtitle = '';
        if ((bool)$row['is_current_user']) {
            $subtitle = '(You)';
        } elseif (!empty($row['status_message']) && $row['status_message'] !== 'Available') {
            $subtitle = $row['status_message'];
        } elseif (!empty($row['bio'])) {
            $subtitle = $row['bio'];
        } elseif (!empty($row['phone'])) {
            $subtitle = '📱 ' . $row['phone'];
        } else {
            $subtitle = 'Available';
        }
        
        // Build display name with (You) suffix for current user
        $fullname = htmlspecialchars($row['fullname'] ?? $row['username'] ?? 'Unknown');
        if ((bool)$row['is_current_user']) {
            $fullname .= ' (You)';
        }
        
        $users[] = [
            'id' => (int)$user_id,
            'fullname' => $fullname,
            'username' => htmlspecialchars($row['username'] ?? ''),
            'profile_photo' => $profile_photo_url,
            'phone' => htmlspecialchars($row['phone'] ?? ''),
            'bio' => htmlspecialchars($row['bio'] ?? ''),
            'status_message' => htmlspecialchars($row['status_message'] ?? 'Available'),
            'is_online' => $is_online,
            'last_seen' => $row['last_seen'],
            'last_seen_text' => $last_seen_text,
            'subtitle' => htmlspecialchars($subtitle),
            'existing_conversation_id' => $row['existing_conversation_id'] ? (int)$row['existing_conversation_id'] : null,
            'is_in_contacts' => (int)$row['is_in_contacts'] > 0,
            'is_current_user' => (bool)$row['is_current_user'],
            'initials' => strtoupper(substr($row['fullname'] ?? $row['username'] ?? 'U', 0, 1))
        ];
    }
    
    $response = [
        'status' => 'success',
        'data' => [
            'users' => $users,
            'online_count' => $online_count,
            'total' => count($users),
            'page' => $page,
            'has_more' => count($users) >= $limit
        ]
    ];
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => 'Failed to fetch users: ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;