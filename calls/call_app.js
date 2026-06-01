//==============================
// CONSTANTS DEFINITION START
//==============================
const MY_ID = String(window.SELF_ID);
const MY_NAME = String(window.SELF_NAME); // your real name
const WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/'; // ✅ ensure HTTPS/WSS in production
const ICE_CONFIG = {
    iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
};
//==============================
// CONSTANTS DEFINITION END
//==============================

//==============================
// RINGTONE SETUP START
//==============================
const ringtone = new Audio('rington.mp3'); 
ringtone.loop = true;

(function enableSoundAfterGesture() {
    const unlock = () => {
        ringtone.play().then(() => { ringtone.pause(); ringtone.currentTime = 0; }).catch(() => {});
        document.removeEventListener('click', unlock);
        document.removeEventListener('touchstart', unlock);
    };
    document.addEventListener('click', unlock, { once: true });
    document.addEventListener('touchstart', unlock, { once: true });
})();
//==============================
// RINGTONE SETUP END
//==============================

//==============================
// STATE INITIALIZATION START
//==============================
let state = {
    callState: 'idle',
    localStream: null,
    remoteStream: null,
    pc: null,
    isVideo: false,
    remoteId: null,
    remoteName: null, // store remote name
    pendingOffer: null,
    timer: null,
    seconds: 0
};
//==============================
// STATE INITIALIZATION END
//==============================

//==============================
// UI ELEMENTS INITIALIZATION START
//==============================
const ui = {
    contactsList: document.getElementById('contactsList'),
    callUI: document.getElementById('callInterface'),
    incomingUI: document.getElementById('incomingCall'),
    callName: document.getElementById('callContactName'),
    incomingName: document.getElementById('incomingContactName'),
    callStatus: document.getElementById('callStatus'),
    callTimer: document.getElementById('callTimer'),
    localVideo: document.getElementById('localVideo'),
    remoteVideo: document.getElementById('remoteVideo'),
    btnAccept: document.getElementById('btnAccept'),
    btnDecline: document.getElementById('btnDecline'),
    btnEnd: document.getElementById('btnEnd'),
    btnMute: document.getElementById('btnMute'),
    btnVideoToggle: document.getElementById('btnVideoToggle'),
    searchInput: document.getElementById('searchInput')
};
//==============================
// UI ELEMENTS INITIALIZATION END
//==============================

