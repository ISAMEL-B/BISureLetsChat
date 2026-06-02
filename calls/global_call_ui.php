<!-- includes/global_call_ui.php -->
<style>
/* Floating call notification bar - visible when receiving call */
#globalCallNotification {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #075E54, #128C7E);
    color: white;
    padding: 10px 20px;
    z-index: 99999;
    display: none;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    animation: slideDown 0.3s ease;
    font-family: 'Poppins', sans-serif;
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

#globalCallNotification.show {
    display: flex;
}

.global-call-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.global-call-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    overflow: hidden;
    flex-shrink: 0;
}

.global-call-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.global-call-details {
    min-width: 0;
}

.global-call-name {
    font-weight: 600;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.global-call-type {
    font-size: 0.8rem;
    opacity: 0.9;
}

.global-call-actions {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
}

.global-call-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s ease;
    color: white;
}

.global-btn-accept {
    background: #25D366;
}

.global-btn-accept:hover {
    background: #1ea84e;
    transform: scale(1.1);
}

.global-btn-decline {
    background: #E74C3C;
}

.global-btn-decline:hover {
    background: #c0392b;
    transform: scale(1.1);
}

/* Active call mini-bar (when in call but on different page) */
#globalActiveCallBar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #0B141A;
    color: white;
    padding: 8px 20px;
    z-index: 99998;
    display: none;
    align-items: center;
    gap: 10px;
    font-family: 'Poppins', sans-serif;
}

#globalActiveCallBar.show {
    display: flex;
}

.global-active-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.global-active-timer {
    font-size: 0.9rem;
    color: #25D366;
    font-weight: 600;
}

.global-active-controls {
    display: flex;
    gap: 8px;
}

.global-active-btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    background: rgba(255,255,255,0.15);
    transition: all 0.2s ease;
}

.global-active-btn:hover {
    background: rgba(255,255,255,0.3);
}

.global-active-btn.end-call {
    background: #E74C3C;
}

.global-active-btn.end-call:hover {
    background: #c0392b;
}
</style>

<!-- Global Call Notification (Incoming) -->
<div id="globalCallNotification">
    <div class="global-call-info">
        <div class="global-call-avatar" id="globalIncomingAvatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="global-call-details">
            <div class="global-call-name" id="globalIncomingName">Incoming Call</div>
            <div class="global-call-type" id="globalIncomingType">Voice Call</div>
        </div>
    </div>
    <div class="global-call-actions">
        <button class="global-call-btn global-btn-decline" id="globalBtnDecline" title="Decline">
            <i class="fas fa-times"></i>
        </button>
        <button class="global-call-btn global-btn-accept" id="globalBtnAccept" title="Accept">
            <i class="fas fa-phone"></i>
        </button>
    </div>
</div>

<!-- Global Active Call Bar (When in call but on different page) -->
<div id="globalActiveCallBar">
    <div class="global-active-info">
        <i class="fas fa-phone-alt" style="color: #25D366; font-size: 0.8rem;"></i>
        <span id="globalActiveCaller" style="font-size: 0.9rem;">On Call</span>
        <span class="global-active-timer" id="globalActiveTimer">00:00</span>
    </div>
    <div class="global-active-controls">
        <button class="global-active-btn" id="globalBtnMuteGlobal" title="Mute">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="global-active-btn end-call" id="globalBtnEndGlobal" title="End Call">
            <i class="fas fa-phone-slash"></i>
        </button>
        <button class="global-active-btn" id="globalBtnReturnToCall" title="Return to Call">
            <i class="fas fa-expand"></i>
        </button>
    </div>
</div>

