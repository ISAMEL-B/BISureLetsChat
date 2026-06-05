<?php
/**
 * BUSure Chat - Navigation Bar
 * Include: <?php include __DIR__ . '/../../includes/navbar.php'; ?>
 */
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* NAVIGATION BAR STYLES */
    :root {
        --primary-color: #128C7E;
        --primary-dark: #075E54;
        --text-light: #FFFFFF;
        --bg-item-hover: rgba(255, 255, 255, 0.15);
    }

    .navbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0 5px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 900;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        transition: all 0.3s ease;
        position: relative;
        padding: 8px 12px;
        border-radius: 12px;
        flex: 1;
        background: transparent;
        cursor: pointer;
        border: none;
        font-family: inherit;
        margin: 0 5px;
        text-decoration: none;
    }

    .nav-item.active {
        background-color: var(--bg-item-hover);
    }

    .nav-icon {
        font-size: 20px;
        margin-bottom: 4px;
        transition: transform 0.3s ease;
    }

    .nav-item.active .nav-icon {
        transform: translateY(-2px);
    }

    .nav-text {
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }

    .nav-item:hover {
        background-color: var(--bg-item-hover);
    }

    /* Badge for notifications */
    .nav-badge {
        position: absolute;
        top: 5px;
        right: 10px;
        background: #f44336;
        color: white;
        font-size: 0.65rem;
        font-weight: bold;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        border: 2px solid var(--primary-dark);
    }

    /* Ensure page content doesn't overlap nav */
    body {
        margin: 0;
        padding-bottom: 70px;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        background: #f5f5f5;
        min-height: 100vh;
    }

    @media (max-width: 480px) {
        .navbar {
            height: 60px;
        }

        .nav-icon {
            font-size: 18px;
        }

        .nav-text {
            font-size: 11px;
        }

        .nav-item {
            padding: 6px 8px;
            margin: 0 3px;
        }

        body {
            padding-bottom: 60px;
        }
    }
</style>

<!-- NAVIGATION BAR -->
<nav class="navbar">

    <!-- Chats -->
    <a href="#"
       data-path="/chat/contacts"
       class="nav-item">
        <div class="nav-icon">
            <i class="fas fa-comment-dots"></i>
        </div>
        <div class="nav-text">Chats</div>
    </a>

    <!-- Groups -->
    <a href="#"
       data-path="/groups/my_groups"
       class="nav-item">
        <div class="nav-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="nav-text">Groups</div>
    </a>

    <!-- Calls -->
    <a href="#"
       data-path="/calls/call"
       class="nav-item">
        <div class="nav-icon">
            <i class="fas fa-phone"></i>
        </div>
        <div class="nav-text">Calls</div>
    </a>

    <!-- Account -->
    <a href="#"
       data-path="/settings/my_profile"
       class="nav-item">
        <div class="nav-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="nav-text">Account</div>
    </a>

</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // ENVIRONMENT DETECTION
    // =========================
    const hostname = window.location.hostname;

    const isLocalhost =
        hostname === 'localhost' ||
        hostname === '127.0.0.1';

    // =========================
    // DYNAMIC PROJECT BASE URL
    // =========================
    const projectFolder = window.location.pathname
        .split('/')
        .slice(0, 1)
        .join('/');

    const baseUrl = window.location.origin + projectFolder;

    // =========================
    // NAVIGATION ITEMS
    // =========================
    const navItems = document.querySelectorAll('.nav-item');

    // =========================
    // ASSIGN DYNAMIC URLS
    // =========================
    navItems.forEach(item => {

        const path = item.dataset.path;

        if (!path) {
            return;
        }

        item.href = baseUrl + path;
    });

    // =========================
    // ACTIVE MENU DETECTION
    // =========================
    const currentPath = window.location.pathname;

    navItems.forEach(item => {

        item.classList.remove('active');

        const itemPath = item.dataset.path;

        if (!itemPath) {
            return;
        }

        // Exact match
        if (currentPath === itemPath) {
            item.classList.add('active');
            return;
        }

        // Partial match for folders/pages
        if (
            currentPath.startsWith(itemPath) ||
            currentPath.includes(itemPath)
        ) {
            item.classList.add('active');
        }
    });

});
</script>