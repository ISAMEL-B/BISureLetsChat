//==============================
// WEBSOCKET SERVER SETUP START
// Creates a WebSocket server for signaling between WebRTC clients
//==============================
const WebSocket = require('ws');

// Create WebSocket server on port 8080
const wss = new WebSocket.Server({ port: 8080 });

// Object to store connected clients mapped by their user ID
const clients = {}; // maps user_id → ws connection

// Handle new WebSocket connections
wss.on('connection', ws => {
    // Handle incoming messages from clients
    ws.on('message', msg => {
        let message;
        try {
            // Parse incoming JSON message
            message = JSON.parse(msg);
        } catch (e) {
            console.error('Invalid JSON', e);
            return;
        }

        // Register new client with their ID
        if (message.type === 'register' && message.id) {
            clients[message.id] = ws;
            console.log(`Registered client: ${message.id}`);
            return;
        }

        // Forward message to the intended recipient if they're connected
        if (message.to && clients[message.to]) {
            clients[message.to].send(JSON.stringify(message));
        }
    });

    // Handle client disconnection
    ws.on('close', () => {
        // Remove client from tracking object
        for (let id in clients) {
            if (clients[id] === ws) delete clients[id];
        }
    });
});

console.log('Signaling server running on ws://localhost:8080');
//==============================
// WEBSOCKET SERVER SETUP END
//==============================