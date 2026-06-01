<?php 
session_start();

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Include database connection
require_once __DIR__ . '/../../config/db.php';

// Initialize variables
$message = '';
$messageType = '';
$contacts = [];

// Fetch contacts list (users the current user has conversations with or all users)
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    
    // Fetch all users except current user, with conversation status
    $contacts_query = "
        SELECT 
            u.id,
            u.fullname,
            u.username,
            u.profile_photo,
            u.status_message,
            u.is_online,
            u.last_seen,
            CASE 
                WHEN cp.conversation_id IS NOT NULL THEN 'existing'
                ELSE 'new'
            END as conversation_status,
            cp.conversation_id
        FROM users u
        LEFT JOIN conversation_participants cp1 ON u.id = cp1.user_id
        LEFT JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id 
            AND cp2.user_id = ?
        LEFT JOIN conversations c ON cp1.conversation_id = c.id 
            AND c.conversation_type = 'private'
        LEFT JOIN conversation_participants cp ON c.id = cp.conversation_id 
            AND cp.user_id = u.id
        WHERE u.id != ?
        GROUP BY u.id
        ORDER BY u.is_online DESC, u.fullname ASC
    ";
    
    $contacts_stmt = $conn->prepare($contacts_query);
    $contacts_stmt->bind_param("ii", $current_user_id, $current_user_id);
    $contacts_stmt->execute();
    $contacts_result = $contacts_stmt->get_result();
    
    while ($row = $contacts_result->fetch_assoc()) {
        $contacts[] = $row;
    }
    $contacts_stmt->close();
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("You must be logged in to send messages.");
        }
        
        $sender_id = $_SESSION['user_id'];
        $recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
        $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
        $message_type = 'text';
        $attachment_path = null;
        
        // Validate recipient
        if ($recipient_id <= 0) {
            throw new Exception("Please select a recipient.");
        }
        
        // Check if recipient exists
        $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $user_check->bind_param("i", $recipient_id);
        $user_check->execute();
        if ($user_check->get_result()->num_rows === 0) {
            throw new Exception("Recipient not found.");
        }
        $user_check->close();
        
        // Handle file attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $file_size = $file['size'];
            $file_tmp = $file['tmp_name'];
            $file_name = $file['name'];
            $file_type = $file['type'];
            
            // Validate file size (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                throw new Exception("File size must be less than 5MB.");
            }
            
            // Determine message type from file
            if (strpos($file_type, 'image/') === 0) {
                $message_type = 'image';
            } elseif (strpos($file_type, 'video/') === 0) {
                $message_type = 'video';
            } elseif (strpos($file_type, 'audio/') === 0) {
                $message_type = 'voice';
            } else {
                $message_type = 'file';
            }
            
            // Create upload directory
            $upload_dir = __DIR__ . '/uploads/attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $attachment_path = 'uploads/attachments/' . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                throw new Exception("Failed to upload file.");
            }
        }
        
        // Validate message content
        if (empty($message_text) && empty($attachment_path)) {
            throw new Exception("Message cannot be empty.");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Check if conversation exists
        $conv_query = "
            SELECT cp1.conversation_id 
            FROM conversation_participants cp1
            JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
            JOIN conversations c ON cp1.conversation_id = c.id
            WHERE cp1.user_id = ? 
            AND cp2.user_id = ? 
            AND c.conversation_type = 'private'
            LIMIT 1
        ";
        
        $conv_stmt = $conn->prepare($conv_query);
        $conv_stmt->bind_param("ii", $sender_id, $recipient_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        
        if ($conv_result->num_rows > 0) {
            $conversation = $conv_result->fetch_assoc();
            $conversation_id = $conversation['conversation_id'];
        } else {
            // Create new conversation
            $create_conv = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
            $create_conv->bind_param("i", $sender_id);
            $create_conv->execute();
            $conversation_id = $conn->insert_id;
            $create_conv->close();
            
            // Add participants
            $add_participants = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
            $add_participants->bind_param("iiii", $conversation_id, $sender_id, $conversation_id, $recipient_id);
            $add_participants->execute();
            $add_participants->close();
        }
        $conv_stmt->close();
        
        // Insert message
        $insert_msg = $conn->prepare("
            INSERT INTO messages (conversation_id, sender_id, message_type, message_text, attachment_path) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert_msg->bind_param("iisss", $conversation_id, $sender_id, $message_type, $message_text, $attachment_path);
        $insert_msg->execute();
        $message_id = $conn->insert_id;
        $insert_msg->close();
        
        // Commit transaction
        $conn->commit();
        
        // Success
        $message = "Message sent successfully!";
        $messageType = 'success';
        
        // Redirect to chat with the recipient
        header("Location: chat?user=" . $recipient_id);
        exit();
        
    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch recipient info if ID is provided in URL
$selected_recipient = null;
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $recipient_id = (int)$_GET['user'];
    $recipient_query = $conn->prepare("SELECT id, fullname, username, profile_photo FROM users WHERE id = ?");
    $recipient_query->bind_param("i", $recipient_id);
    $recipient_query->execute();
    $recipient_result = $recipient_query->get_result();
    $selected_recipient = $recipient_result->fetch_assoc();
    $recipient_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BISure Chat - New Message</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ce-primary: #128C7E;
            --ce-primary-dark: #075E54;
            --ce-secondary: #25D366;
            --ce-accent: #34B7F1;
            --ce-success: #4CAF50;
            --ce-error: #F44336;
            --ce-text-light: #FFFFFF;
            --ce-text-dark: #3B4A54;
            --ce-text-secondary: #718096;
            --ce-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --ce-shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
            --ce-transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --ce-body-bg: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            --ce-card-bg: #ffffff;
            --ce-input-bg: #f9f9f9;
            --ce-input-border: #e0e0e0;
            --ce-input-focus-bg: #ffffff;
            --ce-file-bg: #f9f9f9;
            --ce-file-hover: #f0f0f0;
            --ce-preview-bg: #f5f5f5;
            --ce-overlay-bg: rgba(0, 0, 0, 0.5);
            --ce-spinner-border: #f3f3f3;
            --ce-loading-card: #ffffff;
        }

        .ce-page {
            font-family: 'Poppins', sans-serif;
            background: var(--ce-body-bg);
            color: var(--ce-text-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .ce-container {
            width: 100%;
            max-width: 680px;
            margin: 2rem;
            background: var(--ce-card-bg);
            border-radius: 16px;
            box-shadow: var(--ce-shadow);
            overflow: hidden;
            position: relative;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .ce-header {
            background: linear-gradient(135deg, var(--ce-primary) 0%, var(--ce-primary-dark) 100%);
            color: var(--ce-text-light);
            padding: 1.5rem 2rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ce-header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ce-header h1 i {
            font-size: 1.5rem;
        }

        .ce-back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--ce-transition);
            text-decoration: none;
        }

        .ce-back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        .ce-form-container {
            padding: 2rem;
        }

        .ce-message {
            display: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            animation: ceFadeIn 0.3s ease-out;
        }

        @keyframes ceFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ce-msg-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--ce-success);
            border-left: 4px solid var(--ce-success);
        }

        .ce-msg-error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--ce-error);
            border-left: 4px solid var(--ce-error);
        }

        .ce-form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .ce-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--ce-primary-dark);
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .ce-form-control {
            width: 95%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--ce-input-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--ce-transition);
            background-color: var(--ce-input-bg);
            color: var(--ce-text-dark);
            font-family: 'Poppins', sans-serif;
        }

        .ce-form-control:focus {
            outline: none;
            border-color: var(--ce-accent);
            box-shadow: 0 0 0 2px rgba(52, 183, 241, 0.2);
            background-color: var(--ce-input-focus-bg);
        }

        .ce-form-control::placeholder {
            color: #aaa;
        }

        select.ce-form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23718096' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        textarea.ce-form-control {
            min-height: 120px;
            resize: vertical;
        }

        .ce-file-input {
            display: none;
        }

        .ce-file-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.8rem 1rem;
            background-color: var(--ce-file-bg);
            border: 1px dashed var(--ce-input-border);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--ce-transition);
        }

        .ce-file-label:hover {
            background-color: var(--ce-file-hover);
            border-color: var(--ce-accent);
        }

        .ce-file-label i {
            color: var(--ce-primary);
            font-size: 1.2rem;
        }

        .ce-file-name {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
            transition: color 0.3s ease;
        }

        .ce-file-preview {
            margin-top: 0.5rem;
            display: none;
            align-items: center;
            gap: 8px;
            padding: 0.5rem;
            background: var(--ce-preview-bg);
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .ce-file-preview i {
            color: var(--ce-primary);
        }

        .ce-remove-file {
            color: var(--ce-error);
            cursor: pointer;
            margin-left: auto;
        }

        /* Contact list styling */
        .ce-contact-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--ce-input-border);
            border-radius: 8px;
            margin-top: 0.5rem;
        }

        .ce-contact-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.2s;
            gap: 10px;
        }

        .ce-contact-item:hover {
            background: var(--ce-file-hover);
        }

        .ce-contact-item.selected {
            background: rgba(18, 140, 126, 0.1);
            border-left: 3px solid var(--ce-primary);
        }

        .ce-contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ce-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .ce-contact-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .ce-contact-info {
            flex: 1;
            min-width: 0;
        }

        .ce-contact-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ce-contact-status {
            font-size: 0.8rem;
            color: var(--ce-text-secondary);
        }

        .ce-online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ce-secondary);
            margin-right: 5px;
        }

        .ce-submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--ce-primary) 0%, var(--ce-primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--ce-transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
        }

        .ce-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(18, 140, 126, 0.3);
        }

        .ce-submit-btn:active {
            transform: translateY(0);
        }

        .ce-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .ce-submit-btn i {
            font-size: 1.2rem;
        }

        /* Loading Overlay */
        .ce-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--ce-overlay-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            display: none;
        }

        .ce-loading-content {
            background: var(--ce-loading-card);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--ce-shadow-lg);
            transition: background 0.3s ease;
        }

        .ce-loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--ce-spinner-border);
            border-top: 5px solid var(--ce-primary);
            border-radius: 50%;
            animation: ceSpin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes ceSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .ce-loading-text {
            font-weight: 500;
            color: var(--ce-primary-dark);
            transition: color 0.3s ease;
        }

        /* Search input */
        .ce-search-input {
            width: 100%;
            padding: 0.6rem 1rem;
            border: none;
            border-bottom: 1px solid var(--ce-input-border);
            font-size: 0.9rem;
            background: transparent;
            color: var(--ce-text-dark);
            font-family: 'Poppins', sans-serif;
        }

        .ce-search-input:focus {
            outline: none;
            border-bottom-color: var(--ce-accent);
        }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode .ce-page {
            --ce-body-bg: linear-gradient(135deg, #0B141A 0%, #121E25 100%);
            --ce-card-bg: #1F2C33;
            --ce-text-dark: #E9EDEF;
            --ce-text-secondary: #8696A0;
            --ce-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --ce-shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.4);
            --ce-input-bg: #2A3942;
            --ce-input-border: #374248;
            --ce-input-focus-bg: #2A3942;
            --ce-file-bg: #2A3942;
            --ce-file-hover: #374248;
            --ce-preview-bg: #2A3942;
            --ce-overlay-bg: rgba(0, 0, 0, 0.7);
            --ce-spinner-border: #2A3942;
            --ce-loading-card: #1F2C33;
        }

        body.dark-mode .ce-form-group label {
            color: var(--ce-secondary);
        }

        body.dark-mode .ce-form-control {
            color: var(--ce-text-dark);
        }

        body.dark-mode .ce-form-control::placeholder {
            color: #8696A0;
        }

        body.dark-mode .ce-file-name {
            color: #8696A0;
        }

        body.dark-mode .ce-loading-text {
            color: var(--ce-text-dark);
        }

        body.dark-mode .ce-msg-success {
            background-color: rgba(76, 175, 80, 0.15);
        }

        body.dark-mode .ce-msg-error {
            background-color: rgba(244, 67, 54, 0.15);
        }

        body.dark-mode .ce-contact-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        body.dark-mode .ce-contact-item.selected {
            background: rgba(18, 140, 126, 0.2);
        }

        body.dark-mode .ce-search-input {
            color: var(--ce-text-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ce-container {
                margin: 1rem;
                border-radius: 12px;
            }

            .ce-header {
                padding: 1.2rem 1.5rem;
            }

            .ce-header h1 {
                font-size: 1.5rem;
            }

            .ce-form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <!-- including the floating sidebar -->
    <?php include '../../includes/cd_hamburger.php'; ?>
    
    <!-- Loading Overlay -->
    <div class="ce-loading-overlay" id="ceLoadingOverlay">
        <div class="ce-loading-content">
            <div class="ce-loading-spinner"></div>
            <div class="ce-loading-text">Sending your message...</div>
        </div>
    </div>

    <div class="ce-page">
        <div class="ce-container">
            <div class="ce-header">
                <h1><i class="fas fa-paper-plane"></i> New Message</h1>
                <a href="contacts" class="ce-back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>

            <div class="ce-form-container">
                <?php if (!empty($message)): ?>
                    <div id="ceMessage" class="ce-message ce-msg-<?php echo htmlspecialchars($messageType); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="ceMessageForm">
                    <!-- Recipient Selection -->
                    <div class="ce-form-group">
                        <label for="ceRecipientSearch">Recipient</label>
                        <input type="text" 
                               id="ceRecipientSearch" 
                               class="ce-form-control ce-search-input" 
                               placeholder="Search contacts..." 
                               autocomplete="off"
                               value="<?php echo $selected_recipient ? htmlspecialchars($selected_recipient['fullname'] ?? $selected_recipient['username']) : ''; ?>">
                        <input type="hidden" name="recipient_id" id="ceRecipientId" value="<?php echo $selected_recipient['id'] ?? ''; ?>">
                        <div class="ce-contact-list" id="ceContactList">
                            <?php foreach ($contacts as $contact): ?>
                                <div class="ce-contact-item <?php echo ($selected_recipient && $selected_recipient['id'] == $contact['id']) ? 'selected' : ''; ?>" 
                                     data-id="<?php echo $contact['id']; ?>"
                                     data-name="<?php echo htmlspecialchars($contact['fullname'] ?? $contact['username']); ?>">
                                    <div class="ce-contact-avatar">
                                        <?php if (!empty($contact['profile_photo']) && $contact['profile_photo'] !== 'user.png'): ?>
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($contact['profile_photo']); ?>" alt="<?php echo htmlspecialchars($contact['fullname']); ?>">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($contact['fullname'] ?? $contact['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ce-contact-info">
                                        <div class="ce-contact-name"><?php echo htmlspecialchars($contact['fullname'] ?? $contact['username']); ?></div>
                                        <div class="ce-contact-status">
                                            <?php if ($contact['is_online']): ?>
                                                <span class="ce-online-dot"></span> Online
                                            <?php else: ?>
                                                <?php echo !empty($contact['status_message']) ? htmlspecialchars($contact['status_message']) : 'Offline'; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div class="ce-form-group">
                        <label for="ceMessageText">Message</label>
                        <textarea name="message" id="ceMessageText" class="ce-form-control" rows="6" placeholder="Write your message here..." required></textarea>
                    </div>

                    <!-- Attachment -->
                    <div class="ce-form-group">
                        <label>Attachment (optional)</label>
                        <input type="file" name="attachment" id="ceAttachment" class="ce-file-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx,.zip">
                        <label for="ceAttachment" class="ce-file-label">
                            <i class="fas fa-paperclip"></i>
                            <span class="ce-file-name">Choose a file...</span>
                        </label>
                        <div class="ce-file-preview" id="ceFilePreview"></div>
                    </div>

                    <button type="submit" name="submit" class="ce-submit-btn" id="ceSubmitBtn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- including the floating navbar -->
    <?php include '../../includes/navbar.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show message if exists
            var messageType = "<?php echo htmlspecialchars($messageType); ?>";
            var messageText = "<?php echo htmlspecialchars($message); ?>";

            if (messageType && messageText) {
                var messageDiv = document.getElementById('ceMessage');
                messageDiv.className = 'ce-message ce-msg-' + messageType;
                messageDiv.textContent = messageText;
                messageDiv.style.display = 'block';

                setTimeout(function() {
                    messageDiv.style.display = 'none';
                }, messageType === 'success' ? 10000 : 8000);
            }

            // Contact selection
            var contactItems = document.querySelectorAll('.ce-contact-item');
            var recipientSearch = document.getElementById('ceRecipientSearch');
            var recipientId = document.getElementById('ceRecipientId');
            var contactList = document.getElementById('ceContactList');

            contactItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // Remove previous selection
                    contactItems.forEach(function(ci) {
                        ci.classList.remove('selected');
                    });
                    
                    // Add selection
                    this.classList.add('selected');
                    
                    // Update hidden input
                    recipientId.value = this.getAttribute('data-id');
                    recipientSearch.value = this.getAttribute('data-name');
                    
                    // Scroll to top of contact list
                    contactList.scrollTop = 0;
                });
            });

            // Search filter
            recipientSearch.addEventListener('input', function() {
                var searchTerm = this.value.toLowerCase();
                
                contactItems.forEach(function(item) {
                    var name = item.getAttribute('data-name').toLowerCase();
                    if (name.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // File input handling
            var fileInput = document.getElementById('ceAttachment');
            var fileNameSpan = document.querySelector('.ce-file-name');
            var filePreviewDiv = document.getElementById('ceFilePreview');

            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var file = this.files[0];
                    fileNameSpan.textContent = file.name;

                    // Check file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB.');
                        ceClearFileInput();
                        return;
                    }

                    filePreviewDiv.innerHTML = 
                        '<i class="fas fa-file"></i>' +
                        '<span>' + file.name + ' (' + ceFormatFileSize(file.size) + ')</span>' +
                        '<span class="ce-remove-file" onclick="ceClearFileInput()">' +
                            '<i class="fas fa-times"></i>' +
                        '</span>';
                    filePreviewDiv.style.display = 'flex';
                }
            });

            // Form submission validation
            var messageForm = document.getElementById('ceMessageForm');
            var loadingOverlay = document.getElementById('ceLoadingOverlay');

            messageForm.addEventListener('submit', function(e) {
                if (!recipientId.value) {
                    e.preventDefault();
                    alert('Please select a recipient.');
                    recipientSearch.focus();
                    return false;
                }

                var messageText = document.getElementById('ceMessageText').value.trim();
                var file = fileInput.files[0];

                if (!messageText && !file) {
                    e.preventDefault();
                    alert('Please enter a message or attach a file.');
                    return false;
                }

                // Show loading overlay
                loadingOverlay.style.display = 'flex';
                document.getElementById('ceSubmitBtn').disabled = true;
            });
        });

        function ceFormatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function ceClearFileInput() {
            var fileInput = document.getElementById('ceAttachment');
            var fileNameSpan = document.querySelector('.ce-file-name');
            var filePreviewDiv = document.getElementById('ceFilePreview');

            fileInput.value = '';
            fileNameSpan.textContent = 'Choose a file...';
            filePreviewDiv.style.display = 'none';
        }
    </script>
</body>

</html>