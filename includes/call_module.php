<?php
// session_status() === PHP_SESSION_NONE && session_start();

if (empty($_SESSION['user_id'])) {
    return;
}

$isCallsPage = strpos($_SERVER['REQUEST_URI'], '/calls') !== false;
$current_user_id = $_SESSION['user_id'];
?>

<!-- CALL MODULE OVERLAYS (hidden until needed) -->
<div class="cl-call-overlay" id="clCallOverlay" style="display:none;">
    <div class="cl-video-area" id="clVideoArea">
        <video id="clRemoteVideo" class="cl-remote-video" autoplay playsinline></video>
        
        <div class="cl-audio-only-indicator" id="clAudioIndicator" style="display:none;">
            <div class="cl-audio-avatar" id="clAudioAvatar"><i class="fas fa-user"></i></div>
        </div>

        <div class="cl-local-video-pip" id="clLocalPip" style="display:none;">
            <div class="cl-pip-handle"></div>
            <video id="clLocalVideo" autoplay muted playsinline></video>
            <div class="cl-pip-avatar-placeholder" id="clPipAvatar">
                <i class="fas fa-user"></i>
            </div>
        </div>

        <div class="cl-call-info-bar">
            <div class="cl-call-name-overlay" id="clCallNameOverlay">Contact</div>
            <div class="cl-call-status-overlay" id="clCallStatusOverlay">Calling</div>
            <div class="cl-call-timer-overlay" id="clCallTimerOverlay"></div>
        </div>

        <div class="cl-call-controls-bar">
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnMute" title="Mute"><i class="fas fa-microphone"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnVideo" title="Camera"><i class="fas fa-video"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnFlip" title="Switch Camera"><i class="fas fa-sync-alt"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-end" id="clBtnEnd" title="End Call"><i class="fas fa-phone-slash"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnSpeaker" title="Speaker"><i class="fas fa-volume-up"></i></button>
        </div>
    </div>
</div>

<div class="cl-incoming-overlay" id="clIncomingOverlay" style="display:none;">
    <div class="cl-incoming-card">
        <div class="cl-incoming-avatar" id="clIncomingAvatar"><i class="fas fa-user"></i></div>
        <div class="cl-incoming-name" id="clIncomingName">Contact</div>
        <div class="cl-incoming-type" id="clIncomingType">📞 Voice Call</div>
        <div class="cl-incoming-actions">
            <div class="cl-incoming-btn-wrap">
                <button class="cl-incoming-btn cl-incoming-decline" id="clBtnDecline"><i class="fas fa-phone-slash"></i></button>
                <span class="cl-incoming-label">Decline</span>
            </div>
            <div class="cl-incoming-btn-wrap">
                <button class="cl-incoming-btn cl-incoming-accept" id="clBtnAccept"><i class="fas fa-phone"></i></button>
                <span class="cl-incoming-label">Accept</span>
            </div>
        </div>
    </div>
</div>

<div id="clToastContainer"></div>

<?php if (!$isCallsPage): ?>
<style>
.cl-call-overlay, .cl-incoming-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    width: 100vw; height: 100vh; height: 100dvh;
    z-index: 99999;
}
.cl-call-overlay {
    background: #0B141A; flex-direction: column;
}
.cl-incoming-overlay {
    background: rgba(0,0,0,0.9); align-items: center; justify-content: center;
    backdrop-filter: blur(15px);
}
.cl-call-overlay.cl-active { display: flex !important; }
.cl-incoming-overlay.cl-active { display: flex !important; }
.cl-video-area {
    flex: 1; position: relative; background: #111;
    display: flex; align-items: center; justify-content: center;
    min-height: 0; overflow: hidden;
}

.cl-remote-video {
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    background: #1a1a1a;
    transform: scaleX(-1);  /* Add this to un-mirror if it's arriving mirrored */
}

