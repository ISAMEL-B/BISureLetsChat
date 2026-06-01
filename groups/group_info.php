<?php
/**
 * BUSure Chat - Group Info Page
 * ✅ Updated to match busure_lets_chat schema and BUSureLetsChat structure
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// ✅ FIXED: Updated paths to match BUSureLetsChat structure
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Get group ID from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) {
    header('Location: my_groups');
    exit();
}

// ✅ FIXED: Get group information using correct schema (groups_chat + users)
$stmt = $conn->prepare("
    SELECT 
        g.id AS group_id,
        g.conversation_id,
        g.group_name,
        g.group_photo,
        g.description,
        g.created_by,
        g.created_at,
        u.fullname AS creator_name,
        u.username AS creator_username
    FROM groups_chat g 
    LEFT JOIN users u ON g.created_by = u.id 
    WHERE g.id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();
$group = $group_result->fetch_assoc();
$stmt->close();

if (!$group) {
    header('Location: my_groups');
    exit();
}

// Check if user is a member of this group
$current_user_id = $_SESSION['user_id'];

// ✅ FIXED: role instead of is_admin
$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $current_user_id);
$stmt->execute();
$membership_result = $stmt->get_result();
$is_member = $membership_result->num_rows > 0;
$user_membership = $membership_result->fetch_assoc();
$is_admin = $is_member && ($user_membership['role'] === 'admin');
$stmt->close();

if (!$is_member) {
    header('Location: my_groups');
    exit();
}

// ✅ FIXED: Get group members with correct schema (users + group_members)
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.fullname,
        u.username,
        u.profile_photo,
        u.phone,
        gm.role,
        gm.joined_at
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = ? 
    ORDER BY gm.role = 'admin' DESC, u.fullname ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$group_members = [];
while ($row = $members_result->fetch_assoc()) {
    $group_members[] = $row;
}
$stmt->close();

// ✅ FIXED: Get current user info from users table
$stmt = $conn->prepare("SELECT fullname, username, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$current_user = $user_result->fetch_assoc();
$stmt->close();

// Handle leave group action
if (isset($_POST['leave_group'])) {
    // Remove from group_members
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $current_user_id);
    $stmt->execute();
    $stmt->close();

    // Remove from conversation_participants
    $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group['conversation_id'], $current_user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: my_groups');
    exit();
}

// ✅ FIXED: Handle make admin action (role = 'admin')
if ($is_admin && isset($_POST['make_admin'])) {
    $target_user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE group_members SET role = 'admin' WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_info?group_id=$group_id");
    exit();
}

// ✅ FIXED: Handle remove admin action (role = 'member')
if ($is_admin && isset($_POST['remove_admin'])) {
    $target_user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_info?group_id=$group_id");
    exit();
}

// Handle remove member action
if ($is_admin && isset($_POST['remove_member'])) {
    $target_user_id = intval($_POST['user_id']);
    
    // Remove from group_members
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();

    // Remove from conversation_participants
    $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group['conversation_id'], $target_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_info?group_id=$group_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($group['group_name']) ?> Info | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --whatsapp-green: #128C7E;
            --whatsapp-dark-green: #075E54;
            --whatsapp-light-green: #25D366;
            --whatsapp-teal-green: #34B7F1;
            --whatsapp-chat-bg: #e5ddd5;
            --pro-gradient: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            --pro-gradient-hover: linear-gradient(135deg, #0da792 0%, #064b43 100%);
            --light: #f8f9fa;
            --dark: #212529;
            --text-light: #ffffff;
            --text-dark: #495057;
            --text-secondary: #6c757d;
            --accent: #25D366;
            --success: #25D366;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --radius: 12px;
            --card-bg: #ffffff;
            --pro-badge: #FFD700;
            --call-primary: #25D366;
            --call-secondary: #128C7E;
            --call-bg: #f0f8f5;
            --decline: #f44336;
            --decline-hover: #e53935;
            --border-light: #f0f0f0;
            --option-icon-bg: #f0f2f5;
            --danger-bg: #fff5f5;
            --danger-border: #ffcccc;
            --modal-bg: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--whatsapp-chat-bg);
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: var(--shadow-lg);
            position: relative;
            transition: background 0.3s ease;
        }

        header {
            background: var(--pro-gradient);
            padding: 1.2rem 1.5rem;
            color: var(--text-light);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .back-button {
            color: var(--text-light);
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .back-button:hover {
            transform: translateX(-3px);
        }

        .header-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .group-info-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .group-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .group-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 1rem;
            background: var(--pro-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2.5rem;
            box-shadow: var(--shadow-lg);
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .group-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .group-meta {
            color: var(--text-secondary);
            text-align: center;
        }

        .created-by {
            margin-top: 0.5rem;
            font-style: italic;
        }

        .group-description {
            margin-top: 0.8rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .info-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--whatsapp-dark-green);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .members-list {
            list-style: none;
        }

        .member-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .member-item:last-child {
            border-bottom: none;
        }

        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            background: var(--pro-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .member-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-badge {
            background: var(--pro-gradient);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }

        .member-details {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .member-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .action-btn:hover {
            color: var(--whatsapp-green);
        }

        .danger-btn:hover {
            color: var(--decline);
        }

        .action-form {
            display: inline;
        }

        .settings-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .settings-option:last-child {
            border-bottom: none;
        }

        .option-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .option-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--option-icon-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--whatsapp-dark-green);
            transition: background 0.3s ease;
        }

        .option-details {
            flex: 1;
        }

        .option-title {
            font-weight: 500;
        }

        .option-description {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
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

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--whatsapp-green);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }

        .danger-zone {
            border: 1px solid var(--danger-border);
            background: var(--danger-bg);
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .danger-zone .section-title {
            color: var(--decline);
        }

        .leave-btn {
            background: var(--decline);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .leave-btn:hover {
            background: var(--decline-hover);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--modal-bg);
            border-radius: var(--radius);
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            transition: background 0.3s ease;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .modal-text {
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .modal-btn-cancel {
            background: #f0f2f5;
            color: var(--text-dark);
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .modal-btn-confirm {
            background: var(--decline);
            color: white;
        }

        .modal-btn-confirm:hover {
            background: var(--decline-hover);
        }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode {
            --whatsapp-chat-bg: #0B141A;
            --card-bg: #1F2C33;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-light: #2A3942;
            --option-icon-bg: #2A3942;
            --danger-bg: #2A1F1F;
            --danger-border: #4A2A2A;
            --modal-bg: #1F2C33;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.4);
            background: #0B141A;
            color: var(--text-dark);
        }

        body.dark-mode .main-wrapper {
            background: var(--card-bg);
        }

        body.dark-mode .info-section {
            background: var(--card-bg);
        }

        body.dark-mode .section-title {
            color: var(--whatsapp-light-green);
        }

        body.dark-mode .member-item {
            border-bottom-color: var(--border-light);
        }

        body.dark-mode .settings-option {
            border-bottom-color: var(--border-light);
        }

        body.dark-mode .option-icon {
            background: var(--option-icon-bg);
            color: var(--whatsapp-light-green);
        }

        body.dark-mode .danger-zone {
            background: var(--danger-bg);
            border-color: var(--danger-border);
        }

        body.dark-mode .modal-content {
            background: var(--modal-bg);
        }

        body.dark-mode .modal-btn-cancel {
            background: #2A3942;
            color: var(--text-dark);
        }

        body.dark-mode .modal-btn-cancel:hover {
            background: #374248;
        }

        @media (max-width: 480px) {
            .group-info-container {
                padding: 1rem;
            }

            .group-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }

            .group-name {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <div class="main-wrapper">
        <header>
            <div class="header-left">
                <!-- ✅ FIXED: Updated hamburger path -->
                <?php if (file_exists(__DIR__ . '/../includes/cd_hamburger.php')) {
                    require_once __DIR__ . '/../includes/cd_hamburger.php';
                } ?>
                <!-- ✅ FIXED: Updated back link -->
                <a href="group_chat?group_id=<?= $group_id ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="header-title">Group Info</h1>
            </div>
        </header>

        <div class="group-info-container">
            <div class="group-header">
                <div class="group-avatar">
                    <!-- ✅ FIXED: Uses group_photo with correct path -->
                    <?php if (!empty($group['group_photo'])): ?>
                        <img src="<?= htmlspecialchars('../../uploads/images/' . $group['group_photo']) ?>" alt="Group image">
                    <?php else: ?>
                        <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h2 class="group-name"><?= htmlspecialchars($group['group_name']) ?></h2>
                <div class="group-meta">
                    <p><?= count($group_members) ?> members</p>
                    <p class="created-by">Created by <?= htmlspecialchars($group['creator_name'] ?? $group['creator_username']) ?> on <?= date('M j, Y', strtotime($group['created_at'])) ?></p>
                    <?php if (!empty($group['description'])): ?>
                        <p class="group-description"><?= htmlspecialchars($group['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-section">
                <h3 class="section-title"><i class="fas fa-users"></i> Members</h3>
                <ul class="members-list">
                    <?php foreach ($group_members as $member): ?>
                        <li class="member-item">
                            <div class="member-avatar">
                                <!-- ✅ FIXED: Uses profile_photo with correct path -->
                                <?php if (!empty($member['profile_photo'])): ?>
                                    <img src="<?= htmlspecialchars('../../uploads/profiles/' . $member['profile_photo']) ?>" alt="Profile">
                                <?php else: ?>
                                    <?= strtoupper(substr($member['fullname'] ?? $member['username'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name">
                                    <!-- ✅ FIXED: Uses fullname -->
                                    <?= htmlspecialchars($member['fullname'] ?? $member['username']) ?>
                                    <?php if ($member['role'] === 'admin'): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                    <?php if ($member['id'] == $current_user_id): ?>
                                        <span style="color: var(--text-secondary); font-size: 0.8rem;">(You)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="member-details">
                                    Joined <?= date('M j, Y', strtotime($member['joined_at'])) ?>
                                </div>
                            </div>
                            <?php if ($is_admin && $member['id'] != $current_user_id): ?>
                                <div class="member-actions">
                                    <?php if ($member['role'] === 'admin'): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                            <button type="submit" name="remove_admin" class="action-btn" title="Remove as admin">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                            <button type="submit" name="make_admin" class="action-btn" title="Make admin">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                        <button type="submit" name="remove_member" class="action-btn danger-btn" title="Remove from group">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($is_admin): ?>
                <div class="info-section">
                    <h3 class="section-title"><i class="fas fa-cog"></i> Group Settings</h3>
                    <div class="settings-option">
                        <div class="option-info">
                            <div class="option-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="option-details">
                                <div class="option-title">Group Privacy</div>
                                <div class="option-description">Make group private (invitation only)</div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="settings-option">
                        <div class="option-info">
                            <div class="option-icon">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="option-details">
                                <div class="option-title">Send Messages</div>
                                <div class="option-description">Allow all members to send messages</div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="settings-option">
                        <div class="option-info">
                            <div class="option-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="option-details">
                                <div class="option-title">Add Members</div>
                                <div class="option-description">Allow all members to add people</div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-section danger-zone">
                <h3 class="section-title"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                <form method="POST" onsubmit="return confirmLeaveGroup()">
                    <button type="submit" name="leave_group" class="leave-btn">
                        <i class="fas fa-sign-out-alt"></i> Leave Group
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave Group Confirmation Modal -->
    <div class="modal" id="leaveGroupModal">
        <div class="modal-content">
            <h3 class="modal-title">Leave Group</h3>
            <p class="modal-text">
                Are you sure you want to leave "<?= htmlspecialchars($group['group_name']) ?>"?
                You won't be able to see any messages unless you're added back to the group.
            </p>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="leave_group" class="modal-btn modal-btn-confirm">Leave Group</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmLeaveGroup() {
            document.getElementById('leaveGroupModal').style.display = 'flex';
            return false;
        }

        function closeModal() {
            document.getElementById('leaveGroupModal').style.display = 'none';
        }

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('leaveGroupModal');
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
</body>

</html>