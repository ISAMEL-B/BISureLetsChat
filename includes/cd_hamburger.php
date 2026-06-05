<?php

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check dark mode preference
$sidebar_darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Get logged-in user ID
$sidebar_current_user_id = (int)($_SESSION['user_id'] ?? 0);

// ✅ FETCH LATEST USER DATA FROM DATABASE
if ($sidebar_current_user_id > 0) {
    require_once __DIR__ . '/../config/db.php';
    
    $sidebar_stmt = $conn->prepare("SELECT id, username, fullname, email, profile_photo, bio, status_message FROM users WHERE id = ?");
    $sidebar_stmt->bind_param("i", $sidebar_current_user_id);
    $sidebar_stmt->execute();
    $sidebar_result = $sidebar_stmt->get_result();
    $sidebar_user_data = $sidebar_result->fetch_assoc();
    $sidebar_stmt->close();
    
    $_SESSION['user_data'] = $sidebar_user_data;
} else {
    $sidebar_user_data = $_SESSION['user_data'] ?? [];
}

$sidebar_picture_path = '../settings/uploads/profiles/';

if (!empty($sidebar_user_data['profile_photo'])) {
    $sidebar_picture_url = $sidebar_picture_path . htmlspecialchars($sidebar_user_data['profile_photo'], ENT_QUOTES, 'UTF-8');
} else {
    $sidebar_picture_url = '../assets/images/default-profile.png';
}

