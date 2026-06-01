<?php
// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../register/register.php');
    exit();
}

include '../../../register/config/db.php';

// Get group ID from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) {
    header('Location: contacts.php');
    exit();
}

// Get group information
$stmt = $conn->prepare("
    SELECT g.*, o.username as creator_name 
    FROM groups g 
    LEFT JOIN offices o ON g.created_by = o.office_id 
    WHERE g.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();
$group = $group_result->fetch_assoc();
$stmt->close();

if (!$group) {
    header('Location: contacts.php');
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
    header('Location: contacts.php');
    exit();
}

// Get group members
$stmt = $conn->prepare("
    SELECT o.office_id, o.username, o.profile_picture, gm.is_admin 
    FROM group_members gm 
    JOIN offices o ON gm.user_id = o.office_id 
    WHERE gm.group_id = ? 
    ORDER BY gm.is_admin DESC, o.username ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$group_members = [];
while ($row = $members_result->fetch_assoc()) {
    $group_members[] = $row;
}
$stmt->close();

// Get group messages
$stmt = $conn->prepare("
    SELECT gm.*, o.username as sender_name, o.profile_picture as sender_image 
    FROM group_messages gm 
    JOIN offices o ON gm.sender_id = o.office_id 
    WHERE gm.group_id = ? 
    ORDER BY gm.sent_at ASC
");
$stmt->bind_param("i", $group_id);
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
    <link href="css/group_chat.css" rel="stylesheet">

</head>

<body>
    <div class="main-wrapper">
        <header>
            <div class="header-left">
                <a href="groups.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="group-info">
                    <div class="group-avatar">
                        <?php if (!empty($group['group_image'])): ?>
                            <img src="<?= htmlspecialchars($group['group_image']) ?>" alt="Group image">
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
                <button class="header-button" id="callButton">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="header-button" id="videoCallButton">
                    <i class="fas fa-video"></i>
                </button>
                <button class="header-button" id="groupInfoButton">
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
                            <?php if ($message['sender_id'] != $current_user_id): ?>
                                <div class="message-sender"><?= htmlspecialchars($message['sender_name']) ?></div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?= htmlspecialchars($message['message']) ?>
                                <div class="message-time">
                                    <?= date('h:i A', strtotime($message['timestamp'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="message-input-container">
                <button class="attachment-button" id="attachmentButton">
                    <i class="fas fa-paperclip"></i>
                </button>
                <textarea class="message-input" id="messageInput" placeholder="Type a message..." rows="1"></textarea>
                <button class="send-button" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messagesContainer');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const groupId = <?= $group_id ?>;
            const currentUserId = <?= $current_user_id ?>;
            
            // Auto-scroll to bottom of messages
            function scrollToBottom() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Adjust textarea height based on content
            function adjustTextareaHeight() {
                messageInput.style.height = 'auto';
                messageInput.style.height = (messageInput.scrollHeight) + 'px';
            }
            
            messageInput.addEventListener('input', adjustTextareaHeight);
            
            // Send message function
            function sendMessage() {
                const message = messageInput.value.trim();
                if (!message) return;
                
                // Create message element immediately for better UX
                const messageElement = document.createElement('div');
                messageElement.className = 'message sent';
                messageElement.innerHTML = `
                    <div class="message-content">
                        ${message}
                        <div class="message-time">Just now</div>
                    </div>
                `;
                
                // Remove empty state if it exists
                const emptyChat = document.querySelector('.empty-chat');
                if (emptyChat) {
                    emptyChat.remove();
                }
                
                messagesContainer.appendChild(messageElement);
                scrollToBottom();
                
                // Clear input and reset height
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Send message to server via AJAX
                const formData = new FormData();
                formData.append('group_id', groupId);
                formData.append('message', message);
                
                fetch('send_group_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Handle error - maybe show a notification
                        console.error('Failed to send message:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                });
            }
            
            // Send message on button click
            sendButton.addEventListener('click', sendMessage);
            
            // Send message on Enter key (but allow Shift+Enter for new line)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Group info button
            document.getElementById('groupInfoButton').addEventListener('click', function() {
                window.location.href = 'group_info.php?group_id=' + groupId;
            });
            
            // Call buttons (placeholder functionality)
            document.getElementById('callButton').addEventListener('click', function() {
                alert('Group voice call feature coming soon!');
            });
            
            document.getElementById('videoCallButton').addEventListener('click', function() {
                alert('Group video call feature coming soon!');
            });
            
            // Initial scroll to bottom
            scrollToBottom();
            
            // WebSocket connection for real-time messaging
            const wsUrl = `wss://callingserver-5c0z.onrender.com/ws/group_chat/?group_id=${groupId}&user_id=${currentUserId}`;
            let socket;
            
            function initWebSocket() {
                socket = new WebSocket(wsUrl);
                
                socket.onopen = function() {
                    console.log('WebSocket connection established');
                };
                
                socket.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'new_message') {
                        // Add new message to chat
                        const message = data.message;
                        const isSent = message.sender_id == currentUserId;
                        
                        const messageElement = document.createElement('div');
                        messageElement.className = `message ${isSent ? 'sent' : 'received'}`;
                        
                        if (!isSent) {
                            messageElement.innerHTML = `
                                <div class="message-sender">${message.sender_name}</div>
                                <div class="message-content">
                                    ${message.message}
                                    <div class="message-time">${new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            `;
                        } else {
                            messageElement.innerHTML = `
                                <div class="message-content">
                                    ${message.message}
                                    <div class="message-time">${new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            `;
                        }
                        
                        // Remove empty state if it exists
                        const emptyChat = document.querySelector('.empty-chat');
                        if (emptyChat) {
                            emptyChat.remove();
                        }
                        
                        messagesContainer.appendChild(messageElement);
                        scrollToBottom();
                    }
                };
                
                socket.onclose = function() {
                    console.log('WebSocket connection closed, attempting to reconnect...');
                    setTimeout(initWebSocket, 3000);
                };
                
                socket.onerror = function(error) {
                    console.error('WebSocket error:', error);
                };
            }
            
            // Initialize WebSocket connection
            initWebSocket();
        });
    </script>
</body>

</html>