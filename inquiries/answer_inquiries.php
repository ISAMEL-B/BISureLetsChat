<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

$current_user_id = $_SESSION['user_id'];

// ✅ Check if user is admin
$role_check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$role_check->bind_param("i", $current_user_id);
$role_check->execute();
$user_role = $role_check->get_result()->fetch_assoc()['role'] ?? 'normal';
$role_check->close();

if ($user_role !== 'admin') {
    header("Location: ../chat/contacts");
    exit();
}

$message = '';
$messageType = '';

// ✅ Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $user_id = (int)$_POST['user_id'];
    $reply_message = trim($_POST['reply_message'] ?? '');
    $new_status = $_POST['status'] ?? 'replied';
    
    if (empty($reply_message)) {
        $message = 'Please enter a reply message.';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        
        try {
            // Update inquiry status
            $update = $conn->prepare("UPDATE inquiries SET status = ?, admin_response = ?, responded_by = ?, responded_at = NOW(), updated_at = NOW() WHERE id = ?");
            $update->bind_param("ssii", $new_status, $reply_message, $current_user_id, $inquiry_id);
            $update->execute();
            $update->close();
            
            // Send reply to user's inbox
            $conv_query = "
                SELECT cp1.conversation_id 
                FROM conversation_participants cp1
                JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                JOIN conversations c ON cp1.conversation_id = c.id
                WHERE cp1.user_id = ? AND cp2.user_id = ? AND c.conversation_type = 'private'
                LIMIT 1
            ";
            $conv_stmt = $conn->prepare($conv_query);
            $conv_stmt->bind_param("ii", $current_user_id, $user_id);
            $conv_stmt->execute();
            $conv_result = $conv_stmt->get_result();
            
            if ($conv_result->num_rows > 0) {
                $conversation_id = $conv_result->fetch_assoc()['conversation_id'];
            } else {
                $create_conv = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
                $create_conv->bind_param("i", $current_user_id);
                $create_conv->execute();
                $conversation_id = $conn->insert_id;
                $create_conv->close();
                
                $add_p = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
                $add_p->bind_param("iiii", $conversation_id, $current_user_id, $conversation_id, $user_id);
                $add_p->execute();
                $add_p->close();
            }
            $conv_stmt->close();
            
            // Build reply message
            $formatted_reply = "✅ **INQUIRY RESPONSE #{$inquiry_id}**\n\n";
            $formatted_reply .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $formatted_reply .= "👤 **From:** Support Team\n";
            $formatted_reply .= "📅 **Date:** " . date('F j, Y \a\t g:i A') . "\n";
            $formatted_reply .= "🏷️ **Status:** " . ucfirst($new_status) . "\n";
            $formatted_reply .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $formatted_reply .= "💬 **Response:**\n{$reply_message}\n\n";
            $formatted_reply .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $formatted_reply .= "📋 _This is a response to your inquiry. Reply here if you need further assistance._";
            
            $insert_msg = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message_type, message_text) VALUES (?, ?, 'text', ?)");
            $insert_msg->bind_param("iis", $conversation_id, $current_user_id, $formatted_reply);
            $insert_msg->execute();
            $insert_msg->close();
            
            // If resolved or closed, update resolved_at
            if ($new_status === 'resolved' || $new_status === 'closed') {
                $resolve = $conn->prepare("UPDATE inquiries SET resolved_at = NOW() WHERE id = ?");
                $resolve->bind_param("i", $inquiry_id);
                $resolve->execute();
                $resolve->close();
            }
            
            $conn->commit();
            $message = 'Response sent successfully!';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to send response.';
            $messageType = 'error';
        }
    }
}

