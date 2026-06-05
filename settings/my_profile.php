<?php
/**
 * BUSure Chat - Profile Page
 * ✅ Fixed: Paths, upload directory, and redirect loops
 */
require_once __DIR__ . '/../includes/auth_check.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login');
    exit();
}

// ✅ FIXED: Correct path from settings/ to config/
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found. ID: " . $user_id);
}

// Define upload directory - subfolder within the same directory as this file
$upload_base_dir = __DIR__ . '/uploads/profiles/';
$upload_web_path = 'uploads/profiles/'; // Relative web path

// Default profile photo
$profile_photo = !empty($user['profile_photo']) 
    ? $upload_web_path . $user['profile_photo'] 
    : '../assets/images/default-profile.png';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_base_dir)) {
        if (!mkdir($upload_base_dir, 0755, true)) {
            $upload_error = "Failed to create upload directory.";
        }
    }

    if (!isset($upload_error)) {
        $file_tmp = $_FILES["profile_photo"]["tmp_name"];
        $file_name = $_FILES["profile_photo"]["name"];
        $file_size = $_FILES["profile_photo"]["size"];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        $allowed_types = ["jpg", "jpeg", "png", "gif", "webp"];
        
        if ($file_size > 5000000) {
            $upload_error = "File too large (max 5MB).";
        } elseif (!in_array($ext, $allowed_types)) {
            $upload_error = "Only JPG, PNG, GIF, WebP allowed.";
        } else {
            // Generate unique filename
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
            $target_file = $upload_base_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Delete old profile photo if exists
                if (!empty($user['profile_photo'])) {
                    $old_file = $upload_base_dir . $user['profile_photo'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                // Update database
                $update_stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_filename, $user_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['profile_photo'] = $new_filename;
                    $_SESSION['success'] = "Profile photo updated successfully!";
                    
                    // ✅ FIXED: Use AJAX response instead of redirect to prevent page shake
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        echo json_encode(['success' => true, 'message' => 'Photo updated!', 'filename' => $new_filename]);
                        exit();
                    }
                    
                    // Redirect only for non-AJAX requests
                    header("Location: my_profile");
                    exit();
                } else {
                    $upload_error = "Database error: " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $upload_error = "Failed to upload file. Please check directory permissions.";
            }
        }
    }
}

// Handle bio + status_message update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio']) && !isset($_FILES['profile_photo'])) {
    $bio = trim($_POST['bio']);
    $status = trim($_POST['status_message']);

    $update_stmt = $conn->prepare("UPDATE users SET bio = ?, status_message = ? WHERE id = ?");
    
    if ($update_stmt) {
        $update_stmt->bind_param("ssi", $bio, $status, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $update_error = "Database Error: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
    
    // ✅ FIXED: Only redirect if not AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        header("Location: my_profile");
        exit();
    }
}

