<?php
session_start();
if (!isset($_SESSION['user_id'])) return;

$isCallsPage = strpos($_SERVER['REQUEST_URI'], '/calls') !== false;
// Get base URL for consistent paths
$baseUrl = '/bisureletschat'; // CHANGE THIS to your app's base path, or use: dirname($_SERVER['SCRIPT_NAME'], 2)
?>
<?php if (!$isCallsPage): ?>

<style>
    .global-incoming-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh; height: 100dvh;
        background: rgba(0,0,0,0.9); z-index: 99999;
        display: none; align-items: center; justify-content: center;
        backdrop-filter: blur(15px);
    }
    .global-incoming-overlay.active { display: flex; }
    .global-incoming-card {
        background: #ffffff; border-radius: 28px; padding: 2.5rem 2rem;
        text-align: center; max-width: 350px; width: 90%;
        box-shadow: 0 25px 70px rgba(0,0,0,0.5);
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.5); }
        50% { box-shadow: 0 0 0 25px rgba(37, 211, 102, 0); }
    }
    .global-incoming-avatar {
        width: 90px; height: 90px; border-radius: 50%;
        background: linear-gradient(135deg, #128C7E, #25D366);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem; font-size: 2.5rem; color: white; overflow: hidden;
        animation: pulse 2s ease-in-out infinite;
    }
    .global-incoming-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .global-incoming-name { font-size: 1.3rem; font-weight: 600; margin-bottom: 4px; color: #2D3748; }
    .global-incoming-type { font-size: 0.9rem; color: #718096; margin-bottom: 2rem; }
    .global-incoming-actions { display: flex; justify-content: center; gap: 2rem; }
    .global-incoming-btn-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; }
    .global-incoming-btn {
        width: 60px; height: 60px; border-radius: 50%; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: white;
    }
    .global-btn-decline { background: #E74C3C; }
    .global-btn-accept { background: #25D366; }
    .global-incoming-label { font-size: 0.75rem; color: #718096; font-weight: 500; }
</style>

<div class="global-incoming-overlay" id="globalIncomingOverlay">
    <div class="global-incoming-card">
        <div class="global-incoming-avatar" id="globalIncomingAvatar"><i class="fas fa-user"></i></div>
        <div class="global-incoming-name" id="globalIncomingName">Contact</div>
        <div class="global-incoming-type" id="globalIncomingType">📞 Voice Call</div>
        <div class="global-incoming-actions">
            <div class="global-incoming-btn-wrap" id="globalBtnDecline">
                <div class="global-incoming-btn global-btn-decline"><i class="fas fa-phone-slash"></i></div>
                <span class="global-incoming-label">Decline</span>
            </div>
            <div class="global-incoming-btn-wrap" id="globalBtnAccept">
                <div class="global-incoming-btn global-btn-accept"><i class="fas fa-phone"></i></div>
                <span class="global-incoming-label">Accept</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.location.href.indexOf('/calls') !== -1) return;
    
    var WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/';
    var MY_ID = '<?= $_SESSION['user_id'] ?>';
    var MY_NAME = '<?= $_SESSION['fullname'] ?? $_SESSION['username'] ?? '' ?>';
    // ✅ ABSOLUTE paths
    var BASE_PATH = '<?= $baseUrl ?>';
    var CALLS_URL = BASE_PATH + '/calls/call';
    var SAVE_URL = BASE_PATH + '/calls/includes/save_pending_call.php';
    var HANDLER_URL = BASE_PATH + '/calls/content/call_handler.php';
    var RINGTONE_URL = BASE_PATH + '/calls/rington.mp3';
    
    var gState = {
        sock: null,
        remoteId: null,
        remoteName: null,
        isVideo: false,
        dbCallId: null,
        connected: false  // ✅ Track connection state
    };
    
    var ringtone = new Audio(RINGTONE_URL);
    ringtone.loop = true;
    
    // Pre-warm ringtone
    document.addEventListener('click', function warm() {
        ringtone.play().then(function() { ringtone.pause(); ringtone.currentTime = 0; }).catch(function(){});
    }, { once: true });
    
    // ✅ Also handle touchstart for mobile
    document.addEventListener('touchstart', function warm2() {
        ringtone.play().then(function() { ringtone.pause(); ringtone.currentTime = 0; }).catch(function(){});
    }, { once: true });
    
    function doConnect() {
        if (gState.sock && (gState.sock.readyState === WebSocket.OPEN || gState.sock.readyState === WebSocket.CONNECTING)) {
            return;
        }
        
        console.log('Global: Connecting WebSocket...');
        gState.sock = new WebSocket(WS_URL);
        
        gState.sock.onopen = function() {
            console.log('Global: WebSocket connected, registering as', MY_ID);
            gState.connected = true;
            gState.sock.send(JSON.stringify({ type: 'register', id: MY_ID, name: MY_NAME }));
        };
        
        gState.sock.onmessage = function(ev) {
            var msg = JSON.parse(ev.data);
            if (String(msg.to) !== MY_ID) return;
            
            console.log('Global: Received message type:', msg.type);
            
            if (msg.type === 'offer') {
                console.log('Global: Incoming offer from:', msg.fromName);
                gState.isVideo = !!msg.isVideo;
                gState.remoteId = String(msg.from);
                gState.remoteName = msg.fromName || 'User';
                gState.dbCallId = msg.callId || null;
                
                var overlay = document.getElementById('globalIncomingOverlay');
                if (!overlay) { console.error('Global: Overlay element not found!'); return; }
                
                document.getElementById('globalIncomingName').textContent = gState.remoteName;
                document.getElementById('globalIncomingType').textContent = gState.isVideo ? '📹 Video Call' : '📞 Voice Call';
                
                if (msg.fromPicture) {
                    document.getElementById('globalIncomingAvatar').innerHTML = '<img src="' + msg.fromPicture + '" style="width:100%;height:100%;object-fit:cover;">';
                } else {
                    document.getElementById('globalIncomingAvatar').innerHTML = '<i class="fas fa-user"></i>';
                }
                
                overlay.classList.add('active');
                ringtone.currentTime = 0;
                ringtone.play().catch(function(e){ console.log('Global: Ringtone play failed:', e); });
            }
            
            if (msg.type === 'hangup' || msg.type === 'decline') {
                ringtone.pause();
                var ov = document.getElementById('globalIncomingOverlay');
                if (ov) ov.classList.remove('active');
            }
        };
        
        gState.sock.onclose = function(e) {
            console.log('Global: WebSocket closed, code:', e.code, 'reconnecting in 3s...');
            gState.connected = false;
            ringtone.pause();
            setTimeout(doConnect, 3000);
        };
        
        gState.sock.onerror = function(err) {
            console.log('Global: WebSocket error');
        };
    }
    
    // ✅ Wait for DOM to be ready
    function initButtons() {
        var acceptBtn = document.getElementById('globalBtnAccept');
        var declineBtn = document.getElementById('globalBtnDecline');
        
        if (!acceptBtn || !declineBtn) {
            // Elements not ready, retry
            setTimeout(initButtons, 200);
            return;
        }
        
        acceptBtn.addEventListener('click', function() {
            console.log('Global: Accept clicked');
            ringtone.pause();
            document.getElementById('globalIncomingOverlay').classList.remove('active');
            
            var fd = new FormData();
            fd.append('from', gState.remoteId);
            fd.append('fromName', gState.remoteName);
            fd.append('fromPicture', '');
            fd.append('isVideo', gState.isVideo ? '1' : '0');
            fd.append('callId', gState.dbCallId || '');
            
            fetch(SAVE_URL, {
                method: 'POST',
                body: fd
            }).then(function(r) { return r.text(); })
              .then(function(txt) {
                console.log('Global: Save response:', txt);
                window.location.href = CALLS_URL;
              }).catch(function(err) {
                console.error('Global: Save failed:', err);
                window.location.href = CALLS_URL;
              });
        });
        
        declineBtn.addEventListener('click', function() {
            console.log('Global: Decline clicked');
            ringtone.pause();
            document.getElementById('globalIncomingOverlay').classList.remove('active');
            
            if (gState.sock && gState.sock.readyState === WebSocket.OPEN) {
                gState.sock.send(JSON.stringify({
                    type: 'decline',
                    from: MY_ID,
                    to: gState.remoteId
                }));
            }
            
            if (gState.dbCallId) {
                var fd2 = new FormData();
                fd2.append('action', 'decline_call');
                fd2.append('call_id', gState.dbCallId);
                fetch(HANDLER_URL, { method: 'POST', body: fd2 });
            }
        });
    }
    
    // Start everything
    initButtons();
    doConnect();
})();
</script>
<?php endif; ?>