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
    // Include database connection
    require_once __DIR__ . '/../config/db.php';
    
    // Fetch latest user data
    $sidebar_stmt = $conn->prepare("SELECT id, username, fullname, email, profile_photo, bio, status_message FROM users WHERE id = ?");
    $sidebar_stmt->bind_param("i", $sidebar_current_user_id);
    $sidebar_stmt->execute();
    $sidebar_result = $sidebar_stmt->get_result();
    $sidebar_user_data = $sidebar_result->fetch_assoc();
    $sidebar_stmt->close();
    
    // Also update session data for other pages to use
    $_SESSION['user_data'] = $sidebar_user_data;
} else {
    $sidebar_user_data = $_SESSION['user_data'] ?? [];
}

// ✅ Using relative path for sidebar (includes/ folder to settings/uploads/profiles/)
$sidebar_picture_path = '../settings/uploads/profiles/';

// Profile picture
if (!empty($sidebar_user_data['profile_photo'])) {
    $sidebar_picture_url = $sidebar_picture_path . htmlspecialchars($sidebar_user_data['profile_photo'], ENT_QUOTES, 'UTF-8');
} else {
    $sidebar_picture_url = '../assets/images/default-profile.png';
}

// Get display name
$sidebar_display_name = $sidebar_user_data['fullname'] ?? $sidebar_user_data['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta name="google" content="notranslate">
    <meta name="format-detection" content="telephone=no">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BISURE Chat">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    
    <title>Hamburger Menu</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        /* =============================================
           BSC HAMBURGER SIDEBAR - PREFIXED CLASSES
           All classes use 'bsc-' prefix to avoid collisions
           ============================================= */
        :root {
            --bsc-sidebar-width: 280px;
            --bsc-safe-area-top: env(safe-area-inset-top, 0px);
            --bsc-safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* =============================================
           HAMBURGER ICON
           ============================================= */
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

        /* Hamburger icon lines */
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

        /* Animated hamburger when active */
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

        /* =============================================
           SIDEBAR OVERLAY
           ============================================= */
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

        /* =============================================
           SIDEBAR - ABOVE NAVBAR
           ============================================= */
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

        /* =============================================
           CLOSE (X) BUTTON INSIDE SIDEBAR
           ============================================= */
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

        /* =============================================
           SIDEBAR HEADER / PROFILE SECTION
           ============================================= */
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

        /* =============================================
           SIDEBAR MENU ITEMS
           ============================================= */
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

        /* Divider */
        .bsc-sidebar-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 8px 20px;
        }

        /* Install App Button */
        #bscInstallApp {
            background: linear-gradient(135deg, #128C7E, #075E54) !important;
            color: white !important;
            border-radius: 8px;
            margin: 8px 12px;
            transition: all 0.3s ease;
            border-left: none !important;
            animation: bscPulseInstall 2s ease-in-out infinite;
        }

        @keyframes bscPulseInstall {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(18, 140, 126, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(18, 140, 126, 0);
            }
        }

        #bscInstallApp:hover {
            background: linear-gradient(135deg, #075E54, #128C7E) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(18, 140, 126, 0.3);
            animation: none;
        }

        #bscInstallApp i {
            color: white !important;
        }

        #bscInstallApp .bsc-menu-text {
            font-weight: 600;
        }

        #bscInstallApp .bsc-menu-badge {
            background: rgba(255, 255, 255, 0.3);
            font-size: 10px;
            padding: 2px 6px;
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

        /* =============================================
           SIDEBAR FOOTER
           ============================================= */
        .bsc-sidebar-footer {
            padding: 12px 20px;
            padding-bottom: calc(12px + var(--bsc-safe-area-bottom));
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        /* =============================================
           DARK MODE
           ============================================= */
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

        /* =============================================
           RESPONSIVE
           ============================================= */
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

<!-- Hamburger Button -->
<button class="bsc-hamburger" id="bscHamburger" aria-label="Toggle menu">
    <span class="bsc-hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
    </span>
</button>

<!-- Overlay (Click outside to close) -->
<div class="bsc-hamburger-overlay" id="bscHamburgerOverlay"></div>

<!-- Sidebar -->
<aside class="bsc-sidebar" id="bscSidebar" aria-label="Navigation sidebar">

    <!-- CLOSE (X) BUTTON -->
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

    <!-- Menu Items -->
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

        <li onclick="bscGoTo('/settings/my_profile')">
            <i class="fas fa-user-circle"></i>
            <span class="bsc-menu-text">Profile</span>
        </li>

        <li onclick="bscGoTo('/settings/settings')">
            <i class="fas fa-cog"></i>
            <span class="bsc-menu-text">Settings</span>
        </li>

        <div class="bsc-sidebar-divider"></div>

        <!-- Install App Button (Dynamic) -->
        <li id="bscInstallApp" style="display:none;">
            <i class="fas fa-download"></i>
            <span class="bsc-menu-text">Install BISURE Chat</span>
            <span class="bsc-menu-badge">New</span>
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
        <li onclick="bscGoTo('/inquiries/answer_inquiries')">
            <i class="fas fa-headset"></i>
            <span class="bsc-menu-text">Admin Chat Help</span>
        </li>
        <?php endif; ?>
        
        <li onclick="bscGoTo('/auth/logout.php')" class="bsc-logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span class="bsc-menu-text">Logout</span>
        </li>
    </ul>

    <!-- Footer -->
    <div class="bsc-sidebar-footer">
        &copy; <?php echo date('Y'); ?> BisureChat
    </div>

</aside>

<script>
    (function() {
        // =========================
        // DYNAMIC PROJECT BASE URL
        // =========================
        const projectFolder = window.location.pathname
            .split('/')
            .slice(0, 1)
            .join('/');

        const baseUrl = window.location.origin + projectFolder;

        // =========================
        // NAMESPACED NAVIGATION FUNCTION
        // =========================
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
        // SIDEBAR ELEMENTS (PREFIXED IDs)
        // =========================
        const bscSidebar = document.getElementById('bscSidebar');
        const bscHamburgerOverlay = document.getElementById('bscHamburgerOverlay');
        const bscHamburger = document.getElementById('bscHamburger');
        const bscSidebarCloseBtn = document.getElementById('bscSidebarCloseBtn');

        // =========================
        // TOGGLE FUNCTIONS
        // =========================
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

        // =========================
        // OPEN WITH HAMBURGER
        // =========================
        bscHamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (bscSidebar.classList.contains('bsc-active')) {
                bscCloseSidebar();
            } else {
                bscOpenSidebar();
            }
        });

        // =========================
        // CLOSE WITH X BUTTON
        // =========================
        bscSidebarCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            bscCloseSidebar();
        });

        // =========================
        // CLOSE BY CLICKING OUTSIDE (OVERLAY)
        // =========================
        bscHamburgerOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
            bscCloseSidebar();
        });

        // =========================
        // CLOSE WITH ESC KEY
        // =========================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && bscSidebar.classList.contains('bsc-active')) {
                bscCloseSidebar();
            }
        });

        // =========================
        // SWIPE LEFT TO CLOSE (Mobile)
        // =========================
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

        // =========================
        // CLOSE ON MENU ITEM CLICK (Mobile only)
        // =========================
        if (window.innerWidth < 768) {
            document.querySelectorAll('.bsc-sidebar-menu li').forEach(item => {
                item.addEventListener('click', function() {
                    setTimeout(bscCloseSidebar, 150);
                });
            });
        }

        // =========================
        // PREVENT BODY SCROLL WHEN SIDEBAR IS OPEN
        // =========================
        bscSidebar.addEventListener('touchmove', function(e) {
            const menu = bscSidebar.querySelector('.bsc-sidebar-menu');
            if (menu && !menu.contains(e.target)) {
                e.preventDefault();
            }
        }, { passive: false });

    })();
    
    // ============================================
    // BISURE CHAT PWA INSTALL + SERVICE WORKER
    // ============================================

    (() => {
        let deferredPrompt = null;
        const installBtn = document.getElementById('bscInstallApp');
        
        if (!installBtn) {
            console.warn('Install button not found');
            return;
        }

        // Hide initially
        installBtn.style.display = 'none';
        
        // Detect platform
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isAndroid = /Android/.test(navigator.userAgent);
        const isDesktop = !isIOS && !isAndroid && !/Mobile|Tablet/.test(navigator.userAgent);

        // Check if already installed
        function isInstalled() {
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return true;
            }
            if (window.navigator.standalone === true) {
                return true;
            }
            // Check if launched from home screen on Android
            if (document.referrer.includes('android-app://')) {
                return true;
            }
            return false;
        }

        // Register Service Worker
        async function registerServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                console.warn('Service Worker not supported');
                return null;
            }

            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js', {
                    scope: '/'
                });
                console.log('✅ Service Worker Registered:', registration.scope);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('New service worker available');
                        }
                    });
                });
                
                return registration;
            } catch (error) {
                console.error('❌ Service Worker Registration Failed:', error);
                return null;
            }
        }

        // Show install instructions based on platform
        function showInstallInstructions() {
            let message = '';
            let title = '📱 Install BISURE Chat';
            
            if (isDesktop) {
                message = 'To install BISURE Chat on your computer:\n\n' +
                         'Chrome/Edge:\n' +
                         '• Click the Install icon (⊕) in the address bar\n' +
                         '• Or click ⋮ menu → "Install BISURE Chat..."\n\n' +
                         'Firefox:\n' +
                         '• Click the ⊕ icon in the address bar\n\n' +
                         'Safari:\n' +
                         '• File → Add to Dock';
            } else if (isAndroid) {
                message = 'To install BISURE Chat on your Android:\n\n' +
                         'Chrome:\n' +
                         '• Tap ⋮ menu → "Install app"\n\n' +
                         'Samsung Internet:\n' +
                         '• Tap ☰ menu → "Add page to" → "Home screen"\n\n' +
                         'Firefox:\n' +
                         '• Tap ⋮ menu → "Install"';
            } else if (isIOS) {
                message = 'To install BISURE Chat on your iPhone/iPad:\n\n' +
                         'Safari:\n' +
                         '• Tap the Share button (📤)\n' +
                         '• Scroll down and tap "Add to Home Screen"\n' +
                         '• Tap "Add" in the top right corner\n\n' +
                         'The app will appear on your home screen!';
            }
            
            if (message) {
                // Create a styled dialog instead of alert
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.8);
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                `;
                
                const dialog = document.createElement('div');
                dialog.style.cssText = `
                    background: white;
                    border-radius: 16px;
                    padding: 24px;
                    max-width: 400px;
                    width: 100%;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    animation: slideUp 0.3s ease-out;
                `;
                
                dialog.innerHTML = `
                    <style>
                        @keyframes slideUp {
                            from {
                                opacity: 0;
                                transform: translateY(20px);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                    </style>
                    <div style="text-align: center; font-size: 40px; margin-bottom: 16px;">📱</div>
                    <h3 style="margin: 0 0 16px 0; font-size: 18px; color: #333;">Install BISURE Chat</h3>
                    <div style="white-space: pre-line; color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">${message}</div>
                    <button style="
                        width: 100%;
                        padding: 12px;
                        background: #128C7E;
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                    ">Got it!</button>
                `;
                
                overlay.appendChild(dialog);
                document.body.appendChild(overlay);
                
                // Close on button click
                dialog.querySelector('button').addEventListener('click', () => {
                    overlay.remove();
                });
                
                // Close on overlay click
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        overlay.remove();
                    }
                });
            }
        }

        // Handle install button click
        installBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation(); // Prevent sidebar from closing
            
            // For Chrome/Edge with beforeinstallprompt
            if (deferredPrompt) {
                try {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    
                    console.log(`User response to install prompt: ${outcome}`);
                    
                    if (outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        installBtn.style.display = 'none';
                    } else {
                        console.log('User dismissed the install prompt');
                        // Show manual instructions as fallback
                        setTimeout(() => showInstallInstructions(), 500);
                    }
                    
                    deferredPrompt = null;
                    return;
                } catch (err) {
                    console.error('Install prompt failed:', err);
                }
            }
            
            // For other browsers/platforms, show instructions
            showInstallInstructions();
        });

        // Capture beforeinstallprompt event (Chrome/Edge)
        window.addEventListener('beforeinstallprompt', (event) => {
            console.log('✅ beforeinstallprompt event fired');
            
            // Prevent the default mini-infobar from appearing
            event.preventDefault();
            
            // Store the event for later use
            deferredPrompt = event;
            
            // Show the install button
            if (!isInstalled()) {
                updateInstallButton();
                console.log('Install button shown (beforeinstallprompt)');
            }
        });

        // Handle successful installation
        window.addEventListener('appinstalled', () => {
            console.log('✅ BISURE Chat was installed successfully');
            deferredPrompt = null;
            installBtn.style.display = 'none';
            
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: #128C7E;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                z-index: 99999;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                animation: slideDown 0.3s ease-out;
            `;
            successDiv.innerHTML = '✅ App installed successfully!';
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.style.animation = 'slideUp 0.3s ease-in forwards';
                setTimeout(() => successDiv.remove(), 300);
            }, 3000);
        });

        // Update install button text and icon based on platform
        function updateInstallButton() {
            const icon = installBtn.querySelector('i');
            const text = installBtn.querySelector('.bsc-menu-text');
            const badge = installBtn.querySelector('.bsc-menu-badge');
            
            if (icon) {
                if (isDesktop) {
                    icon.className = 'fas fa-desktop';
                } else if (isAndroid) {
                    icon.className = 'fas fa-android';
                } else if (isIOS) {
                    icon.className = 'fas fa-apple';
                } else {
                    icon.className = 'fas fa-download';
                }
            }
            
            if (text) {
                if (isDesktop) {
                    text.textContent = 'Install Desktop App';
                } else if (isAndroid) {
                    text.textContent = 'Install Android App';
                } else if (isIOS) {
                    text.textContent = 'Add to Home Screen';
                } else {
                    text.textContent = 'Install BISURE Chat';
                }
            }
            
            if (badge) {
                badge.textContent = 'Free';
            }
        }

        // Handle display mode changes (detect if app was installed externally)
        const displayModeQuery = window.matchMedia('(display-mode: standalone)');
        displayModeQuery.addEventListener('change', (evt) => {
            if (evt.matches) {
                console.log('App launched in standalone mode');
                installBtn.style.display = 'none';
            }
        });

        // Initialize on page load
        window.addEventListener('load', async () => {
            // Register service worker first
            const registration = await registerServiceWorker();
            
            if (!registration) {
                console.warn('Cannot register service worker - install features disabled');
                return;
            }
            
            // Check if already installed
            if (isInstalled()) {
                console.log('✅ App is already installed (standalone mode)');
                installBtn.style.display = 'none';
                return;
            }
            
            // Update button appearance
            updateInstallButton();
            
            // Show install button for platforms that support it
            if (isDesktop || isAndroid || isIOS || deferredPrompt) {
                console.log(`Showing install button for ${isDesktop ? 'Desktop' : isAndroid ? 'Android' : isIOS ? 'iOS' : 'other platform'}`);
                installBtn.style.display = 'flex';
            }
        });

        // Check for standalone mode immediately (before load event)
        if (isInstalled()) {
            console.log('App is already installed, hiding install button');
            installBtn.style.display = 'none';
        }

        // Handle service worker updates
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('Service Worker updated, reloading...');
                // Optionally show update notification
                const updateDiv = document.createElement('div');
                updateDiv.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #0d6efd;
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    z-index: 99999;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    animation: slideUp 0.3s ease-out;
                    text-align: center;
                `;
                updateDiv.innerHTML = `
                    <div style="margin-bottom: 8px;">🔄 New version available!</div>
                    <button style="
                        background: white;
                        color: #0d6efd;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-weight: 600;
                        cursor: pointer;
                        margin-top: 8px;
                    ">Update Now</button>
                `;
                
                document.body.appendChild(updateDiv);
                
                updateDiv.querySelector('button').addEventListener('click', () => {
                    window.location.reload();
                });
                
                setTimeout(() => {
                    updateDiv.style.animation = 'slideDown 0.3s ease-in forwards';
                    setTimeout(() => updateDiv.remove(), 300);
                }, 10000);
            });
        }

        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from { transform: translateX(-50%) translateY(0); opacity: 1; }
                to { transform: translateX(-50%) translateY(20px); opacity: 0; }
            }
            @keyframes slideUp {
                from { transform: translateX(-50%) translateY(20px); opacity: 0; }
                to { transform: translateX(-50%) translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

    })();

</script>

</body>
</html>