$sidebar_display_name = $sidebar_user_data['fullname'] ?? $sidebar_user_data['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta name="google" content="notranslate">
    <meta name="format-detection" content="telephone=no">
   
    <title>BISURE Chat</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        /* =============================================
           BSC HAMBURGER SIDEBAR
           ============================================= */
        :root {
            --bsc-sidebar-width: 280px;
            --bsc-safe-area-top: env(safe-area-inset-top, 0px);
            --bsc-safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }

        .bsc-hamburger {
            font-size: 20px;
            cursor: pointer;
            position: relative;
            z-index: 1003;
            color: inherit;
            background: transparent;
            padding: 6px 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            outline: none;
            -webkit-tap-highlight-color: transparent;
        }

        .bsc-hamburger:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .bsc-hamburger:active {
            background: rgba(255, 255, 255, 0.25);
        }

        .bsc-hamburger-icon {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 20px;
            transition: all 0.3s ease;
        }

        .bsc-hamburger-icon span {
            display: block;
            width: 100%;
            height: 2px;
            background-color: currentColor;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .bsc-hamburger.bsc-active .bsc-hamburger-icon span:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }

        .bsc-hamburger.bsc-active .bsc-hamburger-icon span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        .bsc-hamburger.bsc-active .bsc-hamburger-icon span:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        .bsc-hamburger-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            -webkit-backdrop-filter: blur(3px);
            backdrop-filter: blur(3px);
            cursor: pointer;
        }

        .bsc-hamburger-overlay.bsc-active {
            display: block;
            opacity: 1;
        }

        .bsc-sidebar {
            position: fixed;
            top: 0;
            left: calc(-1 * var(--bsc-sidebar-width) - 20px);
            width: var(--bsc-sidebar-width);
            height: 100%;
            height: 100dvh;
            background-color: #FFFFFF;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.25);
            transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10001;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .bsc-sidebar.bsc-active {
            left: 0;
        }

        .bsc-sidebar-close-btn {
            position: absolute;
            top: calc(12px + var(--bsc-safe-area-top));
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #FFFFFF;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
        }

        .bsc-sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: rotate(90deg);
        }

        .bsc-sidebar-close-btn:active {
            background: rgba(255, 255, 255, 0.5);
            transform: rotate(90deg) scale(0.9);
        }

        .bsc-sidebar-header {
            padding: 30px 20px 20px;
            padding-top: calc(30px + var(--bsc-safe-area-top));
            text-align: center;
            background: linear-gradient(135deg, #075E54, #128C7E);
            color: #FFFFFF;
            flex-shrink: 0;
            position: relative;
        }

        .bsc-sidebar-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, rgba(7, 94, 84, 0.3), transparent);
        }

        .bsc-sidebar-profile-pic {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 10px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .bsc-sidebar-profile-name {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .bsc-sidebar-profile-status {
            font-size: 12px;
            opacity: 0.8;
        }

        .bsc-sidebar-menu {
            list-style-type: none;
            padding: 10px 0;
            margin: 0;
            overflow-y: auto;
            flex-grow: 1;
            -webkit-overflow-scrolling: touch;
        }

        .bsc-sidebar-menu li {
            padding: 14px 24px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #333;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 15px;
            position: relative;
            border-left: 3px solid transparent;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }

        .bsc-sidebar-menu li:hover {
            background-color: #f5f5f5;
            border-left-color: #128C7E;
        }

        .bsc-sidebar-menu li:active {
            background-color: #e8e8e8;
        }

        .bsc-sidebar-menu li i {
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #666;
            transition: color 0.2s ease;
        }

        .bsc-sidebar-menu li:hover i {
            color: #128C7E;
        }

        .bsc-sidebar-menu li .bsc-menu-text {
            flex: 1;
        }

        .bsc-sidebar-menu li .bsc-menu-badge {
            background: #25D366;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        .bsc-sidebar-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 8px 20px;
        }

        /* =============================================
           INSTALL BUTTON - HIGHLIGHTED
           ============================================= */
        #bscInstallApp {
            background: linear-gradient(135deg, #1F6B3D, #2E8B57) !important;
            color: white !important;
            border-radius: 8px;
            margin: 8px 12px;
            font-weight: 600;
            border-left: none !important;
            animation: bscPulseInstall 2s ease-in-out infinite;
        }

        @keyframes bscPulseInstall {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
            }
        }

        #bscInstallApp:hover {
            background: linear-gradient(135deg, #0056b3, #003d80) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
            animation: none;
        }

        #bscInstallApp i {
            color: white !important;
        }

        /* Logout item */
        .bsc-sidebar-menu li.bsc-logout-item {
            margin-top: auto;
            border-top: 1px solid #e0e0e0;
            color: #e74c3c;
        }

        .bsc-sidebar-menu li.bsc-logout-item i {
            color: #e74c3c;
        }

        .bsc-sidebar-menu li.bsc-logout-item:hover {
            background-color: #fff5f5;
            border-left-color: #e74c3c;
        }

        .bsc-sidebar-footer {
            padding: 12px 20px;
            padding-bottom: calc(12px + var(--bsc-safe-area-bottom));
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        /* Dark Mode */
        body.dark-mode .bsc-sidebar {
            background-color: #1F2C33;
        }

        body.dark-mode .bsc-sidebar-header {
            background: linear-gradient(135deg, #0A1F1A, #1A3A34);
        }

        body.dark-mode .bsc-sidebar-close-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #E9EDEF;
        }

        body.dark-mode .bsc-sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        body.dark-mode .bsc-sidebar-menu li {
            color: #E9EDEF;
        }

        body.dark-mode .bsc-sidebar-menu li:hover {
            background-color: #2A3942;
            border-left-color: #25D366;
        }

        body.dark-mode .bsc-sidebar-menu li:active {
            background-color: #374248;
        }

        body.dark-mode .bsc-sidebar-menu li i {
            color: #8696A0;
        }

        body.dark-mode .bsc-sidebar-menu li:hover i {
            color: #25D366;
        }

        body.dark-mode .bsc-sidebar-divider {
            background: #2A3942;
        }

        body.dark-mode .bsc-sidebar-footer {
            color: #8696A0;
            border-top-color: #2A3942;
        }

        body.dark-mode .bsc-sidebar-menu li.bsc-logout-item {
            border-top-color: #2A3942;
        }

        body.dark-mode .bsc-sidebar-menu li.bsc-logout-item:hover {
            background-color: #2A1A1A;
        }

        body.dark-mode .bsc-hamburger-overlay {
            background: rgba(0, 0, 0, 0.8);
        }

        body.dark-mode #bscInstallApp {
            background: linear-gradient(135deg, #25D366, #128C7E) !important;
        }

        body.dark-mode #bscInstallApp:hover {
            background: linear-gradient(135deg, #128C7E, #25D366) !important;
        }

        /* Responsive */
        @media (max-width: 480px) {
            :root {
                --bsc-sidebar-width: 260px;
            }

            .bsc-sidebar-header {
                padding: 25px 16px 16px;
                padding-top: calc(25px + var(--bsc-safe-area-top));
            }

            .bsc-sidebar-close-btn {
                top: calc(8px + var(--bsc-safe-area-top));
                right: 8px;
                width: 32px;
                height: 32px;
                font-size: 16px;
            }

            .bsc-sidebar-profile-pic {
                width: 60px;
                height: 60px;
            }

            .bsc-sidebar-profile-name {
                font-size: 16px;
            }

            .bsc-sidebar-menu li {
                padding: 12px 20px;
                font-size: 14px;
                gap: 12px;
            }

            .bsc-sidebar-menu li i {
                font-size: 16px;
                width: 20px;
            }
        }

        @media (max-width: 360px) {
            :root {
                --bsc-sidebar-width: 240px;
            }

            .bsc-sidebar-menu li {
                padding: 11px 16px;
                font-size: 13px;
            }

            .bsc-sidebar-close-btn {
                width: 28px;
                height: 28px;
                font-size: 14px;
            }
        }

        @media (min-width: 1024px) {
            :root {
                --bsc-sidebar-width: 300px;
            }

            .bsc-sidebar-menu li {
                padding: 16px 28px;
                font-size: 16px;
            }

            .bsc-sidebar-close-btn {
                top: calc(16px + var(--bsc-safe-area-top));
                right: 16px;
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
    </style>
</head>

<body class="<?= $sidebar_darkMode ? 'dark-mode' : '' ?>">

<button class="bsc-hamburger" id="bscHamburger" aria-label="Toggle menu">
    <span class="bsc-hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </span>
</button>

<div class="bsc-hamburger-overlay" id="bscHamburgerOverlay"></div>

<aside class="bsc-sidebar" id="bscSidebar" aria-label="Navigation sidebar">

    <button class="bsc-sidebar-close-btn" id="bscSidebarCloseBtn" aria-label="Close menu">
        <i class="fas fa-times"></i>
    </button>

    <div class="bsc-sidebar-header">
        <img src="<?php echo $sidebar_picture_url; ?>"
            alt="Profile Picture"
            class="bsc-sidebar-profile-pic"
            onerror="this.onerror=null; this.src='../assets/images/default-profile.png';">
        <div class="bsc-sidebar-profile-name">
            <?php echo htmlspecialchars($sidebar_display_name); ?>
        </div>
        <div class="bsc-sidebar-profile-status">
            <i class="fas fa-circle" style="color: #25D366; font-size: 8px;"></i> Online
        </div>
    </div>

    <ul class="bsc-sidebar-menu">
        <li onclick="bscGoTo('/chat/contacts')">
            <i class="fas fa-comment-dots"></i>
            <span class="bsc-menu-text">Chats</span>
        </li>

        <li onclick="bscGoTo('/groups/my_groups')">
            <i class="fas fa-users"></i>
            <span class="bsc-menu-text">Groups</span>
        </li>

        <li onclick="bscGoTo('/calls/call')">
            <i class="fas fa-phone"></i>
            <span class="bsc-menu-text">Calls</span>
        </li>

        <div class="bsc-sidebar-divider"></div>

        <!-- INSTALL BUTTON - Always visible until installed -->
        <li id="bscInstallApp" onclick="handleInstallClick(event)" style="display:none;">
            <i class="fas fa-download"></i>
            <span class="bsc-menu-text">Install BISURE Chat</span>
            <span class="bsc-menu-badge">Free</span>
        </li>

        <div class="bsc-sidebar-divider"></div>

        <li onclick="bscGoTo('/settings/my_profile')">
            <i class="fas fa-user-circle"></i>
            <span class="bsc-menu-text">Profile</span>
        </li>

        <li onclick="bscGoTo('/settings/settings')">
            <i class="fas fa-cog"></i>
            <span class="bsc-menu-text">Settings</span>
        </li>

        <div class="bsc-sidebar-divider"></div>

        <li onclick="bscGoTo('/auth/forgot/forgot_password')">
            <i class="fas fa-key"></i>
            <span class="bsc-menu-text">Change Password</span>
        </li>

        <li onclick="bscGoTo('/more/terms_of_use')">
            <i class="fas fa-file-contract"></i>
            <span class="bsc-menu-text">Terms of Use</span>
        </li>

        <li onclick="bscGoTo('/more/privacy_and_policy')">
            <i class="fas fa-user-shield"></i>
            <span class="bsc-menu-text">Privacy Policy</span>
        </li>

        <li onclick="bscGoTo('/inquiries/contact_us')">
            <i class="fas fa-question-circle"></i>
            <span class="bsc-menu-text">Contact Us</span>
        </li>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li onclick="bscGoTo('/admin/pages/dashboard')">
            <i class="fas fa-headset"></i>
            <span class="bsc-menu-text">Admin Pannel</span>
        </li>
        <?php endif; ?>
        
        <li onclick="bscGoTo('/auth/logout.php')" class="bsc-logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span class="bsc-menu-text">Logout</span>
        </li>
    </ul>

    <div class="bsc-sidebar-footer">
        &copy; <?php echo date('Y'); ?> BisureChat
    </div>

</aside>

<script>
    // =========================
    // DYNAMIC PROJECT BASE URL
    // =========================
    (function() {
        const projectFolder = window.location.pathname
            .split('/')
            .slice(0, 1)
            .join('/');

        const baseUrl = window.location.origin + projectFolder;

        window.bscGoTo = function(path) {
            const globalPages = ['/mailing/', '/register/'];
            const isGlobal = globalPages.some(globalPath => path.startsWith(globalPath));

            if (isGlobal) {
                window.location.href = window.location.origin + path;
                return;
            }

            window.location.href = baseUrl + path;
        };

        // =========================
        // SIDEBAR ELEMENTS
        // =========================
        const bscSidebar = document.getElementById('bscSidebar');
        const bscHamburgerOverlay = document.getElementById('bscHamburgerOverlay');
        const bscHamburger = document.getElementById('bscHamburger');
        const bscSidebarCloseBtn = document.getElementById('bscSidebarCloseBtn');

        function bscOpenSidebar() {
            bscSidebar.classList.add('bsc-active');
            bscHamburgerOverlay.classList.add('bsc-active');
            bscHamburger.classList.add('bsc-active');
            document.body.style.overflow = 'hidden';
        }

        function bscCloseSidebar() {
            bscSidebar.classList.remove('bsc-active');
            bscHamburgerOverlay.classList.remove('bsc-active');
            bscHamburger.classList.remove('bsc-active');
            document.body.style.overflow = '';
        }

        bscHamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (bscSidebar.classList.contains('bsc-active')) {
                bscCloseSidebar();
            } else {
                bscOpenSidebar();
            }
        });

        bscSidebarCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            bscCloseSidebar();
        });

        bscHamburgerOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
            bscCloseSidebar();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && bscSidebar.classList.contains('bsc-active')) {
                bscCloseSidebar();
            }
        });

        let bscTouchStartX = 0;
        let bscTouchStartY = 0;

        bscSidebar.addEventListener('touchstart', function(e) {
            bscTouchStartX = e.changedTouches[0].screenX;
            bscTouchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        bscSidebar.addEventListener('touchend', function(e) {
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;
            const dx = bscTouchStartX - touchEndX;
            const dy = Math.abs(touchEndY - bscTouchStartY);

            if (dx > 60 && dy < dx * 0.5) {
                bscCloseSidebar();
            }
        });

        if (window.innerWidth < 768) {
            document.querySelectorAll('.bsc-sidebar-menu li').forEach(item => {
                item.addEventListener('click', function() {
                    setTimeout(bscCloseSidebar, 150);
                });
            });
        }

        bscSidebar.addEventListener('touchmove', function(e) {
            const menu = bscSidebar.querySelector('.bsc-sidebar-menu');
            if (menu && !menu.contains(e.target)) {
                e.preventDefault();
            }
        }, { passive: false });

    })();

    // ============================================
    // ENHANCED PWA INSTALL HANDLER
    // ============================================
    (function() {
        let deferredPrompt = null;
        let hasUserInteracted = false;
        let installButtonVisible = false;
        let detectionInterval = null;
        
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js', {
                    scope: '/'
                })
                .then(function(registration) {
                    console.log('✅ ServiceWorker registered:', registration.scope);
                    
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('🔄 Service Worker update found!');
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('🆕 New version available - will update on next load');
                                showUpdateBanner();
                            }
                        });
                    });
                })
                .catch(function(err) {
                    console.error('❌ ServiceWorker registration failed:', err);
                });
            });
        }

        // ⭐ IMPROVED: Multiple ways to detect if app is installed
        function isAppInstalled() {
            // Method 1: Standalone display mode (most reliable)
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('✅ Detected: standalone mode');
                return true;
            }
            
            // Method 2: Fullscreen display mode
            if (window.matchMedia('(display-mode: fullscreen)').matches) {
                console.log('✅ Detected: fullscreen mode');
                return true;
            }
            
            // Method 3: Minimal-ui display mode
            if (window.matchMedia('(display-mode: minimal-ui)').matches) {
                console.log('✅ Detected: minimal-ui mode');
                return true;
            }
            
            // Method 4: iOS standalone
            if (window.navigator.standalone === true) {
                console.log('✅ Detected: iOS standalone');
                return true;
            }
            
            // Method 5: Check if launched from app shortcut (Android)
            if (document.referrer.includes('android-app://')) {
                console.log('✅ Detected: Android app referrer');
                return true;
            }
            
            // ⭐ Method 6: Check localStorage ONLY if not in browser tab
            // Only trust localStorage if we're not in a regular browser
            if (window.matchMedia('(display-mode: browser)').matches === false) {
                try {
                    if (localStorage.getItem('pwa_installed') === 'true') {
                        console.log('✅ Detected: localStorage flag (not in browser mode)');
                        return true;
                    }
                } catch(e) {}
            }
            
            console.log('❌ App is NOT installed (browser tab mode)');
            return false;
        }

        // ⭐ CLEAR localStorage when uninstalled
        function clearInstallState() {
            try {
                localStorage.removeItem('pwa_installed');
                localStorage.removeItem('pwa_install_date');
                console.log('🧹 Cleared install state from localStorage');
            } catch(e) {}
        }

        // Save installation state
        function markAsInstalled() {
            try {
                localStorage.setItem('pwa_installed', 'true');
                localStorage.setItem('pwa_install_date', new Date().toISOString());
                console.log('💾 Saved install state to localStorage');
            } catch(e) {}
        }

        // ⭐ IMPROVED: Force button check without relying on localStorage
        function updateInstallButton() {
            const installBtn = document.getElementById('bscInstallApp');
            if (!installBtn) return;
            
            const installed = isAppInstalled();
            
            if (installed) {
                // App IS installed - hide button
                installBtn.style.display = 'none';
                installButtonVisible = false;
                clearInstallState(); // Clear localStorage if we're somehow in browser
                console.log('📱 Install button HIDDEN (app is installed)');
            } else {
                // App NOT installed - show button if we have prompt
                if (deferredPrompt || hasUserInteracted) {
                    installBtn.style.display = 'flex';
                    installButtonVisible = true;
                    console.log('📱 Install button SHOWN (app not installed)');
                } else {
                    console.log('⏳ Waiting for beforeinstallprompt event...');
                    // Button will show when event fires
                }
            }
        }

        // ⭐ Listen for install prompt - THIS IS CRITICAL
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('🎯 beforeinstallprompt EVENT FIRED!');
            
            // Prevent Chrome from automatically showing prompt
            e.preventDefault();
            
            // Store the prompt
            deferredPrompt = e;
            
            // Log available platforms
            console.log('📱 Installation platforms:', e.platforms);
            
            // Show button immediately
            const installBtn = document.getElementById('bscInstallApp');
            if (installBtn && !isAppInstalled()) {
                installBtn.style.display = 'flex';
                installButtonVisible = true;
                console.log('📱 Install button shown (beforeinstallprompt fired)');
            }
        });

        // ⭐ Listen for successful installation
        window.addEventListener('appinstalled', () => {
            console.log('🎉 App was installed successfully!');
            
            // Clear the prompt
            deferredPrompt = null;
            
            // Mark as installed
            markAsInstalled();
            
            // Hide button with animation
            const installBtn = document.getElementById('bscInstallApp');
            if (installBtn) {
                installBtn.style.transition = 'all 0.3s ease';
                installBtn.style.opacity = '0';
                installBtn.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    installBtn.style.display = 'none';
                    installButtonVisible = false;
                }, 300);
            }
            
            // Track installation
            if (typeof gtag === 'function') {
                gtag('event', 'pwa_install', {
                    event_category: 'engagement',
                    event_label: 'PWA Installed'
                });
            }
        });

        // ⭐ Monitor display mode changes (detects uninstall)
        const displayModeQuery = window.matchMedia('(display-mode: standalone)');
        displayModeQuery.addEventListener('change', (evt) => {
            console.log('🔄 Display mode changed:', evt.matches ? 'standalone' : 'browser');
            
            if (evt.matches) {
                // Switched TO standalone mode (installed)
                markAsInstalled();
                updateInstallButton();
            } else {
                // Switched TO browser mode (uninstalled)
                clearInstallState();
                deferredPrompt = null; // Reset prompt
                updateInstallButton();
                
                // Force re-check after a delay
                setTimeout(() => {
                    updateInstallButton();
                    console.log('🔍 Re-checked after display mode change');
                }, 1000);
            }
        });

        // ⭐ Handle install button click
        window.handleInstallClick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const installBtn = document.getElementById('bscInstallApp');
            hasUserInteracted = true;
            
            if (deferredPrompt) {
                console.log('📲 Showing browser install prompt...');
                
                // Show loading state
                if (installBtn) {
                    const originalHTML = installBtn.innerHTML;
                    installBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span class="bsc-menu-text">Installing...</span>';
                }
                
                // ⭐ TRIGGER THE BROWSER INSTALL PROMPT
                deferredPrompt.prompt();
                
                // Wait for user choice
                deferredPrompt.userChoice.then((choiceResult) => {
                    console.log('👤 User choice:', choiceResult.outcome);
                    
                    if (choiceResult.outcome === 'accepted') {
                        console.log('✅ User accepted install');
                        markAsInstalled();
                        
                        if (installBtn) {
                            installBtn.style.display = 'none';
                            installButtonVisible = false;
                        }
                    } else {
                        console.log('❌ User dismissed install');
                        
                        // Reset button
                        if (installBtn) {
                            installBtn.innerHTML = '<i class="fas fa-download"></i><span class="bsc-menu-text">Install BISURE Chat</span><span class="bsc-menu-badge">Free</span>';
                        }
                    }
                    
                    // Clear prompt (can only be used once)
                    deferredPrompt = null;
                });
                
            } else {
                // No prompt available - show manual instructions
                console.log('ℹ️ No install prompt available, showing manual instructions');
                showInstallInstructions();
            }
        };

        // Manual install instructions
        function showInstallInstructions() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            const isChrome = /Chrome/.test(navigator.userAgent);
            const isEdge = /Edg/.test(navigator.userAgent);
            const isSafari = /Safari/.test(navigator.userAgent) && !isChrome;
            
            let message = '';
            let title = '📱 Install BISURE Chat';
            
            if (isIOS && isSafari) {
                message = 'To install BISURE Chat:\n\n' +
                         '1️⃣ Tap the Share button (📤) at bottom\n' +
                         '2️⃣ Scroll down and tap "Add to Home Screen"\n' +
                         '3️⃣ Tap "Add" in top right corner\n\n' +
                         '🔔 The app will appear on your home screen!';
                         
            } else if (isAndroid && isChrome) {
                message = 'To install BISURE Chat:\n\n' +
                         '1️⃣ Tap the 3-dot menu (⋮) at top right\n' +
                         '2️⃣ Select "Install app" or "Add to Home Screen"\n' +
                         '3️⃣ Tap "Install" in the popup\n\n' +
                         '🔔 The app will appear in your app drawer!';
                         
            } else if (isEdge) {
                message = 'To install BISURE Chat:\n\n' +
                         '1️⃣ Click the 3-dot menu (⋯) at top right\n' +
                         '2️⃣ Go to "Apps" → "Install this site as an app"\n' +
                         '3️⃣ Click "Install"\n\n' +
                         '🔔 Access from desktop or start menu!';
                         
            } else if (isChrome) {
                message = 'To install BISURE Chat:\n\n' +
                         '1️⃣ Look for the Install icon (⊕) in address bar\n' +
                         '   OR\n' +
                         '2️⃣ Click 3-dot menu (⋮) → "Cast, save & share"\n' +
                         '3️⃣ Select "Install page as app..."\n\n' +
                         '🔔 Use BISURE Chat as a desktop app!';
                         
            } else {
                message = 'To install BISURE Chat:\n\n' +
                         '📱 Mobile: Use Chrome or Safari\n' +
                         '💻 Desktop: Use Chrome or Edge\n\n' +
                         'Look for "Install app" in your browser menu\n' +
                         'or the Install icon (⊕) in the address bar.';
            }
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                animation: fadeIn 0.3s ease;
                padding: 20px;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 24px;
                    border-radius: 16px;
                    max-width: 400px;
                    width: 100%;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    animation: slideUp 0.3s ease;
                ">
                    <h3 style="margin: 0 0 16px 0; color: #075E54; font-size: 18px;">
                        ${title}
                    </h3>
                    <div style="white-space: pre-line; line-height: 1.6; color: #333; margin-bottom: 20px;">
                        ${message}
                    </div>
                    <button onclick="this.closest('[style*=fixed]').remove()" style="
                        width: 100%;
                        padding: 12px;
                        background: #075E54;
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                    ">
                        Got it!
                    </button>
                </div>
            `;
            
            const styleSheet = document.createElement('style');
            styleSheet.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            modal.appendChild(styleSheet);
            
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Update banner
        function showUpdateBanner() {
            const banner = document.createElement('div');
            banner.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #075E54;
                color: white;
                padding: 12px 20px;
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 14px;
                animation: slideDown 0.3s ease;
            `;
            banner.innerHTML = `
                <span>🔄 New version available!</span>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #075E54;
                    border: none;
                    padding: 6px 16px;
                    border-radius: 15px;
                    font-weight: 600;
                    cursor: pointer;
                ">Update</button>
            `;
            
            const styleSheet = document.createElement('style');
            styleSheet.textContent = `
                @keyframes slideDown {
                    from { transform: translateY(-100%); }
                    to { transform: translateY(0); }
                }
            `;
            document.head.appendChild(styleSheet);
            document.body.appendChild(banner);
        }

        // ⭐ Initialize - THIS RUNS ON EVERY PAGE LOAD
        function init() {
            console.log('🔍 Checking PWA installation status...');
            console.log('📊 Display mode:', window.matchMedia('(display-mode: standalone)').matches ? 'standalone' : 'browser');
            console.log('📊 iOS standalone:', window.navigator.standalone || false);
            
            // ⭐ ALWAYS check actual display mode first
            if (isAppInstalled()) {
                // App is installed - hide button
                const installBtn = document.getElementById('bscInstallApp');
                if (installBtn) {
                    installBtn.style.display = 'none';
                    installButtonVisible = false;
                }
                console.log('✅ App installed - button hidden');
            } else {
                // App is NOT installed - clear any stale state
                clearInstallState();
                deferredPrompt = null;
                
                // Show button immediately (will be updated when prompt fires)
                const installBtn = document.getElementById('bscInstallApp');
                if (installBtn) {
                    installBtn.style.display = 'flex';
                    installButtonVisible = true;
                }
                console.log('📱 App NOT installed - button shown');
            }
            
            // ⭐ CONTINUOUS CHECK: Re-check every 2 seconds for changes
            if (detectionInterval) {
                clearInterval(detectionInterval);
            }
            
            detectionInterval = setInterval(() => {
                const installed = isAppInstalled();
                const installBtn = document.getElementById('bscInstallApp');
                
                if (installBtn) {
                    if (installed && installButtonVisible) {
                        // Was showing, now installed
                        installBtn.style.display = 'none';
                        installButtonVisible = false;
                        clearInstallState();
                        console.log('🔄 Detected installation - hiding button');
                    } else if (!installed && !installButtonVisible && deferredPrompt) {
                        // Was hidden, now uninstalled
                        installBtn.style.display = 'flex';
                        installButtonVisible = true;
                        console.log('🔄 Detected uninstall - showing button');
                    }
                }
            }, 2000);
        }

        // Run init
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        // Extra check after full page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                updateInstallButton();
                console.log('🔍 Final check after full page load');
            }, 1000);
            
            setTimeout(() => {
                updateInstallButton();
                console.log('🔍 Second check after 3 seconds');
            }, 3000);
        });
        
        // ⭐ Check when page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                console.log('👁️ Page visible again - rechecking install state');
                updateInstallButton();
            }
        });

    })();
</script>

</body>
</html>