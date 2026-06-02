<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

$current_user_id = $_SESSION['user_id'] ?? null;
$message = '';
$messageType = '';

// Get current user info from database
$user_info = null;
if ($current_user_id) {
    $stmt = $conn->prepare("SELECT fullname, username, email, phone, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiry = trim($_POST['inquiry'] ?? '');
    
    // Validation
    if (empty($inquiry)) {
        $message = 'Please enter your inquiry.';
        $messageType = 'error';
    } elseif (strlen($inquiry) < 10) {
        $message = 'Please provide more details (at least 10 characters).';
        $messageType = 'error';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // ✅ STEP 1: Insert into inquiries table
            $stmt = $conn->prepare("
                INSERT INTO inquiries (user_id, user_fullname, user_username, user_email, user_phone, message, status, priority, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 'medium', NOW())
            ");
            
            $fullname = $user_info['fullname'] ?? $user_info['username'] ?? 'Unknown';
            $username = $user_info['username'] ?? 'N/A';
            $email = $user_info['email'] ?? null;
            $phone = $user_info['phone'] ?? null;
            
            $stmt->bind_param("isssss", $current_user_id, $fullname, $username, $email, $phone, $inquiry);
            $stmt->execute();
            $inquiry_id = $conn->insert_id;
            $stmt->close();
            
            // ✅ STEP 2: Get all admin users
            $admins_query = "SELECT id, fullname, username FROM users WHERE role = 'admin' AND id != ?";
            $admins_stmt = $conn->prepare($admins_query);
            $admins_stmt->bind_param("i", $current_user_id);
            $admins_stmt->execute();
            $admins_result = $admins_stmt->get_result();
            $admins = [];
            while ($admin = $admins_result->fetch_assoc()) {
                $admins[] = $admin;
            }
            $admins_stmt->close();
            
            if (empty($admins)) {
                // If no admins with 'admin' role, fall back to user ID 1
                $fallback = $conn->query("SELECT id, fullname, username FROM users WHERE id = 1 AND id != {$current_user_id}");
                if ($fallback->num_rows > 0) {
                    $admins[] = $fallback->fetch_assoc();
                }
            }
            
            // ✅ STEP 3: Build system notification message for admins
            $inquiry_link = "inquiries?view={$inquiry_id}";
            $current_date = date('F j, Y \a\t g:i A');
            
            // Truncate inquiry for preview
            $preview = mb_strlen($inquiry) > 100 ? mb_substr($inquiry, 0, 100) . '...' : $inquiry;
            
            $admin_notification = "🔔 **NEW SUPPORT INQUIRY**\n\n";
            $admin_notification .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $admin_notification .= "🆔 **Inquiry ID:** #{$inquiry_id}\n";
            $admin_notification .= "👤 **From:** {$fullname} (@{$username})\n";
            $admin_notification .= "📧 **Email:** {$email}\n";
            $admin_notification .= "📱 **Phone:** {$phone}\n";
            $admin_notification .= "📅 **Submitted:** {$current_date}\n";
            $admin_notification .= "🏷️ **Status:** ⚠️ Pending\n";
            $admin_notification .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $admin_notification .= "💬 **Message Preview:**\n{$preview}\n\n";
            $admin_notification .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $admin_notification .= "🔗 **View full inquiry:** {$inquiry_link}\n";
            $admin_notification .= "📋 _Use the Admin Panel to manage this inquiry_";
            
            // ✅ STEP 4: Send notification to each admin via system (NOT from user)
            // Using a SYSTEM user (ID 0 or a dedicated system/bot user)
            // We use the current user as sender but mark it as system-generated
            
            $system_sender_id = $current_user_id; // The inquiry is sent FROM the user
            
            foreach ($admins as $admin) {
                // Find or create conversation between USER and ADMIN
                $conv_query = "
                    SELECT cp1.conversation_id 
                    FROM conversation_participants cp1
                    JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                    JOIN conversations c ON cp1.conversation_id = c.id
                    WHERE cp1.user_id = ? AND cp2.user_id = ? AND c.conversation_type = 'private'
                    LIMIT 1
                ";
                $conv_stmt = $conn->prepare($conv_query);
                $conv_stmt->bind_param("ii", $system_sender_id, $admin['id']);
                $conv_stmt->execute();
                $conv_result = $conv_stmt->get_result();
                
                if ($conv_result->num_rows > 0) {
                    $conversation_id = $conv_result->fetch_assoc()['conversation_id'];
                } else {
                    // Create new conversation between user and admin
                    $create_conv = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
                    $create_conv->bind_param("i", $system_sender_id);
                    $create_conv->execute();
                    $conversation_id = $conn->insert_id;
                    $create_conv->close();
                    
                    // Add both as participants
                    $add_p = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
                    $add_p->bind_param("iiii", $conversation_id, $system_sender_id, $conversation_id, $admin['id']);
                    $add_p->execute();
                    $add_p->close();
                }
                $conv_stmt->close();
                
                // Insert the inquiry notification message
                $insert_msg = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message_type, message_text) VALUES (?, ?, 'text', ?)");
                $insert_msg->bind_param("iis", $conversation_id, $system_sender_id, $admin_notification);
                $insert_msg->execute();
                $insert_msg->close();
            }
            
            // ✅ STEP 5: Also send confirmation to the user in their conversation with the first admin
            if (!empty($admins)) {
                $user_confirmation = "✅ **INQUIRY SUBMITTED**\n\n";
                $user_confirmation .= "━━━━━━━━━━━━━━━━━━━━━━\n";
                $user_confirmation .= "🆔 **Inquiry ID:** #{$inquiry_id}\n";
                $user_confirmation .= "📅 **Submitted:** {$current_date}\n";
                $user_confirmation .= "🏷️ **Status:** Pending Review\n";
                $user_confirmation .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
                $user_confirmation .= "💬 **Your Message:**\n{$inquiry}\n\n";
                $user_confirmation .= "━━━━━━━━━━━━━━━━━━━━━━\n";
                $user_confirmation .= "📋 Our team will review your inquiry and respond shortly.\n";
                $user_confirmation .= "🔗 Reference: #{$inquiry_id}";
                
                // Find conversation with first admin for confirmation
                $conv_query2 = "
                    SELECT cp1.conversation_id 
                    FROM conversation_participants cp1
                    JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                    JOIN conversations c ON cp1.conversation_id = c.id
                    WHERE cp1.user_id = ? AND cp2.user_id = ? AND c.conversation_type = 'private'
                    LIMIT 1
                ";
                $conv_stmt2 = $conn->prepare($conv_query2);
                $conv_stmt2->bind_param("ii", $current_user_id, $admins[0]['id']);
                $conv_stmt2->execute();
                $conv_result2 = $conv_stmt2->get_result();
                
                if ($conv_result2->num_rows > 0) {
                    $conv_id2 = $conv_result2->fetch_assoc()['conversation_id'];
                    $insert_confirm = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message_type, message_text) VALUES (?, ?, 'text', ?)");
                    $insert_confirm->bind_param("iis", $conv_id2, $current_user_id, $user_confirmation);
                    $insert_confirm->execute();
                    $insert_confirm->close();
                }
                $conv_stmt2->close();
            }
            
            // Commit transaction
            $conn->commit();
            
            $message = "Thank you! Your inquiry (#{$inquiry_id}) has been submitted successfully. Our team will respond shortly.";
            $messageType = 'success';
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to submit inquiry. Please try again later.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Contact Us | BisureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cu-primary: #128C7E;
            --cu-primary-dark: #075E54;
            --cu-secondary: #25D366;
            --cu-bg: #e5ddd5;
            --cu-card: #ffffff;
            --cu-text: #2D3748;
            --cu-text-secondary: #718096;
            --cu-border: #E2E8F0;
            --cu-hover: rgba(18, 140, 126, 0.04);
            --cu-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --cu-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --cu-success: #4CAF50;
            --cu-error: #E74C3C;
            --cu-input-bg: #f9f9f9;
            --cu-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --nav-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cu-bg);
            color: var(--cu-text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* HEADER */
        .contacts-header {
            background: linear-gradient(135deg, var(--cu-primary-dark), var(--cu-primary));
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .header-actions {
            flex-shrink: 0;
            width: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .pro-badge {
            background: #FFD700;
            color: #000;
            font-size: 0.65rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* MAIN CONTAINER */
        .contacts-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--cu-card);
            min-height: calc(100vh - 60px);
            box-shadow: var(--cu-shadow);
            transition: background-color 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        /* HERO */
        .cu-hero {
            padding: 2.5rem 1.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--cu-border);
        }

        .cu-hero-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(18, 140, 126, 0.1), rgba(37, 211, 102, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
        }

        .cu-hero-icon i { font-size: 2.2rem; color: var(--cu-primary); }
        .cu-hero h2 { font-size: 1.5rem; font-weight: 600; color: var(--cu-text); margin-bottom: 0.5rem; }
        .cu-hero p { font-size: 0.9rem; color: var(--cu-text-secondary); max-width: 500px; margin: 0 auto; line-height: 1.6; }

        /* USER INFO CARD */
        .cu-user-card {
            margin: 1.5rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, rgba(18, 140, 126, 0.05), rgba(37, 211, 102, 0.05));
            border: 1px solid rgba(18, 140, 126, 0.15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cu-user-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cu-primary), var(--cu-secondary));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 1.3rem;
            flex-shrink: 0; overflow: hidden;
        }

        .cu-user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cu-user-details { flex: 1; min-width: 0; }
        .cu-user-name { font-weight: 600; font-size: 1rem; color: var(--cu-text); margin-bottom: 2px; }

        .cu-user-meta {
            display: flex; flex-wrap: wrap; gap: 8px;
            font-size: 0.75rem; color: var(--cu-text-secondary);
        }

        .cu-user-meta span {
            display: flex; align-items: center; gap: 4px;
            background: var(--cu-card); padding: 3px 10px;
            border-radius: 20px; border: 1px solid var(--cu-border);
        }

        .cu-user-meta span i { color: var(--cu-primary); font-size: 0.65rem; }

        /* FORM */
        .cu-form-section { padding: 0 1.5rem 1.5rem; }
        .cu-form-title { font-size: 1.05rem; font-weight: 600; color: var(--cu-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
        .cu-form-title i { color: var(--cu-primary); }

        .cu-textarea-wrapper { position: relative; }
        .cu-textarea-icon {
            position: absolute; left: 14px; top: 16px;
            color: var(--cu-text-secondary); font-size: 0.9rem;
            z-index: 1; pointer-events: none; transition: var(--cu-transition);
        }

        .cu-textarea {
            width: 100%; padding: 14px 14px 14px 42px;
            border: 2px solid var(--cu-border); border-radius: 14px;
            font-size: 0.9rem; font-family: 'Poppins', sans-serif;
            background: var(--cu-input-bg); color: var(--cu-text);
            resize: vertical; min-height: 160px; outline: none;
            transition: var(--cu-transition); line-height: 1.6;
        }

        .cu-textarea:focus { border-color: var(--cu-primary); box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1); background: var(--cu-card); }
        .cu-textarea:focus ~ .cu-textarea-icon { color: var(--cu-primary); }
        .cu-textarea::placeholder { color: #a0a0a0; }

        .cu-char-count { text-align: right; font-size: 0.75rem; color: var(--cu-text-secondary); margin-top: 4px; }

        .cu-info-text {
            font-size: 0.78rem; color: var(--cu-text-secondary);
            margin-top: 8px; display: flex; align-items: flex-start;
            gap: 6px; line-height: 1.5;
        }

        .cu-info-text i { color: var(--cu-primary); margin-top: 2px; flex-shrink: 0; }

        .cu-submit-btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, var(--cu-primary), var(--cu-secondary));
            color: white; border: none; border-radius: 14px;
            font-size: 0.95rem; font-weight: 600;
            font-family: 'Poppins', sans-serif; cursor: pointer;
            transition: var(--cu-transition);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 15px rgba(18, 140, 126, 0.3); margin-top: 1rem;
        }

        .cu-submit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(18, 140, 126, 0.4); }
        .cu-submit-btn:active { transform: translateY(0); }
        .cu-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* MESSAGES */
        .cu-message { padding: 12px 16px; border-radius: 10px; margin-bottom: 1rem; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .cu-msg-error { background: #FDECEA; color: #C0392B; border-left: 4px solid #E74C3C; }
        .cu-msg-success { background: #E8F5E9; color: #1B5E20; border-left: 4px solid #25D366; }

        /* FAQ */
        .cu-faq { padding: 1.5rem; border-top: 1px solid var(--cu-border); }
        .cu-faq-title { font-size: 1.05rem; font-weight: 600; color: var(--cu-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
        .cu-faq-title i { color: var(--cu-primary); }
        .cu-faq-item { border: 1px solid var(--cu-border); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
        .cu-faq-question { padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; font-weight: 500; font-size: 0.9rem; color: var(--cu-text); transition: var(--cu-transition); }
        .cu-faq-question:hover { background: var(--cu-hover); }
        .cu-faq-question i { transition: var(--cu-transition); color: var(--cu-text-secondary); font-size: 0.8rem; }
        .cu-faq-item.open .cu-faq-question i { transform: rotate(180deg); color: var(--cu-primary); }
        .cu-faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; padding: 0 16px; font-size: 0.85rem; color: var(--cu-text-secondary); line-height: 1.6; }
        .cu-faq-item.open .cu-faq-answer { max-height: 200px; padding: 0 16px 14px; }

        /* DARK MODE */
        body.dark-mode {
            --cu-bg: #0B141A; --cu-card: #1F2C33; --cu-text: #E9EDEF;
            --cu-text-secondary: #8696A0; --cu-border: #2A3942;
            --cu-hover: rgba(255, 255, 255, 0.04); --cu-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            --cu-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.5); --cu-input-bg: #2A3942;
            background: var(--cu-bg);
        }
        body.dark-mode .contacts-container { background-color: #1F2C33; box-shadow: 0 0 20px rgba(0, 0, 0, 0.4); }
        body.dark-mode .cu-textarea { background: var(--cu-input-bg); border-color: #374248; color: var(--cu-text); }
        body.dark-mode .cu-textarea::placeholder { color: var(--cu-text-secondary); }
        body.dark-mode .cu-textarea:focus { border-color: #25D366; box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15); }
        body.dark-mode .cu-user-card { background: linear-gradient(135deg, rgba(37, 211, 102, 0.06), rgba(18, 140, 126, 0.06)); border-color: rgba(37, 211, 102, 0.15); }
        body.dark-mode .cu-user-meta span { background: #2A3942; border-color: #374248; }
        body.dark-mode .cu-msg-error { background: #2A1A1A; color: #F5C6CB; }
        body.dark-mode .cu-msg-success { background: #1A2A1A; color: #81C784; }
        body.dark-mode .cu-faq-item { border-color: #2A3942; }
        body.dark-mode .cu-faq-question:hover { background: rgba(255, 255, 255, 0.04); }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            .contacts-header { padding: 14px 16px; }
            .header-title { font-size: 18px; }
            .cu-hero { padding: 2rem 1rem 1.5rem; }
            .cu-hero-icon { width: 65px; height: 65px; }
            .cu-hero-icon i { font-size: 1.8rem; }
            .cu-hero h2 { font-size: 1.3rem; }
            .cu-user-card { margin: 1rem; padding: 1rem; gap: 10px; }
            .cu-user-avatar { width: 44px; height: 44px; font-size: 1.1rem; }
            .cu-form-section { padding: 0 1rem 1.5rem; }
            .cu-faq { padding: 1rem; }
            .cu-textarea { padding: 12px 12px 12px 38px; font-size: 0.85rem; min-height: 140px; }
        }
        @media (max-width: 360px) { .cu-user-meta { flex-direction: column; gap: 4px; } }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="contacts-header">
    <div><?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?></div>
    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;">
        <div class="header-title">Contact Us <span class="pro-badge">PRO</span></div>
    </div>
    <div class="header-actions"></div>
</div>

<div class="contacts-container">
    
    <div class="cu-hero">
        <div class="cu-hero-icon"><i class="fas fa-headset"></i></div>
        <h2>We're Here to Help</h2>
        <p>Have questions or feedback? Send us a message and our admin team will respond in your chats within 24 hours.</p>
    </div>

    <?php if ($user_info): ?>
    <div class="cu-user-card">
        <div class="cu-user-avatar">
            <?php if (!empty($user_info['profile_photo'])): ?>
                <img src="../../uploads/profiles/<?= htmlspecialchars($user_info['profile_photo']) ?>" alt="Avatar">
            <?php else: ?>
                <?= strtoupper(substr($user_info['fullname'] ?? $user_info['username'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="cu-user-details">
            <div class="cu-user-name"><?= htmlspecialchars($user_info['fullname'] ?? $user_info['username']) ?></div>
            <div class="cu-user-meta">
                <span><i class="fas fa-at"></i> @<?= htmlspecialchars($user_info['username']) ?></span>
                <?php if (!empty($user_info['email'])): ?>
                <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($user_info['email']) ?></span>
                <?php endif; ?>
                <?php if (!empty($user_info['phone'])): ?>
                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($user_info['phone']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="cu-form-section">
        <div class="cu-form-title"><i class="fas fa-pen"></i> Your Inquiry</div>

        <?php if (!empty($message)): ?>
            <div class="cu-message <?= $messageType === 'success' ? 'cu-msg-success' : 'cu-msg-error' ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="cuContactForm">
            <div class="cu-textarea-wrapper">
                <i class="fas fa-comment-dots cu-textarea-icon"></i>
                <textarea name="inquiry" class="cu-textarea" id="cuInquiry" 
                          placeholder="Tell us how we can help you... Describe your issue, question, or feedback in detail."
                          required maxlength="2000"></textarea>
            </div>
            <div class="cu-char-count"><span id="cuCharCount">0</span> / 2000 characters</div>
            
            <div class="cu-info-text">
                <i class="fas fa-info-circle"></i>
                <span>Your name, username, email, and phone number will be automatically included. An administrator will respond to you directly in your chats.</span>
            </div>

            <button type="submit" class="cu-submit-btn" id="cuSubmitBtn">
                <i class="fas fa-paper-plane"></i> Send Inquiry
            </button>
        </form>
    </div>

    <div class="cu-faq">
        <div class="cu-faq-title"><i class="fas fa-question-circle"></i> Frequently Asked Questions</div>
        <div class="cu-faq-item">
            <div class="cu-faq-question" onclick="toggleFaq(this)">How do I start a new conversation?<i class="fas fa-chevron-down"></i></div>
            <div class="cu-faq-answer">Go to the Contacts page, tap on any contact, and start typing your message. You can also use the New Message button to search and message any user.</div>
        </div>
        <div class="cu-faq-item">
            <div class="cu-faq-question" onclick="toggleFaq(this)">How do I make a voice or video call?<i class="fas fa-chevron-down"></i></div>
            <div class="cu-faq-answer">Navigate to the Calls page, select a contact, and choose either Voice Call or Video Call. Ensure you have granted microphone and camera permissions.</div>
        </div>
        <div class="cu-faq-item">
            <div class="cu-faq-question" onclick="toggleFaq(this)">How do I delete my account?<i class="fas fa-chevron-down"></i></div>
            <div class="cu-faq-answer">Go to Settings → Profile → Delete Account. You'll need to verify your identity by entering your email/username and password. This action is permanent.</div>
        </div>
        <div class="cu-faq-item">
            <div class="cu-faq-question" onclick="toggleFaq(this)">Is my data secure?<i class="fas fa-chevron-down"></i></div>
            <div class="cu-faq-answer">Yes! All messages are end-to-end encrypted. We use industry-standard security protocols to protect your data. Your privacy is our top priority.</div>
        </div>
    </div>

</div>

<?php // require_once __DIR__ . '/../includes/navbar.php'; ?>

<script>
    const inquiryTextarea = document.getElementById('cuInquiry');
    const charCount = document.getElementById('cuCharCount');
    
    inquiryTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    function toggleFaq(element) {
        const faqItem = element.parentElement;
        const isOpen = faqItem.classList.contains('open');
        document.querySelectorAll('.cu-faq-item').forEach(item => item.classList.remove('open'));
        if (!isOpen) faqItem.classList.add('open');
    }

    document.getElementById('cuContactForm').addEventListener('submit', function(e) {
        const inquiry = inquiryTextarea.value.trim();
        if (!inquiry) { e.preventDefault(); alert('Please enter your inquiry.'); return; }
        if (inquiry.length < 10) { e.preventDefault(); alert('Please provide more details (at least 10 characters).'); return; }
        const btn = document.getElementById('cuSubmitBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
    });
</script>
</body>
</html>