// Re-fetch updated user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Update profile photo path after potential update
$profile_photo = !empty($user['profile_photo']) 
    ? $upload_web_path . $user['profile_photo'] 
    : '../assets/images/default-profile.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($user['fullname'] ?? $user['username']) ?> | BisureChat</title>
    
     <!-- PWA Meta Tags -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>
    
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #128C7E; 
            --primary-dark: #075E54; 
            --secondary: #25D366;
            --bg: #f5f5f5; 
            --card: #fff; 
            --text: #2D3748; 
            --text2: #718096;
            --border: #E2E8F0; 
            --nav-h: 65px;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Roboto', sans-serif; 
        }
        
        body { 
            background: var(--bg); 
            color: var(--text); 
            min-height: 100vh; 
        }
        
        body.dark-mode { 
            --bg: #0B141A; 
            --card: #1F2C33; 
            --text: #E9EDEF; 
            --text2: #8696A0; 
            --border: #2A3942; 
            background: #0B141A; 
        }
        
        .page { 
            max-width: 500px; 
            margin: 0 auto; 
            background: var(--card); 
            min-height: 100vh; 
            padding-bottom: calc(var(--nav-h) + 20px); 
            box-shadow: 0 10px 30px rgba(0,0,0,.15); 
        }
        
        .hdr { 
            background: linear-gradient(135deg, var(--primary-dark), var(--primary)); 
            color: #fff; 
            padding: 1rem 1.5rem; 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
        }
        
        .hdr h1 { 
            font-size: 1.2rem; 
            font-weight: 500; 
        }
        
        .top { 
            background: linear-gradient(135deg, var(--primary-dark), var(--primary)); 
            padding: 30px 20px 20px; 
            text-align: center; 
        }
        
        .av-wrap { 
            position: relative; 
            display: inline-block; 
            margin-bottom: 12px; 
        }
        
        .av { 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 4px solid rgba(255,255,255,.3); 
            cursor: pointer; 
            transition: transform 0.3s ease;
        }
        
        .av:hover {
            transform: scale(1.05);
        }
        
        .cam { 
            position: absolute; 
            bottom: 6px; 
            right: 6px; 
            background: var(--secondary); 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            color: #fff; 
            font-size: 15px; 
            transition: transform 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .cam:hover {
            transform: scale(1.1);
        }
        
        .top h2 { 
            color: #fff; 
            font-size: 1.2rem; 
            margin-bottom: 2px; 
        }
        
        .top .uname { 
            color: rgba(255,255,255,.7); 
            font-size: .85rem; 
            margin-bottom: 6px; 
        }
        
        .top .st { 
            color: rgba(255,255,255,.8); 
            font-size: .85rem; 
        }
        
        .dot { 
            display: inline-block; 
            width: 8px; 
            height: 8px; 
            border-radius: 50%; 
            background: var(--secondary); 
            margin-right: 5px; 
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .info { 
            padding: 20px; 
        }
        
        .sec { 
            margin-bottom: 20px; 
        }
        
        .sec h3 { 
            color: var(--primary); 
            font-size: .8rem; 
            text-transform: uppercase; 
            letter-spacing: .5px; 
            margin-bottom: 10px; 
        }
        
        body.dark-mode .sec h3 { 
            color: var(--secondary); 
        }
        
        .row { 
            display: flex; 
            padding: 12px 0; 
            border-bottom: 1px solid var(--border); 
            align-items: center; 
        }
        
        .row:last-child { 
            border-bottom: none; 
        }
        
        .ico { 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            background: rgba(18,140,126,.1); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 12px; 
            color: var(--primary); 
            flex-shrink: 0; 
        }
        
        .det { 
            flex: 1; 
        }
        
        .det label { 
            font-size: .7rem; 
            color: var(--text2); 
            display: block; 
        }
        
        .det span { 
            font-size: .9rem; 
            color: var(--text); 
            word-break: break-word; 
        }
        
        .btns { 
            display: flex; 
            gap: 10px; 
            margin-top: 20px; 
        }
        
        .btn { 
            flex: 1; 
            padding: 12px; 
            border: none; 
            border-radius: 8px; 
            font-size: .9rem; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 6px; 
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-p { 
            background: var(--primary); 
            color: #fff; 
        }
        
        .btn-d { 
            background: #f5f5f5; 
            color: #E74C3C; 
            border: 1px solid #e0e0e0; 
        }
        
        body.dark-mode .btn-d { 
            background: #2A3942; 
            border-color: #3A4A52; 
        }
        
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,.5); 
            z-index: 1000; 
            align-items: center; 
            justify-content: center; 
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-box { 
            background: var(--card); 
            width: 90%; 
            max-width: 400px; 
            border-radius: 12px; 
            overflow: hidden; 
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-head { 
            background: var(--primary); 
            color: #fff; 
            padding: 14px 18px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .modal-body { 
            padding: 18px; 
        }
        
        .modal-body label {
            display: block;
            font-size: .8rem;
            color: var(--text2);
            margin-bottom: 6px;
        }
        
        .modal-body input, 
        .modal-body textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid var(--border); 
            border-radius: 8px; 
            font-size: .9rem; 
            background: var(--card); 
            color: var(--text); 
            margin-bottom: 12px; 
            font-family: 'Roboto', sans-serif; 
            resize: vertical; 
        }
        
        .modal-body input:focus,
        .modal-body textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
        }
        
        .modal-foot { 
            padding: 12px 18px; 
            display: flex; 
            justify-content: flex-end; 
            gap: 8px; 
            border-top: 1px solid var(--border); 
        }
        
        .btn-c { 
            background: #f0f0f0; 
            color: var(--text); 
            border: none; 
            padding: 10px 18px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: .9rem; 
            transition: background 0.2s;
        }
        
        .btn-c:hover {
            background: #e0e0e0;
        }
        
        body.dark-mode .btn-c { 
            background: #2A3942; 
            color: var(--text); 
        }
        
        body.dark-mode .btn-c:hover {
            background: #374248;
        }
        
        .toast { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: var(--secondary); 
            color: #fff; 
            padding: 14px 20px; 
            border-radius: 8px; 
            z-index: 2000; 
            transform: translateX(150%); 
            transition: transform .3s ease; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 350px;
        }
        
        .toast.show { 
            transform: translateX(0); 
        }
        
        .toast.err { 
            background: #E74C3C; 
        }
        
        #imgModal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,.9); 
            z-index: 3000; 
            align-items: center; 
            justify-content: center; 
        }
        
        #imgModal img { 
            max-width: 90%; 
            max-height: 80vh; 
            border-radius: 12px; 
        }
        
        #imgModal .cls { 
            position: absolute; 
            top: 20px; 
            right: 30px; 
            color: #fff; 
            font-size: 35px; 
            cursor: pointer; 
            transition: transform 0.2s;
        }
        
        #imgModal .cls:hover {
            transform: scale(1.2);
        }
        
        /* Loading spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            margin-right: 5px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) { 
            .av {
                width: 110px;
                height: 110px;
            }
            
            .toast {
                left: 20px;
                right: 20px;
                max-width: none;
            }
        }
    </style>
</head>
<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="page">
    <div class="hdr">
        <?php if(file_exists(__DIR__.'/../includes/cd_hamburger.php')) include __DIR__.'/../includes/cd_hamburger.php'; ?>
        <h1>Profile</h1>
    </div>
    
    <div class="top">
        <div class="av-wrap">
            <img src="<?= htmlspecialchars($profile_photo) ?>" 
                 alt="Profile" 
                 class="av" 
                 id="previewTrigger" 
                 onerror="this.src='../assets/images/default-profile.png'">
            <label for="profile_photo_input" class="cam" title="Change photo">
                <i class="fas fa-camera"></i>
            </label>
            <form id="pictureForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="profile_photo" id="profile_photo_input" accept="image/*">
            </form>
        </div>
        <h2><?= htmlspecialchars($user['fullname'] ?? $user['username']) ?></h2>
        <div class="uname">@<?= htmlspecialchars($user['username']) ?></div>
        <div class="st"><span class="dot"></span><?= htmlspecialchars($user['status_message'] ?? 'Available') ?></div>
    </div>
    
    <div class="info">
        <div class="sec">
            <h3><i class="fas fa-user-circle"></i> Personal Info</h3>
            <div class="row">
                <div class="ico"><i class="fas fa-user"></i></div>
                <div class="det">
                    <label>Full Name</label>
                    <span><?= htmlspecialchars($user['fullname'] ?? '') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="ico"><i class="fas fa-at"></i></div>
                <div class="det">
                    <label>Username</label>
                    <span>@<?= htmlspecialchars($user['username']) ?></span>
                </div>
            </div>
            <div class="row">
                <div class="ico"><i class="fas fa-envelope"></i></div>
                <div class="det">
                    <label>Email</label>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
            </div>
            <div class="row">
                <div class="ico"><i class="fas fa-phone"></i></div>
                <div class="det">
                    <label>Phone</label>
                    <span><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="ico"><i class="fas fa-calendar-alt"></i></div>
                <div class="det">
                    <label>Member Since</label>
                    <span><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
        
        <div class="sec">
            <h3><i class="fas fa-info-circle"></i> About</h3>
            <div class="row">
                <div class="ico"><i class="fas fa-book"></i></div>
                <div class="det">
                    <label>Bio</label>
                    <span><?= !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio yet.' ?></span>
                </div>
            </div>
            <div class="row">
                <div class="ico"><i class="fas fa-comment-dots"></i></div>
                <div class="det">
                    <label>Status</label>
                    <span><?= htmlspecialchars($user['status_message'] ?? 'Available') ?></span>
                </div>
            </div>
        </div>
        
        <div class="btns">
            <button class="btn btn-p" id="editBtn">
                <i class="fas fa-pencil-alt"></i> Edit Profile
            </button>
            <a href="../auth/logout" class="btn btn-d">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-head">
            <span>Edit Profile</span>
            <span style="cursor:pointer" id="closeEdit">&times;</span>
        </div>
        <form method="POST" action="my_profile" id="editProfileForm">
            <div class="modal-body">
                <label for="bio">Bio</label>
                <textarea name="bio" id="bio" rows="2" maxlength="255" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                
                <label for="status_message">Status Message</label>
                <input type="text" name="status_message" id="status_message" maxlength="255" placeholder="What's on your mind?" value="<?= htmlspecialchars($user['status_message'] ?? '') ?>">
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-c" id="cancelEdit">Cancel</button>
                <button type="submit" class="btn btn-p">
                    <span class="spinner" id="saveSpinner"></span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Image Modal -->
<div id="imgModal">
    <span class="cls" id="closeImg">&times;</span>
    <img id="modalImage" src="" alt="Profile Photo">
</div>

<!-- Toast Messages -->
<?php if(isset($_SESSION['success'])): ?>
<div class="toast show" id="toast">
    <i class="fas fa-check-circle"></i> 
    <?= htmlspecialchars($_SESSION['success']); ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if(isset($upload_error)): ?>
<div class="toast show err" id="toast">
    <i class="fas fa-exclamation-circle"></i> 
    <?= htmlspecialchars($upload_error); ?>
</div>
<?php endif; ?>

<?php if(isset($update_error)): ?>
<div class="toast show err" id="toast">
    <i class="fas fa-exclamation-circle"></i> 
    <?= htmlspecialchars($update_error); ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<script>
// ✅ FIXED: Prevent form resubmission and page shake
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle profile photo upload via AJAX to prevent page reload/shake
    const photoInput = document.getElementById('profile_photo_input');
    const pictureForm = document.getElementById('pictureForm');
    
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Show loading state on avatar
            const avatar = document.getElementById('previewTrigger');
            avatar.style.opacity = '0.6';
            
            // Create FormData and submit via AJAX
            const formData = new FormData(pictureForm);
            
            fetch('my_profile', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the avatar image without page reload
                    const timestamp = new Date().getTime();
                    avatar.src = 'uploads/profiles/' + data.filename + '?t=' + timestamp;
                    avatar.style.opacity = '1';
                    
                    // Show success toast
                    showToast('Profile photo updated!', 'success');
                } else {
                    avatar.style.opacity = '1';
                    showToast(data.message || 'Upload failed', 'error');
                }
            })
            .catch(error => {
                avatar.style.opacity = '1';
                showToast('Upload failed. Please try again.', 'error');
                console.error('Error:', error);
            });
        }
    });
    
    // Image preview modal
    const previewTrigger = document.getElementById('previewTrigger');
    const imgModal = document.getElementById('imgModal');
    const modalImage = document.getElementById('modalImage');
    const closeImg = document.getElementById('closeImg');
    
    previewTrigger.addEventListener('click', function() {
        imgModal.style.display = 'flex';
        modalImage.src = this.src;
        document.body.style.overflow = 'hidden';
    });
    
    closeImg.addEventListener('click', function() {
        imgModal.style.display = 'none';
        document.body.style.overflow = '';
    });
    
    imgModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
    
    // Edit modal
    const editModal = document.getElementById('editModal');
    const editBtn = document.getElementById('editBtn');
    const closeEdit = document.getElementById('closeEdit');
    const cancelEdit = document.getElementById('cancelEdit');
    
    editBtn.addEventListener('click', function() {
        editModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
    
    closeEdit.addEventListener('click', closeEditModal);
    cancelEdit.addEventListener('click', closeEditModal);
    
    editModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    
    function closeEditModal() {
        editModal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (imgModal.style.display === 'flex') {
                imgModal.style.display = 'none';
                document.body.style.overflow = '';
            }
            if (editModal.style.display === 'flex') {
                closeEditModal();
            }
        }
    });
    
    // Toast auto-hide
    const toast = document.getElementById('toast');
    if (toast) {
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, 3000);
    }
    
    // Function to show toast messages
    function showToast(message, type) {
        // Remove existing toasts
        const existingToast = document.getElementById('dynamicToast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toastDiv = document.createElement('div');
        toastDiv.id = 'dynamicToast';
        toastDiv.className = 'toast show' + (type === 'error' ? ' err' : '');
        toastDiv.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            ${message}
        `;
        document.body.appendChild(toastDiv);
        
        // Trigger animation
        setTimeout(() => toastDiv.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            toastDiv.classList.remove('show');
            setTimeout(() => toastDiv.remove(), 300);
        }, 3000);
    }
});
</script>
</body>
</html>