// ✅ Handle quick status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $new_status = $_POST['quick_status'];
    
    $allowed = ['pending', 'read', 'replied', 'resolved', 'closed'];
    if (in_array($new_status, $allowed)) {
        $update = $conn->prepare("UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?");
        $update->bind_param("si", $new_status, $inquiry_id);
        $update->execute();
        
        if ($new_status === 'resolved' || $new_status === 'closed') {
            $resolve = $conn->prepare("UPDATE inquiries SET resolved_at = NOW() WHERE id = ?");
            $resolve->bind_param("i", $inquiry_id);
            $resolve->execute();
            $resolve->close();
        }
        $update->close();
    }
    
    // Return JSON for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// ✅ Fetch inquiries with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT i.*, 
          u.fullname as assigned_name, 
          r.fullname as responder_name
          FROM inquiries i 
          LEFT JOIN users u ON i.assigned_to = u.id 
          LEFT JOIN users r ON i.responded_by = r.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($filter !== 'all') {
    $query .= " AND i.status = ?";
    $params[] = $filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (i.user_fullname LIKE ? OR i.user_username LIKE ? OR i.user_email LIKE ? OR i.message LIKE ? OR i.id = ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search;
    $types .= "ssssi";
}

$query .= " ORDER BY 
    CASE i.status 
        WHEN 'pending' THEN 1 
        WHEN 'read' THEN 2 
        WHEN 'replied' THEN 3 
        WHEN 'resolved' THEN 4 
        WHEN 'closed' THEN 5 
    END, 
    i.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inquiries_result = $stmt->get_result();

// ✅ Build array for both PHP display AND JavaScript
$inquiries_array = [];
while ($row = $inquiries_result->fetch_assoc()) {
    $inquiries_array[] = $row;
}

// Count by status
$counts = $conn->query("SELECT status, COUNT(*) as count FROM inquiries GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$count_map = [];
foreach ($counts as $c) {
    $count_map[$c['status']] = $c['count'];
}
$total_inquiries = array_sum($count_map);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inquiries | Admin Panel</title>
    <!-- PWA Meta Tags -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ai-primary: #128C7E;
            --ai-primary-dark: #075E54;
            --ai-secondary: #25D366;
            --ai-bg: #e5ddd5;
            --ai-card: #ffffff;
            --ai-text: #2D3748;
            --ai-text-secondary: #718096;
            --ai-border: #E2E8F0;
            --ai-hover: rgba(18, 140, 126, 0.04);
            --ai-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --ai-pending: #F39C12;
            --ai-read: #3498DB;
            --ai-replied: #25D366;
            --ai-resolved: #2ECC71;
            --ai-closed: #95A5A6;
            --ai-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --nav-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--ai-bg);
            color: var(--ai-text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* HEADER */
        .contacts-header {
            background: linear-gradient(135deg, var(--ai-primary-dark), var(--ai-primary));
            color: #FFFFFF;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 60px;
        }

        .contacts-header > div:first-child {
            flex-shrink: 0;
            width: 40px;
            display: flex;
            align-items: center;
        }

        .header-title {
            flex: 1;
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.3px;
        }

        .header-actions {
            flex-shrink: 0;
            width: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .admin-badge {
            background: #FFD700;
            color: #000;
            font-size: 0.6rem;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* MAIN CONTAINER */
        .ai-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--ai-card);
            min-height: calc(100vh - 60px);
            box-shadow: var(--ai-shadow);
            transition: background-color 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        /* STATS BAR */
        .ai-stats {
            display: flex;
            gap: 8px;
            padding: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-bottom: 1px solid var(--ai-border);
        }
        .ai-stats::-webkit-scrollbar { height: 0; }

        .ai-stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 16px;
            border-radius: 12px;
            background: var(--ai-bg);
            min-width: 70px;
            cursor: pointer;
            transition: var(--ai-transition);
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            flex-shrink: 0;
        }

        .ai-stat-item:hover { border-color: var(--ai-primary); }
        .ai-stat-item.active { border-color: var(--ai-primary); background: rgba(18, 140, 126, 0.08); }

        .ai-stat-count { font-size: 1.3rem; font-weight: 700; }
        .ai-stat-label { font-size: 0.7rem; color: var(--ai-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }

        /* SEARCH */
        .ai-search-wrap { padding: 0.75rem 1rem; border-bottom: 1px solid var(--ai-border); }
        .ai-search-inner { position: relative; }
        .ai-search-inner i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--ai-text-secondary); }
        .ai-search-inner input {
            width: 100%; padding: 10px 14px 10px 38px;
            border: 1px solid var(--ai-border); border-radius: 24px;
            font-size: 0.9rem; outline: none; background: #f9f9f9;
            color: var(--ai-text); font-family: 'Poppins', sans-serif;
        }
        .ai-search-inner input:focus { border-color: var(--ai-primary); box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1); }

        /* INQUIRY LIST */
        .ai-list { padding: 0; }

        .ai-item {
            padding: 16px 20px;
            border-bottom: 1px solid var(--ai-border);
            transition: var(--ai-transition);
            cursor: pointer;
        }

        .ai-item:hover { background: var(--ai-hover); }

        .ai-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .ai-user-info { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }

        .ai-user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--ai-primary), var(--ai-secondary));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }

        .ai-user-name { font-weight: 600; font-size: 0.9rem; }
        .ai-user-email { font-size: 0.75rem; color: var(--ai-text-secondary); }

        /* STATUS BADGES */
        .ai-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .ai-status-pending { background: #FFF3CD; color: #856404; }
        .ai-status-read { background: #D6EAF8; color: #1A5276; }
        .ai-status-replied { background: #D5F5E3; color: #1B5E20; }
        .ai-status-resolved { background: #D5F5E3; color: #0E6251; border: 1px solid #2ECC71; }
        .ai-status-closed { background: #EAECEE; color: #4A5568; }

        .ai-message-preview {
            font-size: 0.85rem;
            color: var(--ai-text-secondary);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ai-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
            font-size: 0.75rem;
            color: var(--ai-text-secondary);
            flex-wrap: wrap;
        }

        .ai-meta span { display: flex; align-items: center; gap: 4px; }
        .ai-meta i { font-size: 0.65rem; }

        /* RESPONSE BADGE */
        .ai-response-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #D5F5E3;
            color: #1B5E20;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* REPLY MODAL */
        .ai-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 2000;
            display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
        }
        .ai-modal-overlay.show { display: flex; }

        .ai-modal {
            background: var(--ai-card); border-radius: 20px;
            width: 90%; max-width: 550px; max-height: 85vh;
            overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: aiSlideUp 0.3s ease;
        }

        @keyframes aiSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ai-modal-header {
            background: linear-gradient(135deg, var(--ai-primary-dark), var(--ai-primary));
            color: white; padding: 20px; border-radius: 20px 20px 0 0;
            position: relative;
        }

        .ai-modal-close {
            position: absolute; top: 14px; right: 14px;
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(255,255,255,0.2); border: none; color: white;
            cursor: pointer; font-size: 1rem; display: flex;
            align-items: center; justify-content: center;
        }

        .ai-modal-close:hover { background: rgba(255,255,255,0.3); }

        .ai-modal-body { padding: 20px; }

        .ai-detail-row {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px solid var(--ai-border);
            font-size: 0.85rem;
        }
        .ai-detail-label { color: var(--ai-text-secondary); }
        .ai-detail-value { font-weight: 500; text-align: right; }

        .ai-full-message {
            background: var(--ai-bg); padding: 14px; border-radius: 10px;
            margin: 12px 0; font-size: 0.9rem; line-height: 1.6;
            white-space: pre-wrap; word-break: break-word;
        }

        .ai-reply-textarea {
            width: 100%; padding: 12px; border: 2px solid var(--ai-border);
            border-radius: 12px; font-size: 0.9rem; font-family: 'Poppins', sans-serif;
            min-height: 100px; resize: vertical; outline: none;
            background: #f9f9f9; color: var(--ai-text);
        }
        .ai-reply-textarea:focus { border-color: var(--ai-primary); }

        .ai-modal-actions { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }

        .ai-btn {
            padding: 10px 20px; border-radius: 10px; border: none;
            font-weight: 600; font-size: 0.85rem; cursor: pointer;
            font-family: 'Poppins', sans-serif; transition: var(--ai-transition);
            display: flex; align-items: center; gap: 6px;
        }

        .ai-btn-reply { background: var(--ai-secondary); color: white; flex: 1; justify-content: center; }
        .ai-btn-reply:hover { background: #1ea84e; }
        .ai-btn-resolve { background: #2ECC71; color: white; }
        .ai-btn-resolve:hover { background: #27AE60; }
        .ai-btn-close-ticket { background: #95A5A6; color: white; }
        .ai-btn-close-ticket:hover { background: #7F8C8D; }
        .ai-btn-cancel { background: #E0E0E0; color: #333; }
        .ai-btn-cancel:hover { background: #ccc; }

        /* MESSAGE ALERT */
        .ai-alert {
            padding: 12px 16px; border-radius: 10px;
            margin: 12px 20px; font-size: 0.85rem;
            display: flex; align-items: center; gap: 8px;
        }
        .ai-alert-error { background: #FDECEA; color: #C0392B; border-left: 4px solid #E74C3C; }
        .ai-alert-success { background: #E8F5E9; color: #1B5E20; border-left: 4px solid #25D366; }

        /* EMPTY STATE */
        .ai-empty { text-align: center; padding: 3rem 1.5rem; color: var(--ai-text-secondary); }
        .ai-empty i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
        .ai-empty h3 { font-weight: 600; margin-bottom: 0.5rem; color: var(--ai-text); }

        /* DARK MODE */
        body.dark-mode {
            --ai-bg: #0B141A; --ai-card: #1F2C33; --ai-text: #E9EDEF;
            --ai-text-secondary: #8696A0; --ai-border: #2A3942;
            --ai-hover: rgba(255, 255, 255, 0.04);
            --ai-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            background: var(--ai-bg);
        }
        body.dark-mode .ai-container { background-color: #1F2C33; box-shadow: 0 0 20px rgba(0,0,0,0.4); }
        body.dark-mode .ai-stat-item { background: #2A3942; }
        body.dark-mode .ai-search-inner input { background: #2A3942; border-color: #374248; color: #E9EDEF; }
        body.dark-mode .ai-search-inner input::placeholder { color: #8696A0; }
        body.dark-mode .ai-item { border-bottom-color: #2A3942; }
        body.dark-mode .ai-item:hover { background: rgba(255,255,255,0.03); }
        body.dark-mode .ai-reply-textarea { background: #2A3942; border-color: #374248; color: #E9EDEF; }
        body.dark-mode .ai-full-message { background: #2A3942; }
        body.dark-mode .ai-status-pending { background: #3D2E00; color: #F39C12; }
        body.dark-mode .ai-status-read { background: #1A2D3D; color: #3498DB; }
        body.dark-mode .ai-status-replied { background: #1A3D1A; color: #25D366; }
        body.dark-mode .ai-status-resolved { background: #1A3D2A; color: #2ECC71; border-color: #2ECC71; }
        body.dark-mode .ai-status-closed { background: #2A2A2A; color: #95A5A6; }
        body.dark-mode .ai-response-badge { background: #1A3D1A; color: #25D366; }
        body.dark-mode .ai-modal { background: #1F2C33; }
        body.dark-mode .ai-btn-cancel { background: #374248; color: #E9EDEF; }
        body.dark-mode .ai-alert-error { background: #2A1A1A; color: #F5C6CB; }
        body.dark-mode .ai-alert-success { background: #1A2A1A; color: #81C784; }

        @media (max-width: 480px) {
            .contacts-header { padding: 14px 16px; }
            .header-title { font-size: 18px; }
            .ai-stats { padding: 0.75rem; gap: 6px; }
            .ai-stat-item { padding: 8px 12px; min-width: 60px; }
            .ai-stat-count { font-size: 1.1rem; }
            .ai-item { padding: 14px 16px; }
            .ai-modal { width: 95%; max-height: 90vh; }
            .ai-modal-actions { flex-direction: column; }
            .ai-btn-reply { width: 100%; }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="contacts-header">
    <div><?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?></div>
    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;">
        <div class="header-title">Inquiries <span class="admin-badge">ADMIN</span></div>
    </div>
    <div class="header-actions"></div>
</div>

<div class="ai-container">
    
    <!-- Stats Bar -->
    <div class="ai-stats">
        <a href="?filter=all" class="ai-stat-item <?= $filter === 'all' ? 'active' : '' ?>">
            <span class="ai-stat-count"><?= $total_inquiries ?></span>
            <span class="ai-stat-label">All</span>
        </a>
        <a href="?filter=pending" class="ai-stat-item <?= $filter === 'pending' ? 'active' : '' ?>">
            <span class="ai-stat-count" style="color: #F39C12;"><?= $count_map['pending'] ?? 0 ?></span>
            <span class="ai-stat-label">Pending</span>
        </a>
        <a href="?filter=read" class="ai-stat-item <?= $filter === 'read' ? 'active' : '' ?>">
            <span class="ai-stat-count" style="color: #3498DB;"><?= $count_map['read'] ?? 0 ?></span>
            <span class="ai-stat-label">Read</span>
        </a>
        <a href="?filter=replied" class="ai-stat-item <?= $filter === 'replied' ? 'active' : '' ?>">
            <span class="ai-stat-count" style="color: #25D366;"><?= $count_map['replied'] ?? 0 ?></span>
            <span class="ai-stat-label">Replied</span>
        </a>
        <a href="?filter=resolved" class="ai-stat-item <?= $filter === 'resolved' ? 'active' : '' ?>">
            <span class="ai-stat-count" style="color: #2ECC71;"><?= $count_map['resolved'] ?? 0 ?></span>
            <span class="ai-stat-label">Resolved</span>
        </a>
        <a href="?filter=closed" class="ai-stat-item <?= $filter === 'closed' ? 'active' : '' ?>">
            <span class="ai-stat-count" style="color: #95A5A6;"><?= $count_map['closed'] ?? 0 ?></span>
            <span class="ai-stat-label">Closed</span>
        </a>
    </div>

    <!-- Search -->
    <div class="ai-search-wrap">
        <form method="GET" style="margin:0;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div class="ai-search-inner">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search inquiries by name, email, or ID..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            </div>
        </form>
    </div>

    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="ai-alert <?= $messageType === 'success' ? 'ai-alert-success' : 'ai-alert-error' ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Inquiry List -->
    <div class="ai-list">
        <?php if (count($inquiries_array) > 0): ?>
            <?php foreach ($inquiries_array as $inquiry): ?>
                <div class="ai-item" onclick="openInquiry(<?= $inquiry['id'] ?>)">
                    <div class="ai-item-header">
                        <div class="ai-user-info">
                            <div class="ai-user-avatar">
                                <?= strtoupper(substr($inquiry['user_fullname'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="ai-user-name"><?= htmlspecialchars($inquiry['user_fullname']) ?></div>
                                <div class="ai-user-email">@<?= htmlspecialchars($inquiry['user_username']) ?></div>
                            </div>
                        </div>
                        <span class="ai-status ai-status-<?= $inquiry['status'] ?>"><?= ucfirst($inquiry['status']) ?></span>
                    </div>
                    
                    <div class="ai-message-preview"><?= htmlspecialchars($inquiry['message']) ?></div>
                    
                    <div class="ai-meta">
                        <span><i class="fas fa-hashtag"></i> #<?= $inquiry['id'] ?></span>
                        <span><i class="fas fa-clock"></i> <?= date('M j, Y g:i A', strtotime($inquiry['created_at'])) ?></span>
                        <?php if (!empty($inquiry['user_email'])): ?>
                            <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($inquiry['user_email']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($inquiry['user_phone'])): ?>
                            <span><i class="fas fa-phone"></i> <?= htmlspecialchars($inquiry['user_phone']) ?></span>
                        <?php endif; ?>
                        
                        <?php if ($inquiry['status'] === 'replied' || $inquiry['status'] === 'resolved'): ?>
                            <span class="ai-response-badge">
                                <i class="fas fa-check-circle"></i> 
                                <?= $inquiry['status'] === 'resolved' ? 'Resolved' : 'Responded' ?>
                                <?php if ($inquiry['responder_name']): ?>
                                    by <?= htmlspecialchars($inquiry['responder_name']) ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ai-empty">
                <i class="fas fa-inbox"></i>
                <h3>No inquiries found</h3>
                <p><?= $filter !== 'all' ? 'No ' . $filter . ' inquiries.' : 'All caught up! No inquiries yet.' ?></p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Reply Modal -->
<div class="ai-modal-overlay" id="aiModalOverlay" onclick="closeModal(event)">
    <div class="ai-modal" id="aiModal" onclick="event.stopPropagation()">
        <div class="ai-modal-header">
            <strong><i class="fas fa-ticket-alt"></i> Inquiry Details</strong>
            <button class="ai-modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="ai-modal-body" id="aiModalBody">
            <!-- Filled by JS -->
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<script>
    // ✅ Properly loaded inquiries data
    const inquiriesData = <?= json_encode($inquiries_array) ?>;

    function openInquiry(id) {
        const inquiry = inquiriesData.find(i => i.id == id);
        if (!inquiry) return;

        const modalBody = document.getElementById('aiModalBody');
        
        const statusOptions = ['pending', 'read', 'replied', 'resolved', 'closed'];
        const statusHTML = statusOptions.map(s => 
            `<option value="${s}" ${inquiry.status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`
        ).join('');

        modalBody.innerHTML = `
            <!-- User Header -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--ai-border);">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#128C7E,#25D366);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.2rem;flex-shrink:0;">
                    ${escapeHtml(inquiry.user_fullname).charAt(0).toUpperCase()}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.95rem;">${escapeHtml(inquiry.user_fullname)}</div>
                    <div style="font-size:0.78rem;color:var(--ai-text-secondary);">@${escapeHtml(inquiry.user_username)}</div>
                </div>
                <span class="ai-status ai-status-${inquiry.status}">${inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1)}</span>
            </div>
            
            <!-- Details Grid -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;font-size:0.8rem;">
                <div>
                    <span style="color:var(--ai-text-secondary);font-size:0.7rem;">🆔 Inquiry ID</span><br>
                    <strong>#${inquiry.id}</strong>
                </div>
                <div>
                    <span style="color:var(--ai-text-secondary);font-size:0.7rem;">📅 Submitted</span><br>
                    <strong>${new Date(inquiry.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'})}</strong>
                </div>
                ${inquiry.user_email ? `
                <div>
                    <span style="color:var(--ai-text-secondary);font-size:0.7rem;">📧 Email</span><br>
                    <strong>${escapeHtml(inquiry.user_email)}</strong>
                </div>` : ''}
                ${inquiry.user_phone ? `
                <div>
                    <span style="color:var(--ai-text-secondary);font-size:0.7rem;">📱 Phone</span><br>
                    <strong>${escapeHtml(inquiry.user_phone)}</strong>
                </div>` : ''}
                <div>
                    <span style="color:var(--ai-text-secondary);font-size:0.7rem;">🏷️ Status</span><br>
                    <form method="POST" style="display:inline;" id="quickStatusForm">
                        <input type="hidden" name="inquiry_id" value="${inquiry.id}">
                        <select name="quick_status" onchange="updateStatus(this, ${inquiry.id})" 
                                style="padding:3px 6px;border-radius:5px;border:1px solid #ddd;font-size:0.75rem;font-family:'Poppins',sans-serif;cursor:pointer;background:var(--ai-card);color:var(--ai-text);">
                            ${statusHTML}
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Inquiry Message -->
            <div style="font-weight:600;font-size:0.85rem;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                <span>📩</span> Inquiry Message
            </div>
            <div class="ai-full-message">${escapeHtml(inquiry.message)}</div>
            
            <!-- Previous Response -->
            ${inquiry.admin_response ? `
            <div style="font-weight:600;font-size:0.85rem;margin-top:12px;margin-bottom:6px;display:flex;align-items:center;gap:6px;color:#25D366;">
                <span>✅</span> Previous Response ${inquiry.responder_name ? 'by ' + escapeHtml(inquiry.responder_name) : ''}
            </div>
            <div class="ai-full-message" style="border-left:3px solid #25D366;">${escapeHtml(inquiry.admin_response)}</div>
            ` : ''}
            
            <!-- Reply Form -->
            <form method="POST" onsubmit="return validateReply(this)" style="margin-top:16px;">
                <input type="hidden" name="inquiry_id" value="${inquiry.id}">
                <input type="hidden" name="user_id" value="${inquiry.user_id}">
                <input type="hidden" name="status" id="replyStatus" value="replied">
                
                <div style="font-weight:600;font-size:0.85rem;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <span>💬</span> Your Reply
                </div>
                <textarea name="reply_message" class="ai-reply-textarea" 
                          placeholder="Type your response to ${escapeHtml(inquiry.user_fullname)}... This will be sent directly to their chat inbox." required></textarea>
                
                <div class="ai-modal-actions">
                    <button type="submit" name="reply" value="1" class="ai-btn ai-btn-reply">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                    <button type="button" class="ai-btn ai-btn-resolve" onclick="submitWithStatus('resolved')">
                        <i class="fas fa-check-double"></i> Resolve
                    </button>
                    <button type="button" class="ai-btn ai-btn-close-ticket" onclick="submitWithStatus('closed')">
                        <i class="fas fa-times-circle"></i> Close
                    </button>
                    <button type="button" class="ai-btn ai-btn-cancel" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        `;

        document.getElementById('aiModalOverlay').classList.add('show');
        
        // Auto-mark as "read" when opened from pending
        if (inquiry.status === 'pending') {
            updateStatusSilent(inquiry.id, 'read');
        }
    }

    function updateStatus(selectEl, inquiryId) {
        const newStatus = selectEl.value;
        // Submit form via fetch
        const formData = new FormData();
        formData.append('quick_status', newStatus);
        formData.append('inquiry_id', inquiryId);
        
        fetch('', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        }).then(() => {
            // Update the inquiry in our local array
            const inquiry = inquiriesData.find(i => i.id == inquiryId);
            if (inquiry) {
                inquiry.status = newStatus;
            }
            // Reload after short delay to reflect changes
            setTimeout(() => location.reload(), 500);
        });
    }

    function updateStatusSilent(inquiryId, newStatus) {
        const formData = new FormData();
        formData.append('quick_status', newStatus);
        formData.append('inquiry_id', inquiryId);
        
        fetch('', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(formData)
        }).then(() => {
            const inquiry = inquiriesData.find(i => i.id == inquiryId);
            if (inquiry) inquiry.status = newStatus;
        });
    }

    function submitWithStatus(status) {
        document.getElementById('replyStatus').value = status;
        const form = document.querySelector('#aiModalBody form');
        if (validateReply(form)) {
            const btns = form.querySelectorAll('button');
            btns.forEach(b => b.disabled = true);
            form.querySelector('.ai-btn-reply').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            form.submit();
        }
    }

    function validateReply(form) {
        const textarea = form.querySelector('textarea');
        if (!textarea.value.trim()) {
            alert('Please enter a reply message.');
            textarea.focus();
            return false;
        }
        if (textarea.value.trim().length < 5) {
            alert('Reply must be at least 5 characters.');
            textarea.focus();
            return false;
        }
        return true;
    }

    function closeModal(e) {
        if (e && e.target !== document.getElementById('aiModalOverlay')) return;
        document.getElementById('aiModalOverlay').classList.remove('show');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('aiModalOverlay').classList.remove('show');
        }
    });
</script>
</body>
</html>