<?php
/**
 * BUSure Chat - Create Group Page
 * ✅ Updated to match busure_lets_chat schema and BUSureLetsChat structure
 * ✅ Currently shows ALL users - will separate contacts later
 */

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login');
    exit();
}

// ✅ FIXED: Updated path to match BUSureLetsChat structure
require_once __DIR__ . '/../config/db.php';

// Fetch ALL users for group member selection using correct schema
$current_user_id = $_SESSION['user_id'];

// ✅ CHANGED: Now shows ALL users from users table (except current user)
// Will filter by contacts table later
$sql = "
    SELECT u.id, u.fullname, u.username, u.profile_photo 
    FROM users u
    WHERE u.id != ?
    ORDER BY u.fullname ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create Group | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #128C7E;
            --primary-dark: #075E54;
            --secondary-color: #25D366;
            --background-light: #f5f5f5;
            --card-bg: #FFFFFF;
            --text-dark: #2D3748;
            --text-light: #FFFFFF;
            --text-secondary: #718096;
            --border-color: #E2E8F0;
            --hover-light: rgba(0, 0, 0, 0.03);
            --selected-bg: rgba(18, 140, 126, 0.08);
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --input-bg: #FFFFFF;
            --danger-color: #E74C3C;
            --nav-height: 60px;
            --pro-badge: #FFD700;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .main-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--card-bg);
            min-height: 100vh;
            box-shadow: var(--shadow);
            transition: background-color 0.3s ease;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .back-button {
            color: var(--text-light);
            font-size: 20px;
            text-decoration: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pro-badge {
            background: var(--pro-badge);
            color: #000;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .header-actions {
            display: flex;
            gap: 5px;
        }

        .header-button {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 18px;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .header-button:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Group Creation Container */
        .group-creation-container {
            padding: 20px;
            padding-bottom: 100px;
        }

        /* Group Image Section */
        .group-image-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }

        .group-image-upload {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px dashed var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: var(--hover-light);
            position: relative;
            overflow: hidden;
        }

        .group-image-upload:hover {
            border-color: var(--primary-color);
            background-color: var(--selected-bg);
        }

        .group-image-upload i {
            font-size: 32px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .group-image-upload:hover i {
            color: var(--primary-color);
        }

        .group-image-preview {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .group-image-text {
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Input Group */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            background-color: var(--input-bg);
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-family: 'Roboto', sans-serif;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
        }

        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: #a0a0a0;
        }

        .char-count {
            text-align: right;
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Selected Members Section */
        .selected-members-section {
            margin-bottom: 20px;
        }

        .selected-members-title,
        .contacts-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .selected-members-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 36px;
        }

        .selected-member {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background-color: var(--selected-bg);
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 500;
            animation: popIn 0.2s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .remove-member {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            font-size: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .remove-member:hover {
            background: rgba(231, 76, 60, 0.1);
        }

        .no-members-selected {
            color: var(--text-secondary);
            font-size: 13px;
            font-style: italic;
        }

        /* Contacts Section */
        .contacts-section {
            margin-bottom: 24px;
        }

        .contacts-search {
            margin-bottom: 12px;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-container i {
            position: absolute;
            left: 14px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            background-color: var(--input-bg);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
        }

        .search-container input::placeholder {
            color: #a0a0a0;
        }

        .contacts-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--card-bg);
        }

        .contacts-list::-webkit-scrollbar {
            width: 5px;
        }

        .contacts-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .contacts-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid var(--border-color);
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-item:hover {
            background-color: var(--hover-light);
        }

        .contact-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .contact-name {
            font-size: 15px;
            font-weight: 500;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-username {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .contact-checkbox {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            color: white;
            font-size: 12px;
        }

        .contact-checkbox.selected {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Create Button */
        .create-group-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            position: sticky;
            bottom: 20px;
        }

        .create-group-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
        }

        .create-group-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            animation: slideUp 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
        }

        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        @keyframes fadeOut {
            to { opacity: 0; }
        }

        /* =============================================
           DARK MODE OVERRIDES
           ============================================= */
        body.dark-mode {
            --background-light: #121E25;
            --card-bg: #1F2C33;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-color: #2A3942;
            --hover-light: rgba(255, 255, 255, 0.03);
            --selected-bg: rgba(37, 211, 102, 0.12);
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            --input-bg: #2A3942;
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        body.dark-mode .main-wrapper {
            background-color: var(--card-bg);
        }

        body.dark-mode .group-image-upload {
            border-color: var(--border-color);
            background-color: var(--hover-light);
        }

        body.dark-mode .group-image-upload:hover {
            border-color: var(--secondary-color);
            background-color: var(--selected-bg);
        }

        body.dark-mode .input-group input,
        body.dark-mode .input-group textarea,
        body.dark-mode .search-container input {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-dark);
        }

        body.dark-mode .input-group input::placeholder,
        body.dark-mode .input-group textarea::placeholder,
        body.dark-mode .search-container input::placeholder {
            color: var(--text-secondary);
        }

        body.dark-mode .contacts-list {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        body.dark-mode .contact-item {
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .contact-item:hover {
            background-color: var(--hover-light);
        }

        body.dark-mode .contact-checkbox {
            border-color: var(--border-color);
        }

        body.dark-mode .selected-member {
            background-color: var(--selected-bg);
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .group-creation-container {
                padding: 16px;
                padding-bottom: 90px;
            }

            .header-title {
                font-size: 18px;
            }

            .group-image-upload {
                width: 80px;
                height: 80px;
            }

            .group-image-upload i {
                font-size: 26px;
            }

            .contact-avatar {
                width: 38px;
                height: 38px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">
    <div class="main-wrapper">
        <header>
            <div class="header-left">
                <!-- ✅ FIXED: Updated back link -->
                <a href="my_groups" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="header-title">New Group <span class="pro-badge">PRO</span></h1>
            </div>
            <div class="header-actions">
                <button class="header-button" id="helpButton">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
        </header>

        <div class="group-creation-container">
            <div class="group-image-section">
                <div class="group-image-upload" id="groupImageUpload">
                    <i class="fas fa-camera"></i>
                    <img class="group-image-preview" id="groupImagePreview" alt="Group image preview">
                </div>
                <div class="group-image-text">Add group icon</div>
                <input type="file" id="groupImageInput" accept="image/*" style="display: none;">
            </div>

            <div class="input-group">
                <label for="groupName">Group Name</label>
                <input type="text" id="groupName" placeholder="Enter group name" maxlength="150">
                <div class="char-count"><span id="nameCharCount">0</span>/150</div>
            </div>

            <!-- ✅ NEW: Group Description (optional) -->
            <div class="input-group">
                <label for="groupDescription">Description (optional)</label>
                <textarea id="groupDescription" placeholder="Add a group description..." maxlength="500"></textarea>
                <div class="char-count"><span id="descCharCount">0</span>/500</div>
            </div>

            <div class="selected-members-section">
                <div class="selected-members-title">
                    Selected Members (<span id="selectedCount">0</span>)
                </div>
                <div class="selected-members-list" id="selectedMembersList">
                    <span class="no-members-selected">No members selected</span>
                </div>
            </div>

            <div class="contacts-section">
                <div class="contacts-title">Select Members</div>
                <div class="contacts-search">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="contactsSearch" placeholder="Search contacts...">
                    </div>
                </div>
                <div class="contacts-list" id="contactsList">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <!-- ✅ FIXED: Uses 'id' and 'fullname' from users table -->
                            <div class="contact-item" data-id="<?= $row['id'] ?>">
                                <div class="contact-avatar">
                                    <?php if (!empty($row['profile_photo'])): ?>
                                        <img src="<?= htmlspecialchars('../../uploads/profiles/' . $row['profile_photo']) ?>" alt="Profile">
                                    <?php else: ?>
                                        <?= strtoupper(substr($row['fullname'] ?? $row['username'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($row['fullname'] ?? $row['username']) ?></div>
                                    <div class="contact-username">@<?= htmlspecialchars($row['username']) ?></div>
                                </div>
                                <div class="contact-checkbox" data-id="<?= $row['id'] ?>">
                                    <i class="fas fa-check" style="display: none;"></i>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                            <i class="fas fa-user-plus" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            No contacts available. Add contacts first to create a group.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <button class="create-group-btn" id="createGroupBtn" disabled>Create Group</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM elements
            const groupImageUpload = document.getElementById('groupImageUpload');
            const groupImageInput = document.getElementById('groupImageInput');
            const groupImagePreview = document.getElementById('groupImagePreview');
            const groupNameInput = document.getElementById('groupName');
            const groupDescription = document.getElementById('groupDescription');
            const contactsSearch = document.getElementById('contactsSearch');
            const contactsList = document.getElementById('contactsList');
            const selectedMembersList = document.getElementById('selectedMembersList');
            const createGroupBtn = document.getElementById('createGroupBtn');
            const selectedCount = document.getElementById('selectedCount');
            const nameCharCount = document.getElementById('nameCharCount');
            const descCharCount = document.getElementById('descCharCount');
            
            // Selected members array
            let selectedMembers = [];
            
            // Character count updates
            groupNameInput.addEventListener('input', function() {
                nameCharCount.textContent = this.value.length;
                updateCreateButtonState();
            });
            
            groupDescription.addEventListener('input', function() {
                descCharCount.textContent = this.value.length;
            });
            
            // Group image upload functionality
            groupImageUpload.addEventListener('click', function() {
                groupImageInput.click();
            });
            
            groupImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        showToast('Please select an image file');
                        return;
                    }
                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showToast('Image must be less than 5MB');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        groupImagePreview.src = e.target.result;
                        groupImagePreview.style.display = 'block';
                        groupImageUpload.querySelector('i').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Contact search functionality
            contactsSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const contactItems = contactsList.querySelectorAll('.contact-item');
                
                contactItems.forEach(item => {
                    const name = item.querySelector('.contact-name').textContent.toLowerCase();
                    const username = item.querySelector('.contact-username').textContent.toLowerCase();
                    if (name.includes(searchTerm) || username.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // Contact selection functionality
            contactsList.addEventListener('click', function(e) {
                const contactItem = e.target.closest('.contact-item');
                if (!contactItem) return;
                
                const checkbox = contactItem.querySelector('.contact-checkbox');
                const contactId = checkbox.getAttribute('data-id');
                const contactName = contactItem.querySelector('.contact-name').textContent;
                
                if (checkbox.classList.contains('selected')) {
                    // Deselect contact
                    checkbox.classList.remove('selected');
                    checkbox.querySelector('i').style.display = 'none';
                    
                    // Remove from selected members
                    selectedMembers = selectedMembers.filter(member => member.id !== contactId);
                    
                    // Remove from selected members list
                    const memberElement = document.querySelector(`.selected-member[data-id="${contactId}"]`);
                    if (memberElement) {
                        memberElement.remove();
                    }
                } else {
                    // Select contact
                    checkbox.classList.add('selected');
                    checkbox.querySelector('i').style.display = 'block';
                    
                    // Add to selected members
                    selectedMembers.push({
                        id: contactId,
                        name: contactName
                    });
                    
                    // Add to selected members list
                    const memberElement = document.createElement('div');
                    memberElement.className = 'selected-member';
                    memberElement.setAttribute('data-id', contactId);
                    memberElement.innerHTML = `
                        ${contactName}
                        <button class="remove-member" data-id="${contactId}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    selectedMembersList.appendChild(memberElement);
                    
                    // Add event listener to remove button
                    memberElement.querySelector('.remove-member').addEventListener('click', function(e) {
                        e.stopPropagation();
                        const memberId = this.getAttribute('data-id');
                        
                        // Remove from selected members
                        selectedMembers = selectedMembers.filter(member => member.id !== memberId);
                        
                        // Remove from selected members list
                        this.parentElement.remove();
                        
                        // Update checkbox
                        const checkbox = document.querySelector(`.contact-checkbox[data-id="${memberId}"]`);
                        if (checkbox) {
                            checkbox.classList.remove('selected');
                            checkbox.querySelector('i').style.display = 'none';
                        }
                        
                        // Update UI
                        updateSelectedCount();
                        updateCreateButtonState();
                    });
                }
                
                // Update UI
                updateSelectedCount();
                updateCreateButtonState();
            });
            
            function updateSelectedCount() {
                const count = selectedMembers.length;
                selectedCount.textContent = count;
                
                // Show/hide "no members selected" message
                const noMembersMsg = selectedMembersList.querySelector('.no-members-selected');
                if (count === 0) {
                    if (!noMembersMsg) {
                        const msg = document.createElement('span');
                        msg.className = 'no-members-selected';
                        msg.textContent = 'No members selected';
                        selectedMembersList.appendChild(msg);
                    }
                } else {
                    if (noMembersMsg) {
                        noMembersMsg.remove();
                    }
                }
            }
            
            // Group name validation
            groupNameInput.addEventListener('input', updateCreateButtonState);
            
            // Update create button state based on validation
            function updateCreateButtonState() {
                const hasGroupName = groupNameInput.value.trim().length > 0;
                const hasMembers = selectedMembers.length > 0;
                
                createGroupBtn.disabled = !(hasGroupName && hasMembers);
            }
            
            // Create group functionality
            createGroupBtn.addEventListener('click', function() {
                if (this.disabled) return;
                
                const groupName = groupNameInput.value.trim();
                const groupDesc = groupDescription.value.trim();
                const memberIds = selectedMembers.map(member => member.id);
                
                // Validate
                if (groupName.length < 2) {
                    showToast('Group name must be at least 2 characters');
                    return;
                }
                
                if (memberIds.length < 1) {
                    showToast('Please select at least one member');
                    return;
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('group_name', groupName);
                formData.append('description', groupDesc);
                formData.append('members', JSON.stringify(memberIds));
                
                if (groupImageInput.files[0]) {
                    formData.append('group_image', groupImageInput.files[0]);
                }
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                this.disabled = true;
                
                // ✅ FIXED: Updated fetch URL to match structure
                fetch('content/bisure_create_group_content.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('Group created successfully!');
                        setTimeout(() => {
                            // ✅ FIXED: Redirect to group info page
                            window.location.href = 'mine/converse.php?group_id=' + data.group_id;
                        }, 1000);
                    } else {
                        showToast(data.message || 'Error creating group');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error. Please try again.');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
            
            // Help button functionality
            document.getElementById('helpButton').addEventListener('click', function() {
                showToast('Select contacts and add a group name to create a group chat');
            });
            
            // Toast notification helper
            function showToast(message) {
                const existing = document.querySelector('.toast');
                if (existing) existing.remove();
                
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.textContent = message;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 3000);
            }
        });
    </script>
</body>

</html>