//==============================
// RIPPLE EFFECT FUNCTION START
//==============================
function addRippleEffect(element) {
    element.addEventListener('click', e => {
        const x = e.clientX - e.target.getBoundingClientRect().left;
        const y = e.clientY - e.target.getBoundingClientRect().top;
        const ripple = document.createElement('span');
        ripple.classList.add('ripple');
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        element.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
}
//==============================
// RIPPLE EFFECT FUNCTION END
//==============================

//==============================
// TIMER FUNCTIONS START
//==============================
function startTimer() {
    stopTimer();
    state.seconds = 0;
    state.timer = setInterval(() => {
        state.seconds++;
        ui.callTimer.textContent =
            String(Math.floor(state.seconds / 60)).padStart(2, '0') + ':' +
            String(state.seconds % 60).padStart(2, '0');
    }, 1000);
}
function stopTimer() {
    clearInterval(state.timer);
    ui.callTimer.textContent = '00:00';
}
//==============================
// TIMER FUNCTIONS END
//==============================

//==============================
// CLEANUP FUNCTIONS START
//==============================
function cleanupPeer() {
    if (state.pc) {
        state.pc.close();
        state.pc = null;
    }
}
function stopStreams() {
    state.localStream?.getTracks().forEach(t => t.stop());
    state.remoteStream?.getTracks().forEach(t => t.stop());
    ui.localVideo.srcObject = null;
    ui.remoteVideo.srcObject = null;
}
//==============================
// CLEANUP FUNCTIONS END
//==============================

//==============================
// NOTIFICATION FUNCTION START
//==============================
function showNotification(message, type = "info") {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === "error" ? "#d9534f" : "var(--pro-gradient)"};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.5s ease;
        max-width: 350px;
        line-height: 1.4;
        font-size: 14px;
    `;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => (notification.style.transform = 'translateX(0)'), 10);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 6000);
}
//==============================
// NOTIFICATION FUNCTION END
//==============================

//==============================
// WEBSOCKET SETUP START
//==============================
const sock = new WebSocket(WS_URL);

// Register with ID and NAME
sock.onopen = () => sock.send(JSON.stringify({ type: 'register', id: MY_ID, name: MY_NAME }));

sock.onmessage = async ev => {
    const msg = JSON.parse(ev.data);
    if (String(msg.to) !== MY_ID) return;

    switch (msg.type) {
        case 'offer':
            state.isVideo = !!msg.isVideo;
            state.remoteId = String(msg.from);
            state.remoteName = msg.fromName || ('User ' + state.remoteId);
            state.pendingOffer = msg.sdp;

            ui.incomingName.textContent = state.remoteName;
            ui.incomingUI.style.display = 'flex';

            const existingCallType = ui.incomingUI.querySelector('.call-type');
            if (existingCallType) existingCallType.remove();

            const callTypeElement = document.createElement('div');
            callTypeElement.className = 'call-type';
            callTypeElement.textContent = state.isVideo ? 'Video Call' : 'Voice Call';
            ui.incomingName.after(callTypeElement);

            ringtone.currentTime = 0;
            ringtone.play().catch(err => console.warn('Ringtone play blocked:', err));
            break;

        case 'answer':
            if (!state.pc) return;
            await state.pc.setRemoteDescription(msg.sdp);
            ui.callStatus.textContent = 'Connected';
            ui.callName.textContent = state.remoteName;
            startTimer();
            ringtone.pause();
            ringtone.currentTime = 0;
            break;

        case 'candidate':
            if (state.pc && msg.candidate)
                await state.pc.addIceCandidate(msg.candidate);
            break;

        case 'decline':
            showNotification(`${state.remoteName} declined the call`);
            fullReset();
            break;

        case 'hangup':
            showNotification(`${state.remoteName} ended the call`);
            fullReset();
            break;
    }
};

sock.onclose = () => {
    ringtone.pause();
    ringtone.currentTime = 0;
    showNotification('Connection lost', 'error');
};

// Always send your NAME
function send(to, payload) {
    sock.send(JSON.stringify({ from: MY_ID, fromName: MY_NAME, to: String(to), ...payload }));
}
//==============================
// WEBSOCKET SETUP END
//==============================

//==============================
// PEER CONNECTION SETUP START
//==============================
function buildPeer() {
    state.pc = new RTCPeerConnection(ICE_CONFIG);

    state.pc.onicecandidate = e => {
        if (e.candidate && state.remoteId)
            send(state.remoteId, { type: 'candidate', candidate: e.candidate });
    };

    state.pc.ontrack = e => {
        state.remoteStream = e.streams[0];
        ui.remoteVideo.srcObject = state.remoteStream;
        if (state.isVideo) ui.callUI.classList.remove('call-audio-only');
        else ui.callUI.classList.add('call-audio-only');
    };

    state.pc.onconnectionstatechange = () => {
        const s = state.pc.connectionState;
        if (s === 'connected') startTimer();
        else if (['failed', 'disconnected', 'closed'].includes(s)) {
            showNotification('Call disconnected', 'error');
            fullReset();
        }
    };
}
//==============================
// PEER CONNECTION SETUP END
//==============================

//==============================
// MEDIA REQUEST
//==============================
async function requestMedia(video = false) {
    try {
        return await navigator.mediaDevices.getUserMedia(video ? { audio: true, video: true } : { audio: true });
    } catch (err) {
        console.error("Media access error:", err);
        showNotification("Microphone/Camera access denied", "error");
        return null;
    }
}
//==============================
// CALL ACTIONS
//==============================
async function startCall(name, id, video) {
    if (state.callState !== 'idle') return showNotification('Already in a call', 'error');

    state.isVideo = !!video;
    state.remoteId = String(id);
    state.remoteName = name;
    ui.callName.textContent = name;
    ui.callStatus.textContent = video ? 'Starting video...' : 'Calling...';
    ui.callUI.style.display = 'flex';

    state.localStream = await requestMedia(video);
    if (!state.localStream) return fullReset();

    if (video) ui.localVideo.srcObject = state.localStream;
    else ui.callUI.classList.add('call-audio-only');

    buildPeer();
    state.localStream.getTracks().forEach(t => state.pc.addTrack(t, state.localStream));
    const offer = await state.pc.createOffer();
    await state.pc.setLocalDescription(offer);
    send(state.remoteId, { type: 'offer', sdp: offer, isVideo: video, fromName: MY_NAME });
    state.callState = 'dialing';
}

async function acceptCall() {
    ringtone.pause();
    ringtone.currentTime = 0;
    if (!state.pendingOffer || !state.remoteId) return;

    ui.incomingUI.style.display = 'none';
    ui.callName.textContent = state.remoteName;
    ui.callUI.style.display = 'flex';

    state.localStream = await requestMedia(state.isVideo);
    if (!state.localStream) {
        send(state.remoteId, { type: 'decline', fromName: MY_NAME });
        return fullReset();
    }

    if (state.isVideo) ui.localVideo.srcObject = state.localStream;

    buildPeer();
    state.localStream.getTracks().forEach(t => state.pc.addTrack(t, state.localStream));
    await state.pc.setRemoteDescription(state.pendingOffer);
    const answer = await state.pc.createAnswer();
    await state.pc.setLocalDescription(answer);
    send(state.remoteId, { type: 'answer', sdp: answer, fromName: MY_NAME });
    state.pendingOffer = null;
    state.callState = 'connected';
}

function declineCall() {
    ringtone.pause();
    ringtone.currentTime = 0;
    if (state.remoteId) send(state.remoteId, { type: 'decline', fromName: MY_NAME });
    fullReset();
}

function hangup() {
    ringtone.pause();
    ringtone.currentTime = 0;
    if (state.remoteId) send(state.remoteId, { type: 'hangup', fromName: MY_NAME });
    fullReset();
}

function toggleMute() {
    if (!state.localStream) return;
    const mic = state.localStream.getAudioTracks()[0];
    if (mic) { mic.enabled = !mic.enabled; ui.btnMute.classList.toggle('muted', !mic.enabled); }
}

function toggleVideo() {
    if (!state.localStream || !state.isVideo) return;
    const cam = state.localStream.getVideoTracks()[0];
    if (cam) { cam.enabled = !cam.enabled; ui.btnVideoToggle.classList.toggle('disabled', !cam.enabled); }
}

function fullReset() {
    ringtone.pause();
    ringtone.currentTime = 0;
    cleanupPeer();
    stopStreams();
    stopTimer();
    state = { ...state, callState: 'idle', isVideo: false, remoteId: null, pendingOffer: null, remoteName: null };
    ui.callUI.style.display = 'none';
    ui.incomingUI.style.display = 'none';
    ui.btnMute.classList.remove('muted');
    ui.btnVideoToggle.classList.remove('disabled');
}

//==============================
// EVENT BINDINGS
//==============================
ui.contactsList.querySelectorAll('.contact-item').forEach(item => {
    const name = item.dataset.userName, id = item.dataset.userId;
    item.querySelector('.call-btn').addEventListener('click', () => startCall(name, id, false));
    item.querySelector('.video-call-btn').addEventListener('click', () => startCall(name, id, true));
    addRippleEffect(item.querySelector('.call-btn'));
    addRippleEffect(item.querySelector('.video-call-btn'));
});

addRippleEffect(ui.btnAccept);
addRippleEffect(ui.btnDecline);
addRippleEffect(ui.btnEnd);
addRippleEffect(ui.btnMute);
addRippleEffect(ui.btnVideoToggle);

ui.btnAccept.addEventListener('click', acceptCall);
ui.btnDecline.addEventListener('click', declineCall);
ui.btnEnd.addEventListener('click', hangup);
ui.btnMute.addEventListener('click', toggleMute);
ui.btnVideoToggle.addEventListener('click', toggleVideo);

ui.searchInput.addEventListener('input', () => {
    const term = ui.searchInput.value.toLowerCase();
    ui.contactsList.querySelectorAll('.contact-item').forEach(item => {
        const name = item.dataset.userName.toLowerCase();
        item.style.display = name.includes(term) ? 'flex' : 'none';
    });
});
