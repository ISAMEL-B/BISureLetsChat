<?php
// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Database connection
require_once __DIR__ . '/../config/db.php';

// Authentication check
require_once __DIR__ . '/../includes/auth_check.php';

// Get current user ID
$current_user_id = $_SESSION['user_id'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Contacts | BisureChat</title>
    
 	<!-- PWA Meta Tags -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>
    
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
            --unread-badge: #25D366;
            --sent-message: #DCF8C6;
            --received-message: #FFFFFF;
            --hover-light: rgba(0, 0, 0, 0.02);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-dark: 0 4px 20px rgba(0, 0, 0, 0.15);
            --tick-sent: #9C27B0; /* Purple for sent (single tick) */
            --tick-delivered: #9C27B0; /* Purple for delivered (double tick) */
            --tick-read: #25D366; /* Green for read (double tick) */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Header */
        .contacts-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-light);
        }

        .header-title {
            flex: 1;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.3px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
        }

        .header-button {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .header-button:hover {
            transform: scale(1.1);
        }

        /* Search bar */
        .search-container {
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 24px;
            border: none;
            background-color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            outline: none;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            box-shadow: 0 0 0 2px var(--accent-gold);
        }

        /* Contacts list */
        .contacts-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--text-light);
            min-height: calc(100vh - 70px);
            box-shadow: var(--shadow-light);
        }

        .contact-list {
            list-style: none;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .contact-item:hover {
            background-color: var(--hover-light);
        }

        /* Avatar */
        .contact-avatar {
            position: relative;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-weight: bold;
            margin-right: 18px;
            flex-shrink: 0;
            box-shadow: var(--shadow-light);
        }

        .avatar-img,
        .avatar-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            object-position: top center;
            border: 2px solid rgba(255, 255, 255, 0.8);
            display: block;
        }

        .avatar-text {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), #0D7B6C);
            color: var(--text-light);
            font-weight: bold;
            font-size: 22px;
            text-align: center;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }

        /* Online status dot */
        .online-dot {
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 14px;
            height: 14px;
            background-color: var(--secondary-color);
            border: 2px solid var(--text-light);
            border-radius: 50%;
            z-index: 10;
            box-shadow: 0 0 4px rgba(37, 211, 102, 0.5);
        }

        /* Contact info */
        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .contact-name {
            font-weight: 500;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            font-size: 17px;
            color: var(--text-dark);
            gap: 8px;
        }

        /* Online text indicator */
        .online-text {
            font-size: 11px;
            color: var(--secondary-color);
            font-weight: 500;
        }

        /* Last message row with ticks */
        .contact-last-message-row {
            display: flex;
            align-items: center;
            gap: 3px;  /* Small gap between ticks and message */
        }

        .contact-last-message {
            font-size: 14px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 210px;
            order: 0;
        }

        .contact-last-message.unread-message {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* =============================================
           MESSAGE TICKS (WhatsApp Style)
           ============================================= */
        .message-ticks {
            display: inline-flex;
            align-items: center;
            gap: 0;
            flex-shrink: 0;
            font-size: 12px;
            line-height: 1;
            position: relative;
            order: -1;  /* ✅ Forces ticks to appear first in the row */
        }

        /* Single tick - Sent (Purple) */
        .message-ticks.tick-sent {
            color: var(--tick-sent);
        }

        /* Double tick - Delivered (Purple) */
        .message-ticks.tick-delivered {
            color: var(--tick-delivered);
        }

        /* Double tick - Read (Green) */
        .message-ticks.tick-read {
            color: var(--tick-read);
        }

        .message-ticks .tick-icon {
            display: inline-block;
            font-size: 12px;
            line-height: 1;
        }

        /* Second tick overlapping effect */
        .message-ticks.tick-delivered .tick-icon:nth-child(2),
        .message-ticks.tick-read .tick-icon:nth-child(2) {
            margin-left: -5px;
        }

        .contact-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            min-width: 65px;
            flex-shrink: 0;
        }

        .contact-time {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 400;
            white-space: nowrap;
        }

        /* Unread badge */
        .unread-badge {
            background-color: var(--unread-badge);
            color: var(--text-light);
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            padding: 0 5px;
            box-shadow: 0 2px 4px rgba(37, 211, 102, 0.3);
            animation: badgePop 0.3s ease;
        }

        @keyframes badgePop {
            0% { transform: scale(0.5); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* New chat button - FIXED POSITION */
        .new-chat-button {
            position: fixed;
            bottom: 80px;
            right: 30px;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            z-index: 90;
            transition: box-shadow 0.3s ease, background 0.3s ease;
            border: none;
            will-change: box-shadow;
        }

        .new-chat-button:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #2edb6f, #15a07a);
        }

        .new-chat-button:active {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Loading spinner */
        .spinner {
            width: 36px;
            height: 36px;
            border: 4px solid rgba(0, 0, 0, 0.05);
            border-left-color: var(--accent-gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--border-color);
            opacity: 0.7;
        }

        .empty-title {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .empty-description {
            font-size: 15px;
            max-width: 300px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .close-btn {
            position: absolute;
            top: 30px;
            right: 40px;
            font-size: 2.5rem;
            color: var(--accent-gold);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            transform: rotate(90deg);
            color: var(--text-light);
        }

        #onlineCounter {
            font-size: 0.7em;
            font-weight: bold;
            margin-left: 5px;
            color: rgb(4, 252, 12);
        }

        /* Loading skeleton */
        .loading-placeholder {
            display: flex;
            align-items: center;
            padding: 15px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .placeholder-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            margin-right: 15px;
        }

        .placeholder-text {
            flex: 1;
        }

        .placeholder-line {
            height: 12px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .placeholder-line:first-child {
            width: 60%;
        }

        .placeholder-line:last-child {
            width: 40%;
        }

        .error-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .error-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #e74c3c;
        }

        .retry-button {
            background: #128C7E;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Dark mode */
        body.dark-mode {
            --background-light: #121E25;
            --background-dark: #0B141A;
            --text-light: #E9EDEF;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-color: #2A3942;
            background-color: #0B141A !important;
            color: var(--text-dark);
        }

        body.dark-mode .contacts-container {
            background-color: #1F2C33;
            box-shadow: var(--shadow-dark);
        }

        body.dark-mode .contact-item {
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .contact-item:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }

        body.dark-mode .contact-name {
            color: var(--text-light);
        }

        body.dark-mode .contact-last-message {
            color: var(--text-secondary);
        }

        body.dark-mode .contact-last-message.unread-message {
            color: var(--text-light);
        }

        body.dark-mode .search-container {
            background-color: #1F2C33;
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .search-input {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-light);
            border-color: var(--border-color);
        }

        body.dark-mode .empty-state {
            color: var(--text-secondary);
        }

        body.dark-mode .empty-state h3 {
            color: var(--text-light);
        }

        body.dark-mode .contact-time {
            color: var(--text-secondary);
        }

        body.dark-mode .online-dot {
            border-color: #1F2C33;
        }

        body.dark-mode .online-text {
            color: #25D366;
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

            .new-chat-button {
                bottom: 80px;
                right: 25px;
                width: 56px;
                height: 56px;
                font-size: 24px;
            }

            .avatar-text {
                font-size: 20px;
            }

            .contact-last-message {
                max-width: 160px;
            }
        }

        @media (max-width: 480px) {
            .contacts-header {
                padding: 14px 16px;
            }

            .header-title {
                font-size: 18px;
            }

            .header-button {
                font-size: 18px;
            }

            .search-container {
                padding: 12px 16px;
            }

            .search-input {
                padding: 10px 16px;
                font-size: 14px;
            }

            .contact-item {
                padding: 12px 16px;
            }

            .contact-avatar {
                width: 46px;
                height: 46px;
                margin-right: 12px;
            }

            .avatar-text {
                font-size: 18px;
            }

            .contact-name {
                font-size: 15px;
                margin-bottom: 3px;
            }

            .contact-last-message {
                font-size: 12px;
                max-width: 120px;
            }

            .message-ticks {
                font-size: 10px;
            }

            .message-ticks .tick-icon {
                font-size: 10px;
            }

            .contact-meta {
                min-width: 50px;
                gap: 4px;
            }

            .contact-time {
                font-size: 11px;
            }

            .online-dot {
                width: 10px;
                height: 10px;
                bottom: 2px;
                right: 2px;
            }

            .unread-badge {
                min-width: 20px;
                height: 20px;
                font-size: 10px;
            }

            .new-chat-button {
                bottom: 70px;
                right: 20px;
                width: 52px;
                height: 52px;
                font-size: 22px;
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
                <span id="onlineCounter" title="Click to view online users"
                    style="cursor: pointer; font-weight: 500; text-decoration: none; font-size: 0.7em; color: rgb(4, 252, 12); margin-left: 5px;">
                </span>
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
            <!-- Loading skeleton shown initially -->
            <div id="loadingPlaceholder">
                <?php for($i = 0; $i < 5; $i++): ?>
                <div class="loading-placeholder">
                    <div class="placeholder-avatar"></div>
                    <div class="placeholder-text">
                        <div class="placeholder-line"></div>
                        <div class="placeholder-line"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </ul>
    </div>

    <div class="new-chat-button" onclick="window.location.href='new_converse'"><i class="fas fa-plus"></i></div>

    <div id="imageModal" class="modal" style="display: none;">
        <span class="close-btn">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <audio id="notifySound" src="notification.mp3" preload="auto"></audio>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <?php //include __DIR__ . '/../includes/call_receiver.php'; ?>
    
    <?php include __DIR__ . '/../includes/call_module.php'; ?>

    <script>
        // Contact Management with AJAX - Optimized for speed
        const ContactsManager = {
            currentPage: 1,
            searchQuery: '',
            isLoading: false,
            hasMore: true,
            contacts: [],
            
            init() {
                this.fetchContacts(true);
                this.setupSearchListener();
                this.setupInfiniteScroll();
                this.setupPeriodicRefresh();
            },
            
            async fetchContacts(reset = false) {
                if (this.isLoading) return;
                if (!reset && !this.hasMore) return;
                
                this.isLoading = true;
                
                if (reset) {
                    this.currentPage = 1;
                    this.contacts = [];
                }
                
                if (reset && this.contacts.length === 0) {
                    this.showLoading();
                }
                
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        limit: 50,
                        search: this.searchQuery
                    });
                    
                    const response = await fetch(`content/contact_fetch.php?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Invalid server response');
                    }
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        if (reset) {
                            this.contacts = data.data.contacts;
                        } else {
                            const existingIds = new Set(this.contacts.map(c => c.id));
                            const newContacts = data.data.contacts.filter(c => !existingIds.has(c.id));
                            this.contacts = [...this.contacts, ...newContacts];
                        }
                        
                        this.hasMore = data.data.has_more;
                        this.currentPage++;
                        
                        this.renderContacts();
                        this.updateOnlineCounter(data.data.online_count);
                    } else {
                        throw new Error(data.message || 'Failed to fetch contacts');
                    }
                } catch (error) {
                    console.error('Error fetching contacts:', error);
                    if (this.contacts.length === 0) {
                        this.showError(error.message || 'Failed to load contacts');
                    }
                } finally {
                    this.isLoading = false;
                    this.hideLoading();
                }
            },
            
            renderContacts() {
                const contactList = document.getElementById('contactList');
                
                if (this.contacts.length === 0) {
                    contactList.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
                            <h3>No contacts' conversation(s) found</h3>
                            <p>Start a new conversation by tapping the + button</p>
                        </div>
                    `;
                    return;
                }
                
                const contactsHTML = this.contacts.map(contact => this.createContactItem(contact)).join('');
                contactList.innerHTML = contactsHTML;
                
                this.updateDocumentTitle();
            },
            
            /**
             * Generate tick HTML based on message read status
             * 
             * Tick logic:
             * - If last message NOT sent by me: no ticks (it's their message)
             * - If last message sent by me AND not read: single purple tick (✓ sent)
             * - If last message sent by me AND delivered but not read: double purple tick (✓✓ delivered)
             * - If last message sent by me AND read: double green tick (✓✓ read)
             */
            getTickHTML(contact) {
                // If last message was NOT sent by current user, show no ticks
                if (!contact.is_last_message_sent) {
                    return '';
                }
                
                // Last message was sent by current user - determine tick status
                if (contact.is_last_message_read) {
                    // Read by recipient - double GREEN ticks
                    return `
                        <span class="message-ticks tick-read" title="Read">
                            <span class="tick-icon">✓</span>
                            <span class="tick-icon">✓</span>
                        </span>`;
                } else if (contact.is_last_message_delivered) {
                    // Delivered but not read - double PURPLE ticks
                    return `
                        <span class="message-ticks tick-delivered" title="Delivered">
                            <span class="tick-icon">✓</span>
                            <span class="tick-icon">✓</span>
                        </span>`;
                } else {
                    // Sent but not delivered - single PURPLE tick
                    return `
                        <span class="message-ticks tick-sent" title="Sent">
                            <span class="tick-icon">✓</span>
                        </span>`;
                }
            },
            
            createContactItem(contact) {
                // Avatar HTML
                const avatarHTML = contact.profile_photo 
                    ? `<img src="${this.escapeHtml(contact.profile_photo)}" 
                        alt="${this.escapeHtml(contact.fullname)}" 
                        class="avatar-image" 
                        loading="lazy" 
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <span class="avatar-text" style="display:none;">${contact.initials}</span>`
                    : `<span class="avatar-text">${contact.initials}</span>`;
                
                // Online status dot
                const onlineDot = contact.is_online 
                    ? '<div class="online-dot"></div>' 
                    : '';
                
                // Online text indicator next to name
                const onlineText = contact.is_online 
                    ? '<span class="online-text">online</span>' 
                    : '';
                
                // Unread badge
                const unreadBadge = contact.unread_count > 0 
                    ? `<span class="unread-badge">${contact.unread_count > 99 ? '99+' : contact.unread_count}</span>`
                    : '';
                
                // Message class for unread messages
                const lastMessageClass = (contact.unread_count > 0 && !contact.is_last_message_sent) 
                    ? 'unread-message' 
                    : '';
                
                // Get ticks HTML
                const ticksHTML = this.getTickHTML(contact);
                
                // ✅ Format last message with ticks BEFORE "You:" prefix
                let displayMessage = contact.last_message || '';
                
                // If message starts with "You: ", we need to insert ticks after "You: " but before the message content
                // Actually, ticks should go BEFORE the entire message including "You:"
                // Like WhatsApp: ✓✓ You: hello
                
                const convId = contact.conversation_id || 0;
                
                return `
                    <li class="contact-item" 
                        data-id="${contact.id}" 
                        data-conversation="${convId}" 
                        data-unread="${contact.unread_count}"
                        onclick="openConverse(${contact.id}, ${convId})">
                        <div class="contact-avatar">
                            ${avatarHTML}
                            ${onlineDot}
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">
                                ${this.escapeHtml(contact.fullname)}
                                ${onlineText}
                            </div>
                            <div class="contact-last-message-row">
                                ${ticksHTML}
                                <span class="contact-last-message ${lastMessageClass}">
                                    ${this.escapeHtml(displayMessage)}
                                </span>
                            </div>
                        </div>
                        <div class="contact-meta">
                            <div class="contact-time">${contact.last_message_time || ''}</div>
                            ${unreadBadge}
                        </div>
                    </li>
                `;
            },
            
            updateOnlineCounter(count) {
                const counter = document.getElementById('onlineCounter');
                if (count > 0) {
                    counter.textContent = `(${count} online)`;
                    counter.style.display = 'inline';
                } else {
                    counter.style.display = 'none';
                }
            },
            
            updateDocumentTitle() {
                const totalUnread = this.contacts.reduce((sum, contact) => {
                    return sum + (contact.unread_count || 0);
                }, 0);
                
                if (totalUnread > 0) {
                    document.title = `(${totalUnread}) Contacts | BisureChat`;
                } else {
                    document.title = 'Contacts | BisureChat';
                }
            },
            
            setupSearchListener() {
                const searchInput = document.getElementById('searchInput');
                let debounceTimer;
                
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.searchQuery = e.target.value.trim();
                        this.fetchContacts(true);
                    }, 300);
                });
            },
            
            setupInfiniteScroll() {
                const contactsContainer = document.querySelector('.contacts-container');
                
                contactsContainer.addEventListener('scroll', () => {
                    if (this.isLoading || !this.hasMore) return;
                    
                    const { scrollTop, scrollHeight, clientHeight } = contactsContainer;
                    
                    if (scrollTop + clientHeight >= scrollHeight - 200) {
                        this.fetchContacts(false);
                    }
                });
            },
            
            setupPeriodicRefresh() {
                setInterval(() => {
                    if (!document.hidden) {
                        this.fetchContacts(true);
                    }
                }, 30000);
            },
            
            showLoading() {
                const placeholder = document.getElementById('loadingPlaceholder');
                if (placeholder) {
                    placeholder.style.display = 'block';
                }
            },
            
            hideLoading() {
                const placeholder = document.getElementById('loadingPlaceholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            },
            
            showError(message) {
                const contactList = document.getElementById('contactList');
                contactList.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>Failed to load contacts</h3>
                        <p>${this.escapeHtml(message)}</p>
                        <button class="retry-button" onclick="ContactsManager.fetchContacts(true)">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `;
            },
            
            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        function openConverse(userId, conversationId) {
            if (!userId) {
                console.error('No user ID provided');
                return;
            }
            
            let url = `converse?contactId=${userId}`;
            
            if (conversationId && conversationId > 0) {
                url += `&conversation=${conversationId}`;
            }
            
            window.location.href = url;
        }
        
        function openImagePreview(element) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = element.dataset.full;
        }
        
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('close-btn')) {
                this.style.display = 'none';
            }
        });
        
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchContainer = document.getElementById('searchContainer');
            const searchInput = document.getElementById('searchInput');
            
            if (searchContainer.style.display === 'none' || !searchContainer.style.display) {
                searchContainer.style.display = 'block';
                searchInput.focus();
            } else {
                searchContainer.style.display = 'none';
                searchInput.value = '';
                ContactsManager.searchQuery = '';
                ContactsManager.fetchContacts(true);
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('imageModal').style.display = 'none';
            }
        });
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => ContactsManager.init());
        } else {
            ContactsManager.init();
        }
    </script>
</body>
</html>