.cl-remote-video {
    width: 100%; height: 100%; object-fit: cover; background: #1a1a1a;
}
.cl-local-video-pip {
    position: absolute; top: 60px; right: 16px;
    width: 120px; height: 170px; border-radius: 16px;
    overflow: hidden; border: 2px solid rgba(255,255,255,0.6);
    background: #2a2a2a; z-index: 10;
    cursor: grab; touch-action: none; user-select: none;
    transition: opacity 0.3s ease;
}
.cl-local-video-pip:active { cursor: grabbing; }
.cl-local-video-pip.dragging { opacity: 0.85; }
.cl-local-video-pip video {
    width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);
    pointer-events: none;
}
.cl-pip-handle {
    position: absolute; top: 6px; left: 50%;
    transform: translateX(-50%); width: 30px; height: 4px;
    background: rgba(255,255,255,0.6); border-radius: 2px; z-index: 2;
    pointer-events: none;
}
.cl-pip-avatar-placeholder {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    display: none; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #128C7E, #25D366);
    border-radius: 16px;
}
.cl-pip-avatar-placeholder.cl-show { display: flex; }
.cl-pip-avatar-placeholder i { font-size: 3rem; color: white; }
.cl-pip-avatar-placeholder img { width: 100%; height: 100%; object-fit: cover; border-radius: 14px; }
.cl-audio-only-indicator {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%); text-align: center; color: white;
}
.cl-audio-avatar {
    width: 130px; height: 130px; border-radius: 50%;
    background: linear-gradient(135deg, #128C7E, #25D366);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.2rem; font-size: 3.5rem; color: white;
    animation: clPulse 2.5s ease-in-out infinite;
}
.cl-audio-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
@keyframes clPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.5); }
    50% { box-shadow: 0 0 0 35px rgba(37, 211, 102, 0); }
}
.cl-call-info-bar {
    padding: 3.5rem 1.5rem 1rem; text-align: center; color: white;
    background: linear-gradient(to bottom, rgba(0,0,0,0.85) 0%, transparent 100%);
    position: absolute; top: 0; left: 0; right: 0; z-index: 5; pointer-events: none;
}
.cl-call-name-overlay { font-size: 1.3rem; font-weight: 600; }
.cl-call-status-overlay { font-size: 1rem; font-weight: 700; margin-top: 6px; }
.cl-call-timer-overlay {
    font-size: 1.8rem; font-weight: 300; letter-spacing: 4px;
    color: #25D366; margin-top: 8px;
}
.cl-call-controls-bar {
    padding: 1rem 1.5rem 2.5rem; display: flex; align-items: center;
    justify-content: center; gap: 1.25rem;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
    position: absolute; bottom: 0; left: 0; right: 0; z-index: 5;
}
.cl-ctrl-btn {
    width: 52px; height: 52px; border-radius: 50%; border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: white;
}
.cl-ctrl-secondary { background: rgba(255,255,255,0.15); }
.cl-ctrl-secondary.cl-active-ctrl { background: rgba(255,255,255,0.4); border: 2px solid rgba(255,255,255,0.6); }
.cl-ctrl-end {
    background: #E74C3C; width: 62px; height: 62px; font-size: 1.4rem;
}
.cl-incoming-card {
    background: #ffffff; border-radius: 28px; padding: 2.5rem 2rem;
    text-align: center; max-width: 350px; width: 90%;
}
.cl-incoming-avatar {
    width: 90px; height: 90px; border-radius: 50%;
    background: linear-gradient(135deg, #128C7E, #25D366);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem; font-size: 2.5rem; color: white; overflow: hidden;
    animation: clPulse 2s ease-in-out infinite;
}
.cl-incoming-avatar img { width: 100%; height: 100%; object-fit: cover; }
.cl-incoming-name { font-size: 1.3rem; font-weight: 600; margin-bottom: 4px; color: #2D3748; }
.cl-incoming-type { font-size: 0.9rem; color: #718096; margin-bottom: 2rem; }
.cl-incoming-actions { display: flex; justify-content: center; gap: 2rem; }
.cl-incoming-btn-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.cl-incoming-btn {
    width: 60px; height: 60px; border-radius: 50%; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: white;
}
.cl-incoming-decline { background: #E74C3C; }
.cl-incoming-accept { background: #25D366; }
.cl-incoming-label { font-size: 0.75rem; color: #718096; font-weight: 500; }
.cl-toast {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
    z-index: 999999; padding: 12px 24px; border-radius: 12px; color: white;
    font-weight: 600; font-size: 0.9rem;
}
.cl-toast-info { background: #128C7E; }
.cl-toast-error { background: #E74C3C; }

/* Responsive PIP */
@media (max-width: 480px) {
    .cl-local-video-pip {
        width: 90px; height: 130px; top: 50px; right: 10px;
    }
}
</style>
<?php endif; ?>

<script>
// Only run call module script once
if (!window._callModuleLoaded) {
window._callModuleLoaded = true;

(function() {
    var MY_ID = '<?= $current_user_id ?>';
    var MY_NAME = '<?= $_SESSION['fullname'] ?? $_SESSION['username'] ?? '' ?>';
    var WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/';
    var ICE_CONFIG = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };
    
    var basePath = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/') ?>';
    var CALLS_PATH = basePath + '/calls';
    
    var ringtone = new Audio(CALLS_PATH + '/rington.mp3');
    ringtone.loop = true;
    
    var state = {
        callState: 'idle',
        localStream: null, remoteStream: null, pc: null,
        isVideo: false, remoteId: null, remoteName: null,
        remotePicture: null, pendingOffer: null,
        timer: null, seconds: 0, isMuted: false, isCameraOff: false,
        dbCallId: null
    };
    
    var callingDotsInterval = null;
    var isRinging = false;
    
    function $(id) { return document.getElementById(id); }
    
    // ============= DRAG FUNCTIONALITY FOR PIP =============
    function makeDraggable(el) {
        if (!el) return;
        
        var isDragging = false;
        var startX, startY, startLeft, startTop;
        
        el.addEventListener('pointerdown', function(e) {
            // Only drag from the PIP itself, not the video inside
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseInt(el.style.left) || el.getBoundingClientRect().left;
            startTop = parseInt(el.style.top) || el.getBoundingClientRect().top;
            el.classList.add('dragging');
            el.setPointerCapture(e.pointerId);
            e.preventDefault();
        });
        
        el.addEventListener('pointermove', function(e) {
            if (!isDragging) return;
            
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var parent = el.parentElement.getBoundingClientRect();
            
            var newLeft = Math.min(Math.max(startLeft + dx, 0), parent.width - el.offsetWidth - 10);
            var newTop = Math.min(Math.max(startTop + dy, 0), parent.height - el.offsetHeight - 10);
            
            el.style.left = newLeft + 'px';
            el.style.top = newTop + 'px';
            el.style.right = 'auto';
        });
        
        el.addEventListener('pointerup', function() {
            isDragging = false;
            el.classList.remove('dragging');
            el.style.cursor = 'grab';
        });
        
        el.addEventListener('pointercancel', function() {
            isDragging = false;
            el.classList.remove('dragging');
            el.style.cursor = 'grab';
        });
    }
    
    function resetPipPosition() {
        var lp = $('clLocalPip');
        if (lp) {
            lp.style.left = '';
            lp.style.top = '';
            lp.style.right = '16px';
        }
    }
    
    function callAPI(action, data) {
        data = data || {};
        var fd = new FormData();
        fd.append('action', action);
        for (var key in data) fd.append(key, data[key]);
        
        return fetch(CALLS_PATH + '/content/call_handler.php', {
            method: 'POST', body: fd
        }).then(function(r) { return r.json(); })
          .then(function(res) {
              if (res.success && res.call_id) state.dbCallId = res.call_id;
              return res;
          }).catch(function(err) {
              console.error('Call API error:', err);
          });
    }
    
    function showToast(msg, type) {
        type = type || 'info';
        var t = document.createElement('div');
        t.className = 'cl-toast cl-toast-' + type;
        t.textContent = msg;
        var container = $('clToastContainer');
        if (container) {
            container.appendChild(t);
            setTimeout(function() { t.remove(); }, 3500);
        }
    }
    
    function startTimer() {
        stopTimer();
        state.seconds = 0;
        var timerOverlay = $('clCallTimerOverlay');
        var statusOverlay = $('clCallStatusOverlay');
        if (timerOverlay) timerOverlay.textContent = '00:00';
        if (statusOverlay) {
            statusOverlay.textContent = 'Connected';
            statusOverlay.style.color = '#25D366';
        }
        state.timer = setInterval(function() {
            state.seconds++;
            if (timerOverlay) {
                timerOverlay.textContent = 
                    String(Math.floor(state.seconds/60)).padStart(2,'0') + ':' + 
                    String(state.seconds%60).padStart(2,'0');
            }
        }, 1000);
    }
    
    function stopTimer() {
        clearInterval(state.timer);
        var timerOverlay = $('clCallTimerOverlay');
        if (timerOverlay) timerOverlay.textContent = '';
        var statusOverlay = $('clCallStatusOverlay');
        if (statusOverlay) statusOverlay.style.color = '';
    }
    
    function cleanupPeer() {
        if (state.pc) { 
            try { state.pc.close(); } catch(e) {}
            state.pc = null; 
        }
    }
    
    function stopStreams() {
        if (state.localStream) {
            state.localStream.getTracks().forEach(function(t) { try { t.stop(); } catch(e) {} });
            state.localStream = null;
        }
        if (state.remoteStream) {
            state.remoteStream.getTracks().forEach(function(t) { try { t.stop(); } catch(e) {} });
            state.remoteStream = null;
        }
        var rv = $('clRemoteVideo'), lv = $('clLocalVideo');
        if (rv) rv.srcObject = null;
        if (lv) lv.srcObject = null;
    }
    
    function buildPeer() {
        cleanupPeer();
        state.pc = new RTCPeerConnection(ICE_CONFIG);
        
        state.pc.onicecandidate = function(e) {
            if (e.candidate && state.remoteId && sock && sock.readyState === WebSocket.OPEN) {
                sock.send(JSON.stringify({
                    type: 'candidate', candidate: e.candidate,
                    from: MY_ID, to: state.remoteId, callId: state.dbCallId
                }));
            }
        };
        
        state.pc.ontrack = function(e) {
            console.log('Remote track received');
            state.remoteStream = e.streams[0];
            var rv = $('clRemoteVideo');
            if (rv) {
                rv.srcObject = state.remoteStream;
                rv.style.display = state.isVideo ? 'block' : 'none';
            }
            if (state.isVideo) {
                var ai = $('clAudioIndicator'), lp = $('clLocalPip');
                if (ai) ai.style.display = 'none';
                if (lp) lp.style.display = 'block';
            }
        };
        
        state.pc.onconnectionstatechange = function() {
            var s = state.pc ? state.pc.connectionState : 'closed';
            console.log('Connection state:', s);
            
            if (s === 'connected') {
                var statusOverlay = $('clCallStatusOverlay');
                if (statusOverlay) {
                    statusOverlay.textContent = 'Connected';
                    statusOverlay.style.color = '#25D366';
                }
                startTimer();
            } else if (s === 'failed' || s === 'disconnected' || s === 'closed') {
                if (state.callState === 'connected') {
                    showToast('Call disconnected', 'error');
                }
                if (state.dbCallId) {
                    callAPI('end_call', { call_id: state.dbCallId, duration: state.seconds });
                }
                fullReset();
            }
        };
    }
    
    async function requestMedia(video) {
        try {
            return await navigator.mediaDevices.getUserMedia(
                video ? { audio: true, video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } } 
                      : { audio: true }
            );
        } catch(err) {
            console.error('Media error:', err);
            showToast('Media access denied. Please allow microphone/camera.', 'error');
            return null;
        }
    }
    
    async function acceptCall() {
        ringtone.pause(); ringtone.currentTime = 0;
        isRinging = false;
        
        if (!state.pendingOffer || !state.remoteId) {
            console.log('No pending offer or remote ID');
            return;
        }
        
        console.log('Accepting call from:', state.remoteName);
        
        var io = $('clIncomingOverlay');
        if (io) io.classList.remove('cl-active');
        
        var cno = $('clCallNameOverlay');
        if (cno) cno.textContent = state.remoteName || 'Contact';
        
        var statusOverlay = $('clCallStatusOverlay');
        if (statusOverlay) {
            statusOverlay.textContent = 'Connecting...';
            statusOverlay.style.color = '#ffffff';
        }
        
        var timerOverlay = $('clCallTimerOverlay');
        if (timerOverlay) timerOverlay.textContent = '';
        
        if (state.isVideo) {
            var ai = $('clAudioIndicator'), lp = $('clLocalPip'), rv = $('clRemoteVideo');
            if (ai) ai.style.display = 'none';
            if (lp) { 
                lp.style.display = 'block'; 
                resetPipPosition();
                makeDraggable(lp);  // ✅ Enable dragging
            }
            if (rv) rv.style.display = 'block';
            state.isCameraOff = false;
            var btnVid = $('clBtnVideo');
            if (btnVid) btnVid.classList.remove('cl-active-ctrl');
        } else {
            var ai2 = $('clAudioIndicator'), lp2 = $('clLocalPip'), rv2 = $('clRemoteVideo');
            if (ai2) ai2.style.display = 'block';
            if (lp2) lp2.style.display = 'none';
            if (rv2) rv2.style.display = 'none';
            var aa = $('clAudioAvatar');
            if (aa) {
                aa.innerHTML = state.remotePicture 
                    ? '<img src="' + state.remotePicture + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
                    : '<i class="fas fa-user"></i>';
            }
        }
        
        var co = $('clCallOverlay');
        if (co) co.classList.add('cl-active');
        
        if (state.dbCallId) {
            callAPI('answer_call', { call_id: state.dbCallId });
        }
        
        state.localStream = await requestMedia(state.isVideo);
        if (!state.localStream) {
            if (state.dbCallId) {
                callAPI('decline_call', { call_id: state.dbCallId });
            }
            if (sock && sock.readyState === WebSocket.OPEN) {
                sock.send(JSON.stringify({ type: 'decline', from: MY_ID, to: state.remoteId }));
            }
            return fullReset();
        }
        
        if (state.isVideo) {
            var lv = $('clLocalVideo');
            if (lv) lv.srcObject = state.localStream;
        }
        
        buildPeer();
        state.localStream.getTracks().forEach(function(t) { 
            if (state.pc) state.pc.addTrack(t, state.localStream); 
        });
        
        try {
            await state.pc.setRemoteDescription(new RTCSessionDescription(state.pendingOffer));
            var answer = await state.pc.createAnswer();
            await state.pc.setLocalDescription(answer);
            
            if (sock && sock.readyState === WebSocket.OPEN) {
                sock.send(JSON.stringify({ 
                    type: 'answer', sdp: answer, 
                    from: MY_ID, to: state.remoteId, 
                    callId: state.dbCallId 
                }));
            }
            
            state.pendingOffer = null;
            state.callState = 'connected';
            console.log('Answer sent, waiting for connection...');
        } catch(err) {
            console.error('Error creating answer:', err);
            showToast('Connection failed', 'error');
            fullReset();
        }
    }
    
    function declineCall() {
        console.log('Declining call');
        ringtone.pause(); ringtone.currentTime = 0;
        isRinging = false;
        
        if (state.dbCallId) {
            callAPI('decline_call', { call_id: state.dbCallId });
        }
        
        if (sock && sock.readyState === WebSocket.OPEN && state.remoteId) {
            sock.send(JSON.stringify({ type: 'decline', from: MY_ID, to: state.remoteId }));
        }
        
        var io = $('clIncomingOverlay');
        if (io) io.classList.remove('cl-active');
        
        fullReset();
    }
    
    function hangup() {
        console.log('Hanging up');
        ringtone.pause(); ringtone.currentTime = 0;
        isRinging = false;
        
        if (state.dbCallId && state.callState === 'connected') {
            callAPI('end_call', { call_id: state.dbCallId, duration: state.seconds });
        } else if (state.dbCallId) {
            callAPI('missed_call', { call_id: state.dbCallId });
        }
        
        if (sock && sock.readyState === WebSocket.OPEN && state.remoteId) {
            sock.send(JSON.stringify({ type: 'hangup', from: MY_ID, to: state.remoteId }));
        }
        
        fullReset();
    }
    
    function toggleMute() {
        if (!state.localStream) return;
        state.isMuted = !state.isMuted;
        state.localStream.getAudioTracks().forEach(function(t) { t.enabled = !state.isMuted; });
        var btn = $('clBtnMute');
        if (btn) {
            if (state.isMuted) {
                btn.classList.add('cl-active-ctrl');
                btn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            } else {
                btn.classList.remove('cl-active-ctrl');
                btn.innerHTML = '<i class="fas fa-microphone"></i>';
            }
        }
    }
    
    function toggleVideo() {
        if (!state.localStream || !state.isVideo) return;
        state.isCameraOff = !state.isCameraOff;
        state.localStream.getVideoTracks().forEach(function(t) { t.enabled = !state.isCameraOff; });
        var btn = $('clBtnVideo');
        if (btn) btn.classList.toggle('cl-active-ctrl', state.isCameraOff);
        
        var pipAvatar = $('clPipAvatar');
        var localVideo = $('clLocalVideo');
        if (state.isCameraOff) {
            if (pipAvatar) pipAvatar.classList.add('cl-show');
            if (localVideo) localVideo.style.display = 'none';
        } else {
            if (pipAvatar) pipAvatar.classList.remove('cl-show');
            if (localVideo) localVideo.style.display = 'block';
        }
    }
    
    async function flipCamera() {
        if (!state.localStream || !state.isVideo) return;
        var currentTrack = state.localStream.getVideoTracks()[0];
        if (!currentTrack) return;
        var currentFacing = currentTrack.getSettings().facingMode;
        var newFacing = currentFacing === 'user' ? 'environment' : 'user';
        currentTrack.stop();
        try {
            var newStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: newFacing } });
            var newTrack = newStream.getVideoTracks()[0];
            var sender = state.pc.getSenders().find(function(s) { return s.track && s.track.kind === 'video'; });
            if (sender) await sender.replaceTrack(newTrack);
            state.localStream.removeTrack(currentTrack);
            state.localStream.addTrack(newTrack);
            var lv = $('clLocalVideo');
            if (lv) lv.srcObject = state.localStream;
        } catch(e) {
            console.error('Camera flip error:', e);
        }
    }
    
    function fullReset() {
        console.log('Full reset');
        ringtone.pause(); ringtone.currentTime = 0;
        isRinging = false;
        clearInterval(callingDotsInterval);
        cleanupPeer();
        stopStreams();
        stopTimer();
        
        state = {
            callState: 'idle', localStream: null, remoteStream: null, pc: null,
            isVideo: false, remoteId: null, remoteName: null, remotePicture: null,
            pendingOffer: null, timer: null, seconds: 0, isMuted: false, isCameraOff: false,
            dbCallId: null
        };
        
        var co = $('clCallOverlay'), io = $('clIncomingOverlay');
        if (co) co.classList.remove('cl-active');
        if (io) io.classList.remove('cl-active');
        
        var muteBtn = $('clBtnMute'), vidBtn = $('clBtnVideo'), spkBtn = $('clBtnSpeaker');
        if (muteBtn) { muteBtn.classList.remove('cl-active-ctrl'); muteBtn.innerHTML = '<i class="fas fa-microphone"></i>'; }
        if (vidBtn) vidBtn.classList.remove('cl-active-ctrl');
        if (spkBtn) spkBtn.classList.remove('cl-active-ctrl');
        
        var ai = $('clAudioIndicator'), lp = $('clLocalPip'), rv = $('clRemoteVideo');
        if (ai) ai.style.display = 'none';
        if (lp) lp.style.display = 'none';
        if (rv) rv.style.display = 'none';
        
        resetPipPosition();  // ✅ Reset PIP position
    }
    
    // ============= WEBSOCKET =============
    var sock = new WebSocket(WS_URL);
    
    sock.onopen = function() {
        console.log('Call module: WebSocket connected, registering as', MY_ID);
        sock.send(JSON.stringify({ type: 'register', id: MY_ID, name: MY_NAME }));
    };
    
    sock.onmessage = async function(ev) {
        var msg = JSON.parse(ev.data);
        if (String(msg.to) !== MY_ID) return;
        
        console.log('Call module: Received', msg.type);
        
        switch(msg.type) {
            case 'offer':
                if (isRinging || state.callState === 'connected' || state.callState === 'dialing') {
                    console.log('Ignoring duplicate offer');
                    return;
                }
                
                console.log('Incoming call from:', msg.fromName);
                isRinging = true;
                state.isVideo = !!msg.isVideo;
                state.remoteId = String(msg.from);
                state.remoteName = msg.fromName || 'User';
                state.remotePicture = msg.fromPicture || '';
                state.pendingOffer = msg.sdp;
                state.dbCallId = msg.callId || null;
                state.callState = 'ringing';
                
                var iname = $('clIncomingName');
                var itype = $('clIncomingType');
                var iavatar = $('clIncomingAvatar');
                var io = $('clIncomingOverlay');
                
                if (iname) iname.textContent = state.remoteName;
                if (itype) itype.textContent = state.isVideo ? '📹 Video Call' : '📞 Voice Call';
                if (iavatar) {
                    iavatar.innerHTML = state.remotePicture 
                        ? '<img src="' + state.remotePicture + '" style="width:100%;height:100%;object-fit:cover;">'
                        : '<i class="fas fa-user"></i>';
                }
                if (io) io.classList.add('cl-active');
                
                ringtone.currentTime = 0;
                ringtone.play().catch(function(e){ console.log('Ringtone error:', e); });
                break;
                
            case 'answer':
                if (!state.pc) return;
                try {
                    await state.pc.setRemoteDescription(new RTCSessionDescription(msg.sdp));
                    if (state.dbCallId) callAPI('answer_call', { call_id: state.dbCallId });
                    startTimer();
                } catch(e) {
                    console.error('Error setting remote description:', e);
                }
                ringtone.pause(); ringtone.currentTime = 0;
                break;
                
            case 'candidate':
                if (state.pc && msg.candidate) {
                    try {
                        await state.pc.addIceCandidate(new RTCIceCandidate(msg.candidate));
                    } catch(e) {
                        console.error('ICE candidate error:', e);
                    }
                }
                break;
                
            case 'decline':
                showToast((state.remoteName || 'User') + ' declined', 'error');
                fullReset();
                break;
                
            case 'hangup':
                showToast((state.remoteName || 'User') + ' ended call', 'error');
                fullReset();
                break;
        }
    };
    
    sock.onclose = function(e) {
        console.log('Call module: WebSocket closed, code:', e.code);
        ringtone.pause();
        if (state.callState === 'connected') {
            showToast('Connection lost', 'error');
        }
        fullReset();
    };
    
    sock.onerror = function(e) {
        console.log('Call module: WebSocket error');
    };
    
    function attachListeners() {
        var accept = $('clBtnAccept'), decline = $('clBtnDecline'), end = $('clBtnEnd');
        var mute = $('clBtnMute'), video = $('clBtnVideo'), flip = $('clBtnFlip'), speaker = $('clBtnSpeaker');
        
        if (!accept || !decline || !end) {
            setTimeout(attachListeners, 300);
            return;
        }
        
        var newAccept = accept.cloneNode(true);
        var newDecline = decline.cloneNode(true);
        var newEnd = end.cloneNode(true);
        accept.parentNode.replaceChild(newAccept, accept);
        decline.parentNode.replaceChild(newDecline, decline);
        end.parentNode.replaceChild(newEnd, end);
        
        newAccept.addEventListener('click', acceptCall);
        newDecline.addEventListener('click', declineCall);
        newEnd.addEventListener('click', hangup);
        
        if (mute) {
            var newMute = mute.cloneNode(true);
            mute.parentNode.replaceChild(newMute, mute);
            newMute.addEventListener('click', toggleMute);
        }
        if (video) {
            var newVideo = video.cloneNode(true);
            video.parentNode.replaceChild(newVideo, video);
            newVideo.addEventListener('click', toggleVideo);
        }
        if (flip) {
            var newFlip = flip.cloneNode(true);
            flip.parentNode.replaceChild(newFlip, flip);
            newFlip.addEventListener('click', flipCamera);
        }
        if (speaker) {
            var newSpeaker = speaker.cloneNode(true);
            speaker.parentNode.replaceChild(newSpeaker, speaker);
            newSpeaker.addEventListener('click', function() {
                var btn = $('clBtnSpeaker');
                if (btn) btn.classList.toggle('cl-active-ctrl');
            });
        }
        
        console.log('Call module: Button listeners attached');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachListeners);
    } else {
        attachListeners();
    }
    
})();
}
</script>