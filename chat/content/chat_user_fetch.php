<?php
/**
 * BUSure Chat - Private Converse (Chat) Page
 * ✅ Fixed self-chat functionality
 */

// Start the session to manage user sessions
session_start();

// Database connection
require_once __DIR__ . '/../../config/db.php';
    
// Check if the user is logged in
require_once __DIR__ . '/../../includes/auth_check.php';

$current_user_id = $_SESSION['user_id'];

// Get the contact_id from GET parameters
if (isset($_GET['contactId'])) {
    $contact_id = filter_var($_GET['contactId'], FILTER_VALIDATE_INT);
    if ($contact_id === false || $contact_id <= 0) {
        header("Location: chats");
        exit();
    }
} else {
    header("Location: chats");
    exit();
}

// Check if this is a self-chat
$is_self_chat = ($contact_id == $current_user_id);

// Initialize all variables
$contact_name = 'Unknown';
$contact_username = '';
$contact_profile_photo = 'assets/images/default-profile.png';
$contact_is_online = 0;
$contact_last_seen = '';
$contact_status_message = 'Available';
$conversation_id = 0;
$unread_count = 0;
$messages_array = [];

if ($is_self_chat) {
    // ============ SELF-CHAT ============
    
    // Get current user info
    $user_sql = "SELECT fullname, username, profile_photo FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    if ($user_stmt) {
        $user_stmt->bind_param("i", $current_user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows > 0) {
            $user_row = $user_result->fetch_assoc();
            $contact_name = htmlspecialchars($user_row['fullname'] ?? $user_row['username'] ?? 'You', ENT_QUOTES, 'UTF-8');
            $contact_username = htmlspecialchars($user_row['username'] ?? '', ENT_QUOTES, 'UTF-8');
            $contact_status_message = '📝 Saved messages & notes';
            
            if (!empty($user_row['profile_photo'])) {
                $safe_photo = basename($user_row['profile_photo']);
                $contact_profile_photo = '../../uploads/profiles/' . htmlspecialchars($safe_photo, ENT_QUOTES, 'UTF-8');
            }
        }
        $user_stmt->close();
    }
    
    // Find or create self-conversation
    $conv_sql = "SELECT c.id FROM conversations c 
                 INNER JOIN conversation_participants cp ON c.id = cp.conversation_id 
                 WHERE c.conversation_type = 'self' AND cp.user_id = ? LIMIT 1";
    $conv_stmt = $conn->prepare($conv_sql);
    if ($conv_stmt) {
        $conv_stmt->bind_param("i", $current_user_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        
        if ($conv_result->num_rows > 0) {
            $conversation_id = (int)$conv_result->fetch_assoc()['id'];
        } else {
            $create = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('self', ?)");
            if ($create) {
                $create->bind_param("i", $current_user_id);
                $create->execute();
                $conversation_id = (int)$create->insert_id;
                $create->close();
                
                $add = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
                if ($add) {
                    $add->bind_param("ii", $conversation_id, $current_user_id);
                    $add->execute();
                    $add->close();
                }
            }
        }
        $conv_stmt->close();
    }
    
    // Fetch self-messages
    if ($conversation_id > 0) {
        $msg_sql = "SELECT * FROM messages WHERE conversation_id = ? AND is_deleted = 0 ORDER BY created_at ASC";
        $msg_stmt = $conn->prepare($msg_sql);
        if ($msg_stmt) {
            $msg_stmt->bind_param("i", $conversation_id);
            $msg_stmt->execute();
            $msg_result = $msg_stmt->get_result();
            
            while ($row = $msg_result->fetch_assoc()) {
                $row['read_at'] = date('Y-m-d H:i:s');
                $row['is_read'] = 1;
                $row['read_count'] = 1;
                $messages_array[] = $row;
            }
            $msg_stmt->close();
        }
    }
    
} else {
    // ============ PRIVATE CHAT ============
    
    // Fetch contact info
    $contact_sql = "SELECT id, fullname, username, profile_photo, is_online, last_seen, status_message FROM users WHERE id = ?";
    $contact_stmt = $conn->prepare($contact_sql);
    if ($contact_stmt) {
        $contact_stmt->bind_param("i", $contact_id);
        $contact_stmt->execute();
        $contact_result = $contact_stmt->get_result();

        if ($contact_result->num_rows > 0) {
            $contact_row = $contact_result->fetch_assoc();
            $contact_name = htmlspecialchars($contact_row['fullname'] ?? $contact_row['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $contact_username = htmlspecialchars($contact_row['username'] ?? '', ENT_QUOTES, 'UTF-8');
            $contact_is_online = $contact_row['is_online'];
            $contact_last_seen = $contact_row['last_seen'];
            $contact_status_message = htmlspecialchars($contact_row['status_message'] ?? 'Available', ENT_QUOTES, 'UTF-8');
            
            if (!empty($contact_row['profile_photo'])) {
                $safe_photo = basename($contact_row['profile_photo']);
                $contact_profile_photo = '../../uploads/profiles/' . htmlspecialchars($safe_photo, ENT_QUOTES, 'UTF-8');
            }
        }
        $contact_stmt->close();
    }

    // Find or create private conversation
    $conv_sql = "
        SELECT cp1.conversation_id
        FROM conversation_participants cp1
        INNER JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
        INNER JOIN conversations c ON c.id = cp1.conversation_id AND c.conversation_type = 'private'
        WHERE cp1.user_id = ? AND cp2.user_id = ?
        LIMIT 1
    ";
    $conv_stmt = $conn->prepare($conv_sql);
    if ($conv_stmt) {
        $conv_stmt->bind_param("ii", $current_user_id, $contact_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();

        if ($conv_result->num_rows > 0) {
            $conversation_id = (int)$conv_result->fetch_assoc()['conversation_id'];
        } else {
            $create = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
            if ($create) {
                $create->bind_param("i", $current_user_id);
                $create->execute();
                $conversation_id = (int)$create->insert_id;
                $create->close();
                
                $add = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
                if ($add) {
                    $add->bind_param("ii", $conversation_id, $current_user_id);
                    $add->execute();
                    $add->bind_param("ii", $conversation_id, $contact_id);
                    $add->execute();
                    $add->close();
                }
                
                $ac = $conn->prepare("INSERT IGNORE INTO contacts (user_id, contact_user_id) VALUES (?, ?), (?, ?)");
                if ($ac) {
                    $ac->bind_param("iiii", $current_user_id, $contact_id, $contact_id, $current_user_id);
                    $ac->execute();
                    $ac->close();
                }
            }
        }
        $conv_stmt->close();
    }

    // Mark messages as read
    $mark = $conn->prepare("
        INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
        SELECT m.id, ?, NOW() FROM messages m
        WHERE m.conversation_id = ? AND m.sender_id = ? AND m.is_deleted = 0
        AND m.id NOT IN (SELECT message_id FROM message_reads WHERE user_id = ?)
    ");
    if ($mark) {
        $mark->bind_param("iiii", $current_user_id, $conversation_id, $contact_id, $current_user_id);
        $mark->execute();
        $mark->close();
    }

    // Fetch messages
    if ($conversation_id > 0) {
        $msg_sql = "
            SELECT m.*, 
                   EXISTS(SELECT 1 FROM message_reads mr WHERE mr.message_id = m.id AND mr.user_id = ?) AS is_read,
                   (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) AS read_count
            FROM messages m WHERE m.conversation_id = ? ORDER BY m.created_at ASC
        ";
        $msg_stmt = $conn->prepare($msg_sql);
        if ($msg_stmt) {
            $msg_stmt->bind_param("ii", $contact_id, $conversation_id);
            $msg_stmt->execute();
            $msg_result = $msg_stmt->get_result();
            
            while ($row = $msg_result->fetch_assoc()) {
                $row['read_at'] = $row['is_read'] ? date('Y-m-d H:i:s') : null;
                $messages_array[] = $row;
            }
            $msg_stmt->close();
        }
    }

    // Unread count
    $unread_sql = "
        SELECT COUNT(*) as unread FROM messages m
        WHERE m.conversation_id = ? AND m.sender_id != ? AND m.is_deleted = 0
        AND m.id NOT IN (SELECT message_id FROM message_reads WHERE user_id = ?)
    ";
    $unread_stmt = $conn->prepare($unread_sql);
    if ($unread_stmt) {
        $unread_stmt->bind_param("iii", $conversation_id, $current_user_id, $current_user_id);
        $unread_stmt->execute();
        $ur = $unread_stmt->get_result()->fetch_assoc();
        $unread_count = (int)($ur['unread'] ?? 0);
        $unread_stmt->close();
    }
}

// Create result object
class ArrayResult {
    private $data;
    private $position = 0;
    public $num_rows;
    
    public function __construct($data) {
        $this->data = $data;
        $this->num_rows = count($data);
    }
    
    public function fetch_assoc() {
        if ($this->position < count($this->data)) {
            return $this->data[$this->position++];
        }
        return null;
    }
    
    public function close() {
        $this->data = [];
        $this->position = 0;
    }
}

$messages_result = new ArrayResult($messages_array);

// Now include the HTML template
// The HTML part remains the same as your existing chat page
?>