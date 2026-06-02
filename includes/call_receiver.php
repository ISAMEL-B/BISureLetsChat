<?php
// This goes at the BOTTOM of every page, before </body>
session_start();
if (!isset($_SESSION['user_id'])) return;
?>
<script>
(function() {
    // Don't run if already on the calls page
    if (window.location.href.includes('/calls')) return;
    
    const WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/';
    const MY_ID = '<?= $_SESSION['user_id'] ?>';
    const MY_NAME = '<?= $_SESSION['fullname'] ?? $_SESSION['username'] ?? '' ?>';
    
    const sock = new WebSocket(WS_URL);
    
    sock.onopen = function() {
        sock.send(JSON.stringify({ type: 'register', id: MY_ID, name: MY_NAME }));
    };
    
    sock.onmessage = function(ev) {
        const msg = JSON.parse(ev.data);
        if (msg.type === 'offer' && String(msg.to) === MY_ID) {
            // Save call data to session via fetch
            const formData = new FormData();
            formData.append('from', msg.from);
            formData.append('fromName', msg.fromName || 'User');
            formData.append('fromPicture', msg.fromPicture || '');
            formData.append('isVideo', msg.isVideo ? '1' : '0');
            formData.append('callId', msg.callId || '');
            
            fetch('includes/save_pending_call.php', {
                method: 'POST',
                body: formData
            }).then(function() {
                window.location.href = '../calls/call'; // Redirect to calls page
            });
        }
    };
    
    sock.onclose = function() {
        // Reconnect after 3 seconds
        setTimeout(function() { location.reload(); }, 3000);
    };
})();
</script>