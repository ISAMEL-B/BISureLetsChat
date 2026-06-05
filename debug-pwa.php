<?php
// debug-pwa.php - Enhanced PWA Debugging Tool
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Debug - BISURE Chat</title>
    
    <!-- CRITICAL: Manifest Link -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-pass { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
        .status-warn { color: #ffc107; font-weight: bold; }
        .check-item {
            padding: 12px;
            margin: 8px 0;
            border-left: 4px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .check-item.pass { border-left-color: #28a745; background: #d4edda; }
        .check-item.fail { border-left-color: #dc3545; background: #f8d7da; }
        .check-item.warn { border-left-color: #ffc107; background: #fff3cd; }
        button {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        button:active {
            transform: translateY(0);
        }
        .install-big-btn {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            padding: 20px 40px;
            font-size: 18px;
            border-radius: 12px;
            width: 100%;
            margin: 10px 0;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            50% { box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); }
        }
        .install-big-btn:hover {
            animation: none;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            margin: 10px 0;
        }
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin: 10px 0;
        }
        .log-entry {
            margin: 5px 0;
            padding: 2px 0;
        }
        .log-success { color: #4ec9b0; }
        .log-error { color: #f44747; }
        .log-warning { color: #cca700; }
        .log-info { color: #9cdcfe; }
        h2 {
            margin-bottom: 15px;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        .badge-success { background: #d4edda; color: #28a745; }
        .badge-danger { background: #f8d7da; color: #dc3545; }
    </style>
</head>
<body>
    <h1>🔍 PWA Debug Tool - BISURE Chat</h1>
    
    <!-- Big Install Button -->
    <div class="card" style="text-align: center;">
        <h2>📱 Install BISURE Chat</h2>
        <button id="forceInstallBtn" class="install-big-btn" style="display:none;">
            ⬇️ Click Here to Install BISURE Chat
        </button>
        <div id="installStatus" style="margin-top: 10px; font-size: 14px;"></div>
    </div>
    
    <!-- Server Checks -->
    <div class="card">
        <h2>1. Server & Environment Check</h2>
        <?php
        $checks = [];
        
        // Check HTTPS
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $checks[] = [
            'name' => 'HTTPS',
            'status' => $is_https ? 'pass' : 'fail',
            'message' => $is_https ? '✅ HTTPS is enabled' : '❌ HTTPS is NOT enabled - PWA requires HTTPS!',
            'detail' => 'Current protocol: ' . ($is_https ? 'https://' : 'http://')
        ];
        
        // Check if service worker file exists
        $sw_path = __DIR__ . '/service-worker.js';
        $checks[] = [
            'name' => 'Service Worker File',
            'status' => file_exists($sw_path) ? 'pass' : 'fail',
            'message' => file_exists($sw_path) ? '✅ service-worker.js exists' : '❌ service-worker.js NOT found',
            'detail' => 'Expected at: ' . $sw_path
        ];
        
        // Check if manifest exists
        $manifest_path = __DIR__ . '/manifest.json';
        $checks[] = [
            'name' => 'Manifest File',
            'status' => file_exists($manifest_path) ? 'pass' : 'fail',
            'message' => file_exists($manifest_path) ? '✅ manifest.json exists' : '❌ manifest.json NOT found',
            'detail' => 'Expected at: ' . $manifest_path
        ];
        
        // Check icons
        $icons_192 = __DIR__ . '/assets/icons/icon-192x192.png';
        $icons_512 = __DIR__ . '/assets/icons/icon-512x512.png';
        $checks[] = [
            'name' => 'Icon Files',
            'status' => (file_exists($icons_192) && file_exists($icons_512)) ? 'pass' : 'fail',
            'message' => (file_exists($icons_192) && file_exists($icons_512)) ? 
                        '✅ Icon files exist' : '❌ Icon files missing',
            'detail' => "192x192: " . (file_exists($icons_192) ? 'Found' : 'Missing') . 
                       " | 512x512: " . (file_exists($icons_512) ? 'Found' : 'Missing')
        ];
        
        foreach ($checks as $check) {
            echo "<div class='check-item {$check['status']}'>";
            echo "<strong>{$check['name']}:</strong> {$check['message']}";
            echo "<br><small>{$check['detail']}</small>";
            echo "</div>";
        }
        ?>
    </div>
    
    <!-- Manifest Content -->
    <div class="card">
        <h2>2. Manifest.json Content</h2>
        <?php
        if (file_exists($manifest_path)) {
            $manifest_content = file_get_contents($manifest_path);
            $manifest_json = json_decode($manifest_content, true);
            
            if ($manifest_json) {
                echo "<div style='background:#f8f9fa; padding:15px; border-radius:8px; margin:10px 0;'>";
                echo "<strong>✅ Valid JSON</strong><br><br>";
                echo "<strong>Name:</strong> " . ($manifest_json['name'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                echo "<strong>Short Name:</strong> " . ($manifest_json['short_name'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                echo "<strong>Start URL:</strong> " . ($manifest_json['start_url'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                echo "<strong>Display:</strong> " . ($manifest_json['display'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                echo "<strong>Theme Color:</strong> " . ($manifest_json['theme_color'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                echo "<strong>Background Color:</strong> " . ($manifest_json['background_color'] ?? '<span class="status-fail">MISSING</span>') . "<br>";
                
                if (isset($manifest_json['icons']) && count($manifest_json['icons']) > 0) {
                    echo "<br><strong>Icons:</strong><br>";
                    foreach ($manifest_json['icons'] as $icon) {
                        $icon_exists = file_exists(__DIR__ . $icon['src']);
                        echo "- {$icon['src']} ({$icon['sizes']}) " . 
                             ($icon_exists ? '<span class="status-pass">✅</span>' : '<span class="status-fail">❌ File not found</span>') . 
                             "<br>";
                    }
                } else {
                    echo "<br><span class='status-fail'>❌ No icons defined in manifest</span>";
                }
                echo "</div>";
            } else {
                echo "<div class='status-fail'>❌ Invalid JSON format</div>";
            }
            
            echo "<button onclick='viewManifest()'>View Raw JSON</button>";
            echo "<pre id='rawManifest' style='display:none;'>" . htmlspecialchars($manifest_content) . "</pre>";
        }
        ?>
    </div>
    
    <!-- Service Worker Status -->
    <div class="card">
        <h2>3. Service Worker Registration</h2>
        <div id="sw-status">Checking...</div>
        <button onclick="checkServiceWorker()">Re-check Service Worker</button>
    </div>
    
    <!-- Installability Check -->
    <div class="card">
        <h2>4. Installability Check</h2>
        <div id="install-check">Checking...</div>
        <button onclick="checkInstallability()">Re-check Installability</button>
    </div>
    
    <!-- Browser Info -->
    <div class="card">
        <h2>5. Browser PWA Support</h2>
        <div id="browser-check"></div>
    </div>
    
    <!-- Real-time Event Log -->
    <div class="card">
        <h2>6. Real-time PWA Event Log</h2>
        <div class="log-container" id="eventLog">
            <div class="log-entry log-info">Waiting for PWA events...</div>
        </div>
        <button onclick="clearLogs()">Clear Logs</button>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h2>7. Quick Actions</h2>
        <button onclick="testInstall()">🔧 Test Install Prompt</button>
        <button onclick="clearSW()">🗑️ Clear Service Worker</button>
        <button onclick="reloadPage()">🔄 Hard Reload</button>
        <button onclick="triggerEngagement()">⏱️ Simulate Engagement</button>
    </div>
    
    <script>
        // ============================================
        // EVENT LOGGER
        // ============================================
        function addLog(message, type = 'info') {
            const log = document.getElementById('eventLog');
            const time = new Date().toLocaleTimeString();
            const logClass = `log-${type}`;
            log.innerHTML += `<div class="log-entry ${logClass}">[${time}] ${message}</div>`;
            log.scrollTop = log.scrollHeight;
        }
        
        function clearLogs() {
            document.getElementById('eventLog').innerHTML = '<div class="log-entry log-info">Logs cleared</div>';
        }
        
        // ============================================
        // MANIFEST VIEWER
        // ============================================
        function viewManifest() {
            const raw = document.getElementById('rawManifest');
            raw.style.display = raw.style.display === 'none' ? 'block' : 'none';
        }
        
        // ============================================
        // SERVICE WORKER CHECK
        // ============================================
        async function checkServiceWorker() {
            const status = document.getElementById('sw-status');
            
            if (!('serviceWorker' in navigator)) {
                status.innerHTML = '<div class="check-item fail">❌ Service Workers not supported in this browser</div>';
                addLog('Service Workers NOT supported', 'error');
                return;
            }
            
            addLog('Checking service worker...', 'info');
            
            try {
                const registrations = await navigator.serviceWorker.getRegistrations();
                if (registrations.length > 0) {
                    let html = '<div class="check-item pass">✅ Service Worker Registered!</div>';
                    registrations.forEach(reg => {
                        html += `<div style="margin:5px 0;">
                            <strong>Scope:</strong> ${reg.scope}<br>
                            <strong>State:</strong> ${reg.active ? reg.active.state : 'No active worker'}<br>
                            <strong>Installing:</strong> ${reg.installing ? reg.installing.state : 'None'}<br>
                            <strong>Waiting:</strong> ${reg.waiting ? reg.waiting.state : 'None'}
                        </div>`;
                        addLog(`SW found: scope=${reg.scope}, state=${reg.active ? reg.active.state : 'none'}`, 'success');
                    });
                    status.innerHTML = html;
                } else {
                    status.innerHTML = '<div class="check-item fail">❌ No Service Worker registered.</div>';
                    addLog('No Service Worker found, attempting registration...', 'warning');
                    
                    try {
                        const reg = await navigator.serviceWorker.register('/service-worker.js');
                        status.innerHTML += '<div class="check-item pass">✅ Registration successful! Scope: ' + reg.scope + '</div>';
                        addLog('Service Worker registered successfully', 'success');
                    } catch (err) {
                        status.innerHTML += '<div class="check-item fail">❌ Registration failed: ' + err.message + '</div>';
                        addLog('SW registration failed: ' + err.message, 'error');
                    }
                }
            } catch (err) {
                status.innerHTML = '<div class="check-item fail">❌ Error: ' + err.message + '</div>';
                addLog('Error checking SW: ' + err.message, 'error');
            }
        }
        
        // ============================================
        // INSTALLABILITY CHECK
        // ============================================
        function checkInstallability() {
            const status = document.getElementById('install-check');
            let html = '';
            
            addLog('Running installability check...', 'info');
            
            // Check if already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                html += '<div class="check-item pass">✅ App is already installed!</div>';
                addLog('App is already installed (standalone mode)', 'success');
                status.innerHTML = html;
                return;
            }
            
            // Check manifest link
            const manifestLink = document.querySelector('link[rel="manifest"]');
            if (!manifestLink) {
                html += '<div class="check-item fail">❌ No manifest link found in HTML</div>';
                addLog('No manifest link found', 'error');
            } else {
                html += '<div class="check-item pass">✅ Manifest link found: ' + manifestLink.href + '</div>';
                addLog('Manifest link present: ' + manifestLink.href, 'success');
            }
            
            // Check beforeinstallprompt support
            if ('BeforeInstallPromptEvent' in window) {
                html += '<div class="check-item pass">✅ beforeinstallprompt API supported</div>';
                addLog('beforeinstallprompt API supported', 'success');
            } else {
                html += '<div class="check-item warn">⚠️ beforeinstallprompt not supported</div>';
                addLog('beforeinstallprompt API not supported', 'warning');
            }
            
            // Check HTTPS
            if (window.location.protocol === 'https:') {
                html += '<div class="check-item pass">✅ HTTPS detected</div>';
                addLog('HTTPS detected', 'success');
            } else {
                html += '<div class="check-item fail">❌ Not HTTPS</div>';
                addLog('HTTPS not detected', 'error');
            }
            
            // Check if deferred prompt is available
            if (window.deferredPrompt) {
                html += '<div class="check-item pass">✅ Install prompt is ready! Click the Install button above.</div>';
                addLog('Install prompt is ready (deferredPrompt exists)', 'success');
            } else {
                html += '<div class="check-item warn">⚠️ Install prompt not yet triggered by browser. Engage with the site first.</div>';
                addLog('Install prompt not yet available (needs user engagement)', 'warning');
            }
            
            status.innerHTML = html;
        }
        
        // ============================================
        // TEST INSTALL
        // ============================================
        function testInstall() {
            addLog('Testing install...', 'info');
            
            if (window.deferredPrompt) {
                addLog('Showing install prompt...', 'success');
                window.deferredPrompt.prompt();
                window.deferredPrompt.userChoice.then((result) => {
                    addLog('User choice: ' + result.outcome, result.outcome === 'accepted' ? 'success' : 'warning');
                    if (result.outcome === 'accepted') {
                        document.getElementById('forceInstallBtn').style.display = 'none';
                    }
                });
                window.deferredPrompt = null;
            } else {
                addLog('No install prompt available', 'warning');
                
                if (window.matchMedia('(display-mode: standalone)').matches) {
                    alert('✅ App is already installed!');
                } else {
                    alert('Install prompt not yet available.\n\n' +
                          'Please interact with the site for 30+ seconds:\n' +
                          '- Click around different pages\n' +
                          '- Open the sidebar\n' +
                          '- Scroll through content\n\n' +
                          'Then the install button should appear.');
                }
            }
        }
        
        // ============================================
        // SIMULATE ENGAGEMENT
        // ============================================
        function triggerEngagement() {
            addLog('Simulating user engagement...', 'info');
            
            // Dispatch some user-like events
            document.body.click();
            window.scrollTo(0, 100);
            
            setTimeout(() => {
                window.scrollTo(0, 0);
                addLog('Engagement simulation complete. Check if install prompt appears.', 'success');
                checkInstallability();
            }, 2000);
        }
        
        // ============================================
        // CLEAR SERVICE WORKER
        // ============================================
        async function clearSW() {
            addLog('Clearing service workers...', 'warning');
            
            if ('serviceWorker' in navigator) {
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (let registration of registrations) {
                    await registration.unregister();
                    addLog('Unregistered: ' + registration.scope, 'success');
                }
                addLog('All service workers cleared. Reloading...', 'info');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                addLog('Service Workers not supported', 'error');
            }
        }
        
        // ============================================
        // RELOAD PAGE
        // ============================================
        function reloadPage() {
            addLog('Performing hard reload...', 'info');
            window.location.reload(true);
        }
        
        // ============================================
        // BROWSER CHECK
        // ============================================
        const browserInfo = document.getElementById('browser-check');
        browserInfo.innerHTML = `
            <div style="background:#f8f9fa; padding:15px; border-radius:8px;">
                <strong>User Agent:</strong><br>
                <small>${navigator.userAgent}</small><br><br>
                <strong>Platform:</strong> ${navigator.platform}<br>
                <strong>HTTPS:</strong> ${window.location.protocol === 'https:' ? '<span class="status-pass">✅ Yes</span>' : '<span class="status-fail">❌ No</span>'}<br>
                <strong>Service Worker Support:</strong> ${'serviceWorker' in navigator ? '<span class="status-pass">✅ Yes</span>' : '<span class="status-fail">❌ No</span>'}<br>
                <strong>BeforeInstallPrompt Support:</strong> ${'BeforeInstallPromptEvent' in window ? '<span class="status-pass">✅ Yes</span>' : '<span class="status-fail">❌ No</span>'}<br>
                <strong>Current Display Mode:</strong> ${window.matchMedia('(display-mode: standalone)').matches ? '<span class="status-pass">Standalone (Installed)</span>' : '<span class="status-warn">Browser</span>'}<br>
                <strong>Online Status:</strong> ${navigator.onLine ? '<span class="status-pass">✅ Online</span>' : '<span class="status-fail">❌ Offline</span>'}
            </div>
        `;
        
        addLog(`Browser: ${navigator.userAgent.substring(0, 100)}...`, 'info');
        addLog(`Platform: ${navigator.platform}, HTTPS: ${window.location.protocol === 'https:' ? 'Yes' : 'No'}`, 'info');
        
        // ============================================
        // PWA EVENT LISTENERS
        // ============================================
        
        // Capture beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            addLog('🎉 beforeinstallprompt event fired!', 'success');
            
            // Prevent default browser prompt
            e.preventDefault();
            
            // Store for later use
            window.deferredPrompt = e;
            
            // Show the install button
            const installBtn = document.getElementById('forceInstallBtn');
            installBtn.style.display = 'block';
            document.getElementById('installStatus').innerHTML = 
                '<span class="badge badge-success">Ready to Install!</span>';
            
            addLog('Install button is now visible', 'success');
        });
        
        // Handle app installed
        window.addEventListener('appinstalled', () => {
            addLog('🎉 App was installed successfully!', 'success');
            document.getElementById('forceInstallBtn').style.display = 'none';
            document.getElementById('installStatus').innerHTML = 
                '<span class="badge badge-success">✅ Installed Successfully!</span>';
            alert('✅ BISURE Chat has been installed!');
        });
        
        // Handle display mode change
        window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
            if (e.matches) {
                addLog('App entered standalone mode', 'success');
                document.getElementById('forceInstallBtn').style.display = 'none';
            } else {
                addLog('App exited standalone mode', 'warning');
            }
        });
        
        // ============================================
        // INSTALL BUTTON CLICK
        // ============================================
        document.getElementById('forceInstallBtn').addEventListener('click', () => {
            testInstall();
        });
        
        // ============================================
        // INITIALIZATION
        // ============================================
        window.addEventListener('load', () => {
            addLog('Page loaded, running checks...', 'info');
            
            // Register service worker if not already
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => {
                        addLog(`Service Worker registered: ${reg.scope}`, 'success');
                        
                        // Check for updates
                        reg.addEventListener('updatefound', () => {
                            addLog('Service Worker update found', 'warning');
                        });
                    })
                    .catch(err => {
                        addLog(`Service Worker registration failed: ${err}`, 'error');
                    });
            }
            
            // Run checks
            setTimeout(() => {
                checkServiceWorker();
                checkInstallability();
                
                // If app is already installed, hide button
                if (window.matchMedia('(display-mode: standalone)').matches) {
                    document.getElementById('forceInstallBtn').style.display = 'none';
                    document.getElementById('installStatus').innerHTML = 
                        '<span class="badge badge-success">✅ Already Installed</span>';
                }
            }, 1000);
        });
        
        // Check if deferredPrompt was already captured
        if (window.deferredPrompt) {
            document.getElementById('forceInstallBtn').style.display = 'block';
            addLog('Install prompt already available', 'success');
        }
    </script>
</body>
</html>