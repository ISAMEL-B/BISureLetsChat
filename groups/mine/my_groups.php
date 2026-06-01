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

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

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
    <link href="../css/my_groups.css" rel="stylesheet">
    
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
                <a href="../chat/contacts" class="back-button">
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
        <?php include __DIR__ . '/../../includes/navbar.php'; ?>
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