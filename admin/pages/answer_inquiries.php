<?php
/**
 * BUSure Chat - Admin Inquiries Page
 * ✅ Uses reusable sidebar & footer
 * ✅ Matches busure_lets_chat schema
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../unauthorized");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

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
            $update = $conn->prepare("UPDATE inquiries SET status = ?, admin_response = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
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
            
            // Auto-add to contacts
            $conn->query("INSERT IGNORE INTO contacts (user_id, contact_user_id) VALUES ($current_user_id, $user_id)");
            $conn->query("INSERT IGNORE INTO contacts (user_id, contact_user_id) VALUES ($user_id, $current_user_id)");
            
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
        $update = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
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
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$inquiries_result = $stmt->get_result();

$inquiries_array = [];
while ($row = $inquiries_result->fetch_assoc()) $inquiries_array[] = $row;
$stmt->close();

// Count by status
$counts = $conn->query("SELECT status, COUNT(*) as count FROM inquiries GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$count_map = [];
foreach ($counts as $c) $count_map[$c['status']] = $c['count'];
$total_inquiries = array_sum($count_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inquiries | Admin Panel</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-bar { display: flex; gap: 8px; margin-bottom: 1.25rem; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .stats-bar::-webkit-scrollbar { height: 0; }
        .stat-chip { display: flex; flex-direction: column; align-items: center; padding: 10px 16px; border-radius: 12px; background: #fff; min-width: 75px; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; text-decoration: none; color: inherit; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stat-chip:hover { border-color: #128C7E; }
        .stat-chip.active { border-color: #128C7E; background: rgba(18,140,126,0.05); }
        .stat-chip .sc-num { font-size: 1.3rem; font-weight: 700; }
        .stat-chip .sc-lbl { font-size: 0.7rem; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; }

        .search-wrap { margin-bottom: 1.25rem; }
        .search-inner { position: relative; }
        .search-inner i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }
        .search-inner input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; background: #fff; }
        .search-inner input:focus { outline: none; border-color: #128C7E; box-shadow: 0 0 0 3px rgba(18,140,126,0.1); }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }

        .inq-item { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .inq-item:hover { background: rgba(18,140,126,0.03); }
        .inq-item:last-child { border-bottom: none; }
        .inq-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .inq-user { display: flex; align-items: center; gap: 10px; }
        .inq-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#128C7E,#25D366); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
        .inq-name { font-weight: 600; font-size: 0.9rem; }
        .inq-email { font-size: 0.75rem; color: #718096; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        .status-pending { background: #FFF3CD; color: #856404; }
        .status-read { background: #D6EAF8; color: #1A5276; }
        .status-replied { background: #D5F5E3; color: #1B5E20; }
        .status-resolved { background: #D5F5E3; color: #0E6251; border: 1px solid #2ECC71; }
        .status-closed { background: #EAECEE; color: #4A5568; }

        .inq-preview { font-size: 0.85rem; color: #718096; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .inq-meta { display: flex; align-items: center; gap: 12px; margin-top: 8px; font-size: 0.75rem; color: #A0AEC0; flex-wrap: wrap; }

        .alert { padding: 12px 16px; border-radius: 8px; margin: 0 20px 12px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 16px; width: 90%; max-width: 580px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .modal-head { background: linear-gradient(135deg,#075E54,#128C7E); color: #fff; padding: 18px 20px; border-radius: 16px 16px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-head h3 { font-size: 1.1rem; }
        .modal-close { background: rgba(255,255,255,0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; }
        .modal-close:hover { background: rgba(255,255,255,0.3); }
        .modal-body { padding: 20px; }
        .modal-body textarea { width: 100%; padding: 12px; border: 2px solid #E2E8F0; border-radius: 12px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; min-height: 100px; resize: vertical; }
        .modal-body textarea:focus { outline: none; border-color: #128C7E; }
        .modal-foot { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }

        .btn { padding: 10px 18px; border-radius: 10px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #25D366; color: #fff; } .btn-primary:hover { background: #1ea84e; }
        .btn-success { background: #2ECC71; color: #fff; } .btn-success:hover { background: #27AE60; }
        .btn-secondary { background: #95A5A6; color: #fff; } .btn-secondary:hover { background: #7F8C8D; }
        .btn-cancel { background: #E0E0E0; color: #333; } .btn-cancel:hover { background: #ccc; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.85rem; }
        .detail-lbl { color: #718096; }
        .detail-val { font-weight: 500; text-align: right; }
        .msg-box { background: #f8f9fa; padding: 14px; border-radius: 10px; margin: 12px 0; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
        .msg-box.reply { border-left: 3px solid #25D366; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .modal-box { width: 95%; }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Inquiries</h1>
            <span style="color:#718096;"><?= $total_inquiries ?> total</span>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <a href="?filter=all" class="stat-chip <?= $filter === 'all' ? 'active' : '' ?>">
                <span class="sc-num"><?= $total_inquiries ?></span><span class="sc-lbl">All</span>
            </a>
            <a href="?filter=pending" class="stat-chip <?= $filter === 'pending' ? 'active' : '' ?>">
                <span class="sc-num" style="color:#F39C12;"><?= $count_map['pending'] ?? 0 ?></span><span class="sc-lbl">Pending</span>
            </a>
            <a href="?filter=read" class="stat-chip <?= $filter === 'read' ? 'active' : '' ?>">
                <span class="sc-num" style="color:#3498DB;"><?= $count_map['read'] ?? 0 ?></span><span class="sc-lbl">Read</span>
            </a>
            <a href="?filter=replied" class="stat-chip <?= $filter === 'replied' ? 'active' : '' ?>">
                <span class="sc-num" style="color:#25D366;"><?= $count_map['replied'] ?? 0 ?></span><span class="sc-lbl">Replied</span>
            </a>
            <a href="?filter=resolved" class="stat-chip <?= $filter === 'resolved' ? 'active' : '' ?>">
                <span class="sc-num" style="color:#2ECC71;"><?= $count_map['resolved'] ?? 0 ?></span><span class="sc-lbl">Resolved</span>
            </a>
            <a href="?filter=closed" class="stat-chip <?= $filter === 'closed' ? 'active' : '' ?>">
                <span class="sc-num" style="color:#95A5A6;"><?= $count_map['closed'] ?? 0 ?></span><span class="sc-lbl">Closed</span>
            </a>
        </div>

        <!-- Search -->
        <div class="search-wrap">
            <form method="GET">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <div class="search-inner">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name, email, ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Inquiry List -->
        <div class="card">
            <?php if (count($inquiries_array) > 0): ?>
                <?php foreach ($inquiries_array as $inquiry): ?>
                    <div class="inq-item" onclick="openInquiry(<?= $inquiry['id'] ?>)">
                        <div class="inq-header">
                            <div class="inq-user">
                                <div class="inq-avatar"><?= strtoupper(substr($inquiry['user_fullname'], 0, 1)) ?></div>
                                <div>
                                    <div class="inq-name"><?= htmlspecialchars($inquiry['user_fullname']) ?></div>
                                    <div class="inq-email">@<?= htmlspecialchars($inquiry['user_username']) ?></div>
                                </div>
                            </div>
                            <span class="status-badge status-<?= $inquiry['status'] ?>"><?= ucfirst($inquiry['status']) ?></span>
                        </div>
                        <div class="inq-preview"><?= htmlspecialchars(substr($inquiry['message'], 0, 150)) ?></div>
                        <div class="inq-meta">
                            <span>#<?= $inquiry['id'] ?></span>
                            <span><?= date('M j, Y g:i A', strtotime($inquiry['created_at'])) ?></span>
                            <?php if (!empty($inquiry['user_email'])): ?><span><?= htmlspecialchars($inquiry['user_email']) ?></span><?php endif; ?>
                            <?php if ($inquiry['responder_name']): ?><span style="color:#25D366;">✓ Replied by <?= htmlspecialchars($inquiry['responder_name']) ?></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><h3>No inquiries found</h3><p><?= $filter !== 'all' ? 'No ' . $filter . ' inquiries.' : 'All caught up!' ?></p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Inquiry Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent"></div>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const inquiriesData = <?= json_encode($inquiries_array) ?>;
        const statusOptions = ['pending','read','replied','resolved','closed'];

        function openInquiry(id) {
            const inquiry = inquiriesData.find(i => i.id == id);
            if (!inquiry) return;

            const statusHTML = statusOptions.map(s => 
                `<option value="${s}" ${inquiry.status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`
            ).join('');

            document.getElementById('modalContent').innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0f0f0;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#128C7E,#25D366);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem;">${esc(inquiry.user_fullname).charAt(0).toUpperCase()}</div>
                    <div style="flex:1;">
                        <div style="font-weight:600;">${esc(inquiry.user_fullname)}</div>
                        <div style="font-size:0.78rem;color:#718096;">@${esc(inquiry.user_username)}</div>
                    </div>
                    <span class="status-badge status-${inquiry.status}">${inquiry.status.charAt(0).toUpperCase()+inquiry.status.slice(1)}</span>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;font-size:0.8rem;">
                    <div><span style="color:#718096;">ID</span><br><strong>#${inquiry.id}</strong></div>
                    <div><span style="color:#718096;">Date</span><br><strong>${new Date(inquiry.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</strong></div>
                    ${inquiry.user_email ? `<div><span style="color:#718096;">Email</span><br><strong>${esc(inquiry.user_email)}</strong></div>` : ''}
                    ${inquiry.user_phone ? `<div><span style="color:#718096;">Phone</span><br><strong>${esc(inquiry.user_phone)}</strong></div>` : ''}
                    <div>
                        <span style="color:#718096;">Status</span><br>
                        <select onchange="updateStatus(this,${inquiry.id})" style="padding:3px 6px;border-radius:5px;border:1px solid #ddd;font-size:0.75rem;font-family:'Poppins',sans-serif;">
                            ${statusHTML}
                        </select>
                    </div>
                </div>
                
                <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;">📩 Inquiry Message</div>
                <div class="msg-box">${esc(inquiry.message)}</div>
                
                ${inquiry.admin_response ? `
                <div style="font-weight:600;font-size:0.85rem;margin-top:12px;color:#25D366;">✅ Previous Response</div>
                <div class="msg-box reply">${esc(inquiry.admin_response)}</div>` : ''}
                
                <form method="POST" onsubmit="return validateReply(this)" style="margin-top:16px;">
                    <input type="hidden" name="inquiry_id" value="${inquiry.id}">
                    <input type="hidden" name="user_id" value="${inquiry.user_id}">
                    <input type="hidden" name="status" id="replyStatus" value="replied">
                    <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;">💬 Reply</div>
                    <textarea name="reply_message" placeholder="Type your response... (sent directly to user's chat)" required></textarea>
                    <div class="modal-foot">
                        <button type="submit" name="reply" value="1" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reply</button>
                        <button type="button" class="btn btn-success" onclick="submitWithStatus('resolved')"><i class="fas fa-check-double"></i> Resolve</button>
                        <button type="button" class="btn btn-secondary" onclick="submitWithStatus('closed')"><i class="fas fa-times-circle"></i> Close</button>
                        <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            `;

            document.getElementById('replyModal').classList.add('active');
            if (inquiry.status === 'pending') updateStatusSilent(inquiry.id, 'read');
        }

        function updateStatus(sel, id) {
            fetch('', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: new URLSearchParams({quick_status:sel.value,inquiry_id:id}) })
                .then(() => { const i = inquiriesData.find(x => x.id == id); if(i) i.status = sel.value; setTimeout(() => location.reload(), 500); });
        }

        function updateStatusSilent(id, status) {
            fetch('', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: new URLSearchParams({quick_status:status,inquiry_id:id}) })
                .then(() => { const i = inquiriesData.find(x => x.id == id); if(i) i.status = status; });
        }

        function submitWithStatus(status) {
            document.getElementById('replyStatus').value = status;
            const form = document.querySelector('#modalContent form');
            if (validateReply(form)) { form.querySelector('.btn-primary').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; form.submit(); }
        }

        function validateReply(form) {
            const ta = form.querySelector('textarea');
            if (!ta.value.trim()) { alert('Please enter a reply.'); ta.focus(); return false; }
            if (ta.value.trim().length < 3) { alert('Reply too short.'); ta.focus(); return false; }
            return true;
        }

        function closeModal() { document.getElementById('replyModal').classList.remove('active'); }
        function esc(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }

        document.getElementById('replyModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
    </script>
</body>
</html>
<?php $conn->close(); ?>