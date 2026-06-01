<?php
/**
 * BUSure Chat - Group Converse (Chat) Page
 * ✅ Updated to match busure_lets_chat schema and BUSureLetsChat structure
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// ✅ FIXED: Updated paths to match BUSureLetsChat structure
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Get group ID from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) {
    header('Location: my_groups');
    exit();
}

// ✅ FIXED: Get group information using correct schema (groups_chat + users)
$stmt = $conn->prepare("
    SELECT 
        g.id AS group_id,
        g.conversation_id,
        g.group_name,
        g.group_photo,
        g.description,
        g.created_by,
        g.created_at,
        u.fullname AS creator_name,
        u.username AS creator_username
    FROM groups_chat g 
    LEFT JOIN users u ON g.created_by = u.id 
    WHERE g.id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();
$group = $group_result->fetch_assoc();
$stmt->close();

if (!$group) {
    header('Location: my_groups');
    exit();
}

// Check if user is a member of this group
$current_user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $current_user_id);
$stmt->execute();
$membership_result = $stmt->get_result();
$is_member = $membership_result->num_rows > 0;
$stmt->close();

if (!$is_member) {
    header('Location: my_groups');
    exit();
}

// ✅ FIXED: Get group members using correct schema
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.fullname,
        u.username,
        u.profile_photo,
        gm.role
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ? 
    ORDER BY gm.role = 'admin' DESC, u.fullname ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$group_members = [];
while ($row = $members_result->fetch_assoc()) {
    $group_members[] = $row;
}
$stmt->close();

// ✅ FIXED: Get group messages using correct schema (messages table via conversation_id)
$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.conversation_id,
        m.sender_id,
        m.message_type,
        m.message_text,
        m.attachment_path,
        m.is_deleted,
        m.created_at,
        u.fullname AS sender_name,
        u.username AS sender_username,
        u.profile_photo AS sender_photo
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.conversation_id = ? 
      AND m.is_deleted = 0
    ORDER BY m.created_at ASC
");
$stmt->bind_param("i", $group['conversation_id']);
$stmt->execute();
$messages_result = $stmt->get_result();
$group_messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $group_messages[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($group['group_name']) ?> | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --whatsapp-green: #128C7E;
            --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366;
            --whatsapp-teal-green: #34B7F1;
            --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --pro-gradient-hover: linear-gradient(135deg, #0da792 0%, #064b43 100%);
            --light: #f8f9fa;
            --dark: #212529;
            --text-light: #ffffff;
            --text-dark: #495057;
            --text-secondary: #6c757d;
            --accent: #25D366;
            --success: #25D366;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --radius: 12px;
            --card-bg: #ffffff;
            --pro-badge: #FFD700;
            --call-primary: #25D366;
            --call-secondary: #128C7E;
            --call-bg: #f0f8f5;
            --decline: #f44336;
            --decline-hover: #e53935;
            --input-bg: #ffffff;
            --input-border: #ddd;
            --message-received-bg: #ffffff;
            --message-sent-text: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--whatsapp-chat-bg);
            color: var(--text-dark);
            height: 100vh;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: white;
            height: 100vh;
            box-shadow: var(--shadow-lg);
            position: relative;
            transition: background 0.3s ease;
        }

        header {
            background: var(--pro-gradient);
            padding: 1.2rem 1.5rem;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            flex-shrink: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .back-button {
            color: var(--text-light);
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .back-button:hover {
            transform: translateX(-3px);
        }

        .group-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
        }

        .group-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--whatsapp-light-green);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .group-details {
            display: flex;
            flex-direction: column;
        }

        .group-name {
            font-weight: 500;
            font-size: 1.1rem;
        }

        .group-members {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .header-actions {
            display: flex;
            gap: 1.2rem;
        }

        .header-button {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .header-button:hover {
            transform: scale(1.1);
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }

        .messages-container {
            flex: 1;
            padding: 1rem;
            padding-bottom: 1rem;
            overflow-y: auto;
            background: var(--whatsapp-chat-bg);
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23d4d4d4' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E");
            transition: background 0.3s ease;
            -webkit-overflow-scrolling: touch;
        }

        .messages-container::-webkit-scrollbar {
            width: 5px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 10px;
        }

        .message {
            max-width: 70%;
            margin-bottom: 1rem;
        }

        .message.sent {
            align-self: flex-end;
            margin-left: auto;
            margin-right: 0;
        }

        .message.received {
            align-self: flex-start;
            margin-left: 0;
            margin-right: auto;
        }

        .message-content {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            position: relative;
            word-wrap: break-word;
            display: inline-block;
            width: 100%;
        }

        .message.sent .message-content {
            background: var(--whatsapp-light-green);
            color: var(--message-sent-text);
            border-top-right-radius: 0;
            text-align: left;
        }

        .message.received .message-content {
            background: var(--message-received-bg);
            color: var(--text-dark);
            border-top-left-radius: 0;
            box-shadow: var(--shadow);
            text-align: left;
        }

        .message-sender {
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.3rem;
            color: var(--text-secondary);
        }

        .message-time {
            font-size: 0.7rem;
            margin-top: 0.3rem;
            opacity: 0.8;
            text-align: right;
        }

        .message-input-container {
            padding: 0.8rem 1rem;
            background: white;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-top: 1px solid #f0f0f0;
            transition: background 0.3s ease, border-color 0.3s ease;
            flex-shrink: 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .message-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 25px;
            font-size: 1rem;
            resize: none;
            max-height: 120px;
            outline: none;
            background: var(--input-bg);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .message-input:focus {
            border-color: var(--whatsapp-light-green);
        }

        .send-button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--whatsapp-light-green);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .send-button:hover {
            background: var(--whatsapp-dark-green);
            transform: scale(1.05);
        }

        .attachment-button {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .attachment-button:hover {
            color: var(--whatsapp-dark-green);
        }

        .empty-chat {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--whatsapp-dark-green);
            opacity: 0.5;
        }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode {
            --whatsapp-chat-bg: #0B141A;
            --card-bg: #1F2C33;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --input-bg: #2A3942;
            --input-border: #2A3942;
            --message-received-bg: #202C33;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.4);
            background: #0B141A;
            color: var(--text-dark);
        }

        body.dark-mode .main-wrapper {
            background: var(--card-bg);
        }

        body.dark-mode .messages-container {
            background: #0B141A;
            background-image: none;
        }

        body.dark-mode .messages-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .message-input-container {
            background: var(--card-bg);
            border-top-color: #2A3942;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode .message-input {
            background: var(--input-bg);
            color: var(--text-dark);
            border-color: #2A3942;
        }

        body.dark-mode .message-input::placeholder {
            color: #8696A0;
        }

        body.dark-mode .message.received .message-content {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .attachment-button:hover {
            color: var(--whatsapp-light-green);
        }

        body.dark-mode .empty-chat i {
            opacity: 0.6;
        }

        body.dark-mode .empty-chat h3 {
            color: var(--text-dark);
        }

        @media (max-width: 480px) {
            header {
                padding: 1rem;
            }

            .group-avatar {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .group-name {
                font-size: 1rem;
            }

            .messages-container {
                padding: 0.8rem;
            }

            .message {
                max-width: 85%;
            }

            .message-input-container {
                padding: 0.7rem 0.8rem;
            }

            .send-button {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <div class="main-wrapper">
        <header>
            <div class="header-left">
                <?php if (file_exists(__DIR__ . '/cd_hamburger.php')) {
                    include __DIR__ . '/cd_hamburger.php';
                } ?>
                <!-- ✅ FIXED: Updated back link -->
                <a href="my_groups" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <!-- ✅ FIXED: Updated group info link -->
                <div class="group-info" onclick="window.location.href='group_info?group_id=<?= $group_id ?>'">
                    <div class="group-avatar">
                        <?php if (!empty($group['group_photo'])): ?>
                            <img src="<?= htmlspecialchars('../../uploads/images/' . $group['group_photo']) ?>" alt="Group image">
                        <?php else: ?>
                            <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="group-details">
                        <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                        <div class="group-members"><?= count($group_members) ?> members</div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="header-button" id="callButton" title="Voice Call">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="header-button" id="videoCallButton" title="Video Call">
                    <i class="fas fa-video"></i>
                </button>
                <button class="header-button" id="groupInfoButton" title="Group Info">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </header>

        <div class="chat-container">
            <div class="messages-container" id="messagesContainer">
                <?php if (empty($group_messages)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <h3>No messages yet</h3>
                        <p>Send a message to start the conversation</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($group_messages as $message): ?>
                        <div class="message <?= $message['sender_id'] == $current_user_id ? 'sent' : 'received' ?>">
                            <div class="message-content">
                                <?php if ($message['sender_id'] != $current_user_id): ?>
                                    <!-- ✅ FIXED: Uses fullname -->
                                    <div class="message-sender"><?= htmlspecialchars($message['sender_name'] ?? $message['sender_username']) ?></div>
                                <?php endif; ?>
                                <!-- ✅ FIXED: Uses message_text -->
                                <?= htmlspecialchars($message['message_text']) ?>
                                <div class="message-time">
                                    <!-- ✅ FIXED: Uses created_at -->
                                    <?= date('h:i A', strtotime($message['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="message-input-container">
            <button class="attachment-button" id="attachmentButton" title="Attach">
                <i class="fas fa-paperclip"></i>
            </button>
            <textarea class="message-input" id="messageInput" placeholder="Type a message..." rows="1"></textarea>
            <button class="send-button" id="sendButton" title="Send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var messagesContainer = document.getElementById('messagesContainer');
            var messageInput = document.getElementById('messageInput');
            var sendButton = document.getElementById('sendButton');
            var groupId = <?= $group_id ?>;
            var currentUserId = <?= $current_user_id ?>;
            var conversationId = <?= $group['conversation_id'] ?>;
            
            function scrollToBottom() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            function adjustTextareaHeight() {
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
            }
            
            messageInput.addEventListener('input', adjustTextareaHeight);
            
            function sendMessage() {
                var message = messageInput.value.trim();
                if (!message) return;
                
                var messageElement = document.createElement('div');
                messageElement.className = 'message sent';
                messageElement.innerHTML = 
                    '<div class="message-content">' +
                        message.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                        '<div class="message-time">Just now</div>' +
                    '</div>';
                
                var emptyChat = document.querySelector('.empty-chat');
                if (emptyChat) {
                    emptyChat.remove();
                }
                
                messagesContainer.appendChild(messageElement);
                scrollToBottom();
                
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                var formData = new FormData();
                formData.append('conversation_id', conversationId);
                formData.append('message', message);
                
                // ✅ FIXED: Updated fetch URL to match structure
                fetch('content/send_group_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data.success) {
                        console.error('Failed to send message:', data.message);
                    }
                })
                .catch(function(error) {
                    console.error('Error sending message:', error);
                });
            }
            
            sendButton.addEventListener('click', sendMessage);
            
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // ✅ FIXED: Updated group info link
            document.getElementById('groupInfoButton').addEventListener('click', function() {
                window.location.href = 'group_info?group_id=' + groupId;
            });
            
            document.getElementById('callButton').addEventListener('click', function() {
                alert('Group voice call feature coming soon!');
            });
            
            document.getElementById('videoCallButton').addEventListener('click', function() {
                alert('Group video call feature coming soon!');
            });
            
            scrollToBottom();
        });
    </script>
</body>

</html>