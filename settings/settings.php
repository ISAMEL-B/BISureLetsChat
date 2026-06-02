<?php
/**
 * BUSure Chat - Settings Page
 * ✅ Updated to match busure_lets_chat schema and BUSureLetsChat structure
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/register');
    exit();
}

// ✅ Fetch user data from users table
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, fullname, username, email, phone, profile_photo, bio, status_message, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check dark mode preference from cookie
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Settings | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #128C7E;
            --primary-dark: #0D7B6C;
            --secondary-color: #25D366;
            --accent-gold: #D4AF37;
            --background-light: #F5F5F5;
            --background-dark: #121E25;
            --text-light: #FFFFFF;
            --text-dark: #2D3748;
            --text-secondary: #718096;
            --border-color: #E2E8F0;
            --card-bg: #FFFFFF;
            --hover-light: rgba(0, 0, 0, 0.02);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-dark: 0 4px 20px rgba(0, 0, 0, 0.15);
            --danger-color: #E74C3C;
            --nav-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            --background-light: #121E25;
            --card-bg: #1F2C33;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-color: #2A3942;
            --hover-light: rgba(255, 255, 255, 0.03);
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        .settings-header {
            background-color: var(--primary-dark);
            color: var(--text-light);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            height: 60px;
        }

        .back-button {
            color: var(--text-light);
            font-size: 20px;
            margin-right: 20px;
            text-decoration: none;
        }

        .header-title {
            font-size: 20px;
            font-weight: 800;
            position: absolute;
            top: 5;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .settings-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--card-bg);
            min-height: calc(100vh - var(--nav-height));
            transition: background-color 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        .settings-list {
            padding: 15px 0;
        }

        .settings-section {
            margin-bottom: 20px;
        }

        .section-title {
            padding: 8px 20px;
            color: var(--text-secondary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .setting-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-dark);
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .setting-item:hover {
            background-color: var(--hover-light);
        }

        .setting-item:active {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .setting-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(18, 140, 126, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }

        .setting-content {
            flex: 1;
        }

        .setting-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .setting-description {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .setting-arrow {
            color: var(--text-secondary);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
            margin-left: 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--secondary-color);
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        .danger-zone .setting-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .danger-zone .setting-title {
            color: var(--danger-color);
        }

        .toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast {
            background: linear-gradient(135deg, #128C7E, #25D366);
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4);
            font-weight: 500;
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            min-width: 280px;
            max-width: 360px;
            pointer-events: auto;
            cursor: pointer;
            opacity: 0;
            transform: translateY(-20px);
            animation: slideDownFadeIn 0.4s forwards;
        }

        @keyframes slideDownFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUpFadeOut {
            to { opacity: 0; transform: translateY(-20px); }
        }

        .settings-footer {
            padding: 20px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 12px;
        }

        .app-version {
            margin-top: 5px;
            font-size: 11px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            width: 90%;
            max-width: 400px;
            border-radius: 10px;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            background-color: var(--primary-dark);
            color: var(--text-light);
            padding: 15px;
            font-size: 18px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid var(--border-color);
            gap: 10px;
        }

        .modal-button {
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border: none;
        }

        .modal-cancel {
            background-color: #F5F5F5;
            color: #333;
        }

        .modal-confirm {
            background-color: var(--danger-color);
            color: white;
        }

        body.dark-mode .modal-content {
            background-color: #1F2C33;
        }

        body.dark-mode .modal-cancel {
            background-color: #2A3942;
            color: var(--text-dark);
        }

        @media (max-width: 480px) {
            .setting-item {
                padding: 12px 15px;
            }
            .setting-icon {
                width: 36px;
                height: 36px;
                margin-right: 12px;
            }
        }

        a { text-decoration: none; }
    </style>
</head>

<body class="<?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div id="toastContainer" class="toast-container"></div>

    <div class="settings-header">
        <div>
            <?php if (file_exists(__DIR__ . '/../includes/cd_hamburger.php')) 
                include __DIR__ . '/../includes/cd_hamburger.php'; ?>
        </div>
        <div class="header-title">Settings</div>
    </div>

    <div class="settings-container">
        <div class="settings-list">
            <!-- Account Section -->
            <div class="settings-section">
                <div class="section-title">Account</div>

                <!-- ✅ FIXED: Updated profile link -->
                <a href="my_profile" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-user"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Profile</div>
                        <div class="setting-description">Update your profile information</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>

                <a href="#" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-lock"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Privacy</div>
                        <div class="setting-description">Control who can see your information</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>

                <a href="#" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-bell"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Notifications</div>
                        <div class="setting-description">Customize your notification preferences</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>

            <!-- Chat Settings Section -->
            <div class="settings-section">
                <div class="section-title">Chat Settings</div>

                <div class="setting-item" id="themeSettingItem">
                    <div class="setting-icon"><i class="fas fa-paint-brush"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Theme</div>
                        <div class="setting-description">Dark mode</div>
                    </div>
                    <label class="switch" onclick="event.stopPropagation();">
                        <input type="checkbox" id="darkModeToggle" <?php echo $darkMode ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div class="setting-icon"><i class="fas fa-database"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Storage Usage</div>
                        <div class="setting-description">1.2 GB of 5 GB used</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </div>

                <div class="setting-item">
                    <div class="setting-icon"><i class="fas fa-cloud"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Backup Chats</div>
                        <div class="setting-description">Last backup: 2 days ago</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </div>
            </div>

            <!-- App Settings Section -->
            <div class="settings-section">
                <div class="section-title">App Settings</div>

                <a href="#" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-language"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Language</div>
                        <div class="setting-description">English (Default)</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>

                <a href="#" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">About</div>
                        <div class="setting-description">BisureChat v1.0.0</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>

            <!-- Security Section -->
            <div class="settings-section">
                <div class="section-title">Security</div>

                <!-- ✅ FIXED: Updated delete account link -->
                <a href="../auth/account/delete_account" class="setting-item danger-zone">
                    <div class="setting-icon"><i class="fas fa-user-slash"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Delete Account</div>
                        <div class="setting-description">Permanently delete your account</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>

                <!-- ✅ FIXED: Updated logout link -->
                <a href="../auth/logout" class="setting-item danger-zone">
                    <div class="setting-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Logout</div>
                        <div class="setting-description">Sign out of your account</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>

            <!-- ✅ NEW: Contact Us -->
            <div class="settings-section">
                <div class="section-title">Support</div>

                <a href="../mail/templates/contact_us" class="setting-item">
                    <div class="setting-icon"><i class="fas fa-envelope"></i></div>
                    <div class="setting-content">
                        <div class="setting-title">Contact Us</div>
                        <div class="setting-description">Get help or send feedback</div>
                    </div>
                    <div class="setting-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>
        </div>

        <div class="settings-footer">
            <div>BisureChat</div>
            <div class="app-version">Version 1.3.0</div>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div id="deleteAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span>Delete Account</span>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone. All your data will be permanently removed.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-button modal-cancel">Cancel</button>
                <button class="modal-button modal-confirm">Delete Account</button>
            </div>
        </div>
    </div>

    <!-- ✅ FIXED: Updated navbar path -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <script>
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        function setDarkMode(enabled) {
            if (enabled) {
                body.classList.add('dark-mode');
                darkModeToggle.checked = true;
            } else {
                body.classList.remove('dark-mode');
                darkModeToggle.checked = false;
            }
            const expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = `darkMode=${enabled ? 'enabled' : 'disabled'}; expires=${expires.toUTCString()}; path=/`;
            localStorage.setItem('darkMode', enabled ? 'enabled' : 'disabled');
        }

        darkModeToggle.addEventListener('change', function() {
            const enabled = this.checked;
            setDarkMode(enabled);
            showToast(enabled ? '🌙 Dark mode enabled' : '☀️ Light mode enabled');
        });

        document.getElementById('themeSettingItem').addEventListener('click', function(e) {
            if (e.target.closest('.switch')) return;
            darkModeToggle.checked = !darkModeToggle.checked;
            setDarkMode(darkModeToggle.checked);
            showToast(darkModeToggle.checked ? '🌙 Dark mode enabled' : '☀️ Light mode enabled');
        });

        function showToast(message, duration = 2000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideUpFadeOut 0.3s forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        const modal = document.getElementById('deleteAccountModal');
        const closeModal = document.querySelector('.close-modal');
        const cancelBtn = document.querySelector('.modal-cancel');

        document.querySelector('.danger-zone')?.addEventListener('click', function(e) {
            if (this.href && this.href.includes('delete_account')) {
                e.preventDefault();
                modal.style.display = 'flex';
            }
        });

        closeModal.addEventListener('click', () => modal.style.display = 'none');
        cancelBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', function(e) {
            if (e.target === modal) modal.style.display = 'none';
        });

        document.querySelector('.modal-confirm').addEventListener('click', function() {
            window.location.href = '../auth/account/home';
        });
    </script>
</body>
</html>