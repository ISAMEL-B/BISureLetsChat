<?php
date_default_timezone_set('Africa/Kampala');

session_start();
require_once __DIR__ . '/../config/db.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = ['status' => 'error', 'error' => 'Unknown error occurred'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate user authentication
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not authenticated.");
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Initialize variables
        $message_text = isset($_POST['reply_message']) ? trim($_POST['reply_message']) : '';
        $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        $reply_to_id = isset($_POST['reply_to_id']) && !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

        // Validate receiver ID
        if (empty($receiver_id)) {
            throw new Exception("Receiver ID is missing.");
        }

        // Validate that receiver exists
        $user_check_query = "SELECT id FROM users WHERE id = ?";
        $user_check_stmt = mysqli_prepare($conn, $user_check_query);
        mysqli_stmt_bind_param($user_check_stmt, 'i', $receiver_id);
        mysqli_stmt_execute($user_check_stmt);
        $user_check_result = mysqli_stmt_get_result($user_check_stmt);
        
        if (mysqli_num_rows($user_check_result) === 0) {
            throw new Exception("Receiver does not exist.");
        }
        mysqli_stmt_close($user_check_stmt);

        // Initialize file variables
        $attachment_path = null;
        $message_type = 'text';

        // Handle file upload if present
        if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error. Error code: " . $_FILES['file']['error']);
            }

            $file_tmp_path = $_FILES['file']['tmp_name'];
            $file_name = $_FILES['file']['name'];
            $file_size = $_FILES['file']['size'];
            $file_type = $_FILES['file']['type'];

            // Determine message type based on file MIME type
            if (strpos($file_type, 'image/') === 0) {
                $message_type = 'image';
            } elseif (strpos($file_type, 'video/') === 0) {
                $message_type = 'video';
            } elseif (strpos($file_type, 'audio/') === 0) {
                $message_type = 'voice';
            } else {
                $message_type = 'file';
            }

            // Define allowed file types
            $allowed_file_types = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'audio/mpeg',
                'audio/wav',
                'application/pdf',
                'video/mp4',
                'video/x-msvideo',
                'video/x-matroska',
                'video/ogg',
                'video/webm'
            ];

            // Check file type and size
            if (!in_array($file_type, $allowed_file_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, GIF, MP3, WAV, PDF, MP4, AVI, MKV, OGG, and WebM files are allowed.");
            }

            if ($file_size > 2 * 1024 * 1024) { // Limit file size to 2MB
                throw new Exception("File size exceeds the limit of 2MB.");
            }

            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }

            // Handle file name collision
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', basename($file_name));
            $attachment_path = $upload_dir . $file_name;

            // Move the uploaded file
            if (!move_uploaded_file($file_tmp_path, $attachment_path)) {
                throw new Exception("Failed to upload file.");
            }
        }

        // Validate that we have either a message or a file
        if (empty($message_text) && empty($attachment_path)) {
            throw new Exception("Message cannot be empty.");
        }

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Check if a private conversation already exists between these two users
            $conversation_query = "
                SELECT cp1.conversation_id 
                FROM conversation_participants cp1
                JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                JOIN conversations c ON cp1.conversation_id = c.id
                WHERE cp1.user_id = ? 
                AND cp2.user_id = ? 
                AND c.conversation_type = 'private'
                LIMIT 1
            ";
            
            $conv_stmt = mysqli_prepare($conn, $conversation_query);
            mysqli_stmt_bind_param($conv_stmt, 'ii', $user_id, $receiver_id);
            mysqli_stmt_execute($conv_stmt);
            $conv_result = mysqli_stmt_get_result($conv_stmt);
            
            if (mysqli_num_rows($conv_result) > 0) {
                // Conversation exists, get the ID
                $conversation = mysqli_fetch_assoc($conv_result);
                $conversation_id = $conversation['conversation_id'];
            } else {
                // Create new conversation
                $create_conv_query = "INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)";
                $create_conv_stmt = mysqli_prepare($conn, $create_conv_query);
                mysqli_stmt_bind_param($create_conv_stmt, 'i', $user_id);
                
                if (!mysqli_stmt_execute($create_conv_stmt)) {
                    throw new Exception("Failed to create conversation.");
                }
                
                $conversation_id = mysqli_insert_id($conn);
                mysqli_stmt_close($create_conv_stmt);
                
                // Add both users as participants
                $add_participant_query = "INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)";
                $add_participant_stmt = mysqli_prepare($conn, $add_participant_query);
                mysqli_stmt_bind_param($add_participant_stmt, 'iiii', $conversation_id, $user_id, $conversation_id, $receiver_id);
                
                if (!mysqli_stmt_execute($add_participant_stmt)) {
                    throw new Exception("Failed to add participants.");
                }
                mysqli_stmt_close($add_participant_stmt);
            }
            mysqli_stmt_close($conv_stmt);

            // Insert the message
            $message_query = "INSERT INTO messages (conversation_id, sender_id, message_type, message_text, attachment_path) 
                             VALUES (?, ?, ?, ?, ?)";
            
            $message_stmt = mysqli_prepare($conn, $message_query);
            mysqli_stmt_bind_param($message_stmt, 'iisss', $conversation_id, $user_id, $message_type, $message_text, $attachment_path);
            
            if (!mysqli_stmt_execute($message_stmt)) {
                throw new Exception("Failed to send the message: " . mysqli_stmt_error($message_stmt));
            }
            
            $message_id = mysqli_insert_id($conn);
            mysqli_stmt_close($message_stmt);

            // Fetch the newly inserted message with user details
            $select_query = "
                SELECT 
                    m.id,
                    m.conversation_id,
                    m.sender_id,
                    m.message_type,
                    m.message_text,
                    m.attachment_path,
                    m.is_edited,
                    m.is_deleted,
                    m.created_at,
                    u.fullname as sender_name,
                    u.username as sender_username,
                    u.profile_photo as sender_photo
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.id = ?
            ";
            
            $select_stmt = mysqli_prepare($conn, $select_query);
            mysqli_stmt_bind_param($select_stmt, 'i', $message_id);
            mysqli_stmt_execute($select_stmt);
            $result = mysqli_stmt_get_result($select_stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $new_message = mysqli_fetch_assoc($result);

                // Format the time
                $new_message['formatted_time'] = date('h:i A | M d', strtotime($new_message['created_at']));

                // If this is a reply, add reply information
                if ($reply_to_id) {
                    $reply_query = "
                        SELECT 
                            m.id,
                            m.message_text,
                            m.message_type,
                            m.attachment_path,
                            u.fullname as reply_to_name
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.id = ?
                    ";
                    
                    $reply_stmt = mysqli_prepare($conn, $reply_query);
                    mysqli_stmt_bind_param($reply_stmt, 'i', $reply_to_id);
                    mysqli_stmt_execute($reply_stmt);
                    $reply_result = mysqli_stmt_get_result($reply_stmt);
                    
                    if ($reply_result && mysqli_num_rows($reply_result) > 0) {
                        $new_message['reply_to'] = mysqli_fetch_assoc($reply_result);
                    }
                    mysqli_stmt_close($reply_stmt);
                }

                $response = [
                    'status' => 'success',
                    'message' => $new_message,
                    'conversation_id' => $conversation_id
                ];
            } else {
                throw new Exception("Failed to retrieve the new message.");
            }

            mysqli_stmt_close($select_stmt);

            // Commit transaction
            mysqli_commit($conn);

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            throw $e;
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Close connection
mysqli_close($conn);

// Ensure proper JSON encoding
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>