<?php
/**
 * BUSure Chat - Group Info Page (GROUPS FOLDER)
 * ✅ Groups manage ONLY group photos in settings/uploads/groups/
 * ✅ User profile pictures are only PREVIEWED, no remove option
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

require_once __DIR__ . '/../includes/auth_check.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

require_once __DIR__ . '/../config/db.php';

// Get group ID from URL
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if (!$group_id) {
    header('Location: my_groups');
    exit();
}

// Get group information
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

// Check if user is a member
$current_user_id = $_SESSION['user_id'];

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

// ✅ GROUP PHOTOS ONLY - Stored in settings/uploads/groups/
$group_upload_base_dir = __DIR__ . '/../settings/uploads/groups/';
$group_upload_web_path = '../settings/uploads/groups/';

if (!is_dir($group_upload_base_dir)) {
    mkdir($group_upload_base_dir, 0755, true);
}

// ✅ USER PROFILE PICTURES - For PREVIEW only (read-only from groups folder)
$user_profile_path = '../settings/uploads/profiles/';

// Handle group photo upload (ONLY groups manage this)
if ($is_admin && isset($_FILES['group_photo']) && $_FILES['group_photo']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES["group_photo"]["tmp_name"];
    $file_name = $_FILES["group_photo"]["name"];
    $file_size = $_FILES["group_photo"]["size"];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_types = ["jpg", "jpeg", "png", "gif", "webp"];
    
    if ($file_size > 5000000) {
        $error_msg = "File too large (max 5MB).";
    } elseif (!in_array($ext, $allowed_types)) {
        $error_msg = "Only JPG, PNG, GIF, WebP allowed.";
    } else {
        $new_filename = "group_" . $group_id . "_" . time() . "." . $ext;
        $target_file = $group_upload_base_dir . $new_filename;
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            // Delete old group photo
            if (!empty($group['group_photo'])) {
                $old_file = $group_upload_base_dir . $group['group_photo'];
                if (file_exists($old_file)) unlink($old_file);
            }
            
            $update_stmt = $conn->prepare("UPDATE groups_chat SET group_photo = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_filename, $group_id);
            if ($update_stmt->execute()) {
                $success_msg = "Group photo updated!";
                // Refresh group data
                $stmt = $conn->prepare("SELECT * FROM groups_chat WHERE id = ?");
                $stmt->bind_param("i", $group_id);
                $stmt->execute();
                $group = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            $update_stmt->close();
        } else {
            $error_msg = "Upload failed. Check folder permissions.";
        }
    }
}

// Handle remove group photo (ONLY for group photos)
if ($is_admin && isset($_POST['remove_group_photo'])) {
    if (!empty($group['group_photo'])) {
        $old_file = $group_upload_base_dir . $group['group_photo'];
        if (file_exists($old_file)) unlink($old_file);
    }
    $update_stmt = $conn->prepare("UPDATE groups_chat SET group_photo = NULL WHERE id = ?");
    $update_stmt->bind_param("i", $group_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Refresh group data
    $stmt = $conn->prepare("SELECT * FROM groups_chat WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $success_msg = "Group photo removed!";
}

// Handle group info update
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group_info'])) {
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description']);
    
    if (!empty($group_name)) {
        $update_stmt = $conn->prepare("UPDATE groups_chat SET group_name = ?, description = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $group_name, $description, $group_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Refresh group data
        $stmt = $conn->prepare("SELECT * FROM groups_chat WHERE id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $group = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $success_msg = "Group info updated!";
    }
}

// Handle leave group
if (isset($_POST['leave_group'])) {
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $current_user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group['conversation_id'], $current_user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: my_groups');
    exit();
}

// Handle member management
if ($is_admin && isset($_POST['make_admin'])) {
    $target_user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE group_members SET role = 'admin' WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: group_info?group_id=$group_id");
    exit();
}

if ($is_admin && isset($_POST['remove_admin'])) {
    $target_user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: group_info?group_id=$group_id");
    exit();
}

if ($is_admin && isset($_POST['remove_member'])) {
    $target_user_id = intval($_POST['user_id']);
    
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group['conversation_id'], $target_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_info?group_id=$group_id");
    exit();
}

// Get group members (only PREVIEW user profile pictures)
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.fullname,
        u.username,
        u.profile_photo,
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($group['group_name']) ?> Info | BisureChat</title>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>
    
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: #e5ddd5;
            min-height: 100vh;
        }

        body.dark-mode {
            background: #0B141A;
        }

        .main-wrapper {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        body.dark-mode .main-wrapper {
            background: #1F2C33;
        }

        header {
            background: linear-gradient(135deg, #075E54, #128C7E);
            padding: 1rem 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-button {
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: 500;
        }

        .group-info-container {
            padding: 1.5rem;
        }

        .group-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .group-avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .group-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(18,140,126,0.3);
            cursor: pointer;
        }

        .group-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #075E54, #128C7E);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            cursor: pointer;
        }

        .edit-avatar-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #25D366;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: transform 0.2s;
        }

        .edit-avatar-btn:hover {
            transform: scale(1.1);
        }

        #groupPhotoInput {
            display: none;
        }

        .group-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .edit-name-btn {
            background: none;
            border: none;
            color: #128C7E;
            cursor: pointer;
            font-size: 1rem;
        }

        .group-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .group-description {
            margin-top: 0.5rem;
            color: #6c757d;
            font-style: italic;
        }

        .info-section {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        body.dark-mode .info-section {
            background: #1F2C33;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #128C7E;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .members-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }

        .member-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        body.dark-mode .member-item {
            border-bottom-color: #2A3942;
        }

        .member-item:last-child {
            border-bottom: none;
        }

        .member-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            cursor: pointer;
        }

        .member-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #075E54, #128C7E);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 1rem;
            cursor: pointer;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .admin-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #333;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }

        .member-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.3rem;
            border-radius: 5px;
            color: #6c757d;
        }

        .action-btn:hover {
            background: rgba(0,0,0,0.05);
        }

        .danger-zone {
            border: 1px solid #ffcccc;
            background: #fff5f5;
        }

        body.dark-mode .danger-zone {
            background: #2A1F1F;
            border-color: #4A2A2A;
        }

        .leave-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            width: 100%;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
        }

        body.dark-mode .modal-content {
            background: #1F2C33;
        }

        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .modal input, .modal textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        body.dark-mode .modal input,
        body.dark-mode .modal textarea {
            background: #2A3942;
            color: white;
            border-color: #3A4A52;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background: #128C7E;
            color: white;
        }

        .btn-secondary {
            background: #e0e0e0;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #25D366;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            z-index: 2000;
            display: none;
            animation: slideUp 0.3s ease;
        }

        .toast.error {
            background: #f44336;
        }

        @keyframes slideUp {
            from {
                transform: translateX(-50%) translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .group-avatar, .group-avatar-placeholder {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <div class="main-wrapper">
        <header>
            <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
            <h1 class="header-title">Group Info</h1>
        </header>

        <div class="group-info-container">
            <div class="group-header">
                <div class="group-avatar-wrapper">
                    <?php if (!empty($group['group_photo'])): ?>
                        <img src="<?= htmlspecialchars($group_upload_web_path . $group['group_photo']) ?>?v=<?= time() ?>" 
                             alt="Group" 
                             class="group-avatar"
                             id="groupAvatar"
                             onclick="viewFullImage('<?= htmlspecialchars($group_upload_web_path . $group['group_photo']) ?>', 'group')">
                    <?php else: ?>
                        <div class="group-avatar-placeholder" id="groupAvatarPlaceholder" onclick="viewFullImage(null, 'group')">
                            <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($is_admin): ?>
                        <div class="edit-avatar-btn" onclick="document.getElementById('groupPhotoInput').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="photoUploadForm">
                            <input type="file" name="group_photo" id="groupPhotoInput" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="group-name">
                    <?= htmlspecialchars($group['group_name']) ?>
                    <?php if ($is_admin): ?>
                        <button class="edit-name-btn" onclick="openEditModal()">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="group-meta">
                    <?= count($group_members) ?> members • Created <?= date('M j, Y', strtotime($group['created_at'])) ?>
                </div>
                
                <?php if (!empty($group['description'])): ?>
                    <div class="group-description">
                        <?= htmlspecialchars($group['description']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-users"></i> Members • <?= count($group_members) ?>
                </div>
                <ul class="members-list">
                    <?php foreach ($group_members as $member): ?>
                        <li class="member-item">
                            <!-- ✅ PREVIEW ONLY: User profile pictures - NO remove option -->
                            <?php if (!empty($member['profile_photo'])): ?>
                                <img src="../settings/uploads/profiles/<?= htmlspecialchars($member['profile_photo']) ?>?v=<?= time() ?>" 
                                     class="member-avatar" 
                                     onclick="viewFullImage('../settings/uploads/profiles/<?= htmlspecialchars($member['profile_photo']) ?>', 'user')"
                                     onerror="this.src='../assets/images/default-profile.png'">
                            <?php else: ?>
                                <div class="member-avatar-placeholder" onclick="viewFullImage(null, 'user')">
                                    <?= strtoupper(substr($member['fullname'] ?? $member['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="member-info">
                                <div class="member-name">
                                    <?= htmlspecialchars($member['fullname'] ?? $member['username']) ?>
                                    <?php if ($member['role'] === 'admin'): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                    <?php if ($member['id'] == $current_user_id): ?>
                                        <span style="color: #6c757d; font-size: 0.75rem;">(You)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($is_admin && $member['id'] != $current_user_id): ?>
                                <div class="member-actions">
                                    <?php if ($member['role'] === 'admin'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                            <button type="submit" name="remove_admin" class="action-btn" title="Remove Admin">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                            <button type="submit" name="make_admin" class="action-btn" title="Make Admin">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                        <button type="submit" name="remove_member" class="action-btn" title="Remove Member" onclick="return confirm('Remove this member?')">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="info-section danger-zone">
                <div class="section-title">
                    <i class="fas fa-exclamation-triangle"></i> Danger Zone
                </div>
                <form method="POST" onsubmit="return confirm('Are you sure you want to leave this group? You will lose access to all group messages.')">
                    <button type="submit" name="leave_group" class="leave-btn">
                        <i class="fas fa-sign-out-alt"></i> Leave Group
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Group Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Group Info</h3>
                <span onclick="closeEditModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="text" name="group_name" value="<?= htmlspecialchars($group['group_name']) ?>" required>
                    <textarea name="description" rows="3" placeholder="Group description"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_group_info" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="previewModal" class="modal" style="background: rgba(0,0,0,0.95);">
        <div style="position: relative; max-width: 90%;">
            <span onclick="closePreview()" style="position: absolute; top: -40px; right: 0; color: white; font-size: 30px; cursor: pointer;">&times;</span>
            <img id="previewImage" src="" style="max-width: 100%; max-height: 80vh; border-radius: 12px;">
            <!-- ✅ Remove Photo option ONLY for group photos, NOT for user profile pictures -->
            <div id="removePhotoBtn" style="margin-top: 20px; text-align: center; display: none;">
                <form method="POST">
                    <button type="submit" name="remove_group_photo" class="btn" style="background: #f44336; color: white; padding: 10px 20px;" onclick="return confirm('Remove group photo?')">Remove Photo</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Messages -->
    <?php if(isset($success_msg)): ?>
    <div class="toast" id="toast"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_msg)): ?>
    <div class="toast error" id="toast"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <script>
        let currentImageType = null;
        
        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function viewFullImage(url, type) {
            const modal = document.getElementById('previewModal');
            const img = document.getElementById('previewImage');
            const removeBtn = document.getElementById('removePhotoBtn');
            
            currentImageType = type;
            
            if (url) {
                img.src = url + '?t=' + Date.now();
            } else {
                img.src = '';
            }
            
            // ✅ Show remove button ONLY for group photos
            if (type === 'group' && <?= $is_admin ? 'true' : 'false' ?> && url) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
            
            modal.style.display = 'flex';
        }
        
        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
            currentImageType = null;
        }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                if (e.target.id === 'previewModal') {
                    currentImageType = null;
                }
            }
        };
        
        const toast = document.getElementById('toast');
        if (toast) {
            toast.style.display = 'flex';
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.style.display = 'none', 300);
            }, 3000);
        }
    </script>
</body>
</html>