<script>
// Global Call System
(function() {
    const MY_ID = '<?= $_SESSION['user_id'] ?>';
    const MY_NAME = '<?= $_SESSION['fullname'] ?? $_SESSION['username'] ?>';
    const MY_PICTURE = '<?= $user_picture ?>';
    const WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/';
    
    // Store global call state in sessionStorage for persistence across pages
    window.globalCallState = {
        isInCall: sessionStorage.getItem('globalCallActive') === 'true',
        remoteId: sessionStorage.getItem('globalCallRemoteId') || null,
        remoteName: sessionStorage.getItem('globalCallRemoteName') || null,
        remotePicture: sessionStorage.getItem('globalCallRemotePicture') || null,
        isVideo: sessionStorage.getItem('globalCallIsVideo') === 'true',
        callStartTime: parseInt(sessionStorage.getItem('globalCallStartTime')) || null,
        dbCallId: sessionStorage.getItem('globalCallDbId') || null
    };
    
    // If coming back to call page, redirect
    if (window.globalCallState.isInCall && window.location.pathname.includes('calls')) {
        // Already on call page, let the main call handler take over
        window._resumeCall = true;
    }
    
    // WebSocket connection
    let ws = null;
    let reconnectTimer = null;
    
    function connectWebSocket() {
        if (ws && ws.readyState === WebSocket.OPEN) return;
        
        ws = new WebSocket(WS_URL);
        
        ws.onopen = function() {
            console.log('Global call system connected');
            ws.send(JSON.stringify({
                type: 'register',
                id: MY_ID,
                name: MY_NAME,
                picture: MY_PICTURE
            }));
        };
        
        ws.onmessage = function(event) {
            const msg = JSON.parse(event.data);
            if (String(msg.to) !== MY_ID) return;
            
            if (msg.type === 'offer') {
                // Store incoming call info
                sessionStorage.setItem('globalIncomingCall', JSON.stringify({
                    from: msg.from,
                    fromName: msg.fromName || 'Unknown',
                    fromPicture: msg.fromPicture || '',
                    isVideo: msg.isVideo || false,
                    callId: msg.callId || null,
                    sdp: msg.sdp
                }));
                
                // Show notification
                showIncomingCall(msg);
            }
        };
        
        ws.onclose = function() {
            console.log('Global call system disconnected');
            // Reconnect after 3 seconds
            reconnectTimer = setTimeout(connectWebSocket, 3000);
        };
        
        ws.onerror = function(error) {
            console.error('WebSocket error:', error);
        };
    }
    
    function showIncomingCall(msg) {
        const notification = document.getElementById('globalCallNotification');
        const avatar = document.getElementById('globalIncomingAvatar');
        const name = document.getElementById('globalIncomingName');
        const type = document.getElementById('globalIncomingType');
        
        name.textContent = msg.fromName || 'Unknown Caller';
        type.textContent = msg.isVideo ? '📹 Video Call' : '📞 Voice Call';
        
        if (msg.fromPicture) {
            avatar.innerHTML = `<img src="${msg.fromPicture}" alt="${msg.fromName}">`;
        } else {
            avatar.innerHTML = `<i class="fas fa-user"></i>`;
        }
        
        notification.classList.add('show');
        
        // Play ringtone
        const ringtone = new Audio('/rington.mp3');
        ringtone.loop = true;
        ringtone.play().catch(() => {});
        notification.dataset.ringtone = 'playing';
        notification._ringtone = ringtone;
    }
    
    function hideIncomingCall() {
        const notification = document.getElementById('globalCallNotification');
        notification.classList.remove('show');
        
        if (notification._ringtone) {
            notification._ringtone.pause();
            notification._ringtone = null;
        }
        
        sessionStorage.removeItem('globalIncomingCall');
    }
    
    // Handle Accept
    document.getElementById('globalBtnAccept').addEventListener('click', function() {
        const callData = JSON.parse(sessionStorage.getItem('globalIncomingCall') || '{}');
        hideIncomingCall();
        
        // Redirect to calls page with call info
        const params = new URLSearchParams({
            action: 'accept',
            from: callData.from,
            fromName: callData.fromName,
            fromPicture: callData.fromPicture,
            isVideo: callData.isVideo,
            callId: callData.callId || ''
        });
        
        window.location.href = '/calls?' + params.toString();
    });
    
    // Handle Decline
    document.getElementById('globalBtnDecline').addEventListener('click', function() {
        const callData = JSON.parse(sessionStorage.getItem('globalIncomingCall') || '{}');
        hideIncomingCall();
        
        // Send decline via WebSocket
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'decline',
                from: MY_ID,
                to: callData.from,
                callId: callData.callId
            }));
        }
        
        // Log declined call via API
        if (callData.callId) {
            fetch('/content/call_handler.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'decline_call',
                    call_id: callData.callId
                })
            });
        }
        
        sessionStorage.removeItem('globalIncomingCall');
    });
    
    // End call from global bar
    document.getElementById('globalBtnEndGlobal').addEventListener('click', function() {
        if (ws && ws.readyState === WebSocket.OPEN && window.globalCallState.remoteId) {
            ws.send(JSON.stringify({
                type: 'hangup',
                from: MY_ID,
                to: window.globalCallState.remoteId,
                callId: window.globalCallState.dbCallId
            }));
        }
        
        // Log end call
        if (window.globalCallState.dbCallId) {
            const duration = window.globalCallState.callStartTime 
                ? Math.floor((Date.now() - window.globalCallState.callStartTime) / 1000) 
                : 0;
            
            fetch('/content/call_handler.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'end_call',
                    call_id: window.globalCallState.dbCallId,
                    duration: duration
                })
            });
        }
        
        clearGlobalCallState();
        document.getElementById('globalActiveCallBar').classList.remove('show');
        window.location.reload();
    });
    
    // Return to call page
    document.getElementById('globalBtnReturnToCall').addEventListener('click', function() {
        window.location.href = '/calls';
    });
    
    function clearGlobalCallState() {
        sessionStorage.removeItem('globalCallActive');
        sessionStorage.removeItem('globalCallRemoteId');
        sessionStorage.removeItem('globalCallRemoteName');
        sessionStorage.removeItem('globalCallRemotePicture');
        sessionStorage.removeItem('globalCallIsVideo');
        sessionStorage.removeItem('globalCallStartTime');
        sessionStorage.removeItem('globalCallDbId');
        
        window.globalCallState = {
            isInCall: false,
            remoteId: null,
            remoteName: null,
            remotePicture: null,
            isVideo: false,
            callStartTime: null,
            dbCallId: null
        };
    }
    
    // Show active call bar if in call
    if (window.globalCallState.isInCall) {
        document.getElementById('globalActiveCallBar').classList.add('show');
        document.getElementById('globalActiveCaller').textContent = 
            window.globalCallState.remoteName || 'On Call';
        
        // Update timer
        if (window.globalCallState.callStartTime) {
            setInterval(() => {
                const elapsed = Math.floor((Date.now() - window.globalCallState.callStartTime) / 1000);
                const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
                const secs = String(elapsed % 60).padStart(2, '0');
                document.getElementById('globalActiveTimer').textContent = `${mins}:${secs}`;
            }, 1000);
        }
    }
    
    // Check for incoming call on page load (from sessionStorage)
    const incomingCall = JSON.parse(sessionStorage.getItem('globalIncomingCall') || 'null');
    if (incomingCall) {
        showIncomingCall({
            from: incomingCall.from,
            fromName: incomingCall.fromName,
            fromPicture: incomingCall.fromPicture,
            isVideo: incomingCall.isVideo,
            callId: incomingCall.callId
        });
    }
    
    // Connect WebSocket
    connectWebSocket();
    
    // Expose functions globally
    window.globalCallSystem = {
        acceptCall: function() {
            document.getElementById('globalBtnAccept').click();
        },
        declineCall: function() {
            document.getElementById('globalBtnDecline').click();
        },
        endCall: function() {
            document.getElementById('globalBtnEndGlobal').click();
        }
    };
})();
</script>