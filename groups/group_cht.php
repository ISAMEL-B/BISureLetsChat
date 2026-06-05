<?php
/**
 * BUSure Chat - Group Converse (Chat) Page
 * ✅ Updated to match busure_lets_chat schema
 * ✅ Fixed dark mode, message alignment, ticks
 * ✅ Fixed input/send button visibility and responsiveness
 * ✅ Added "Read more" for long messages (50 words limit)
 */

date_default_timezone_set('Africa/Kampala');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/register');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Get group ID from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) { header('Location: my_groups'); exit(); }

// ✅ Get group info from groups_chat + users
$stmt = $conn->prepare("
    SELECT g.id AS group_id, g.conversation_id, g.group_name, g.group_photo,
           g.description, g.created_by, g.created_at,
           u.fullname AS creator_name, u.username AS creator_username
    FROM groups_chat g LEFT JOIN users u ON g.created_by = u.id WHERE g.id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$group) { header('Location: my_groups'); exit(); }

// Check membership
$current_user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $current_user_id);
$stmt->execute();
$is_member = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$is_member) { header('Location: my_groups'); exit(); }

// ✅ Get members
$stmt = $conn->prepare("
    SELECT u.id, u.fullname, u.username, u.profile_photo, gm.role
    FROM group_members gm JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ? ORDER BY gm.role='admin' DESC, u.fullname ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Get messages via conversation_id
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.message_type, m.message_text, m.created_at,
           u.fullname AS sender_name, u.username AS sender_username,
           (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) AS read_count,
           (SELECT COUNT(*) FROM group_members WHERE group_id = ?) - 1 AS other_members_count
    FROM messages m JOIN users u ON m.sender_id = u.id 
    WHERE m.conversation_id = ? AND m.is_deleted = 0
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $group_id, $group['conversation_id']);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$otherMembersCount = count($members) - 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($group['group_name']) ?> | BisureChat</title>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>
    
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --whatsapp-green: #128C7E; 
            --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366; 
            --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --text-light: #ffffff; 
            --text-dark: #303030; 
            --text-secondary: #667781;
            --sent-bg: #d9fdd3; 
            --received-bg: #ffffff; 
            --card-bg: #ffffff;
            --input-bg: #ffffff; 
            --input-border: #e0e0e0;
            --shadow: 0 1px 3px rgba(0,0,0,.08); 
            --shadow-lg: 0 10px 30px rgba(0,0,0,.15);
            --transition: all 0.2s ease;
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
            --read-more-color: #128C7E;
        }
        body.dark-mode {
            --whatsapp-chat-bg: #0B141A; 
            --text-dark: #E9EDEF; 
            --text-secondary: #8696A0;
            --sent-bg: #005c4b; 
            --received-bg: #202C33; 
            --card-bg: #1F2C33;
            --input-bg: #2A3942; 
            --input-border: #2A3942;
            --shadow: 0 1px 3px rgba(0,0,0,.3); 
            --shadow-lg: 0 10px 30px rgba(0,0,0,.4);
            --read-more-color: #25D366;
            background: #0B141A;
        }
        * { 
            margin:0; 
            padding:0; 
            box-sizing:border-box; 
            font-family:'Roboto',sans-serif; 
            -webkit-tap-highlight-color:transparent; 
        }
        body { 
            background: var(--whatsapp-chat-bg); 
            color: var(--text-dark); 
            height: 100vh; 
            height: 100dvh;
            overflow: hidden; 
            transition: background .3s, color .3s; 
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
        }
        .main-wrapper { 
            display: flex; 
            flex-direction: column; 
            width: 100%; 
            max-width: 500px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            height: 100vh;
            height: 100dvh;
            box-shadow: var(--shadow-lg); 
            position: relative; 
            transition: background .3s; 
        }
        
        header { 
            background: var(--pro-gradient); 
            padding: 0.7rem 1rem; 
            color: var(--text-light); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,.15); 
            flex-shrink: 0; 
            z-index: 100; 
        }
        .header-left { 
            display: flex; 
            align-items: center; 
            gap: 0.7rem; 
            min-width: 0; 
            flex: 1; 
        }
        .back-button { 
            color: var(--text-light); 
            font-size: 1.2rem; 
            text-decoration: none; 
            transition: var(--transition); 
            flex-shrink: 0; 
        }
        .group-info { 
            display: flex; 
            align-items: center; 
            gap: 0.6rem; 
            min-width: 0; 
            cursor: pointer; 
        }
        .group-avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            overflow: hidden; 
            background: var(--whatsapp-light-green); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #fff; 
            font-weight: bold; 
            font-size: 1.1rem; 
            flex-shrink: 0; 
        }
        .group-avatar img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        .group-details { 
            display: flex; 
            flex-direction: column; 
            min-width: 0; 
        }
        .group-name { 
            font-weight: 500; 
            font-size: 1rem; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        .group-members { 
            font-size: 0.7rem; 
            opacity: 0.85; 
        }
        .header-actions { 
            display: flex; 
            gap: 0.8rem; 
            flex-shrink: 0; 
        }
        .header-button { 
            background: none; 
            border: none; 
            color: var(--text-light); 
            font-size: 1.1rem; 
            cursor: pointer; 
            transition: var(--transition); 
            padding: 4px; 
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
            padding: 0.8rem 1rem; 
            overflow-y: auto; 
            background: var(--whatsapp-chat-bg); 
            display: flex; 
            flex-direction: column; 
            gap: 0.2rem; 
            transition: background .3s; 
            -webkit-overflow-scrolling: touch; 
            min-height: 0;
        }
        .messages-container::-webkit-scrollbar { 
            width: 5px; 
        }
        .messages-container::-webkit-scrollbar-thumb { 
            background: rgba(0,0,0,.15); 
            border-radius: 10px; 
        }
        
        .message { 
            display: flex; 
            flex-direction: column; 
            max-width: 75%; 
            word-wrap: break-word; 
        }
        .message.sent { 
            align-self: flex-end; 
            align-items: flex-end; 
        }
        .message.received { 
            align-self: flex-start; 
            align-items: flex-start; 
        }
        .message-content { 
            padding: 0.45rem 0.65rem; 
            border-radius: 8px; 
            position: relative; 
            word-wrap: break-word; 
            line-height: 1.45; 
            font-size: 0.92rem; 
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .message.sent .message-content { 
            background: var(--sent-bg); 
            color: var(--text-dark); 
            border-top-right-radius: 2px; 
        }
        .message.received .message-content { 
            background: var(--received-bg); 
            color: var(--text-dark); 
            border-top-left-radius: 2px; 
            box-shadow: var(--shadow); 
        }
        .message-sender { 
            font-size: 0.73rem; 
            font-weight: 600; 
            margin-bottom: 2px; 
            padding: 0 8px; 
            color: #128C7E; 
        }
        body.dark-mode .message-sender { 
            color: #25D366; 
        }
        .message-time { 
            font-size: 0.63rem; 
            margin-top: 3px; 
            text-align: right; 
            opacity: 0.7; 
            color: #667781; 
            white-space: nowrap; 
        }
        body.dark-mode .message-time { 
            color: #8696A0; 
        }
        
        .message-tick { 
            font-size: 0.65rem; 
            margin-left: 3px; 
            vertical-align: bottom; 
        }
        .tick-delivered { 
            color: #b0b0b0; 
        }
        .tick-read { 
            color: #34B7F1; 
        }
        body.dark-mode .tick-delivered { 
            color: #8696A0; 
        }
        body.dark-mode .tick-read { 
            color: #53bdeb; 
        }
        
        /* Read More styles */
        .message-text-container {
            position: relative;
        }
        .message-text {
            display: inline;
        }
        .message-text.truncated {
            display: inline;
        }
        .message-text.full {
            display: none;
        }
        .message-text.full.show {
            display: inline;
        }
        .message-text.truncated.hide {
            display: none;
        }
        .read-more-btn {
            display: inline;
            color: var(--read-more-color);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0 2px;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
        }
        .read-more-btn:hover {
            text-decoration: underline;
            opacity: 0.8;
        }
        .read-more-btn:active {
            transform: scale(0.95);
        }
        body.dark-mode .read-more-btn {
            color: var(--read-more-color);
        }
        
        /* Fixed input container */
        .message-input-container { 
            padding: 0.5rem 0.7rem; 
            padding-bottom: calc(0.5rem + var(--safe-area-bottom));
            background: var(--card-bg); 
            display: flex; 
            align-items: flex-end; 
            gap: 0.5rem; 
            border-top: 1px solid var(--input-border); 
            flex-shrink: 0; 
            z-index: 50;
            transition: background .3s, border-color .3s; 
        }
        .message-input { 
            flex: 1; 
            padding: 0.55rem 0.9rem; 
            border: 1px solid var(--input-border); 
            border-radius: 24px; 
            font-size: 0.95rem; 
            resize: none; 
            max-height: 100px; 
            min-height: 38px;
            outline: none; 
            background: var(--input-bg); 
            color: var(--text-dark); 
            font-family: 'Roboto', sans-serif; 
            line-height: 1.4; 
            transition: all .3s; 
            -webkit-appearance: none;
        }
        .message-input:focus { 
            border-color: var(--whatsapp-light-green); 
            box-shadow: 0 0 0 2px rgba(37,211,102,.15); 
        }
        .message-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }
        .send-button { 
            width: 42px; 
            height: 42px; 
            min-width: 42px;
            border-radius: 50%; 
            background: var(--whatsapp-light-green); 
            color: #fff; 
            border: none; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: var(--transition); 
            flex-shrink: 0; 
            font-size: 1rem;
        }
        .send-button:active { 
            transform: scale(0.95);
            background: var(--whatsapp-dark-green); 
        }
        .attachment-button { 
            background: none; 
            border: none; 
            color: var(--text-secondary); 
            font-size: 1.2rem; 
            cursor: pointer; 
            transition: var(--transition); 
            padding: 8px; 
            flex-shrink: 0; 
        }
        .attachment-button:active {
            color: var(--whatsapp-green);
        }
        
        .empty-chat { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            text-align: center; 
            padding: 2rem; 
            color: var(--text-secondary); 
        }
        .empty-chat i { 
            font-size: 3.5rem; 
            margin-bottom: 1rem; 
            color: var(--whatsapp-dark-green); 
            opacity: 0.4; 
        }
        
        /* Mobile responsive */
        @media (max-width: 480px) {
            header { 
                padding: 0.6rem 0.8rem; 
            }
            .group-avatar { 
                width: 34px; 
                height: 34px; 
                font-size: 1rem; 
            }
            .messages-container { 
                padding: 0.5rem 0.7rem; 
            }
            .message { 
                max-width: 85%; 
            }
            .message-content { 
                padding: 0.4rem 0.55rem; 
                font-size: 0.88rem; 
            }
            .message-input-container {
                padding: 0.4rem 0.5rem;
                padding-bottom: calc(0.4rem + var(--safe-area-bottom));
                gap: 0.4rem;
            }
            .send-button { 
                width: 38px; 
                height: 38px; 
                min-width: 38px;
            }
            .message-input {
                padding: 0.45rem 0.7rem;
                font-size: 0.9rem;
                min-height: 34px;
            }
            .read-more-btn {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 380px) {
            .message-input-container {
                padding: 0.3rem 0.4rem;
                padding-bottom: calc(0.3rem + var(--safe-area-bottom));
                gap: 0.3rem;
            }
            .send-button { 
                width: 34px; 
                height: 34px; 
                min-width: 34px;
                font-size: 0.9rem;
            }
            .message-input {
                font-size: 0.85rem;
                min-height: 32px;
                padding: 0.4rem 0.6rem;
            }
        }
    </style>
</head>
<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
<div class="main-wrapper">
    <header>
        <div class="header-left">
            <a href="my_groups" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <div class="group-info" onclick="window.location.href='group_info?group_id=<?= $group_id ?>'">
                <div class="group-avatar">
                    <?php if (!empty($group['group_photo'])): ?>
                        <img src="<?= htmlspecialchars('../../uploads/images/' . $group['group_photo']) ?>" alt="Group">
                    <?php else: ?>
                        <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="group-details">
                    <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                    <div class="group-members"><?= count($members) ?> members</div>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <button class="header-button" id="callButton" title="Voice Call"><i class="fas fa-phone"></i></button>
            <button class="header-button" id="videoCallButton" title="Video Call"><i class="fas fa-video"></i></button>
            <button class="header-button" id="groupInfoButton" title="Group Info"><i class="fas fa-info-circle"></i></button>
        </div>
    </header>

    <div class="chat-container">
        <div class="messages-container" id="messagesContainer">
            <?php if (empty($messages)): ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <h3>No messages yet</h3>
                    <p>Send a message to start the conversation</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): 
                    $isSent = $msg['sender_id'] == $current_user_id;
                    $readCount = (int)($msg['read_count'] ?? 0);
                    $allRead = ($otherMembersCount > 0 && $readCount >= $otherMembersCount);
                    $messageText = htmlspecialchars($msg['message_text']);
                    $wordCount = str_word_count($msg['message_text']);
                    $needsTruncation = $wordCount > 50;
                ?>
                    <div class="message <?= $isSent ? 'sent' : 'received' ?>" data-msg-id="<?= $msg['id'] ?>">
                        <?php if (!$isSent): ?>
                            <div class="message-sender"><?= htmlspecialchars($msg['sender_name'] ?? $msg['sender_username']) ?></div>
                        <?php endif; ?>
                        <div class="message-content">
                            <div class="message-text-container">
                                <?php if ($needsTruncation): 
                                    // Get first 50 words
                                    $words = explode(' ', $msg['message_text']);
                                    $truncatedText = implode(' ', array_slice($words, 0, 50));
                                ?>
                                    <span class="message-text truncated"><?= nl2br(htmlspecialchars($truncatedText)) ?>... </span>
                                    <span class="message-text full" style="display:none;"><?= nl2br($messageText) ?> </span>
                                    <button class="read-more-btn" onclick="toggleReadMore(this)">Read more</button>
                                <?php else: ?>
                                    <span class="message-text full show"><?= nl2br($messageText) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                <?php if ($isSent): ?>
                                    <?php if ($allRead): ?>
                                        <span class="message-tick tick-read" title="Read by all">✓✓</span>
                                    <?php else: ?>
                                        <span class="message-tick tick-delivered" title="<?= $readCount ?>/<?= $otherMembersCount ?> read">✓</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Message Input -->
        <div class="message-input-container">
            <button class="attachment-button" id="attachmentButton" title="Attach">
                <i class="fas fa-paperclip"></i>
            </button>
            <textarea 
                class="message-input" 
                id="messageInput" 
                placeholder="Type a message..." 
                rows="1"
                autocomplete="off"
            ></textarea>
            <button class="send-button" id="sendButton" title="Send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const groupId = <?= $group_id ?>;
    const conversationId = <?= $group['conversation_id'] ?>;
    const currentUserId = <?= $current_user_id ?>;
    const otherMembersCount = <?= $otherMembersCount ?>;
    
    // Read more toggle function
    window.toggleReadMore = function(button) {
        const container = button.parentElement;
        const truncated = container.querySelector('.message-text.truncated');
        const full = container.querySelector('.message-text.full');
        
        if (full.style.display === 'none' || !full.style.display) {
            // Show full text
            full.style.display = 'inline';
            truncated.style.display = 'none';
            button.textContent = 'Show less';
        } else {
            // Show truncated text
            full.style.display = 'none';
            truncated.style.display = 'inline';
            button.textContent = 'Read more';
        }
        
        // Scroll to keep the message visible
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    };
    
    function scrollToBottom() { 
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight; 
        }
    }
    
    function adjustHeight() { 
        if (messageInput) {
            messageInput.style.height = 'auto'; 
            messageInput.style.height = Math.min(messageInput.scrollHeight, 100) + 'px'; 
        }
    }
    
    if (messageInput) {
        messageInput.addEventListener('input', adjustHeight);
    }
    
    function escapeHTML(s) { 
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
    
    function truncateMessage(text, maxWords = 50) {
        const words = text.split(/\s+/);
        if (words.length <= maxWords) {
            return { truncated: false, text: text, truncatedText: text };
        }
        const truncatedText = words.slice(0, maxWords).join(' ');
        return { truncated: true, text: text, truncatedText: truncatedText };
    }
    
    function createMessageElement(messageText, isSent, timestamp, messageId, senderName) {
        const el = document.createElement('div');
        el.className = 'message ' + (isSent ? 'sent' : 'received');
        if (messageId) el.setAttribute('data-msg-id', messageId);
        
        const time = timestamp || new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        const truncation = truncateMessage(messageText);
        
        let messageHTML = '';
        
        if (!isSent && senderName) {
            messageHTML += '<div class="message-sender">' + escapeHTML(senderName) + '</div>';
        }
        
        messageHTML += '<div class="message-content">';
        messageHTML += '<div class="message-text-container">';
        
        if (truncation.truncated) {
            messageHTML += '<span class="message-text truncated">' + escapeHTML(truncation.truncatedText).replace(/\n/g, '<br>') + '... </span>';
            messageHTML += '<span class="message-text full" style="display:none;">' + escapeHTML(truncation.text).replace(/\n/g, '<br>') + ' </span>';
            messageHTML += '<button class="read-more-btn" onclick="toggleReadMore(this)">Read more</button>';
        } else {
            messageHTML += '<span class="message-text full show">' + escapeHTML(truncation.text).replace(/\n/g, '<br>') + '</span>';
        }
        
        messageHTML += '</div>';
        messageHTML += '<div class="message-time">' + time;
        
        if (isSent) {
            messageHTML += ' <span class="message-tick tick-delivered">✓</span>';
        }
        
        messageHTML += '</div></div>';
        
        el.innerHTML = messageHTML;
        return el;
    }
    
    function sendMessage() {
        if (!messageInput) return;
        
        const msg = messageInput.value.trim();
        if (!msg) return;
        
        const tempId = 'msg_' + Date.now();
        const el = createMessageElement(msg, true, 'Just now', tempId);
        el.id = tempId;
        
        const empty = document.querySelector('.empty-chat'); 
        if (empty) empty.remove();
        
        messagesContainer.appendChild(el); 
        scrollToBottom();
        messageInput.value = ''; 
        adjustHeight();
        
        const fd = new FormData();
        fd.append('conversation_id', conversationId);
        fd.append('message', msg);
        
        fetch('content/send_group_message.php', { 
            method: 'POST', 
            body: fd 
        })
        .then(r => r.json())
        .then(d => {
            const mel = document.getElementById(tempId);
            if (mel && d.success) {
                const tick = mel.querySelector('.message-tick');
                if (tick) {
                    tick.className = 'message-tick tick-delivered';
                    tick.title = '0/' + otherMembersCount + ' read';
                }
                if (d.message_id) mel.setAttribute('data-msg-id', d.message_id);
            }
        })
        .catch(err => {
            console.error('Send error:', err);
        });
    }
    
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    if (messageInput) {
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendMessage(); 
            }
        });
    }
    
    const groupInfoButton = document.getElementById('groupInfoButton');
    if (groupInfoButton) {
        groupInfoButton.addEventListener('click', () => {
            window.location.href = 'group_info?group_id=' + groupId;
        });
    }
    
    const callButton = document.getElementById('callButton');
    if (callButton) {
        callButton.addEventListener('click', () => alert('Group voice call coming soon!'));
    }
    
    const videoCallButton = document.getElementById('videoCallButton');
    if (videoCallButton) {
        videoCallButton.addEventListener('click', () => alert('Group video call coming soon!'));
    }
    
    scrollToBottom();
    
    setTimeout(() => {
        if (messageInput) messageInput.focus();
    }, 500);
    
    // WebSocket for real-time updates
    const wsUrl = 'wss://callingserver-5c0z.onrender.com/ws/group_chat/?group_id=' + groupId + '&user_id=' + currentUserId;
    let socket;
    
    function initWS() {
        try {
            socket = new WebSocket(wsUrl);
            socket.onopen = () => console.log('WebSocket connected');
            socket.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'new_message') {
                        const m = data.message; 
                        const isSent = m.sender_id == currentUserId;
                        const time = new Date(m.timestamp).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                        const senderName = !isSent ? (m.sender_name || '') : null;
                        
                        const el = createMessageElement(m.message, isSent, time, m.id, senderName);
                        
                        const empty = document.querySelector('.empty-chat'); 
                        if (empty) empty.remove();
                        messagesContainer.appendChild(el); 
                        scrollToBottom();
                    }
                    if (data.type === 'message_read') {
                        const el = document.querySelector('[data-msg-id="' + data.message_id + '"]');
                        if (el) {
                            const tick = el.querySelector('.message-tick');
                            if (tick) {
                                tick.className = data.all_read ? 'message-tick tick-read' : 'message-tick tick-delivered';
                                tick.innerHTML = data.all_read ? '✓✓' : '✓';
                                tick.title = data.all_read ? 'Read by all' : (data.read_count || 0) + '/' + (data.total_members || otherMembersCount) + ' read';
                            }
                        }
                    }
                } catch (e) {
                    console.error('WebSocket message parse error:', e);
                }
            };
            socket.onerror = (error) => console.error('WebSocket error:', error);
            socket.onclose = () => { 
                console.log('WebSocket disconnected, reconnecting...');
                setTimeout(initWS, 3000); 
            };
        } catch(e) {
            console.error('WebSocket init error:', e);
            setTimeout(initWS, 5000);
        }
    }
    
    initWS();
    
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', () => {
            scrollToBottom();
        });
    }
});
</script>
</body>
</html>