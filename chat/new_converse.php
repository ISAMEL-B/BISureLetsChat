<?php
/**
 * BISure Chat - New Conversation Page
 * Browse all users to start a new conversation
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    
    <!-- PWA Meta Tags -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>
    
    <title>BISure | New Conversation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --whatsapp-green: #128C7E;
            --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366;
            --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --text-light: #ffffff;
            --text-dark: #495057;
            --accent: #25D366;
            --success: #25D366;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --radius: 12px;
            --card-bg: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--whatsapp-chat-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .main-wrapper {
            width: 100%;
            max-width: 700px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        header {
            display: flex;
            align-items: center;
            background: var(--pro-gradient);
            padding: 1.25rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: var(--text-light);
            gap: 1rem;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .page-heading {
            flex: 1;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            border: 2px solid var(--whatsapp-dark-green);
            border-radius: 50px;
            font-size: 1rem;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            color: var(--whatsapp-dark-green);
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.3);
            border-color: var(--whatsapp-light-green);
        }

        .input-group input::placeholder {
            color: #a0a0a0;
            font-weight: 400;
        }

        .input-group i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--whatsapp-dark-green);
            font-size: 1.1rem;
        }

        .search-clear {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0a0a0;
            cursor: pointer;
            font-size: 1rem;
            display: none;
            padding: 5px;
        }

        .search-clear.visible { display: block; }
        .search-clear:hover { color: var(--whatsapp-dark-green); }

        .users-container {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            max-height: 65vh;
            overflow-y: auto;
        }

        .users-container::-webkit-scrollbar { width: 6px; }
        .users-container::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.03); }
        .users-container::-webkit-scrollbar-thumb { background: var(--whatsapp-dark-green); border-radius: 10px; }

        .section-header {
            padding: 12px 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--whatsapp-dark-green);
            background: rgba(18, 140, 126, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .user-count {
            font-size: 0.7rem;
            background: rgba(18, 140, 126, 0.1);
            padding: 3px 10px;
            border-radius: 12px;
        }

        .user-card-link {
            text-decoration: none;
            color: inherit;
        }

        .user-card {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            gap: 14px;
            transition: var(--transition);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            position: relative;
        }

        .user-card:last-child { border-bottom: none; }
        .user-card:hover { background: rgba(37, 211, 102, 0.05); }
        .user-card:active { background: rgba(37, 211, 102, 0.1); }

        .online-dot {
            position: absolute;
            bottom: 18px;
            left: 60px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--success);
            border: 2px solid var(--card-bg);
            box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.3);
            z-index: 1;
        }

        .online-dot.offline {
            background-color: #94A3B8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.3);
        }

        .profile-picture {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: var(--pro-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.4rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .default-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }

        .user-details {
            flex-grow: 1;
            min-width: 0;
        }

        .user-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--whatsapp-dark-green);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-status-badge {
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 500;
            flex-shrink: 0;
            margin-left: 8px;
        }

        .badge-online {
            background: rgba(37, 211, 102, 0.15);
            color: #1a8a3f;
        }

        .badge-offline {
            background: rgba(148, 163, 184, 0.15);
            color: #64748b;
        }

        .badge-contacts {
            background: rgba(18, 140, 126, 0.1);
            color: var(--whatsapp-dark-green);
        }

        .user-bottom-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .user-info-text {
            font-size: 0.8rem;
            color: #94A3B8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .user-info-text i {
            margin-right: 4px;
            font-size: 0.7rem;
            width: 14px;
            color: var(--whatsapp-dark-green);
        }

        .skeleton-card {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            gap: 14px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            animation: pulse 1.5s ease-in-out infinite;
        }

        .skeleton-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #e0e0e0;
        }

        .skeleton-text { flex: 1; }
        .skeleton-line {
            height: 12px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .skeleton-line:first-child { width: 50%; }
        .skeleton-line:last-child { width: 35%; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--whatsapp-dark-green);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .empty-state h3 { font-size: 1.1rem; margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.85rem; color: #a0a0a0; }

        .error-state { padding: 2rem; text-align: center; }
        .error-state i { font-size: 2.5rem; color: #e74c3c; margin-bottom: 1rem; }
        .retry-btn {
            background: var(--whatsapp-dark-green);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            body { padding: 0; }
            .main-wrapper { max-width: 100%; gap: 1rem; }
            header { border-radius: 0; padding: 1rem 1.5rem; }
            .page-heading { font-size: 1.2rem; }
            .users-container { max-height: 70vh; border-radius: var(--radius); }
            .profile-picture { width: 44px; height: 44px; font-size: 1.1rem; }
            .online-dot { bottom: 14px; left: 50px; width: 10px; height: 10px; }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Header -->
        <header>
            <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
            <!-- <a href="contacts.php" class="back-btn"><i class="fas fa-arrow-left"></i></a> -->
            <h1 class="page-heading">New Conversation</h1>
        </header>

        <!-- Search -->
        <div class="input-group">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, username or phone..." autocomplete="off" />
            <button class="search-clear" id="searchClear">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <!-- Users List -->
        <div class="users-container" id="usersContainer">
            <div class="section-header">
                <span>All Users</span>
                <span class="user-count" id="visibleCount">Loading...</span>
            </div>
            <div id="loadingSkeletons">
                <?php for($i = 0; $i < 5; $i++): ?>
                <div class="skeleton-card">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-text">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div id="usersList"></div>
        </div>
    </div>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <script>
        const UsersManager = {
            allUsers: [],
            searchQuery: '',
            
            async init() {
                await this.fetchUsers();
                this.setupEventListeners();
            },
            
            async fetchUsers() {
                try {
                    const response = await fetch('content/new_contacts_fetch.php?limit=200', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) throw new Error('Network error');
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        this.allUsers = data.data.users;
                        this.renderUsers();
                    } else {
                        throw new Error(data.message || 'Failed to fetch users');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showError(error.message);
                } finally {
                    document.getElementById('loadingSkeletons').style.display = 'none';
                }
            },
            
            renderUsers() {
                const usersList = document.getElementById('usersList');
                const filtered = this.getFilteredUsers();
                
                if (filtered.length === 0) {
                    usersList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No users found</h3>
                            <p>${this.searchQuery ? 'Try a different search' : 'No other users registered yet'}</p>
                        </div>
                    `;
                } else {
                    usersList.innerHTML = filtered.map(user => this.createUserCard(user)).join('');
                }
                
                document.getElementById('visibleCount').textContent = `${filtered.length} user${filtered.length !== 1 ? 's' : ''}`;
            },
            
            getFilteredUsers() {
                if (!this.searchQuery) return this.allUsers;
                
                const query = this.searchQuery.toLowerCase();
                return this.allUsers.filter(u => 
                    u.fullname.toLowerCase().includes(query) ||
                    u.username.toLowerCase().includes(query) ||
                    (u.phone && u.phone.includes(query)) ||
                    (u.status_message && u.status_message.toLowerCase().includes(query))
                );
            },
            
            createUserCard(user) {
                const avatarHTML = user.profile_photo 
                    ? `<img src="${this.esc(user.profile_photo)}" alt="${this.esc(user.fullname)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="default-avatar" style="display:none;">${user.initials}</div>`
                    : `<div class="default-avatar">${user.initials}</div>`;
                
                const dotClass = user.is_online ? '' : 'offline';
                
                // Status badge
                let statusBadge = '';
                if (user.is_online) {
                    statusBadge = '<span class="user-status-badge badge-online">Online</span>';
                } else if (user.is_in_contacts) {
                    statusBadge = '<span class="user-status-badge badge-contacts">Contact</span>';
                }
                
                // Subtitle with icon
                let subtitleHTML = '';
                if (user.status_message && user.status_message !== 'Available') {
                    subtitleHTML = `<i class="fas fa-comment-dots"></i> ${this.esc(user.status_message)}`;
                } else if (user.bio) {
                    subtitleHTML = `<i class="fas fa-info-circle"></i> ${this.esc(user.bio)}`;
                } else if (user.phone) {
                    subtitleHTML = `<i class="fas fa-phone"></i> ${this.esc(user.phone)}`;
                } else {
                    subtitleHTML = `<i class="fas fa-circle"></i> Available`;
                }
                
                // Link to converse.php with contactId
                const linkUrl = `converse.php?contactId=${user.id}`;
                
                return `
                    <a href="${linkUrl}" class="user-card-link">
                        <div class="user-card" data-name="${this.esc(user.fullname.toLowerCase())}" data-online="${user.is_online ? '1' : '0'}">
                            <div class="online-dot ${dotClass}"></div>
                            <div class="profile-picture">
                                ${avatarHTML}
                            </div>
                            <div class="user-details">
                                <div class="user-top-row">
                                    <span class="user-name">${this.esc(user.fullname)}</span>
                                    ${statusBadge}
                                    <span style="font-size:0.7rem;color:#a0a0a0;flex-shrink:0;">@${this.esc(user.username)}</span>
                                </div>
                                <div class="user-bottom-row">
                                    <span class="user-info-text">${subtitleHTML}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                `;
            },
            
            setupEventListeners() {
                const searchInput = document.getElementById('searchInput');
                const searchClear = document.getElementById('searchClear');
                
                let searchTimeout;
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.trim();
                    searchClear.classList.toggle('visible', query.length > 0);
                    
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.searchQuery = query.toLowerCase();
                        this.renderUsers();
                    }, 200);
                });
                
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    searchClear.classList.remove('visible');
                    this.searchQuery = '';
                    this.renderUsers();
                    searchInput.focus();
                });
                
                document.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        searchInput.focus();
                    }
                });
            },
            
            showError(message) {
                document.getElementById('usersList').innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>Failed to load users</h3>
                        <p>${this.esc(message)}</p>
                        <button class="retry-btn" onclick="UsersManager.init()">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `;
            },
            
            esc(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        document.addEventListener('DOMContentLoaded', () => UsersManager.init());
    </script>
    <?php include __DIR__ . '/../includes/call_module.php'; ?>
</body>
</html>