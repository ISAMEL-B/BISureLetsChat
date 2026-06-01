<?php
include 'chat_user_fetch.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// Get current user ID from session
$current_user_id = $_SESSION['user_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en" data-user-id="<?php echo htmlspecialchars($current_user_id ?? ''); ?>" data-selected-contact-id="<?php echo htmlspecialchars($contact_id ?? ''); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat with <?php echo htmlspecialchars($contact_name ?? ''); ?> | BisureChat</title>
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
            --background-light: #E5E5E5;
            --background-dark: #111B21;
            --text-light: #FFFFFF;
            --text-dark: #3B4A54;
            --text-secondary: #667781;
            --border-color: #E9EDEF;
            --sent-message: #C7F9CC;
            --received-message: #F0FDF4;
            --chat-header-bg: #F0F2F5;
            --card-bg: #FFFFFF;
            --danger-color: #E74C3C;
            --warning-color: #F39C12;
            --reply-highlight: #E9EDEF;
            --reply-border: #D1D7DB;
            --input-bg: #FFFFFF;
            --hover-light: rgba(0, 0, 0, 0.04);
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
            height: 100vh;
            overflow: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Main container */
        .main-container {
            display: flex;
            justify-content: center;
            height: 100vh;
            padding: 0 20px;
        }

        /* Chat container */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            max-width: 1200px;
            position: relative;
            background-color: var(--card-bg);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        /* Chat header */
        .chat-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            gap: 12px;
        }

        .back-button {
            color: var(--text-light);
            font-size: 20px;
            margin-right: 15px;
            text-decoration: none;
        }

        .chat-header-content {
            display: flex;
            align-items: center;
            flex: 1;
            gap: 12px;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .online-status {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            border: 2px solid var(--primary-dark);
            box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.3);
        }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-name {
            font-weight: 600;
            font-size: 16px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-status {
            font-size: 12px;
            opacity: 0.85;
        }

        .header-actions {
            display: flex;
            gap: 8px;
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

        /* Messages area */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 15px 20px;
            background-color: var(--background-light);
            background-image: url('css/whitep.png');
            background-repeat: repeat;
            display: flex;
            flex-direction: column;
            gap: 3px;
            transition: background-color 0.3s ease;
        }

        .message {
            max-width: 65%;
            padding: 8px 12px;
            border-radius: 8px;
            position: relative;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sent {
            align-self: flex-end;
            background-color: var(--sent-message);
            border-top-right-radius: 2px;
        }

        .received {
            align-self: flex-start;
            background-color: var(--received-message);
            border-top-left-radius: 2px;
        }

        .message-text {
            font-size: 14px;
            line-height: 1.45;
            color: var(--text-dark);
        }

        .message-time {
            font-size: 11px;
            color: var(--text-secondary);
            text-align: right;
            margin-top: 4px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 3px;
        }

        /* Ticks */
        .tick {
            margin-left: 3px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 1px;
        }

        .tick.purple {
            color: #9c27b0;
        }

        .tick.green-double {
            color: var(--secondary-color);
            position: relative;
        }

        .tick.green-double i {
            display: inline-block;
            width: 10px;
        }

        .tick.green-double i:nth-child(2) {
            margin-left: -3px;
        }

        /* Reply indicator */
        .reply-indicator {
            display: flex;
            align-items: center;
            background-color: var(--reply-highlight);
            border-left: 3px solid var(--primary-color);
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 6px;
            gap: 8px;
        }

        .reply-sender {
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 1px;
            color: var(--primary-color);
        }

        .reply-content {
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        /* Message attachments */
        .message-attachment {
            margin-top: 6px;
            border-radius: 6px;
            overflow: hidden;
            max-width: 100%;
        }

        .message-image {
            max-width: 100%;
            max-height: 250px;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        .message-video,
        .message-audio {
            width: 100%;
            max-width: 280px;
            border-radius: 6px;
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background-color: var(--hover-light);
            border-radius: 6px;
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 5px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .attachment-link:hover {
            background-color: rgba(18, 140, 126, 0.1);
        }

        /* Message actions */
        .message-actions {
            position: absolute;
            top: -8px;
            right: 8px;
            display: none;
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            padding: 2px;
            z-index: 5;
        }

        .message:hover .message-actions,
        .message:active .message-actions {
            display: flex;
        }

        .message-action {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 12px;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 15px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .message-action:hover {
            background: var(--hover-light);
            color: var(--primary-color);
        }

        /* Edited indicator */
        .edited-indicator {
            font-size: 11px;
            color: var(--text-secondary);
            font-style: italic;
            margin-right: 5px;
        }

        /* Deleted message */
        .message.deleted {
            opacity: 0.6;
        }

        .message.deleted .message-text {
            font-style: italic;
            color: var(--text-secondary);
        }

        /* Input area */
        .input-area {
            background-color: var(--card-bg);
            padding: 10px 16px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            border-top: 1px solid var(--border-color);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .input-button {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 20px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .input-button:hover {
            background: rgba(18, 140, 126, 0.1);
            color: var(--primary-color);
        }

        .message-input {
            flex: 1;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 10px 16px;
            font-size: 14px;
            outline: none;
            resize: none;
            max-height: 120px;
            background-color: var(--input-bg);
            color: var(--text-dark);
            font-family: 'Roboto', sans-serif;
            transition: all 0.3s ease;
        }

        .message-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
        }

        .message-input::placeholder {
            color: #a0a0a0;
        }

        .send-button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
        }

        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* File name display */
        .file-name-display {
            padding: 8px 16px;
            font-size: 13px;
            color: var(--text-secondary);
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: none;
            transition: background-color 0.3s ease;
        }

        .file-name-display.show {
            display: block;
        }

        /* Reply preview */
        .reply-preview {
            padding: 8px 16px;
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: none;
            transition: background-color 0.3s ease;
        }

        .reply-preview.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .reply-preview-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .reply-preview-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .cancel-reply {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 16px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .cancel-reply:hover {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .overlay img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            width: 90%;
            max-width: 400px;
            border-radius: 12px;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--text-light);
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-modal {
            font-size: 22px;
            cursor: pointer;
            opacity: 0.8;
        }

        .close-modal:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 20px;
            color: var(--text-dark);
        }

        .modal-footer {
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid var(--border-color);
        }

        .modal-button {
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .modal-cancel {
            background-color: #f0f0f0;
            color: var(--text-dark);
        }

        .modal-cancel:hover {
            background-color: #e0e0e0;
        }

        .modal-confirm {
            background-color: var(--danger-color);
            color: white;
        }

        .modal-confirm:hover {
            background-color: #c0392b;
        }

        .edit-modal-textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: none;
            font-size: 14px;
            background-color: var(--card-bg);
            color: var(--text-dark);
            font-family: 'Roboto', sans-serif;
        }

        .edit-modal-button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .edit-modal-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }

        /* Show more button */
        .show-more-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .show-more-btn:hover {
            background-color: rgba(18, 140, 126, 0.1);
            text-decoration: underline;
        }

        /* Loading spinner */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s ease-in-out infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* File preview styles */
        .file-preview-container {
            display: none;
            padding: 10px 16px;
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
        }

        .file-preview-container.show {
            display: block;
        }

        .file-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .file-preview img, 
        .file-preview video {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
        }

        .file-icon {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background-color: var(--hover-light);
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .remove-file-btn {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .remove-file-btn:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }

        /* Premium Preview Styles */
        .premium-preview-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .premium-preview-container.active {
            transform: scale(1);
        }

        .premium-preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .premium-default-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .premium-preview-details h3 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .premium-preview-details .status {
            color: var(--secondary-color);
            font-size: 14px;
        }

        .premium-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            color: #333;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
        }

        .premium-close-btn:hover {
            background: rgba(255, 0, 0, 0.8);
            color: white;
        }

        .premium-preview-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .premium-action-btn {
            background: rgba(18, 140, 126, 0.1);
            border: none;
            color: var(--primary-color);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
        }

        .premium-action-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        /* =============================================
           DARK MODE - COMPREHENSIVE OVERRIDES
           ============================================= */
        body.dark-mode {
            --background-light: #0B141A;
            --card-bg: #1F2C33;
            --text-dark: #E9EDEF;
            --text-secondary: #8696A0;
            --border-color: #2A3942;
            --received-message: #202C33;
            --sent-message: #005C4B;
            --chat-header-bg: #202C33;
            --reply-highlight: #2A3942;
            --reply-border: #374248;
            --input-bg: #2A3942;
            --hover-light: rgba(255, 255, 255, 0.05);
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        body.dark-mode .chat-container {
            background-color: var(--background-light);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
        }

        body.dark-mode .messages-container {
            background-color: var(--background-light);
            background-image: none;
        }

        body.dark-mode .message-input {
            background-color: var(--input-bg);
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        body.dark-mode .message-input::placeholder {
            color: var(--text-secondary);
        }

        body.dark-mode .input-area {
            background-color: var(--card-bg);
            border-top-color: var(--border-color);
        }

        body.dark-mode .attachment-link {
            color: var(--secondary-color);
            background-color: rgba(255, 255, 255, 0.06);
        }

        body.dark-mode .attachment-link:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }

        body.dark-mode .file-name-display,
        body.dark-mode .file-preview-container {
            background-color: var(--card-bg);
            border-top-color: var(--border-color);
        }

        body.dark-mode .reply-preview {
            background-color: var(--card-bg);
            border-bottom-color: var(--border-color);
        }

        body.dark-mode .message-actions {
            background-color: #2A3942;
        }

        body.dark-mode .message-action:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        body.dark-mode .modal-content {
            background-color: #1F2C33;
        }

        body.dark-mode .modal-body {
            color: var(--text-dark);
        }

        body.dark-mode .modal-cancel {
            background-color: #2A3942;
            color: var(--text-dark);
        }

        body.dark-mode .modal-cancel:hover {
            background-color: #374248;
        }

        body.dark-mode .edit-modal-textarea {
            background-color: #2A3942;
            color: var(--text-dark);
            border-color: var(--border-color);
        }

        body.dark-mode .show-more-btn:hover {
            background-color: rgba(37, 211, 102, 0.15);
        }

        body.dark-mode .premium-preview-container {
            background: rgba(30, 40, 45, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .premium-preview-content {
            color: white;
        }

        body.dark-mode .premium-action-btn {
            background: rgba(255, 255, 255, 0.08);
        }

        body.dark-mode .premium-action-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        body.dark-mode .premium-close-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        body.dark-mode .premium-close-btn:hover {
            background: rgba(255, 0, 0, 0.7);
        }

        body.dark-mode .file-icon {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--text-dark);
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .main-container {
                padding: 0;
            }

            .messages-container {
                margin-bottom: 0;
            }

            .chat-container {
                box-shadow: none;
                border-radius: 0;
            }

            .message {
                max-width: 80%;
            }

            .chat-header {
                padding: 10px 14px;
            }

            .input-area {
                padding: 8px 12px;
            }

            .message-input {
                padding: 8px 14px;
            }
        }

        @media (min-width: 768px) {
            .chat-container {
                width: 80%;
                max-width: 1200px;
                border-radius: 12px;
                overflow: hidden;
                margin: 20px 0;
                height: calc(100vh - 40px);
            }
        }

        .input-error {
            border: 2px solid var(--danger-color) !important;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15) !important;
        }
    </style>
</head>

<body class="<?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="overlay" id="overlay"></div>

    <div class="main-container">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <div>
                    <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
                </div>

                <div class="chat-header-content">
                    <div class="chat-avatar">
                        <?php if (!empty($contact_profile_photo) && $contact_profile_photo !== '../asssets/images/user.png'): ?>
                            <img src="<?php echo htmlspecialchars($contact_profile_photo); ?>" alt="<?php echo htmlspecialchars($contact_name); ?>" class="avatar-img">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background-color:#128C7E; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">
                                <?php echo strtoupper(substr($contact_name ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="online-status"></span>
                    </div>

                    <div class="chat-info">
                        <div class="chat-name"><?php echo htmlspecialchars($contact_name ?? 'Unknown'); ?></div>
                        <div class="chat-status" id="contactStatus">Online</div>
                    </div>
                </div>
            </div>

            <!-- Reply Preview -->
            <div class="reply-preview" id="replyPreview">
                <div class="reply-preview-content">
                    <div class="reply-preview-text" id="replyPreviewText"></div>
                </div>
                <button class="cancel-reply" id="cancelReplyButton">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <?php
                        $is_sender = ($message['sender_id'] == $current_user_id);
                        $message_class = $is_sender ? 'sent' : 'received';
                        $message_text = htmlspecialchars_decode($message['message_text'] ?? '');
                        $message_time = date('h:i A | M d', strtotime($message['created_at']));
                        $attachment_path = $message['attachment_path'] ?? '';
                        $message_type = $message['message_type'] ?? 'text';
                        $message_id = $message['id'];
                        $is_edited = $message['is_edited'] ?? 0;
                        $is_deleted = $message['is_deleted'] ?? 0;

                        // Determine read status
                        $is_read = false;
                        if (isset($message['read_at']) && $message['read_at'] !== null) {
                            $is_read = true;
                        }
                        
                        $tick_class = 'tick';
                        if ($is_sender) {
                            $tick_class .= $is_read ? ' green-double' : ' purple';
                        }
                        
                        $deleted_class = $is_deleted ? ' deleted' : '';
                        ?>

                        <div class="message <?php echo $message_class . $deleted_class; ?>" data-message-id="<?php echo $message_id; ?>" data-message-type="<?php echo $message_type; ?>">
                            <?php if (isset($message['reply_to_id']) && !empty($message['reply_to_id'])): ?>
                                <?php
                                // Fetch replied message details
                                $reply_query = "
                                    SELECT m.message_text, m.attachment_path, m.message_type, u.fullname, u.username 
                                    FROM messages m 
                                    JOIN users u ON m.sender_id = u.id 
                                    WHERE m.id = ?
                                ";
                                $reply_stmt = $conn->prepare($reply_query);
                                $reply_stmt->bind_param("i", $message['reply_to_id']);
                                $reply_stmt->execute();
                                $reply_result = $reply_stmt->get_result();
                                $replied_message = $reply_result->fetch_assoc();
                                $reply_stmt->close();
                                
                                $reply_content = '';
                                if ($replied_message) {
                                    if (!empty($replied_message['attachment_path'])) {
                                        $reply_content = '📎 ' . ucfirst($replied_message['message_type']);
                                    } else {
                                        $reply_content = substr($replied_message['message_text'], 0, 100);
                                    }
                                    $reply_sender = $replied_message['fullname'] ?? $replied_message['username'];
                                }
                                ?>
                                <?php if ($replied_message): ?>
                                <div class="reply-indicator">
                                    <div>
                                        <div class="reply-sender"><?php echo htmlspecialchars($reply_sender); ?></div>
                                        <div class="reply-content"><?php echo htmlspecialchars($reply_content); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="message-text">
                                <?php if ($is_deleted): ?>
                                    <em>This message was deleted</em>
                                <?php else: ?>
                                    <?php
                                    $message_words = explode(' ', $message_text);
                                    $is_long_message = count($message_words) > 50;
                                    $display_text = $is_long_message ? implode(' ', array_slice($message_words, 0, 50)) : $message_text;
                                    echo nl2br(htmlspecialchars($display_text));
                                    ?>
                                    <?php if ($is_long_message): ?>
                                        <button class="show-more-btn" onclick="showMoreMessage(this, <?php echo $message_id; ?>)">Read More</button>
                                        <div class="full-message" style="display:none;"><?php echo nl2br(htmlspecialchars($message_text)); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$is_deleted && !empty($attachment_path)): ?>
                                <?php
                                $file_extension = strtolower(pathinfo($attachment_path, PATHINFO_EXTENSION));
                                $file_name = basename($attachment_path);
                                ?>

                                <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <div class="message-attachment">
                                        <img src="<?php echo htmlspecialchars($attachment_path); ?>" alt="<?php echo htmlspecialchars($file_name); ?>" class="message-image" onclick="openImage('<?php echo htmlspecialchars($attachment_path); ?>')" loading="lazy">
                                    </div>
                                <?php elseif (in_array($file_extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])): ?>
                                    <div class="message-attachment">
                                        <a href="<?php echo htmlspecialchars($attachment_path); ?>" download="<?php echo htmlspecialchars($file_name); ?>" class="attachment-link">
                                            <i class="fas fa-file"></i> <?php echo htmlspecialchars($file_name); ?>
                                        </a>
                                    </div>
                                <?php elseif (in_array($file_extension, ['mp3', 'wav', 'ogg'])): ?>
                                    <div class="message-attachment">
                                        <audio controls class="message-audio">
                                            <source src="<?php echo htmlspecialchars($attachment_path); ?>" type="audio/<?php echo ($file_extension === 'mp3' ? 'mpeg' : $file_extension); ?>">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                <?php elseif (in_array($file_extension, ['mp4', 'webm', 'ogv', 'avi', 'mkv'])): ?>
                                    <div class="message-attachment">
                                        <video controls class="message-video">
                                            <source src="<?php echo htmlspecialchars($attachment_path); ?>" type="video/<?php echo ($file_extension === 'mp4' ? 'mp4' : $file_extension); ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                <?php else: ?>
                                    <div class="message-attachment">
                                        <a href="<?php echo htmlspecialchars($attachment_path); ?>" download="<?php echo htmlspecialchars($file_name); ?>" class="attachment-link">
                                            <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($file_name); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="message-time">
                                <?php if ($is_edited && !$is_deleted): ?>
                                    <span class="edited-indicator">edited</span>
                                <?php endif; ?>
                                <?php echo $message_time; ?>
                                <?php if ($is_sender && !$is_deleted): ?>
                                    <span class="<?php echo $tick_class; ?>" id="tick-<?php echo $message_id; ?>">
                                        <i class="fas fa-check tick-icon"></i>
                                        <?php if ($is_read): ?>
                                            <i class="fas fa-check tick-icon"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!$is_deleted): ?>
                            <div class="message-actions">
                                <?php if ($is_sender && $message_type === 'text'): ?>
                                    <button class="message-action" onclick="openEditModal(<?php echo $message_id; ?>, '<?php echo addslashes($message_text); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="message-action" onclick="confirmDeleteMessage(<?php echo $message_id; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="message-action" onclick="setReplyMessage(<?php echo $message_id; ?>, '<?php echo addslashes(substr($message_text, 0, 50)); ?>', '<?php echo $is_sender ? 'You' : htmlspecialchars($contact_name ?? 'User'); ?>')">
                                    <i class="fas fa-reply"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 50px; color: var(--text-secondary);">
                        <i class="fas fa-comments" style="font-size: 50px; margin-bottom: 15px;"></i>
                        <p>No messages yet</p>
                        <p>Start the conversation with <?php echo htmlspecialchars($contact_name ?? 'this user'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- File Name Display -->
            <div class="file-name-display" id="fileNameDisplay"></div>
            
            <!-- File Preview -->
            <div class="file-preview-container" id="filePreviewContainer"></div>

            <!-- Input Area -->
            <div class="input-area">
                <button class="input-button" id="emojiButton" title="Emoji">
                    <i class="far fa-smile"></i>
                </button>
                <button class="input-button" id="attachButton" title="Attach File">
                    <i class="fas fa-paperclip"></i>
                </button>
                <input type="file" id="fileInput" style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                <textarea class="message-input" id="messageInput" placeholder="Type a message" rows="1"></textarea>
                <button class="send-button" id="sendButton" title="Send">
                    <i class="fas fa-paper-plane"></i>
                    <div class="spinner" id="sendSpinner"></div>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Message Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span>Edit Message</span>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <textarea class="edit-modal-textarea" id="editMessageInput" placeholder="Edit your message"></textarea>
            </div>
            <div class="modal-footer">
                <button class="modal-button modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button class="modal-button edit-modal-button" onclick="updateMessage()">Update</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span>Delete Message</span>
                <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this message? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-button modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="modal-button modal-confirm" id="confirmDeleteButton">Delete</button>
            </div>
        </div>
    </div>
    
    <script>
        // Current message being edited/deleted/replied to
        let currentEditId = null;
        let currentDeleteId = null;
        let currentReplyId = null;
        let currentReplyContent = '';
        let currentReplySender = '';
        let currentConversationId = <?php echo $conversation_id ?? 'null'; ?>;

        // Initialize touch events for swipe to reply
        let touchStartX = 0;
        let touchStartY = 0;

        // DOM Ready Handler
        document.addEventListener('DOMContentLoaded', function() {
            setupAutoResizeTextarea();
            setupFileInputHandling();
            setupProfilePicturePreview();
            setupMessageSending();
            setupModalHandlers();
            setupSwipeToReply();
            scrollToBottom();
        });

        // Auto-resize textarea
        function setupAutoResizeTextarea() {
            const messageInput = document.getElementById('messageInput');
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        // File input handling
        function setupFileInputHandling() {
            const fileInput = document.getElementById('fileInput');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const attachButton = document.getElementById('attachButton');
            const filePreviewContainer = document.getElementById('filePreviewContainer');

            attachButton.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    fileNameDisplay.textContent = '📎 ' + file.name;
                    fileNameDisplay.classList.add('show');

                    filePreviewContainer.innerHTML = '';
                    filePreviewContainer.classList.remove('show');

                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            filePreviewContainer.innerHTML = `
                                <div class="file-preview">
                                    <img src="${e.target.result}" alt="Preview">
                                    <div class="file-info">
                                        <span>${file.name}</span>
                                        <small>${formatFileSize(file.size)}</small>
                                    </div>
                                    <button class="remove-file-btn" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            filePreviewContainer.classList.add('show');
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type.startsWith('video/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            filePreviewContainer.innerHTML = `
                                <div class="file-preview">
                                    <video controls>
                                        <source src="${e.target.result}" type="${file.type}">
                                        Your browser doesn't support video preview.
                                    </video>
                                    <button class="remove-file-btn" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            filePreviewContainer.classList.add('show');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        filePreviewContainer.innerHTML = `
                            <div class="file-preview">
                                <div class="file-icon">
                                    <i class="fas fa-file"></i>
                                    <span>${file.name}</span>
                                    <small>${formatFileSize(file.size)}</small>
                                </div>
                                <button class="remove-file-btn" onclick="removeFile()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                        filePreviewContainer.classList.add('show');
                    }
                } else {
                    removeFile();
                }
            });

            window.removeFile = function() {
                fileInput.value = '';
                fileNameDisplay.textContent = '';
                fileNameDisplay.classList.remove('show');
                filePreviewContainer.innerHTML = '';
                filePreviewContainer.classList.remove('show');
            };
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Profile Picture Preview
        function setupProfilePicturePreview() {
            const profilePics = document.querySelectorAll('.chat-avatar .avatar-img, .chat-avatar > div');

            profilePics.forEach(pic => {
                pic.style.cursor = 'pointer';

                pic.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const overlay = document.getElementById('overlay');
                    const profilePicUrl = this.tagName === 'IMG' ? this.src : null;
                    const contactName = this.alt || '<?php echo addslashes($contact_name ?? 'User'); ?>';

                    overlay.innerHTML = `
                        <div class="premium-preview-container">
                            <div class="premium-preview-content">
                                ${profilePicUrl ? 
                                    `<img src="${profilePicUrl}" alt="${contactName}" class="premium-preview-image">` : 
                                    `<div class="premium-default-avatar">${this.textContent.trim()}</div>`
                                }
                                <div class="premium-preview-details">
                                    <h3>${contactName}</h3>
                                    <p class="status online">Online</p>
                                </div>
                                <button class="premium-close-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="premium-preview-actions">
                                    <button class="premium-action-btn" title="View Full Size">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                    <button class="premium-action-btn" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;

                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';

                    setTimeout(() => {
                        overlay.querySelector('.premium-preview-container').classList.add('active');
                    }, 10);

                    overlay.querySelector('.premium-close-btn').addEventListener('click', function() {
                        overlay.querySelector('.premium-preview-container').classList.remove('active');
                        setTimeout(() => {
                            overlay.style.display = 'none';
                            overlay.innerHTML = '';
                            document.body.style.overflow = '';
                        }, 300);
                    });

                    const expandBtn = overlay.querySelector('.premium-action-btn:nth-child(1)');
                    const downloadBtn = overlay.querySelector('.premium-action-btn:nth-child(2)');

                    if (profilePicUrl) {
                        expandBtn.addEventListener('click', () => window.open(profilePicUrl, '_blank'));
                        downloadBtn.addEventListener('click', () => {
                            const a = document.createElement('a');
                            a.href = profilePicUrl;
                            a.download = `profile_${contactName.replace(/\s+/g, '_')}.jpg`;
                            a.click();
                        });
                    } else {
                        expandBtn.style.display = 'none';
                        downloadBtn.style.display = 'none';
                    }
                });
            });

            document.getElementById('overlay').addEventListener('click', function(e) {
                if (e.target === this) {
                    const container = this.querySelector('.premium-preview-container');
                    if (container) {
                        container.classList.remove('active');
                        setTimeout(() => {
                            this.style.display = 'none';
                            this.innerHTML = '';
                            document.body.style.overflow = '';
                        }, 300);
                    }
                }
            });
        }

        // Message sending functionality
        function setupMessageSending() {
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');

            sendButton.addEventListener('click', sendMessage);

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            function sendMessage() {
                const message = messageInput.value.trim();
                const file = fileInput.files[0];

                if (!message && !file) {
                    messageInput.classList.add('input-error');
                    messageInput.addEventListener('input', removeInputError);
                    return;
                }

                function removeInputError() {
                    messageInput.classList.remove('input-error');
                }

                const formData = new FormData();
                if (message) formData.append('reply_message', message);
                if (file) formData.append('file', file);
                formData.append('receiver_id', <?php echo $contact_id ?? 0; ?>);

                if (currentReplyId) {
                    formData.append('reply_to_id', currentReplyId);
                }

                const sendSpinner = document.getElementById('sendSpinner');

                sendButton.disabled = true;
                sendButton.querySelector('i').style.display = 'none';
                sendSpinner.style.display = 'block';

                fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            messageInput.value = '';
                            messageInput.style.height = 'auto';
                            removeFile();

                            if (currentReplyId) {
                                cancelReply();
                            }

                            // Update conversation ID if it was newly created
                            if (data.conversation_id) {
                                currentConversationId = data.conversation_id;
                            }

                            // Reload messages or append new message
                            location.reload();
                        } else {
                            throw new Error(data.error || 'Failed to send message');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    })
                    .finally(() => {
                        sendButton.disabled = false;
                        sendButton.querySelector('i').style.display = 'block';
                        sendSpinner.style.display = 'none';
                    });
            }
        }

        // Modal handlers
        function setupModalHandlers() {
            // Edit modal
            window.openEditModal = function(messageId, messageText) {
                currentEditId = messageId;
                document.getElementById('editMessageInput').value = messageText;
                document.getElementById('editModal').style.display = 'flex';
            };

            window.closeEditModal = function() {
                document.getElementById('editModal').style.display = 'none';
                currentEditId = null;
            };

            window.updateMessage = function() {
                const updatedText = document.getElementById('editMessageInput').value.trim();

                if (!updatedText) {
                    alert("Message cannot be empty");
                    return;
                }

                const formData = new FormData();
                formData.append('message_id', currentEditId);
                formData.append('message', updatedText);

                fetch('edit_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert("Error: " + (data.message || 'Failed to update message'));
                        }
                    })
                    .catch(error => {
                        alert('Failed to update message. Please try again.');
                        console.error('Error:', error);
                    });
            };

            // Delete modal
            window.confirmDeleteMessage = function(messageId) {
                currentDeleteId = messageId;
                document.getElementById('deleteModal').style.display = 'flex';
            };

            window.closeDeleteModal = function() {
                document.getElementById('deleteModal').style.display = 'none';
                currentDeleteId = null;
            };

            document.getElementById('confirmDeleteButton').addEventListener('click', function() {
                if (!currentDeleteId) return;

                const formData = new FormData();
                formData.append('message_id', currentDeleteId);

                fetch('delete_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert("Error: " + (data.message || 'Failed to delete message'));
                        }
                    })
                    .catch(error => {
                        alert('Failed to delete message. Please try again.');
                        console.error('Error:', error);
                    });
            });

            // Reply functions
            window.setReplyMessage = function(messageId, messageContent, senderName) {
                currentReplyId = messageId;
                currentReplyContent = messageContent;
                currentReplySender = senderName;

                const replyPreview = document.getElementById('replyPreview');
                const replyPreviewText = document.getElementById('replyPreviewText');

                replyPreviewText.textContent = `Replying to ${senderName}: ${messageContent}${messageContent.length > 50 ? '...' : ''}`;
                replyPreview.classList.add('show');
                document.getElementById('messageInput').focus();
            };

            window.cancelReply = function() {
                currentReplyId = null;
                currentReplyContent = '';
                currentReplySender = '';
                document.getElementById('replyPreview').classList.remove('show');
            };

            document.getElementById('cancelReplyButton').addEventListener('click', cancelReply);
        }

        // Swipe to reply
        function setupSwipeToReply() {
            document.querySelectorAll('.message').forEach(message => {
                let touchStartX = 0;
                let touchStartY = 0;

                message.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                    touchStartY = e.changedTouches[0].screenY;
                }, { passive: true });

                message.addEventListener('touchend', function(e) {
                    const touchEndX = e.changedTouches[0].screenX;
                    const touchEndY = e.changedTouches[0].screenY;
                    const dx = touchEndX - touchStartX;
                    const dy = touchEndY - touchStartY;

                    if (Math.abs(dx) > 50 && Math.abs(dy) < 50 && dx > 0) {
                        const messageId = this.getAttribute('data-message-id');
                        const messageText = this.querySelector('.message-text')?.textContent || '';
                        const isSender = this.classList.contains('sent');
                        const senderName = isSender ? 'You' : '<?php echo addslashes($contact_name ?? 'User'); ?>';

                        setReplyMessage(messageId, messageText.substring(0, 50), senderName);
                    }
                }, { passive: true });
            });
        }

        // Scroll to bottom
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Show more message text
        window.showMoreMessage = function(button, messageId) {
            const messageContainer = button.parentElement;
            const fullMessage = messageContainer.querySelector('.full-message');
            const words = fullMessage.textContent.split(/\s+/);

            let currentCount = parseInt(messageContainer.getAttribute('data-words-shown')) || 50;
            const newCount = currentCount + 50;

            if (newCount >= words.length) {
                messageContainer.innerHTML = fullMessage.innerHTML;
            } else {
                const partialText = words.slice(0, newCount).join(' ');
                messageContainer.innerHTML = nl2br(partialText) +
                    `<button class="show-more-btn" onclick="showMoreMessage(this, ${messageId})">Read More</button>` +
                    `<div class="full-message" style="display:none;">${fullMessage.textContent}</div>`;
                messageContainer.setAttribute('data-words-shown', newCount);
            }
        };

        // Helper function to replace \n with <br>
        function nl2br(str) {
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
        }

        // Open image in overlay
        window.openImage = function(imageUrl) {
            const overlay = document.getElementById('overlay');
            overlay.innerHTML = `
                <div style="position: relative; max-width: 90%; max-height: 90%;">
                    <img src="${imageUrl}" alt="Preview" style="max-width: 100%; max-height: 90vh; border-radius: 8px;">
                    <button onclick="this.closest('#overlay').style.display='none'" 
                            style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            overlay.style.display = 'flex';

            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    this.innerHTML = '';
                }
            });
        };
    </script>
</body>
</html>