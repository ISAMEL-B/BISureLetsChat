<?php
/**
 * BUSure Chat - Group Converse (Chat) Page
 * ✅ 1 tick until ALL members read, then 2 blue ticks
 */

date_default_timezone_set('Africa/Kampala');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login');
    exit();
}

require_once __DIR__ . '/../config/db.php';

$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) { header('Location: my_groups'); exit(); }

// Get group info
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
$memberRes = $stmt->get_result();
$is_member = $memberRes->num_rows > 0;
$stmt->close();
if (!$is_member) { header('Location: my_groups'); exit(); }

// Get members
$stmt = $conn->prepare("
    SELECT u.id, u.fullname, u.username, u.profile_photo, gm.role
    FROM group_members gm JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ? ORDER BY gm.role='admin' DESC, u.fullname ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$membersRes = $stmt->get_result();
$group_members = [];
while ($row = $membersRes->fetch_assoc()) { $group_members[] = $row; }
$stmt->close();

// Get messages with read status (ALL members must read for blue ticks)
$stmt = $conn->prepare("
    SELECT m.id, m.conversation_id, m.sender_id, m.message_type,
           m.message_text, m.attachment_path, m.created_at,
           u.fullname AS sender_name, u.username AS sender_username, u.profile_photo AS sender_photo,
           (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id = m.id) AS read_count,
           (SELECT COUNT(*) FROM group_members WHERE group_id = ?) - 1 AS other_members_count
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.conversation_id = ? AND m.is_deleted = 0
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $group_id, $group['conversation_id']);
$stmt->execute();
$msgRes = $stmt->get_result();
$group_messages = [];
while ($row = $msgRes->fetch_assoc()) { $group_messages[] = $row; }
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($group['group_name']) ?> | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --whatsapp-green: #128C7E; --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366; --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --text-light: #ffffff; --text-dark: #303030; --text-secondary: #667781;
            --sent-bg: #d9fdd3; --received-bg: #ffffff; --card-bg: #ffffff;
            --input-bg: #ffffff; --input-border: #e0e0e0;
            --shadow: 0 1px 3px rgba(0,0,0,.08); --shadow-lg: 0 10px 30px rgba(0,0,0,.15);
            --transition: all 0.2s ease;
        }
        body.dark-mode {
            --whatsapp-chat-bg: #0B141A; --text-dark: #E9EDEF; --text-secondary: #8696A0;
            --sent-bg: #005c4b; --received-bg: #202C33; --card-bg: #1F2C33;
            --input-bg: #2A3942; --input-border: #2A3942;
            --shadow: 0 1px 3px rgba(0,0,0,.3); --shadow-lg: 0 10px 30px rgba(0,0,0,.4);
            background: #0B141A;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Roboto',sans-serif; -webkit-tap-highlight-color:transparent; }
        body { background:var(--whatsapp-chat-bg); color:var(--text-dark); height:100vh; overflow:hidden; transition:background .3s,color .3s; }
        .main-wrapper { display:flex; flex-direction:column; width:100%; max-width:500px; margin:0 auto; background:var(--card-bg); height:100vh; box-shadow:var(--shadow-lg); position:relative; transition:background .3s; }
        
        header { background:var(--pro-gradient); padding:.7rem 1rem; color:var(--text-light); display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,.15); flex-shrink:0; z-index:100; }
        .header-left { display:flex; align-items:center; gap:.7rem; min-width:0; flex:1; }
        .back-button { color:var(--text-light); font-size:1.2rem; text-decoration:none; transition:var(--transition); flex-shrink:0; }
        .back-button:hover { transform:translateX(-3px); }
        .group-info { display:flex; align-items:center; gap:.6rem; min-width:0; cursor:pointer; }
        .group-avatar { width:38px; height:38px; border-radius:50%; overflow:hidden; background:var(--whatsapp-light-green); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:1.1rem; flex-shrink:0; }
        .group-avatar img { width:100%; height:100%; object-fit:cover; }
        .group-details { display:flex; flex-direction:column; min-width:0; }
        .group-name { font-weight:500; font-size:1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .group-members { font-size:.7rem; opacity:.85; }
        .header-actions { display:flex; gap:.8rem; flex-shrink:0; }
        .header-button { background:none; border:none; color:var(--text-light); font-size:1.1rem; cursor:pointer; transition:var(--transition); padding:4px; }
        .header-button:hover { transform:scale(1.1); }
        
        .chat-container { flex:1; display:flex; flex-direction:column; overflow:hidden; min-height:0; }
        .messages-container { flex:1; padding:.8rem 1rem; overflow-y:auto; background:var(--whatsapp-chat-bg); display:flex; flex-direction:column; gap:.2rem; transition:background .3s; -webkit-overflow-scrolling:touch; }
        .messages-container::-webkit-scrollbar { width:5px; }
        .messages-container::-webkit-scrollbar-track { background:transparent; }
        .messages-container::-webkit-scrollbar-thumb { background:rgba(0,0,0,.15); border-radius:10px; }
        
        .message { display:flex; flex-direction:column; max-width:75%; word-wrap:break-word; }
        .message.sent { align-self:flex-end; align-items:flex-end; }
        .message.received { align-self:flex-start; align-items:flex-start; }
        .message-content { padding:.45rem .65rem; border-radius:8px; position:relative; word-wrap:break-word; line-height:1.45; font-size:.92rem; }
        .message.sent .message-content { background:var(--sent-bg); color:var(--text-dark); border-top-right-radius:2px; }
        .message.received .message-content { background:var(--received-bg); color:var(--text-dark); border-top-left-radius:2px; box-shadow:var(--shadow); }
        .message-sender { font-size:.73rem; font-weight:600; margin-bottom:2px; padding:0 8px; color:#128C7E; }
        body.dark-mode .message-sender { color:#25D366; }
        .message-time { font-size:.63rem; margin-top:3px; text-align:right; opacity:.7; color:#667781; white-space:nowrap; }
        body.dark-mode .message-time { color:#8696A0; }
        
        .message-tick { font-size:.65rem; margin-left:3px; vertical-align:bottom; }
        .tick-sent { color:#b0b0b0; }
        .tick-delivered { color:#b0b0b0; }
        .tick-read { color:#34B7F1; }
        body.dark-mode .tick-sent, body.dark-mode .tick-delivered { color:#8696A0; }
        body.dark-mode .tick-read { color:#53bdeb; }
        .tick-error { color:#f44336 !important; }
        
        .message-input-container { padding:.5rem .7rem; background:var(--card-bg); display:flex; align-items:flex-end; gap:.5rem; border-top:1px solid var(--input-border); flex-shrink:0; transition:background .3s,border-color .3s; }
        .message-input { flex:1; padding:.55rem .9rem; border:1px solid var(--input-border); border-radius:24px; font-size:.95rem; resize:none; max-height:100px; outline:none; background:var(--input-bg); color:var(--text-dark); font-family:'Roboto',sans-serif; line-height:1.4; transition:all .3s; }
        .message-input:focus { border-color:var(--whatsapp-light-green); box-shadow:0 0 0 2px rgba(37,211,102,.15); }
        .message-input::placeholder { color:#999; }
        .send-button { width:42px; height:42px; border-radius:50%; background:var(--whatsapp-light-green); color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:var(--transition); flex-shrink:0; }
        .send-button:hover { background:var(--whatsapp-dark-green); transform:scale(1.05); }
        .send-button:active { transform:scale(.95); }
        .attachment-button { background:none; border:none; color:var(--text-secondary); font-size:1.2rem; cursor:pointer; transition:var(--transition); padding:8px; flex-shrink:0; }
        .attachment-button:hover { color:var(--whatsapp-dark-green); }
        
        .empty-chat { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:2rem; color:var(--text-secondary); }
        .empty-chat i { font-size:3.5rem; margin-bottom:1rem; color:var(--whatsapp-dark-green); opacity:.4; }
        .empty-chat h3 { font-size:1.1rem; margin-bottom:.4rem; color:var(--text-dark); }
        .empty-chat p { font-size:.85rem; }
        
        @media (max-width:480px) {
            header { padding:.6rem .8rem; }
            .group-avatar { width:34px; height:34px; font-size:1rem; }
            .group-name { font-size:.95rem; }
            .header-actions { gap:.5rem; }
            .header-button { font-size:1rem; }
            .messages-container { padding:.5rem .7rem; }
            .message { max-width:85%; }
            .message-content { padding:.4rem .55rem; font-size:.88rem; }
            .message-input-container { padding:.4rem .5rem; }
            .message-input { padding:.5rem .8rem; font-size:.9rem; }
            .send-button { width:38px; height:38px; }
        }
        @media (max-width:360px) { .message { max-width:90%; } }
    </style>
</head>
<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
<div class="main-wrapper">
    <header>
        <div class="header-left">
        <?php require_once __DIR__ . '/../includes/cd_hamburger.php';?>
            <!-- <a href="my_groups" class="back-button"><i class="fas fa-arrow-left"></i></a> -->
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
                    <div class="group-members"><?= count($group_members) ?> members</div>
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
            <?php if (empty($group_messages)): ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <h3>No messages yet</h3>
                    <p>Send a message to start the conversation</p>
                </div>
            <?php else: ?>
                <?php foreach ($group_messages as $message): 
                    $isSent = $message['sender_id'] == $current_user_id;
                    $totalOthers = (int)($message['other_members_count'] ?? 0);
                    $readCount = (int)($message['read_count'] ?? 0);
                    $allRead = ($totalOthers > 0 && $readCount >= $totalOthers);
                ?>
                    <div class="message <?= $isSent ? 'sent' : 'received' ?>" data-msg-id="<?= $message['id'] ?>">
                        <?php if (!$isSent): ?>
                            <div class="message-sender"><?= htmlspecialchars($message['sender_name'] ?? $message['sender_username']) ?></div>
                        <?php endif; ?>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($message['message_text'])) ?>
                            <div class="message-time">
                                <?= date('h:i A', strtotime($message['created_at'])) ?>
                                <?php if ($isSent): ?>
                                    <?php if ($allRead): ?>
                                        <span class="message-tick tick-read" title="Read by all">✓✓</span>
                                    <?php else: ?>
                                        <span class="message-tick tick-delivered" title="<?= $readCount ?>/<?= $totalOthers ?> read">✓</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="message-input-container">
            <button class="attachment-button" id="attachmentButton" title="Attach"><i class="fas fa-paperclip"></i></button>
            <textarea class="message-input" id="messageInput" placeholder="Type a message..." rows="1"></textarea>
            <button class="send-button" id="sendButton" title="Send"><i class="fas fa-paper-plane"></i></button>
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
    const totalMembers = <?= count($group_members) ?>;
    const otherMembersCount = totalMembers - 1; // exclude sender
    
    function scrollToBottom() { messagesContainer.scrollTop = messagesContainer.scrollHeight; }
    function adjustTextareaHeight() { messageInput.style.height = 'auto'; messageInput.style.height = Math.min(messageInput.scrollHeight, 100) + 'px'; }
    messageInput.addEventListener('input', adjustTextareaHeight);
    
    function escapeHTML(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    
    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;
        
        const tempId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2,5);
        
        const el = document.createElement('div');
        el.className = 'message sent';
        el.id = tempId;
        // Start with 1 grey tick
        el.innerHTML = `<div class="message-content">${escapeHTML(message)}<div class="message-time">Just now <span class="message-tick tick-delivered" title="0/${otherMembersCount} read">✓</span></div></div>`;
        
        const emptyChat = document.querySelector('.empty-chat');
        if (emptyChat) emptyChat.remove();
        messagesContainer.appendChild(el);
        scrollToBottom();
        messageInput.value = ''; messageInput.style.height = 'auto';
        
        const fd = new FormData();
        fd.append('conversation_id', conversationId);
        fd.append('message', message);
        
        fetch('content/send_group_message.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            const msgEl = document.getElementById(tempId);
            if (!msgEl) return;
            const tickEl = msgEl.querySelector('.message-tick');
            if (data.success) {
                // Keep single tick until all read
                tickEl.className = 'message-tick tick-delivered';
                tickEl.innerHTML = '✓';
                tickEl.title = '0/' + otherMembersCount + ' read';
                if (data.message_id) msgEl.setAttribute('data-msg-id', data.message_id);
            } else {
                tickEl.className = 'message-tick tick-error';
                tickEl.innerHTML = '⚠';
                tickEl.title = data.message || 'Failed to send';
            }
        })
        .catch(() => {
            const msgEl = document.getElementById(tempId);
            if (msgEl) {
                const tickEl = msgEl.querySelector('.message-tick');
                tickEl.className = 'message-tick tick-error';
                tickEl.innerHTML = '⚠';
            }
        });
    }
    
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    
    document.getElementById('groupInfoButton').addEventListener('click', () => {
        window.location.href = 'group_info?group_id=' + groupId;
    });
    document.getElementById('callButton').addEventListener('click', () => alert('Group voice call coming soon!'));
    document.getElementById('videoCallButton').addEventListener('click', () => alert('Group video call coming soon!'));
    
    scrollToBottom();
    
    // WebSocket
    const wsUrl = `wss://callingserver-5c0z.onrender.com/ws/group_chat/?group_id=${groupId}&user_id=${currentUserId}`;
    let socket;
    
    function initWebSocket() {
        try {
            socket = new WebSocket(wsUrl);
            socket.onopen = () => console.log('WS connected');
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                if (data.type === 'new_message') {
                    const msg = data.message;
                    const isSent = msg.sender_id == currentUserId;
                    const el = document.createElement('div');
                    el.className = `message ${isSent ? 'sent' : 'received'}`;
                    if (msg.id) el.setAttribute('data-msg-id', msg.id);
                    const timeStr = new Date(msg.timestamp).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
                    
                    if (!isSent) {
                        el.innerHTML = `<div class="message-sender">${escapeHTML(msg.sender_name||'')}</div><div class="message-content">${escapeHTML(msg.message)}<div class="message-time">${timeStr}</div></div>`;
                    } else {
                        el.innerHTML = `<div class="message-content">${escapeHTML(msg.message)}<div class="message-time">${timeStr} <span class="message-tick tick-delivered" title="0/${otherMembersCount} read">✓</span></div></div>`;
                    }
                    
                    const emptyChat = document.querySelector('.empty-chat');
                    if (emptyChat) emptyChat.remove();
                    messagesContainer.appendChild(el);
                    scrollToBottom();
                }
                // Read receipt update
                if (data.type === 'message_read') {
                    const el = document.querySelector(`[data-msg-id="${data.message_id}"]`);
                    if (el) {
                        const tick = el.querySelector('.message-tick');
                        if (tick) {
                            if (data.all_read) {
                                tick.className = 'message-tick tick-read';
                                tick.innerHTML = '✓✓';
                                tick.title = 'Read by all';
                            } else {
                                tick.className = 'message-tick tick-delivered';
                                tick.innerHTML = '✓';
                                tick.title = (data.read_count||0) + '/' + (data.total_members||otherMembersCount) + ' read';
                            }
                        }
                    }
                }
            };
            socket.onclose = () => { console.log('WS closed'); setTimeout(initWebSocket, 3000); };
            socket.onerror = (err) => console.error('WS error', err);
        } catch(e) { console.log('WS unavailable'); }
    }
    initWebSocket();
});
</script>
</body>
</html>