<?php
// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="google" content="notranslate">
    <meta name="format-detection" content="telephone=no">
    <meta name="referrer" content="no-referrer">

    <title>Hamburger Menu</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
        }

        /* Prevent long-press callouts */
        body,
        .sidebar ul li,
        .nav-item {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        a,
        .sidebar ul li {
            -webkit-touch-callout: none;
        }

        .hamburger {
            font-size: 22px;
            cursor: pointer;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1003;
            color: #333;
            background: rgba(255, 255, 255, 0.8);
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .hamburger:hover {
            background: rgba(255, 255, 255, 1);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100%;
            background-color: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            transition: left 0.3s ease-in-out, background-color 0.3s ease;
            z-index: 1002;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }

        .sidebar.active {
            left: 0;
            pointer-events: auto;
        }

        .profile-section {
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            object-fit: cover;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex-grow: 1;
        }

        .sidebar ul li {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }

        .sidebar ul li:hover {
            background-color: #f0f0f0;
        }

        .sidebar ul li i {
            font-size: 18px;
            color: #555;
            transition: color 0.3s ease;
        }

        .hamburger-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            display: none;
            pointer-events: none;
        }

        .hamburger-overlay.active {
            display: block;
            pointer-events: auto;
        }

        /* =============================================
           DARK MODE - Inline styles (no class on body needed)
           These use CSS specificity to override when parent has dark-mode
           ============================================= */
        
        /* Dark mode sidebar */
        .dark-mode .sidebar {
            background-color: #1F2C33;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.5);
        }

        .dark-mode .profile-section {
            background-color: #1A252B;
            color: #E9EDEF;
        }

        .dark-mode .profile-section h3 {
            color: #E9EDEF;
        }

        .dark-mode .sidebar ul li {
            color: #E9EDEF;
            border-bottom-color: #2A3942;
        }

        .dark-mode .sidebar ul li:hover {
            background-color: #2A3942;
        }

        .dark-mode .sidebar ul li i {
            color: #8696A0;
        }

        .dark-mode .sidebar ul li:hover i {
            color: #25D366;
        }

        .dark-mode .hamburger {
            color: #E9EDEF;
            background: rgba(30, 40, 45, 0.9);
        }

        .dark-mode .hamburger:hover {
            background: rgba(40, 50, 55, 1);
        }

        .dark-mode .hamburger-overlay {
            background: rgba(0, 0, 0, 0.7);
        }
    </style>
</head>

<body>

<!-- Hamburger Icon -->
<div class="hamburger" id="hamburger">
    &#9776;
</div>

<!-- Overlay -->
<div class="hamburger-overlay" id="hamburgerOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">

    <div class="profile-section">

        <img src="<?php echo $picture_path; ?>"
             alt="Profile Picture"
             class="profile-picture">

        <h3>
            <?php echo htmlspecialchars($office_user_data['username'] ?? 'User'); ?>
        </h3>

    </div>

    <ul>


        <li onclick="goTo('/chat/contacts')">
            <i class="fas fa-address-book"></i>
            Contacts
        </li>

        <!-- <li onclick="goTo('/chat/archived.php')">
            <i class="fas fa-archive"></i>
            Archived Chats
        </li> -->

        <li onclick="goTo('/groups/my_groups')">
            <i class="fas fa-users"></i>
            Groups
        </li>

        <li onclick="goTo('/calls/call')">
            <i class="fas fa-phone"></i>
            Call
        </li>

        <li onclick="goTo('/settings/my_profile')">
            <i class="fas fa-user-circle"></i>
            Profile
        </li>

        <li onclick="goTo('/settings/settings')">
            <i class="fas fa-cogs"></i>
            Settings
        </li>

        <li onclick="goTo('/mail/templates/contact_us')">
            <i class="fas fa-envelope"></i>
            Contact Us
        </li>

        <li onclick="goTo('/auth/forgot/forgot_password')">
            <i class="fas fa-key"></i>
            Change Password
        </li>

        <li onclick="goTo('/more/terms_of_use')">
            <i class="fas fa-file-contract"></i>
            Terms of Use
        </li>

        <!-- <li onclick="goTo('/more/terms_and_conditions')">
            <i class="fas fa-paper-plane"></i>
            Terms & Conditions
        </li> -->

        <li onclick="goTo('/more/privacy_and_policy')">
            <i class="fas fa-user-shield"></i>
            Privacy Policy
        </li>

        <li onclick="goTo('/auth/logout.php')">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </li>

    </ul>

</div>

<script>

    // =========================
    // DYNAMIC PROJECT BASE URL
    // =========================
    const projectFolder = window.location.pathname
        .split('/')
        .slice(0, 2)
        .join('/');

    const baseUrl = window.location.origin + projectFolder;

    // =========================
    // DYNAMIC NAVIGATION
    // =========================
    function goTo(path) {

        // Global pages outside chat project folder
        const globalPages = [
            '/mailing/',
            '/register/'
        ];

        const isGlobal = globalPages.some(globalPath =>
            path.startsWith(globalPath)
        );

        if (isGlobal) {
            window.location.href = window.location.origin + path;
            return;
        }

        window.location.href = baseUrl + path;
    }

    // =========================
    // SIDEBAR ELEMENTS
    // =========================
    const sidebar = document.getElementById('sidebar');

    const hamburgerOverlay =
        document.getElementById('hamburgerOverlay');

    const hamburger =
        document.getElementById('hamburger');

    // =========================
    // TOGGLE SIDEBAR
    // =========================
    hamburger.addEventListener('click', function (e) {

        e.stopPropagation();

        sidebar.classList.toggle('active');

        hamburgerOverlay.classList.toggle('active');

    });

    // =========================
    // CLOSE SIDEBAR WITH OVERLAY
    // =========================
    hamburgerOverlay.addEventListener('click', function (e) {

        e.stopPropagation();

        sidebar.classList.remove('active');

        hamburgerOverlay.classList.remove('active');

    });

    // =========================
    // CLOSE SIDEBAR OUTSIDE CLICK
    // =========================
    document.addEventListener('click', function (e) {

        if (
            !sidebar.contains(e.target) &&
            e.target !== hamburger
        ) {

            sidebar.classList.remove('active');

            hamburgerOverlay.classList.remove('active');
        }

    });

</script>

</body>

</html>