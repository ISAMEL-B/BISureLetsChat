<?php
/**
 * BUSure Chat - Private Converse (Chat) Page
 * ✅ Updated to match busure_lets_chat schema
 * ✅ Includes read receipts, ticks, reply functionality
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
    $contact_id = intval($_GET['contactId']);
} else {
    header("Location: chats");
    exit();
}

// ✅ FIXED: Fetch contact info from users table
$contact_sql = "SELECT id, fullname, username, profile_photo, is_online, last_seen, status_message FROM users WHERE id = ?";
$contact_stmt = $conn->prepare($contact_sql);
$contact_stmt->bind_param("i", $contact_id);
$contact_stmt->execute();
$contact_result = $contact_stmt->get_result();

if ($contact_result->num_rows > 0) {
    $contact_row = $contact_result->fetch_assoc();
    $contact_name = htmlspecialchars($contact_row['fullname'] ?? $contact_row['username']);
    $contact_username = htmlspecialchars($contact_row['username']);
    $contact_is_online = $contact_row['is_online'];
    $contact_last_seen = $contact_row['last_seen'];
    $contact_status_message = htmlspecialchars($contact_row['status_message'] ?? 'Available');
    
    // ✅ FIXED: Profile photo path
    $contact_profile_photo = !empty($contact_row['profile_photo']) 
        ? '../../uploads/profiles/' . htmlspecialchars($contact_row['profile_photo']) 
        : 'assets/images/default-profile.png';
} else {
    header("Location: chats");
    exit();
}
$contact_stmt->close();

// ✅ FIXED: Find or create conversation between these two users
$conv_sql = "
    SELECT cp1.conversation_id
    FROM conversation_participants cp1
    INNER JOIN conversation_participants cp2 
        ON cp1.conversation_id = cp2.conversation_id
    INNER JOIN conversations c 
        ON c.id = cp1.conversation_id 
        AND c.conversation_type = 'private'
    WHERE cp1.user_id = ? AND cp2.user_id = ?
    LIMIT 1
";
$conv_stmt = $conn->prepare($conv_sql);
$conv_stmt->bind_param("ii", $current_user_id, $contact_id);
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();

if ($conv_result->num_rows > 0) {
    $conversation_id = $conv_result->fetch_assoc()['conversation_id'];
} else {
    // Create new conversation
    $create_conv = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
    $create_conv->bind_param("i", $current_user_id);
    $create_conv->execute();
    $conversation_id = $create_conv->insert_id;
    $create_conv->close();
    
    // Add both users as participants
    $add_participant = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
    $add_participant->bind_param("ii", $conversation_id, $current_user_id);
    $add_participant->execute();
    $add_participant->bind_param("ii", $conversation_id, $contact_id);
    $add_participant->execute();
    $add_participant->close();
    
    // ✅ Auto-add to contacts table
    $add_contact = $conn->prepare("INSERT IGNORE INTO contacts (user_id, contact_user_id) VALUES (?, ?), (?, ?)");
    $add_contact->bind_param("iiii", $current_user_id, $contact_id, $contact_id, $current_user_id);
    $add_contact->execute();
    $add_contact->close();
}
$conv_stmt->close();

// ✅ FIXED: Mark messages as read (using message_reads table)
// This marks all messages FROM the contact TO the current user as read
$mark_read = $conn->prepare("
    INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
    SELECT m.id, ?, NOW()
    FROM messages m
    WHERE m.conversation_id = ?
      AND m.sender_id = ?
      AND m.is_deleted = 0
      AND m.id NOT IN (SELECT message_id FROM message_reads WHERE user_id = ?)
");
$mark_read->bind_param("iiii", $current_user_id, $conversation_id, $contact_id, $current_user_id);
$mark_read->execute();
$mark_read->close();

// ✅ FIXED: Fetch messages with read status (including is_deleted check)
$messages_sql = "
    SELECT 
        m.id,
        m.conversation_id,
        m.sender_id,
        m.message_type,
        m.message_text,
        m.attachment_path,
        m.reply_to_id,
        m.is_edited,
        m.is_deleted,
        m.created_at,
        m.updated_at,
        EXISTS(
            SELECT 1 FROM message_reads mr 
            WHERE mr.message_id = m.id 
            AND mr.user_id = ?
        ) AS is_read,
        (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) AS read_count
    FROM messages m
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
";

$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->bind_param("ii", $contact_id, $conversation_id);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

// Build array of messages with read status
$messages_array = [];
while ($row = $messages_result->fetch_assoc()) {
    // Add read_at for tick display logic
    $row['read_at'] = $row['is_read'] ? date('Y-m-d H:i:s') : null;
    $messages_array[] = $row;
}
$messages_stmt->close();

// Convert to object-like result for backward compatibility
// This creates a mock result object that works with fetch_assoc()
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
    
    public function fetch_all() {
        return $this->data;
    }
    
    public function close() {
        $this->data = [];
        $this->position = 0;
    }
}

$messages_result = new ArrayResult($messages_array);

// ✅ Get conversation unread count for the current user (for badge updates)
$unread_count_query = "
    SELECT COUNT(*) as unread
    FROM messages m
    WHERE m.conversation_id = ?
    AND m.sender_id != ?
    AND m.is_deleted = 0
    AND m.id NOT IN (
        SELECT message_id FROM message_reads WHERE user_id = ?
    )
";
$unread_stmt = $conn->prepare($unread_count_query);
$unread_stmt->bind_param("iii", $conversation_id, $current_user_id, $current_user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result()->fetch_assoc();
$unread_count = $unread_result['unread'];
$unread_stmt->close();
?>