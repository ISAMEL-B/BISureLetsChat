<?php
// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Database connection
require_once __DIR__ . '/../config/db.php';

// Authentication check
require_once __DIR__ . '/../includes/auth_check.php';

// Contact fetch
require_once __DIR__ . '../content/contact_fetch.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Contacts | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/contacts.css">
    <style>
        #onlineCounter {
            font-size: 0.7em;
            font-weight: bold;
            margin-left: 5px;
            color: rgb(4, 252, 12);
        }

        .contact-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
            min-width: 60px;
        }

        .message-tick {
            font-weight: bold;
            font-size: 14px;
            margin-right: 4px;
            vertical-align: middle;
            user-select: none;
        }

        .online-text {
            display: none;
            font-size: 0.75em;
            color: #2ea532;
            font-weight: bold;
            margin: 0;
            line-height: 1;
        }

        .contact-time {
            color: var(--text-secondary);
            font-size: 0.75em;
            line-height: 1;
            margin: 0;
        }

        .typing-indicator {
            font-weight: bold;
            color: #2ea532;
            font-style: italic;
        }

        .unread-message {
            font-weight: bold;
            color: #2ea532;
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
            background: linear-gradient(135deg, #128C7E, rgb(162, 198, 3));
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(78, 84, 200, 0.4);
            font-weight: 600;
            font-family: 'Roboto', sans-serif;
            font-size: 16px;
            min-width: 280px;
            max-width: 360px;
            pointer-events: auto;
            cursor: pointer;
            user-select: none;
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

        .unread-badge {
            background-color: #2ea532;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 12px;
            min-width: 18px;
            text-align: center;
            line-height: 1;
            display: inline-block;
            margin-top: 2px;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
        }

        .floating-navbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--nav-height);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            z-index: 90;
            padding: 0 10px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            text-decoration: none;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .nav-text {
            font-weight: 500;
        }

        /* =============================================
           DARK MODE - COMPLETE COVERAGE
           ============================================= */
        body.dark-mode {
            --background-light: #121E25;
            --background-dark: #0B141A;
            --text-light: #E9EDEF;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-color: #2A3942;
            --received-message: #202C33;
            --hover-light: rgba(255, 255, 255, 0.03);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.3);
            --shadow-dark: 0 2px 10px rgba(0, 0, 0, 0.4);
            --card-bg: #1F2C33;
            background-color: #0B141A !important;
            color: var(--text-dark);
        }

        body.dark-mode .contacts-container {
            background-color: var(--card-bg);
            box-shadow: var(--shadow-dark);
        }

        body.dark-mode .contact-item {
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .contact-item:hover {
            background-color: var(--hover-light);
        }

        body.dark-mode .contact-name {
            color: var(--text-light);
        }

        body.dark-mode .contact-last-message {
            color: var(--text-secondary);
        }

        body.dark-mode .search-container {
            background-color: var(--card-bg);
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .search-input {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-light);
            border-color: var(--border-color);
        }

        body.dark-mode .search-input::placeholder {
            color: var(--text-secondary);
        }

        body.dark-mode .empty-state {
            color: var(--text-secondary);
        }

        body.dark-mode .empty-state h3 {
            color: var(--text-light);
        }

        body.dark-mode .empty-icon {
            opacity: 0.6;
        }

        body.dark-mode .contact-time {
            color: var(--text-secondary);
        }

        body.dark-mode .online-text {
            color: var(--secondary-color, #25D366);
        }

        body.dark-mode .new-chat-button {
            box-shadow: 0 4px 16px rgba(37, 211, 102, 0.2);
        }

        /* Modal dark mode */
        body.dark-mode .modal {
            background-color: rgba(0, 0, 0, 0.8);
        }

        body.dark-mode .modal-content {
            background-color: var(--card-bg);
        }

        body.dark-mode .spinner {
            border-color: rgba(255, 255, 255, 0.1);
            border-top-color: var(--primary-color, #128C7E);
        }

        /* Body transition */
        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .contacts-header {
                padding: 16px 20px;
            }

            .contact-item {
                padding: 14px 20px;
            }

            .contact-avatar {
                width: 50px;
                height: 50px;
                margin-right: 16px;
            }

            .avatar-text {
                font-size: 20px;
                line-height: 50px;
            }
            
            .header-actions {
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .contacts-header {
                padding: 14px 16px;
            }

            .header-title {
                font-size: 18px;
            }

            .search-container {
                padding: 14px 16px;
            }

            .contact-item {
                padding: 12px 16px;
            }

            .contact-avatar {
                width: 46px;
                height: 46px;
            }

            .avatar-text {
                font-size: 18px;
                line-height: 46px;
            }
            
            .header-button {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .nav-item {
                padding: 6px 8px;
                font-size: 11px;
            }
            
            .nav-icon {
                font-size: 18px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <div class="contacts-header">
        <div>
            <?php if (file_exists(__DIR__ . '/../includes/cd_hamburger.php')) {
                include __DIR__ . '/../includes/cd_hamburger.php';
            } else {
                echo '<button class="header-button"><i class="fas fa-bars"></i></button>';
            } ?>
        </div>
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;">
            <div class="header-title">
                Contacts
                <a
                    href="online.php"
                    id="onlineCounter" title="Click to view online users"
                    style="cursor: pointer; font-weight: 500; text-decoration: none;">
                </a>
            </div>
        </div>
        <div class="header-actions">
            <button class="header-button" id="searchButton"><i class="fas fa-search"></i></button>
            <button onclick="window.location.href='new_converse'" title="Start a new chat" class="header-button"><i class="fas fa-plus-circle"></i></button>
        </div>
    </div>

    <div class="search-container" id="searchContainer" style="display: none;">
        <input type="text" class="search-input" id="searchInput" placeholder="Search contacts...">
    </div>

    <div class="contacts-container">
        <ul class="contact-list" id="contactList">
            <?php
            if (isset($result) && $result->num_rows > 0):
                $initialPreviews = [];
                $hasVisibleContacts = false;

                while ($row = $result->fetch_assoc()):
                    // ✅ FIXED: Updated column names to match users table schema
                    $contactId = $row['id'];
                    $contactName = htmlspecialchars($row['fullname'] ?? $row['username'] ?? 'Unknown');

                    if (empty($row['last_sent_message']) && empty($row['last_received_message'])) {
                        continue;
                    }

                    $hasVisibleContacts = true;

                    // ✅ FIXED: Updated to query users table with correct column names
                    $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
                    $profilePicture = '';
                    if ($stmt) {
                        $stmt->bind_param("i", $contactId);
                        $stmt->execute();
                        $stmt->bind_result($profilePicture);
                        $stmt->fetch();
                        $stmt->close();
                    }

                    $defaultAvatar = strtoupper(substr($contactName, 0, 1));
                    // ✅ FIXED: Updated profile picture path to match uploads/profiles/
                    $profilePicturePath = (!empty($profilePicture) && file_exists('../../uploads/profiles/' . $profilePicture)) 
                        ? '../../uploads/profiles/' . $profilePicture 
                        : '';

                    $lastSentTime = $row['last_sent_time'] ? strtotime($row['last_sent_time']) : 0;
                    $lastReceivedTime = $row['last_received_time'] ? strtotime($row['last_received_time']) : 0;

                    if ($lastSentTime > $lastReceivedTime) {
                        $lastMessage = $row['last_sent_message'] ?? '';
                        $lastMessageTime = date('h:i A', $lastSentTime);
                        $direction = 'sent';
                        $message_status = 'sent';
                    } else {
                        $lastMessage = $row['last_received_message'] ?? '';
                        $lastMessageTime = date('h:i A', $lastReceivedTime);
                        $direction = 'received';
                        $message_status = '';
                    }

                    $unreadCount = $row['unread_count'] ?? 0;

                    $initialPreviews[$contactId] = [
                        'content' => $lastMessage,
                        'timestamp' => date('c', max($lastSentTime, $lastReceivedTime)),
                        'unread_count' => (int)$unreadCount,
                        'direction' => $direction,
                        'message_status' => $message_status,
                        'message_id' => null
                    ];
            ?>
                    <li class="contact-item" data-id="<?= $contactId ?>" onclick="openChat(<?= $contactId ?>)">
                        <div class="contact-avatar">
                            <?php if (!empty($profilePicturePath)): ?>
                                <img src="<?= htmlspecialchars($profilePicturePath) ?>" alt="Profile" class="avatar-image previewable" data-full="<?= htmlspecialchars($profilePicturePath) ?>" onclick="event.stopPropagation(); openImagePreview(this)">
                            <?php else: ?>
                                <span class="avatar-text"><?= $defaultAvatar ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">
                                <?= $contactName ?>
                                <?php if ($contactId == $_SESSION['user_id']): ?>
                                    <span style="margin-left: 5px; font-size: 12px; color: var(--text-secondary);">(you)</span>
                                <?php endif; ?>
                            </div>
                            <div class="contact-last-message">
                                <?= htmlspecialchars($lastMessage) ?>
                            </div>
                        </div>

                        <div class="contact-meta">
                            <div class="online-text" style="display:none;">Online</div>
                            <div class="contact-time"><?= $lastMessageTime ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>

                <?php if (!$hasVisibleContacts): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
                        <h3>No contacts' conversation(s) found</h3>
                        <p>Start a new conversation by tapping the + button</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
                    <h3>No contacts found</h3>
                    <p>Start a new conversation by tapping the + button</p>
                    <p>Check on the top right most corner</p>
                </div>
            <?php endif; ?>
        </ul>

        <div id="loadingSpinner" class="spinner" style="display: none;"></div>
    </div>

    <div class="new-chat-button" onclick="window.location.href='new_converse'"><i class="fas fa-plus"></i></div>

    <div id="imageModal" class="modal" style="display: none;">
        <span class="close-btn">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <audio id="notifySound" src="notification.mp3" preload="auto"></audio>

    <?php 
    // ✅ FIXED: Updated navbar include path
    include __DIR__ . '/../includes/navbar.php'; 
    ?>
    
    <script>
        localStorage.setItem('contactPreviews', JSON.stringify(<?= json_encode($initialPreviews) ?>));
    </script>

    <?php 
    // ✅ FIXED: Updated JS include path
    include __DIR__ . '/js/contactsjs.php'; 
    ?>
</body>

</html>