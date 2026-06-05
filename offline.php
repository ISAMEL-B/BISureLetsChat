<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#075E54">
    <title>BISURE Chat - Offline</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            min-height: 100dvh;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7e6 100%);
            color: #333;
            padding: 20px;
        }
        
        .offline-container {
            text-align: center;
            padding: 40px 30px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 420px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: #075E54;
            border-radius: 20px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(7, 94, 84, 0.3);
        }
        
        .icon-container {
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }
        
        .wifi-icon {
            font-size: 48px;
            display: block;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .cross-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 60px;
            color: #e74c3c;
            opacity: 0.5;
        }
        
        h1 {
            color: #075E54;
            margin-bottom: 12px;
            font-size: 26px;
            font-weight: 700;
        }
        
        p {
            color: #666;
            margin-bottom: 8px;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .tips {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            text-align: left;
        }
        
        .tips h3 {
            color: #075E54;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tips ul {
            list-style: none;
            padding: 0;
        }
        
        .tips li {
            padding: 6px 0;
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tips li::before {
            content: '•';
            color: #075E54;
            font-weight: bold;
            font-size: 18px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #e74c3c;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1.5s ease-in-out infinite;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #075E54;
            color: white;
            box-shadow: 0 4px 12px rgba(7, 94, 84, 0.3);
        }
        
        .btn-primary:hover {
            background: #064f46;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(7, 94, 84, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #f0f2f5;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e4e6e9;
            transform: translateY(-2px);
        }
        
        .retry-counter {
            font-size: 12px;
            color: #999;
            margin-top: 12px;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        @keyframes blink {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            }
            
            .offline-container {
                background: #1F2C33;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            }
            
            h1 {
                color: #25D366;
            }
            
            p, .tips li {
                color: #8696A0;
            }
            
            .tips {
                background: #2A3942;
            }
            
            .tips h3 {
                color: #25D366;
            }
            
            .btn-secondary {
                background: #2A3942;
                color: #E9EDEF;
            }
            
            .btn-secondary:hover {
                background: #374248;
            }
            
            .retry-counter {
                color: #8696A0;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="logo">B</div>
        
        <div class="icon-container">
            <span class="wifi-icon">📡</span>
        </div>
        
        <h1>You're Offline</h1>
        
        <p>
            <span class="status-indicator"></span>
            No internet connection detected
        </p>
        
        <div class="tips">
            <h3>🔍 Troubleshooting Tips</h3>
            <ul>
                <li>Check if Airplane Mode is off</li>
                <li>Turn Wi-Fi off and on again</li>
                <li>Check your mobile data connection</li>
                <li>Move to an area with better signal</li>
                <li>Restart your device if needed</li>
            </ul>
        </div>
        
        <div class="button-group">
            <button class="btn btn-primary" onclick="retryConnection()">
                <span>🔄</span> Retry
            </button>
            <button class="btn btn-secondary" onclick="goBack()">
                <span>←</span> Go Back
            </button>
        </div>
        
        <div class="retry-counter" id="retryMessage">
            Auto-retrying every 5 seconds...
        </div>
    </div>

    <script>
        let retryCount = 0;
        let autoRetryInterval;
        
        // Auto-retry connection
        function startAutoRetry() {
            autoRetryInterval = setInterval(() => {
                retryCount++;
                document.getElementById('retryMessage').textContent = 
                    `Auto-retrying... (Attempt ${retryCount})`;
                
                // Try to fetch the main page
                fetch('/?source=offline')
                    .then(response => {
                        if (response.ok) {
                            // Connection restored!
                            clearInterval(autoRetryInterval);
                            document.getElementById('retryMessage').textContent = 
                                '✅ Connection restored! Redirecting...';
                            
                            setTimeout(() => {
                                window.location.replace('/?source=offline_restored');
                            }, 1000);
                        }
                    })
                    .catch(() => {
                        // Still offline
                        if (retryCount > 20) {
                            clearInterval(autoRetryInterval);
                            document.getElementById('retryMessage').textContent = 
                                'Auto-retry stopped. Please try manually.';
                        }
                    });
            }, 5000);
        }
        
        function retryConnection() {
            clearInterval(autoRetryInterval);
            document.getElementById('retryMessage').textContent = 'Checking connection...';
            
            fetch('/?source=offline_check')
                .then(response => {
                    if (response.ok) {
                        document.getElementById('retryMessage').textContent = 
                            '✅ Connected! Redirecting...';
                        setTimeout(() => {
                            window.location.replace('/?source=offline_restored');
                        }, 500);
                    } else {
                        document.getElementById('retryMessage').textContent = 
                            'Still offline. Starting auto-retry...';
                        retryCount = 0;
                        startAutoRetry();
                    }
                })
                .catch(() => {
                    document.getElementById('retryMessage').textContent = 
                        'Still offline. Starting auto-retry...';
                    retryCount = 0;
                    startAutoRetry();
                });
        }
        
        function goBack() {
            window.history.back();
            
            // If history.back doesn't work (no previous page), try to reload
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
        
        // Listen for online event
        window.addEventListener('online', () => {
            document.getElementById('retryMessage').textContent = 
                '✅ Connection restored! Redirecting...';
            clearInterval(autoRetryInterval);
            
            setTimeout(() => {
                window.location.replace('/?source=online_restored');
            }, 1000);
        });
        
        // Start auto-retry on page load
        startAutoRetry();
        
        // Log that offline page was served
        console.log('📄 Offline page displayed');
    </script>
</body>
</html>