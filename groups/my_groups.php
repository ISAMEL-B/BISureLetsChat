<?php
/**
 * BUSure Chat - Groups Page
 * ✅ Fixed parameter count
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check dark mode preference from cookie
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// ✅ FIXED: Correct number of placeholders (3)
$stmt = $conn->prepare("
    SELECT 
        g.id AS group_id,
        g.group_name,
        g.group_photo,
        g.description,
        g.created_at,
        g.created_by,
        COUNT(gm.user_id) as member_count,
        -- Last message in this group's conversation
        (
            SELECT m.message_text
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
            ORDER BY m.created_at DESC
            LIMIT 1
        ) as last_message,
        -- Last message time
        (
            SELECT m.created_at
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
            ORDER BY m.created_at DESC
            LIMIT 1
        ) as last_message_time,
        -- Unread count for current user
        (
            SELECT COUNT(*)
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
              AND m.sender_id != ?                              -- Placeholder 1
              AND m.id NOT IN (
                  SELECT mr.message_id
                  FROM message_reads mr
                  WHERE mr.user_id = ?                           -- Placeholder 2
              )
        ) as unread_count
    FROM groups_chat g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ?                                         -- Placeholder 3
    GROUP BY g.id
    ORDER BY last_message_time DESC, g.created_at DESC
");

// ✅ FIXED: 3 params, not 4
$stmt->bind_param("iii", 
    $current_user_id,  // Placeholder 1: m.sender_id != ?
    $current_user_id,  // Placeholder 2: mr.user_id = ?
    $current_user_id   // Placeholder 3: gm.user_id = ?
);

$stmt->execute();
$result = $stmt->get_result();
$groups = [];
while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Groups | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- ✅ FIXED: Updated CSS path -->
    <link href="css/my_groups.css" rel="stylesheet">

    <style>
        /* Quick inline styles in case CSS file is missing */
        .main-wrapper {
            max-width: 700px;
            margin: 0 auto;
            min-height: 100vh;
            background: #e5ddd5;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #128C7E, #075E54);
            padding: 1rem 1.5rem;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-button {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .header-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .pro-badge {
            background: #FFD700;
            color: #212529;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 700;
            margin-left: 8px;
            vertical-align: middle;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
        }

        .header-button {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }

        .header-button:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .search-container {
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 0.95rem;
            font-family: 'Roboto', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #128C7E;
        }

        .groups-container {
            background: white;
            min-height: 50vh;
        }

        .groups-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .group-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            gap: 14px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .group-item:hover {
            background: #f5f5f5;
        }

        .group-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #128C7E, #075E54);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .group-info {
            flex: 1;
            min-width: 0;
        }

        .group-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .group-meta {
            font-size: 0.8rem;
            color: #888;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .group-members {
            color: #128C7E;
            font-weight: 500;
        }

        .group-time {
            font-size: 0.7rem;
            color: #aaa;
            position: absolute;
            right: 20px;
            top: 14px;
        }

        .unread-badge {
            background: #25D366;
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
            position: absolute;
            right: 20px;
            bottom: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #888;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }

        .empty-state h3 {
            color: #555;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        .create-group-btn {
            background: linear-gradient(135deg, #128C7E, #075E54);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .create-group-btn:hover {
            transform: translateY(-2px);
        }

        .new-group-button {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #25D366;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
            cursor: pointer;
            z-index: 50;
            transition: all 0.3s;
        }

        .new-group-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }

        /* Dark Mode */
        body.dark-mode {
            background: #0B141A;
        }

        body.dark-mode .main-wrapper {
            background: #0B141A;
        }

        body.dark-mode .groups-container {
            background: #1F2C33;
        }

        body.dark-mode .group-item {
            border-bottom-color: #2A3942;
        }

        body.dark-mode .group-item:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        body.dark-mode .group-name {
            color: #E9EDEF;
        }

        body.dark-mode .group-meta {
            color: #8696A0;
        }

        body.dark-mode .search-container {
            background: #1F2C33;
            border-bottom-color: #2A3942;
        }

        body.dark-mode .input-group input {
            background: rgba(0, 0, 0, 0.3);
            color: #E9EDEF;
            border-color: #2A3942;
        }

        body.dark-mode .empty-state {
            color: #8696A0;
        }

        body.dark-mode .empty-state h3 {
            color: #E9EDEF;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                max-width: 100%;
            }

            .group-item {
                padding: 12px 16px;
            }

            .group-avatar {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .group-item {
                padding: 10px 14px;
                gap: 10px;
            }

            .new-group-button {
                bottom: 70px;
                right: 16px;
                width: 48px;
                height: 48px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : 'light-mode' ?>">
    <div class="main-wrapper">
        <header>
            <!-- ✅ FIXED: Updated hamburger include path -->
            <?php 
            if (file_exists(__DIR__ . '/cd_hamburger.php')) {
                require_once __DIR__ . '/cd_hamburger.php';
            }
            ?>
            <div class="header-left">
                <!-- ✅ FIXED: Updated back button link -->
                <a href="#" onclick="history.back(); return false;" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="header-title">Groups <span class="pro-badge">PRO</span></h1>
            </div>
            <div class="header-actions">
                <button class="header-button" id="searchButton">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </header>

        <div class="search-container" id="searchContainer" style="display: none;">
            <div class="input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search groups...">
            </div>
        </div>

        <div class="groups-container">
            <ul class="groups-list" id="groupsList">
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <!-- ✅ FIXED: Uses 'id' instead of 'group_id' -->
                        <li class="group-item" data-id="<?= $group['group_id'] ?>" onclick="openGroup(<?= $group['group_id'] ?>)">
                            <div class="group-avatar">
                                <!-- ✅ FIXED: Uses group_photo with correct path -->
                                <?php if (!empty($group['group_photo'])): ?>
                                    <img src="<?= htmlspecialchars('../../uploads/images/' . $group['group_photo']) ?>" alt="Group image">
                                <?php else: ?>
                                    <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="group-info">
                                <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                                <div class="group-meta">
                                    <span class="group-members"><?= $group['member_count'] ?> members</span>
                                    <?php if (!empty($group['last_message'])): ?>
                                        • <span><?= htmlspecialchars(substr($group['last_message'], 0, 50)) ?><?= strlen($group['last_message']) > 50 ? '...' : '' ?></span>
                                    <?php else: ?>
                                        • <span>No messages yet</span>
                                    <?php endif; ?>
                                </div>
                                <!-- ✅ FIXED: Show time + unread badge -->
                            </div>
                            <?php if (!empty($group['last_message_time'])): ?>
                                <div class="group-time">
                                    <?= date('M j, g:i A', strtotime($group['last_message_time'])) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($group['unread_count']) && $group['unread_count'] > 0): ?>
                                <div class="unread-badge"><?= $group['unread_count'] ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No groups yet</h3>
                        <p>Create your first group to start chatting with multiple people</p>
                        <button class="create-group-btn" onclick="window.location.href='create_group'">
                            Create Group
                        </button>
                    </div>
                <?php endif; ?>
            </ul>
        </div>

        <div class="new-group-button" onclick="window.location.href='create_group'">
            <i class="fas fa-plus"></i>
        </div>

        <!-- ✅ FIXED: Updated navbar include path -->
        <?php include __DIR__ . '/../includes/navbar.php'; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.getElementById('searchButton');
            const searchBox = document.getElementById('searchContainer');
            const searchInput = document.getElementById('searchInput');

            // Toggle search box
            searchBtn.onclick = () => {
                const visible = searchBox.style.display !== 'none';
                searchBox.style.display = visible ? 'none' : 'block';
                if (!visible) searchInput.focus();
            };

            // Search functionality
            searchInput.oninput = () => {
                const term = searchInput.value.toLowerCase();
                document.querySelectorAll('.group-item').forEach(item => {
                    const name = item.querySelector('.group-name').textContent.toLowerCase();
                    item.style.display = name.includes(term) ? 'flex' : 'none';
                });
            };

            // ✅ FIXED: Updated redirect URL
            window.openGroup = function(groupId) {
                window.location.href = `group_chat?group_id=${groupId}`;
            };
        });
    </script>
</body>

</html>