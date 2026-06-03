<?php
/**
 * BUSure Chat - Admin Sidebar
 * ✅ Reusable sidebar for all admin pages
 */

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
$admin_avatar = strtoupper(substr($admin_name, 0, 1));
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --sidebar-bg: #075E54;
        --sidebar-text: #ffffff;
        --sidebar-hover: rgba(255, 255, 255, 0.1);
        --sidebar-active: rgba(255, 255, 255, 0.15);
        --secondary: #25D366;
        --danger: #E74C3C;
    }

    .sidebar {
        width: 260px;
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 100;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 1.5rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        flex-shrink: 0;
    }

    .sidebar-header i {
        font-size: 1.5rem;
        color: var(--secondary);
    }

    .sidebar-logo {
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .sidebar-logo span {
        color: var(--secondary);
    }

    .sidebar-user {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        flex-shrink: 0;
    }

    .sidebar-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .sidebar-user-info {
        min-width: 0;
    }

    .sidebar-user-name {
        font-weight: 500;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar-user-role {
        font-size: 0.7rem;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sidebar-nav {
        list-style: none;
        padding: 0.75rem 0;
        flex: 1;
        overflow-y: auto;
    }

    .sidebar-nav li {
        margin: 2px 0;
    }

    .sidebar-nav li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 1.25rem;
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 0.88rem;
        border-left: 3px solid transparent;
    }

    .sidebar-nav li a:hover {
        background: var(--sidebar-hover);
        color: #fff;
        border-left-color: var(--secondary);
    }

    .sidebar-nav li a.active {
        background: var(--sidebar-active);
        color: #fff;
        border-left-color: var(--secondary);
        font-weight: 500;
    }

    .sidebar-nav li a i {
        width: 20px;
        text-align: center;
        font-size: 1rem;
    }

    .sidebar-nav .nav-section {
        padding: 0.75rem 1.25rem 0.5rem;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: rgba(255, 255, 255, 0.4);
    }

    .sidebar-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        flex-shrink: 0;
    }

    .sidebar-footer a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 0.85rem;
        transition: color 0.2s;
    }

    .sidebar-footer a:hover {
        color: var(--danger);
    }

    .sidebar-footer a i {
        width: 20px;
        text-align: center;
    }

    /* Mobile toggle button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 12px;
        left: 12px;
        z-index: 101;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--sidebar-bg);
        color: #fff;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    /* Overlay for mobile */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99;
    }

    .sidebar-overlay.active {
        display: block;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-header">
        <i class="fas fa-shield-alt"></i>
        <div class="sidebar-logo">BI<span>Sure</span>Chat</div>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= $admin_avatar ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="sidebar-user-role">Administrator</div>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="sidebar-nav">
        <li class="nav-section">Main</li>
        <li>
            <a href="dashboard" class="<?= $current_page == 'dashboard' || $current_page == 'index' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="users" class="<?= $current_page == 'users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> User Management
            </a>
        </li>
        <li>
            <a href="messages" class="<?= $current_page == 'messages' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Message Center
            </a>
        </li>
        <li>
            <a href="answer_inquiries" class="<?= $current_page == 'answer_inquiries' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Inquiries
            </a>
        </li>
        <li>
            <a href="calls" class="<?= $current_page == 'calls' ? 'active' : '' ?>">
                <i class="fas fa-phone"></i> Call Logs
            </a>
        </li>
        <li>
            <a href="groups" class="<?= $current_page == 'groups' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> Groups
            </a>
        </li>

        <li class="nav-section">System</li>
        <li>
            <a href="analytics" class="<?= $current_page == 'analytics' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
        </li>
        <li>
            <a href="files" class="<?= $current_page == 'files' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> File Management
            </a>
        </li>
        <li>
            <a href="server_logs" class="<?= $current_page == 'server_logs' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Server Logs
            </a>
        </li>
        <li>
            <a href="settings" class="<?= $current_page == 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> System Settings
            </a>
        </li>

        <li class="nav-section">Quick Links</li>
        <li>
            <a href="../../chat/contacts">
                <i class="fas fa-arrow-left"></i> Back to Chat
            </a>
        </li>
    </ul>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="../../auth/logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
    });
</script>