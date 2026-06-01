<script>
    const modal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");
    const closeBtn = document.querySelector(".close-btn");

    function openImagePreview(img) {
        modalImage.src = img.getAttribute("data-full");
        modal.style.display = "flex";
    }

    closeBtn.onclick = () => modal.style.display = "none";
    modal.onclick = (e) => e.target === modal && (modal.style.display = "none");

    const searchBtn = document.getElementById('searchButton');
    const searchBox = document.getElementById('searchContainer');
    const searchInput = document.getElementById('searchInput');

    searchBtn.onclick = () => {
        const visible = searchBox.style.display !== 'none';
        searchBox.style.display = visible ? 'none' : 'block';
        if (!visible) searchInput.focus();
    };

    searchInput.oninput = () => {
        const term = searchInput.value.toLowerCase();
        document.querySelectorAll('.contact-item').forEach(item => {
            const name = item.querySelector('.contact-name').textContent.toLowerCase();
            item.style.display = name.includes(term) ? 'flex' : 'none';
        });
    };

    // We'll track the currently open chat contact here:
    let currentChatContactId = null;

    function openChat(contactId) {
        currentChatContactId = contactId;
        const previews = JSON.parse(localStorage.getItem("contactPreviews") || "{}");
        if (previews[contactId]) {
            previews[contactId].unread_count = 0;
            localStorage.setItem("contactPreviews", JSON.stringify(previews));
        }
        location.href = `converse?contactId=${contactId}`;
    }

    // --- Dark Mode ---
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }

    const userId = <?= json_encode($_SESSION['user_id']) ?>;
    // const wsUrl = `ws://${location.hostname}:8086/?user_id=${userId}`;
   
    // Hosted Django WebSocket server:
    const wsUrl = `wss://callingserver-5c0z.onrender.com/ws/chat/?user_id=${userId}`;
    const notifySound = document.getElementById("notifySound");
    const toastContainer = document.getElementById("toastContainer");

    const messageInput = document.getElementById('sendButton');
    let typingTimeout;

    let socket, reconnectDelay = 1000;
    const typingTimeouts = {}, onlineUsers = new Set();
    let previewsReady = false;

    function initWebSocket() {
        socket = new WebSocket(wsUrl);

        socket.onopen = () => {
            reconnectDelay = 1000;
            socket.send(JSON.stringify({ type: "get_presence" }));
        };

        socket.onmessage = ({ data }) => {
            let payload;
            try { 
                payload = JSON.parse(data); } catch { return; }

                const type = payload.type;
            if (!type) return;

            const actions = {
                presence: () => updateContactPresence(payload.user_id, payload.status),
                initial_presence: () => {
                    onlineUsers.clear();
                    payload.online_users.forEach(id => {
                        onlineUsers.add(id);
                        updateContactPresence(id, "online");
                    });
                    updateOnlineCounter();
                },
                unread_counts: () => updateUnreadCounts(payload.data),
                contact_preview_update: () => {
                    if (!previewsReady) return;
                    updateContactPreview({
                        from: payload.from,
                        content: payload.message,
                        timestamp: payload.timestamp || new Date().toISOString(),
                        unread_count: payload.unread_count || 0,
                        direction: payload.direction || 'received',
                        message_status: payload.message_status || 'sent',
                        message_id: payload.message_id || null
                    });
                },

                message_sent: () => {
                    const m = payload.message;
                    updateContactPreview({
                        from: m.receiver_office_id,
                        content: m.message,
                        timestamp: m.timestamp,
                        unread_count: 0,
                        direction: 'sent',
                        message_status: 'sent',
                        message_id: m.file_id || null
                    });
                },

                message: () => {
                    const m = payload.message;
                    if (!m || m.receiver_office_id != userId) return;

                    const from = m.sender_office_id;
                    const count = (getStoredUnreadCount(from) || 0) + 1;

                    updateContactPreview({
                        from,
                        content: m.message,
                        timestamp: m.timestamp,
                        unread_count: count,
                        direction: 'received',
                        message_status: 'sent',
                        message_id: m.message_id || null
                    });

                    notifySound.play();

                    const name = document.querySelector(`.contact-item[data-id='${from}'] .contact-name`)?.textContent.trim() || "Unknown";
                    showToast(`New message from ${name}`, () => openChat(from));
                },

                typing: () => {
                    alert("Typing event received: " + JSON.stringify(payload));
                    if (!currentChatContactId) return;
                    const senderId = payload.from || payload.user_id;
                    if (senderId != currentChatContactId) return;

                    if (payload.is_typing) {
                        showTypingIndicator(senderId);
                    } else {
                        clearTypingIndicator(senderId);
                    }
                },

                read_receipt: () => updateMessageStatus(payload.message_id, 'read'),
                delivery_receipt: () => updateMessageStatus(payload.message_id, 'delivered'),
            };

            if (type in actions) actions[type]();
        };

        socket.onerror = (e) => {
            // alert("WebSocket error: " + e.message);
            console.error(e);
        };
        socket.onclose = () => {
            // alert("⚠️ WebSocket closed, reconnecting...");
            setTimeout(initWebSocket, reconnectDelay);
            reconnectDelay = Math.min(reconnectDelay * 2, 16000);
        };
    }

    function sendTyping(isTyping) {
        if (!socket || socket.readyState !== WebSocket.OPEN) return;
        if (!currentChatContactId) return;

        alert(`Sending typing: ${isTyping}`);
        socket.send(JSON.stringify({ type: 'typing', to: currentChatContactId, is_typing: isTyping }));
    }

    // Send typing notifications on input:
    messageInput?.addEventListener('input', () => {
        sendTyping(true);

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            sendTyping(false);
        }, 1500);
    });

    function updateMessageStatus(messageId, status) {
        const previews = JSON.parse(localStorage.getItem("contactPreviews") || "{}");
        for (const [id, p] of Object.entries(previews)) {
            if (p.direction === 'sent' && p.message_id == messageId && p.message_status !== 'read') {
                p.message_status = status;
                previews[id] = p;
                updateContactPreview({ ...p, from: id });
                break;
            }
        }
        localStorage.setItem("contactPreviews", JSON.stringify(previews));
    }

    function updateContactPresence(id, status) {
        const item = document.querySelector(`.contact-item[data-id='${id}']`);
        if (!item) return;

        const avatar = item.querySelector(".contact-avatar");
        const onlineDot = avatar.querySelector(".online-status");
        const onlineText = item.querySelector(".online-text");

        if (status === "online") {
            if (!onlineDot) {
                const dot = document.createElement("span");
                dot.classList.add("online-status");
                avatar.appendChild(dot);
            }
            if (id != userId) onlineText.style.display = "block";
            onlineUsers.add(id);
        } else {
            onlineDot?.remove();
            onlineText.style.display = "none";
            onlineUsers.delete(id);
        }

        updateOnlineCounter();
    }

    function updateOnlineCounter() {
        const el = document.getElementById('onlineCounter');
        const count = [...onlineUsers].filter(id => id != userId).length;
        el.textContent = count ? `${count} online` : '';
    }

    function updateUnreadCounts(data) {
        data.forEach(entry => {
            const item = document.querySelector(`.contact-item[data-id='${entry.sender_id}']`);
            if (!item) return;
            let badge = item.querySelector(".unread-badge");
            if (!badge) {
                badge = document.createElement("div");
                badge.classList.add("unread-badge");
                item.querySelector(".contact-meta").appendChild(badge);
            }
            badge.textContent = entry.unread_count;
            badge.style.display = entry.unread_count > 0 ? "flex" : "none";
        });
    }

    function updateContactPreview(data) {
        const item = document.querySelector(`.contact-item[data-id='${data.from}']`);
        if (!item) return;

        const msgEl = item.querySelector('.contact-last-message');
        const timeEl = item.querySelector('.contact-time');
        const meta = item.querySelector('.contact-meta');

        if (msgEl && data.content !== undefined) {
            const dir = data.direction;
            msgEl.textContent = '';

            if (dir === 'sent') {
                const span = document.createElement('span');
                span.classList.add('message-tick');
                span.textContent = data.message_status === 'read' ? '✓✓' : data.message_status === 'delivered' ? '✓✓' : '✓';
                span.style.color = data.message_status === 'read' ? '#2ea532' : 'gray';
                msgEl.appendChild(span);
                msgEl.appendChild(document.createTextNode(` You: ${data.content}`));
            } else {
                msgEl.textContent = data.content;
            }

            if (data.unread_count > 0) msgEl.classList.add('unread-message');
            else msgEl.classList.remove('unread-message');
        }

        if (timeEl && data.timestamp) {
            timeEl.textContent = formatTime(data.timestamp);
        }

        let badge = meta.querySelector('.unread-badge');
        if (!badge) {
            badge = document.createElement('div');
            badge.classList.add('unread-badge');
            meta.appendChild(badge);
        }

        badge.textContent = data.unread_count;
        badge.style.display = data.unread_count ? 'inline-block' : 'none';

        // Save
        const previews = JSON.parse(localStorage.getItem("contactPreviews") || "{}");
        previews[data.from] = {
            content: data.content,
            timestamp: data.timestamp,
            unread_count: data.unread_count,
            direction: data.direction,
            message_status: data.message_status || 'sent',
            message_id: data.message_id || null
        };
        localStorage.setItem("contactPreviews", JSON.stringify(previews));
    }

    function getStoredUnreadCount(id) {
        const p = JSON.parse(localStorage.getItem("contactPreviews") || "{}")[id];
        return p ? parseInt(p.unread_count) : 0;
    }

    function showTypingIndicator(senderId) {
        const item = document.querySelector(`.contact-item[data-id='${senderId}']`);
        const el = item?.querySelector('.contact-last-message');
        if (!el) return;

        if (!el.dataset.originalText) el.dataset.originalText = el.textContent;
        el.innerHTML = '<span class="typing-indicator">typing...</span>';

        clearTimeout(typingTimeouts[senderId]);
        typingTimeouts[senderId] = setTimeout(() => clearTypingIndicator(senderId), 1000);
    }

    function clearTypingIndicator(senderId) {
        const item = document.querySelector(`.contact-item[data-id='${senderId}']`);
        const el = item?.querySelector('.contact-last-message');
        if (!el || !el.dataset.originalText) return;

        el.textContent = el.dataset.originalText;
        delete el.dataset.originalText;
    }

    function formatTime(ts) {
        const d = new Date(ts);
        const now = new Date();
        const mins = Math.floor((now - d) / 60000);

        if (mins < 1) return "Just now";
        if (mins < 10) return `${mins} min${mins > 1 ? 's' : ''} ago`;

        if (d.toDateString() === now.toDateString())
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

        const yest = new Date(); yest.setDate(now.getDate() - 1);
        if (d.toDateString() === yest.toDateString()) return "Yesterday";

        return d.toLocaleDateString(undefined, { weekday: 'long' });
    }

    function showToast(msg, onClick = null) {
        const toast = document.createElement("div");
        toast.className = "toast";
        toast.textContent = msg;
        if (onClick) toast.onclick = () => { onClick(); toast.remove(); };
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideUpFadeOut 0.4s forwards';
            toast.addEventListener('animationend', () => toast.remove());
        }, 4000);
    }

    function restorePreviews() {
        const previews = JSON.parse(localStorage.getItem("contactPreviews") || "{}");
        Object.entries(previews).forEach(([id, preview]) => {
            updateContactPreview({ ...preview, from: id });
        });
        previewsReady = true;
    }

    window.addEventListener("DOMContentLoaded", () => {
        restorePreviews();
        initWebSocket();
    });
</script>
