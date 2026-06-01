<?php
/**
 * BUSure Chat - Contacts Page
 * ✅ Updated to match busure_lets_chat schema and BUSureLetsChat structure
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/content/contact_fetch.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <title>BISure | Contacts</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --whatsapp-green: #128C7E;
            --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366;
            --whatsapp-teal-green: #34B7F1;
            --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --light: #f8f9fa;
            --dark: #212529;
            --text-light: #ffffff;
            --text-dark: #495057;
            --accent: #25D366;
            --success: #25D366;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --radius: 12px;
            --card-bg: #ffffff;
            --pro-badge: #FFD700;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--whatsapp-chat-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* Main Container - Centered, Max 700px */
        .main-wrapper {
            width: 100%;
            max-width: 700px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--pro-gradient);
            padding: 1.25rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: var(--text-light);
            position: relative;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .contact-heading {
            flex: 1;
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-light);
        }

        .pro-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--pro-badge);
            color: var(--dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transform: rotate(15deg);
        }

        /* Search Bar */
        .search-new-chat-container {
            position: relative;
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

        .search-clear.visible {
            display: block;
        }

        .search-clear:hover {
            color: var(--whatsapp-dark-green);
        }

        /* Filter Chips */
        .filter-chips {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-chip {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            background: var(--card-bg);
            border: 1px solid var(--whatsapp-dark-green);
            color: var(--whatsapp-dark-green);
            font-family: 'Poppins', sans-serif;
        }

        .filter-chip:hover {
            background: rgba(18, 140, 126, 0.1);
        }

        .filter-chip.active {
            background: var(--whatsapp-dark-green);
            color: var(--text-light);
            border-color: var(--whatsapp-dark-green);
        }

        .filter-chip .count {
            background: rgba(255, 255, 255, 0.3);
            padding: 2px 7px;
            border-radius: 10px;
            margin-left: 4px;
            font-size: 0.7rem;
        }

        .filter-chip.active .count {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Contacts Container */
        .contacts-container {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            max-height: 60vh;
            overflow-y: auto;
        }

        .contacts-container::-webkit-scrollbar {
            width: 6px;
        }

        .contacts-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.03);
        }

        .contacts-container::-webkit-scrollbar-thumb {
            background: var(--whatsapp-dark-green);
            border-radius: 10px;
        }

        .contacts-container::-webkit-scrollbar-thumb:hover {
            background: var(--whatsapp-green);
        }

        /* Section Header */
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
            backdrop-filter: blur(10px);
        }

        .contact-count {
            font-size: 0.7rem;
            background: rgba(18, 140, 126, 0.1);
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 500;
        }

        /* Contact Card */
        .contact-card-link {
            text-decoration: none;
            color: inherit;
        }

        .contact-card {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            gap: 14px;
            transition: var(--transition);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            position: relative;
        }

        .contact-card:last-child {
            border-bottom: none;
        }

        .contact-card:hover {
            background: rgba(37, 211, 102, 0.05);
        }

        .contact-card:active {
            background: rgba(37, 211, 102, 0.1);
        }

        /* Online Status Dot */
        .contact-status {
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

        .contact-status.offline {
            background-color: #94A3B8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.3);
        }

        /* Profile Picture */
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

        /* Contact Details */
        .contact-details {
            flex-grow: 1;
            min-width: 0;
        }

        .contact-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .contact-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--whatsapp-dark-green);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-time {
            font-size: 0.7rem;
            color: #a0a0a0;
            white-space: nowrap;
            flex-shrink: 0;
            margin-left: 8px;
        }

        .contact-bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .contact-info-text {
            font-size: 0.82rem;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .contact-info-text i {
            margin-right: 6px;
            font-size: 0.7rem;
            color: var(--whatsapp-dark-green);
            width: 14px;
        }

        .unread-badge {
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 22px;
            height: 22px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            flex-shrink: 0;
        }

        /* Empty State */
        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--whatsapp-dark-green);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 0.85rem;
            color: #a0a0a0;
        }

        /* FAB Button */
        .fab-new-chat {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .fab-new-chat:hover {
            background: var(--whatsapp-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }

        .fab-new-chat:active {
            transform: scale(0.95);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-15px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .contact-card {
            animation: fadeIn 0.3s ease-out forwards;
            opacity: 0;
        }

        .contact-card:nth-child(1) { animation-delay: 0.03s; }
        .contact-card:nth-child(2) { animation-delay: 0.06s; }
        .contact-card:nth-child(3) { animation-delay: 0.09s; }
        .contact-card:nth-child(4) { animation-delay: 0.12s; }
        .contact-card:nth-child(5) { animation-delay: 0.15s; }
        .contact-card:nth-child(6) { animation-delay: 0.18s; }
        .contact-card:nth-child(7) { animation-delay: 0.21s; }
        .contact-card:nth-child(8) { animation-delay: 0.24s; }
        .contact-card:nth-child(9) { animation-delay: 0.27s; }
        .contact-card:nth-child(10) { animation-delay: 0.30s; }
        .contact-card:nth-child(n+11) { animation-delay: 0.33s; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .typing-indicator {
            animation: pulse 1.5s ease-in-out infinite;
            color: var(--whatsapp-green);
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            .main-wrapper {
                max-width: 100%;
                gap: 1rem;
            }

            header {
                border-radius: 0;
                padding: 1rem 1.5rem;
            }

            .contact-heading {
                font-size: 1.35rem;
            }

            .contacts-container {
                max-height: 65vh;
                border-radius: var(--radius);
            }

            .profile-picture {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }

            .contact-status {
                bottom: 14px;
                left: 50px;
                width: 10px;
                height: 10px;
            }

            .fab-new-chat {
                bottom: 1.5rem;
                right: 1.5rem;
                width: 48px;
                height: 48px;
                font-size: 1.3rem;
            }

            .filter-chips {
                padding: 0 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .contact-card {
                padding: 12px 16px;
                gap: 10px;
            }

            .contact-name {
                font-size: 0.9rem;
            }

            .contact-info-text {
                font-size: 0.75rem;
            }

            .section-header {
                padding: 10px 16px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Header -->
        <header>
            <div class="logo-container">
                <?php //include 'cd_hamburger.php'; ?>
            </div>
            <h1 class="contact-heading">Contacts</h1>
            <div class="pro-badge">PRO</div>
        </header>

        <!-- Search + Filters -->
        <div class="search-new-chat-container">
            <div class="input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search contacts..." autocomplete="off" />
                <button class="search-clear" id="searchClear">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <div class="filter-chips">
                <span class="filter-chip active" data-filter="all">All <span class="count" id="countAll"><?php echo $result->num_rows; ?></span></span>
                <span class="filter-chip" data-filter="online">Online <span class="count" id="countOnline">0</span></span>
                <span class="filter-chip" data-filter="recent">Recent</span>
            </div>
        </div>

        <!-- Contacts List -->
        <div class="contacts-container" id="contactsContainer">
            <div class="section-header">
                <span>Conversations</span>
                <span class="contact-count" id="visibleCount"><?php echo $result->num_rows; ?> contacts</span>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <?php 
                $index = 0;
                while ($row = $result->fetch_assoc()): 
                    // ✅ FIXED: Use actual is_online and last_seen from database
                    $isOnline = !empty($row['is_online']);
                    
                    // ✅ FIXED: Use fullname instead of username
                    $displayName = htmlspecialchars(ucwords(strtolower($row['fullname'] ?? $row['username'])));
                    
                    // ✅ FIXED: Get first letter for avatar
                    $avatarLetter = strtoupper(substr($displayName, 0, 1));
                    
                    // ✅ FIXED: Use profile_photo column
                    $profilePhoto = '';
                    if (!empty($row['profile_photo'])) {
                        $photoPath = '../../uploads/profiles/' . $row['profile_photo'];
                        if (file_exists($photoPath)) {
                            $profilePhoto = $photoPath;
                        }
                    }
                    
                    // ✅ FIXED: Calculate time from last_seen or use message time
                    $contactTime = '';
                    if (!empty($row['last_seen'])) {
                        $lastSeen = strtotime($row['last_seen']);
                        $diffMinutes = round((time() - $lastSeen) / 60);
                        if ($diffMinutes < 1) {
                            $contactTime = 'Just now';
                        } elseif ($diffMinutes < 60) {
                            $contactTime = $diffMinutes . 'm ago';
                        } elseif ($diffMinutes < 1440) {
                            $contactTime = floor($diffMinutes / 60) . 'h ago';
                        } else {
                            $contactTime = date('M d', $lastSeen);
                        }
                    }
                ?>
                <!-- ✅ FIXED: Updated link to use correct user id -->
                <a href="converse.php?contactId=<?php echo $row['id']; ?>" class="contact-card-link">
                    <div class="contact-card" 
                         data-name="<?php echo strtolower($displayName); ?>"
                         data-online="<?php echo $isOnline ? '1' : '0'; ?>">
                        
                        <div class="contact-status <?php echo !$isOnline ? 'offline' : ''; ?>"></div>

                        <div class="profile-picture">
                            <?php if (!empty($profilePhoto)): ?>
                                <img src="<?php echo htmlspecialchars($profilePhoto); ?>" 
                                     alt="<?php echo $displayName; ?>" />
                            <?php else: ?>
                                <div class="default-avatar">
                                    <?php echo $avatarLetter; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="contact-details">
                            <div class="contact-top-row">
                                <span class="contact-name">
                                    <?php echo $displayName; ?>
                                    <!-- ✅ FIXED: Mark if it's the current user -->
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <span style="font-size:0.7rem; color:#a0a0a0;">(you)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="contact-time">
                                    <?php echo $contactTime; ?>
                                </span>
                            </div>
                            <div class="contact-bottom-row">
                                <span class="contact-info-text">
                                    <?php if (!empty($row['bio'])): ?>
                                        <?php echo htmlspecialchars($row['bio']); ?>
                                    <?php elseif (!empty($row['status_message'])): ?>
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($row['status_message']); ?>
                                    <?php else: ?>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone'] ?? 'No phone'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php $index++; endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No contacts found</h3>
                    <p>Start by adding new contacts to your network</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAB -->
    <button class="fab-new-chat" title="New conversation" onclick="window.location.href='converse.php?new=true'">
        <i class="fas fa-plus"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchClear = document.getElementById('searchClear');
            const contactsContainer = document.getElementById('contactsContainer');
            const filterChips = document.querySelectorAll('.filter-chip');
            const visibleCount = document.getElementById('visibleCount');
            const countAll = document.getElementById('countAll');
            const countOnline = document.getElementById('countOnline');
            
            let activeFilter = 'all';

            // Initialize counts
            const allCards = contactsContainer.querySelectorAll('.contact-card');
            const onlineCards = contactsContainer.querySelectorAll('.contact-card[data-online="1"]');
            countAll.textContent = allCards.length;
            countOnline.textContent = onlineCards.length;

            // Search with debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                
                // Show/hide clear button
                if (query.length > 0) {
                    searchClear.classList.add('visible');
                } else {
                    searchClear.classList.remove('visible');
                }
                
                // Debounce search
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters(query, activeFilter);
                }, 200);
            });

            // Clear search
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchClear.classList.remove('visible');
                searchInput.focus();
                applyFilters('', activeFilter);
            });

            // Filter chips
            filterChips.forEach(chip => {
                chip.addEventListener('click', function() {
                    filterChips.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter || 'all';
                    activeFilter = filter;
                    
                    applyFilters(searchInput.value.toLowerCase().trim(), filter);
                });
            });

            function applyFilters(query, filter) {
                const cards = contactsContainer.querySelectorAll('.contact-card');
                let visibleCountValue = 0;
                let currentOnlineCount = 0;

                // Remove existing empty state if present
                const existingEmpty = contactsContainer.querySelector('.empty-state.filter-empty');
                if (existingEmpty) existingEmpty.remove();

                cards.forEach(card => {
                    const name = card.dataset.name || '';
                    const isOnline = card.dataset.online === '1';
                    
                    let show = true;

                    // Apply search
                    if (query && !name.includes(query)) {
                        show = false;
                    }

                    // Apply filter
                    if (show && filter !== 'all') {
                        if (filter === 'online') {
                            show = isOnline;
                        }
                        // 'recent' shows all for now, you can add date logic
                    }

                    const cardLink = card.closest('.contact-card-link');
                    if (show) {
                        if (cardLink) cardLink.style.display = 'block';
                        visibleCountValue++;
                        if (isOnline) currentOnlineCount++;
                    } else {
                        if (cardLink) cardLink.style.display = 'none';
                    }
                });

                // Update counts
                visibleCount.textContent = visibleCountValue + ' contact' + (visibleCountValue !== 1 ? 's' : '');
                countOnline.textContent = currentOnlineCount;

                // Show empty state if needed
                if (visibleCountValue === 0 && allCards.length > 0) {
                    const sectionHeader = contactsContainer.querySelector('.section-header');
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-state filter-empty';
                    emptyDiv.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h3>No matches found</h3>
                        <p>Try a different search or filter</p>
                    `;
                    // Insert after section header
                    if (sectionHeader && sectionHeader.nextSibling) {
                        contactsContainer.insertBefore(emptyDiv, sectionHeader.nextSibling);
                    } else {
                        contactsContainer.appendChild(emptyDiv);
                    }
                }

                // Show/hide section header counts appropriately
                const originalEmpty = contactsContainer.querySelector('.empty-state:not(.filter-empty)');
                if (originalEmpty && visibleCountValue > 0) {
                    visibleCount.textContent = allCards.length + ' contacts';
                }
            }

            // Keyboard shortcut: Ctrl+K or Cmd+K for search
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
                // Escape to clear search
                if (e.key === 'Escape' && document.activeElement === searchInput) {
                    searchInput.value = '';
                    searchInput.blur();
                    searchClear.classList.remove('visible');
                    applyFilters('', activeFilter);
                }
            });
        });
    </script>
</body>
</html>