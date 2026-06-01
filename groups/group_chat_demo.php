<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Group | BisureChat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Simplified CSS based on your PHP page */
        body {
            font-family: 'Roboto', sans-serif;
            background: #e5ddd5;
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            padding: 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .group-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .group-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25D366;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .messages-container {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #e5ddd5;
        }

        .message {
            max-width: 70%;
            margin-bottom: 1rem;
            padding: 0.8rem;
            border-radius: 8px;
            word-wrap: break-word;
        }

        .sent {
            background: #25D366;
            color: white;
            margin-left: auto;
            border-top-right-radius: 0;
        }

        .received {
            background: #fff;
            color: #212529;
            margin-right: auto;
            border-top-left-radius: 0;
        }

        .message-sender {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.3rem;
        }

        .message-time {
            font-size: 0.7rem;
            margin-top: 0.3rem;
            text-align: right;
            opacity: 0.8;
        }

        .message-input-container {
            display: flex;
            gap: 0.5rem;
            padding: 0.8rem;
            background: #fff;
            border-top: 1px solid #ddd;
        }

        .message-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            border: 1px solid #ddd;
            outline: none;
        }

        .send-button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #25D366;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <header>
            <div class="group-info">
                <div class="group-avatar">T</div>
                <div>
                    <div>Test Group</div>
                    <div style="font-size:0.8rem;">3 members</div>
                </div>
            </div>
            <div>
                <button style="background:none;border:none;color:white;font-size:1.2rem;"><i class="fas fa-phone"></i></button>
                <button style="background:none;border:none;color:white;font-size:1.2rem;"><i class="fas fa-video"></i></button>
            </div>
        </header>

        <div class="messages-container" id="messagesContainer">
            <div class="message received">
                <div class="message-sender">Alice</div>
                Hello, everyone!
                <div class="message-time">10:00 AM</div>
            </div>
            <div class="message sent">
                Hey Alice, welcome!
                <div class="message-time">10:01 AM</div>
            </div>
            <div class="message received">
                <div class="message-sender">Bob</div>
                Glad to have you here.
                <div class="message-time">10:02 AM</div>
            </div>
        </div>

        <div class="message-input-container">
            <input type="text" class="message-input" id="messageInput" placeholder="Type a message...">
            <button class="send-button" id="sendButton"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');

        function addMessage(sender, text, type) {
            const div = document.createElement('div');
            div.className = 'message ' + type;
            div.innerHTML = type === 'received' ? `<div class="message-sender">${sender}</div>${text}<div class="message-time">Just now</div>` : `${text}<div class="message-time">Just now</div>`;
            messagesContainer.appendChild(div);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        sendButton.addEventListener('click', () => {
            const text = messageInput.value.trim();
            if (!text) return;
            addMessage('You', text, 'sent');
            messageInput.value = '';
        });

        messageInput.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendButton.click();
            }
        });
    </script>
</body>

</html>
