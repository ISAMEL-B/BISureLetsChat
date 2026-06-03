<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- PWA Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    
    <title>PWA Localhost Test</title>
</head>
<body>
    <h1>PWA Localhost Test</h1>
    <button id="installBtn" style="display:none; padding:20px; font-size:18px;">
        📱 Install App
    </button>
    <div id="status"></div>
    
    <script>
        let deferredPrompt;
        
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => {
                    console.log('✅ SW Registered on localhost!');
                    document.getElementById('status').innerHTML += 
                        '<p>✅ Service Worker Registered</p>';
                })
                .catch(err => {
                    console.log('SW Error:', err);
                    document.getElementById('status').innerHTML += 
                        '<p>❌ Service Worker Failed: ' + err + '</p>';
                });
        }
        
        // Capture install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('✅ beforeinstallprompt on localhost!');
            e.preventDefault();
            deferredPrompt = e;
            
            document.getElementById('installBtn').style.display = 'block';
            document.getElementById('status').innerHTML += 
                '<p>✅ Install Prompt Available!</p>';
        });
        
        // Install button click
        document.getElementById('installBtn').addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const result = await deferredPrompt.userChoice;
                console.log('Install result:', result.outcome);
                document.getElementById('status').innerHTML += 
                    '<p>Install result: ' + result.outcome + '</p>';
                deferredPrompt = null;
            }
        });
        
        // App installed
        window.addEventListener('appinstalled', () => {
            console.log('✅ App installed on localhost!');
            document.getElementById('status').innerHTML += 
                '<p>✅ App Installed Successfully!</p>';
        });
        
        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            document.getElementById('status').innerHTML += 
                '<p>✅ Running in standalone mode!</p>';
        }
    </script>
</body>
</html>