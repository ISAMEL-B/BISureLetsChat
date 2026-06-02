<?php
/**
 * BUSure Chat - Groups Page
 * ✅ Added: Group avatar preview with smooth animation
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

// Group photos path
$group_photos_path = '../settings/uploads/groups/';

$stmt = $conn->prepare("
    SELECT 
        g.id AS group_id,
        g.group_name,
        g.group_photo,
        g.description,
        g.created_at,
        g.created_by,
        COUNT(gm.user_id) as member_count,
        (
            SELECT m.message_text
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
            ORDER BY m.created_at DESC
            LIMIT 1
        ) as last_message,
        (
            SELECT m.created_at
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
            ORDER BY m.created_at DESC
            LIMIT 1
        ) as last_message_time,
        (
            SELECT COUNT(*)
            FROM messages m
            WHERE m.conversation_id = g.conversation_id
              AND m.is_deleted = 0
              AND m.sender_id != ?
              AND m.id NOT IN (
                  SELECT mr.message_id
                  FROM message_reads mr
                  WHERE mr.user_id = ?
              )
        ) as unread_count
    FROM groups_chat g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ?
    GROUP BY g.id
    ORDER BY last_message_time DESC, g.created_at DESC
");

$stmt->bind_param("iii", 
    $current_user_id,
    $current_user_id,
    $current_user_id
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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        .main-wrapper {
            max-width: 700px;
            margin: 0 auto;
            min-height: 100vh;
            background: #e5ddd5;
            display: flex;
            flex-direction: column;
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
            z-index: 100;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            flex-shrink: 0;
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
        }

        .input-group input:focus {
            outline: none;
            border-color: #128C7E;
        }

        .groups-container {
            background: white;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .groups-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
            overflow-y: auto;
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
            cursor: pointer;
            transition: transform 0.2s;
            position: relative;
        }

        .group-avatar:hover {
            transform: scale(1.05);
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
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 400px;
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

        /* Image Preview Modal */
        .image-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .preview-container {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            animation: zoomIn 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .preview-image {
            width: auto;
            max-width: 80vw;
            max-height: 70vh;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            display: block;
            margin: 0 auto;
        }

        .preview-close {
            position: absolute;
            top: -50px;
            right: 0;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            backdrop-filter: blur(5px);
        }

        .preview-close:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: rotate(90deg);
        }

        .preview-caption {
            text-align: center;
            margin-top: 20px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out 0.1s both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

            .preview-image {
                max-width: 90vw;
                max-height: 60vh;
            }

            .preview-close {
                top: -45px;
                right: -5px;
                width: 40px;
                height: 40px;
                font-size: 24px;
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

            .preview-image {
                max-width: 95vw;
                max-height: 50vh;
            }

            .preview-caption {
                font-size: 0.9rem;
                margin-top: 15px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : 'light-mode' ?>">
    <div class="main-wrapper">
        <header>
            <?php 
            if (file_exists(__DIR__ . '/../includes/cd_hamburger.php')) {
                require_once __DIR__ . '/../includes/cd_hamburger.php';
            }
            ?>
            <div class="header-left">
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
                        <li class="group-item" data-id="<?= $group['group_id'] ?>">
                            <div class="group-avatar" onclick="event.stopPropagation(); previewGroupImage('<?= $group['group_id'] ?>', '<?= htmlspecialchars($group['group_name']) ?>', '<?= !empty($group['group_photo']) ? $group_photos_path . $group['group_photo'] : '' ?>')">
                                <?php if (!empty($group['group_photo'])): ?>
                                    <img src="<?= htmlspecialchars($group_photos_path . $group['group_photo']) ?>?v=<?= time() ?>" 
                                         alt="Group image"
                                         onerror="this.style.display='none'; this.parentElement.innerHTML='<?= strtoupper(substr($group['group_name'], 0, 1)) ?>'">
                                <?php else: ?>
                                    <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="group-info" onclick="openGroup(<?= $group['group_id'] ?>)">
                                <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                                <div class="group-meta">
                                    <span class="group-members"><?= $group['member_count'] ?> members</span>
                                    <?php if (!empty($group['last_message'])): ?>
                                        • <span><?= htmlspecialchars(substr($group['last_message'], 0, 50)) ?><?= strlen($group['last_message']) > 50 ? '...' : '' ?></span>
                                    <?php else: ?>
                                        • <span>No messages yet</span>
                                    <?php endif; ?>
                                </div>
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

        <!-- Image Preview Modal -->
        <div id="imagePreviewModal" class="image-preview-modal">
            <div class="preview-container">
                <button class="preview-close" onclick="closeImagePreview()">
                    <i class="fas fa-times"></i>
                </button>
                <img id="previewImage" class="preview-image" src="" alt="Group preview">
                <div id="previewCaption" class="preview-caption"></div>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/navbar.php'; ?>
    </div>

    <script>
        let currentImageUrl = '';

        function openGroup(groupId) {
            window.location.href = `group_chat?group_id=${groupId}`;
        }

        function previewGroupImage(groupId, groupName, imageUrl) {
            event.stopPropagation();
            
            const modal = document.getElementById('imagePreviewModal');
            const previewImage = document.getElementById('previewImage');
            const previewCaption = document.getElementById('previewCaption');
            
            if (imageUrl) {
                // Add cache busting
                previewImage.src = imageUrl + '?t=' + Date.now();
                currentImageUrl = imageUrl;
            } else {
                // If no image, show placeholder
                previewImage.src = '';
                previewImage.style.background = 'linear-gradient(135deg, #128C7E, #075E54)';
                previewImage.style.display = 'flex';
                previewImage.style.alignItems = 'center';
                previewImage.style.justifyContent = 'center';
                previewImage.style.fontSize = '4rem';
                previewImage.style.fontWeight = 'bold';
                previewImage.style.color = 'white';
                
                // Set text content as fallback
                const firstLetter = groupName.charAt(0).toUpperCase();
                // We'll handle this differently - actually show a div with the letter
                previewImage.outerHTML = `<div class="preview-image" style="width: 200px; height: 200px; border-radius: 50%; background: linear-gradient(135deg, #128C7E, #075E54); display: flex; align-items: center; justify-content: center; font-size: 5rem; font-weight: bold; color: white;">${firstLetter}</div>`;
            }
            
            previewCaption.textContent = groupName;
            
            // Show modal with animation
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Close on escape key
            document.addEventListener('keydown', handleEscapeKey);
        }

        function closeImagePreview() {
            const modal = document.getElementById('imagePreviewModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            document.removeEventListener('keydown', handleEscapeKey);
        }

        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                closeImagePreview();
            }
        }

        // Close modal when clicking outside the image
        document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImagePreview();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.getElementById('searchButton');
            const searchBox = document.getElementById('searchContainer');
            const searchInput = document.getElementById('searchInput');

            // Toggle search box
            if (searchBtn) {
                searchBtn.onclick = () => {
                    const visible = searchBox.style.display !== 'none';
                    searchBox.style.display = visible ? 'none' : 'block';
                    if (!visible) searchInput.focus();
                };
            }

            // Search functionality
            if (searchInput) {
                searchInput.oninput = () => {
                    const term = searchInput.value.toLowerCase();
                    document.querySelectorAll('.group-item').forEach(item => {
                        const name = item.querySelector('.group-name').textContent.toLowerCase();
                        item.style.display = name.includes(term) ? 'flex' : 'none';
                    });
                };
            }
        });
    </script>
</body>

</html>