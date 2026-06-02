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
            .slice(0, 2)
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
</script>

</body>
</html>