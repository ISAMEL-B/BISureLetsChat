<?php
//==============================
// PHP SESSION AND CONFIGURATION START
//==============================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ✅ FIXED: Uses correct column names from users table
$current_user_id = $_SESSION['user_id'];
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// ✅ FIXED: Query uses users table with correct columns
$sql = "
SELECT u.id, u.fullname, u.username, u.phone, u.email, u.profile_photo
FROM users u
WHERE u.id != ?
ORDER BY u.fullname ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
//==============================
// PHP SESSION AND CONFIGURATION END
//==============================
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BISure | Calls</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cl-primary: #128C7E;
            --cl-primary-dark: #075E54;
            --cl-secondary: #25D366;
            --cl-bg: #e5ddd5;
            --cl-card: #ffffff;
            --cl-text: #2D3748;
            --cl-text-secondary: #718096;
            --cl-border: #E2E8F0;
            --cl-hover: rgba(18, 140, 126, 0.04);
            --cl-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --cl-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --cl-online: #25D366;
            --cl-decline: #E74C3C;
            --cl-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --nav-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cl-bg);
            color: var(--cl-text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .cl-page { display: flex; justify-content: center; min-height: 100vh; }
        .cl-wrapper {
            width: 100%; max-width: 600px; background: var(--cl-card);
            min-height: 100vh; box-shadow: var(--cl-shadow-lg);
            position: relative; transition: background 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        .cl-header {
            background: linear-gradient(135deg, var(--cl-primary-dark), var(--cl-primary));
            padding: 1.2rem 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 60px;
        }

        .cl-header-title {
            font-size: 1.35rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .cl-pro-badge {
            background: #FFD700;
            color: #000;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
        }

        .cl-search-wrap { padding: 0.75rem 1rem; background: var(--cl-card); border-bottom: 1px solid var(--cl-border); transition: background 0.3s ease, border-color 0.3s ease; }
        .cl-search-inner { position: relative; }
        .cl-search-inner i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cl-text-secondary); font-size: 0.9rem; }
        .cl-search-inner input {
            width: 100%; padding: 10px 14px 10px 38px; border: 1px solid var(--cl-border); border-radius: 24px;
            font-size: 0.9rem; outline: none; background: var(--cl-card); color: var(--cl-text);
            font-family: 'Poppins', sans-serif; transition: var(--cl-transition);
        }
        .cl-search-inner input:focus { border-color: var(--cl-primary); box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1); }
        .cl-search-inner input::placeholder { color: #a0a0a0; }

        .cl-contacts-list { padding: 0.25rem 0; }
        .cl-contact-item {
            display: flex; align-items: center; padding: 0.75rem 1rem; gap: 12px;
            cursor: pointer; transition: var(--cl-transition); border-bottom: 1px solid var(--cl-border);
        }
        .cl-contact-item:hover { background: var(--cl-hover); }
        .cl-contact-item:active { transform: scale(0.99); }
        .cl-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, var(--cl-primary), var(--cl-secondary));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600; font-size: 1.1rem; flex-shrink: 0; overflow: hidden; position: relative;
        }
        .cl-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cl-online-dot {
            position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px;
            background: var(--cl-online); border-radius: 50%; border: 2px solid var(--cl-card);
        }
        .cl-contact-info { flex: 1; min-width: 0; }
        .cl-contact-name { font-weight: 600; font-size: 0.95rem; color: var(--cl-text); margin-bottom: 2px; }
        .cl-contact-phone { font-size: 0.8rem; color: var(--cl-text-secondary); }
        .cl-contact-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .cl-action-btn {
            width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.95rem; transition: var(--cl-transition);
        }
        .cl-btn-call { background: var(--cl-secondary); color: white; }
        .cl-btn-call:hover { background: #1ea84e; transform: scale(1.1); box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); }
        .cl-btn-video { background: var(--cl-primary); color: white; }
        .cl-btn-video:hover { background: var(--cl-primary-dark); transform: scale(1.1); box-shadow: 0 4px 12px rgba(18, 140, 126, 0.3); }

        /* =============================================
        ACTIVE CALL OVERLAY
        ============================================= */
        .cl-call-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh; height: 100dvh;
            background: #0B141A; z-index: 9999;
            display: none; flex-direction: column;
            animation: clFadeIn 0.3s ease;
        }
        .cl-call-overlay.cl-active { display: flex; }
        @keyframes clFadeIn { from { opacity: 0; } to { opacity: 1; } }

        .cl-video-area {
            flex: 1; position: relative; background: #111;
            display: flex; align-items: center; justify-content: center;
            min-height: 0; overflow: hidden;
        }
        .cl-remote-video {
            width: 100%; height: 100%; object-fit: cover; background: #1a1a1a;
        }
        
        .cl-local-video-pip {
            position: absolute; top: 60px; right: 16px;
            width: 120px; height: 170px; border-radius: 16px;
            overflow: hidden; border: 2px solid rgba(255,255,255,0.6);
            background: #2a2a2a; z-index: 10;
            box-shadow: 0 8px 25px rgba(0,0,0,0.6);
            cursor: grab; touch-action: none; user-select: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .cl-local-video-pip:active { cursor: grabbing; }
        .cl-local-video-pip video {
            width: 100%; height: 100%; object-fit: cover;
            pointer-events: none;
            transform: scaleX(-1);
        }
        .cl-pip-handle {
            position: absolute; top: 6px; left: 50%;
            transform: translateX(-50%); width: 30px; height: 4px;
            background: rgba(255,255,255,0.6); border-radius: 2px; z-index: 2;
        }

        .cl-pip-avatar-placeholder {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: none; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--cl-primary), var(--cl-secondary));
            border-radius: 16px;
        }
        .cl-pip-avatar-placeholder.cl-show { display: flex; }
        .cl-pip-avatar-placeholder i {
            font-size: 3rem; color: white;
        }
        .cl-pip-avatar-placeholder img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 14px;
        }

        .cl-audio-only-indicator {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); text-align: center; color: white;
        }
        .cl-audio-avatar {
            width: 130px; height: 130px; border-radius: 50%;
            background: linear-gradient(135deg, var(--cl-primary), var(--cl-secondary));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem; font-size: 3.5rem; color: white;
            animation: clPulse 2.5s ease-in-out infinite;
            transition: all 0.3s ease;
        }
        .cl-audio-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        @keyframes clPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.5); }
            50% { box-shadow: 0 0 0 35px rgba(37, 211, 102, 0); }
        }

        .cl-call-info-bar {
            padding: 3.5rem 1.5rem 1rem; text-align: center; color: white;
            background: linear-gradient(to bottom, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);
            position: absolute; top: 0; left: 0; right: 0; z-index: 5; pointer-events: none;
        }
        .cl-call-name-overlay { font-size: 1.3rem; font-weight: 600; }
        .cl-call-status-overlay { 
            font-size: 1rem; font-weight: 700; margin-top: 6px;
            letter-spacing: 1px; opacity: 0.95;
        }
        .cl-call-status-overlay.cl-calling-text {
            color: #fff;
            text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        .cl-call-timer-overlay {
            font-size: 1.8rem; font-weight: 300; letter-spacing: 4px;
            color: var(--cl-secondary); margin-top: 8px;
            font-variant-numeric: tabular-nums;
        }

        .cl-dots-anim::after {
            content: '';
            display: inline-block;
            width: 24px;
            text-align: left;
            animation: clDots 2.4s steps(4, end) infinite;
        }
        @keyframes clDots {
            0%, 15% { content: ''; }
            25%, 40% { content: '.'; }
            50%, 65% { content: '..'; }
            75%, 90% { content: '...'; }
            100% { content: ''; }
        }

        .cl-call-controls-bar {
            padding: 1rem 1.5rem 2.5rem; display: flex; align-items: center;
            justify-content: center; gap: 1.25rem;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 50%, transparent 100%);
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 5;
        }
        .cl-ctrl-btn {
            width: 52px; height: 52px; border-radius: 50%; border: none;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; transition: var(--cl-transition); color: white;
        }
        .cl-ctrl-secondary { background: rgba(255,255,255,0.15); }
        .cl-ctrl-secondary:hover { background: rgba(255,255,255,0.3); transform: scale(1.1); }
        .cl-ctrl-secondary.cl-active-ctrl { background: rgba(255,255,255,0.4); border: 2px solid rgba(255,255,255,0.6); }
        .cl-ctrl-end {
            background: var(--cl-decline); width: 62px; height: 62px; font-size: 1.4rem;
        }
        .cl-ctrl-end:hover { background: #c0392b; transform: scale(1.15); box-shadow: 0 8px 25px rgba(231, 76, 60, 0.5); }

        .cl-incoming-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh; height: 100dvh;
            background: rgba(0,0,0,0.9); z-index: 9999;
            display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(15px);
        }
        .cl-incoming-overlay.cl-active { display: flex; }
        .cl-incoming-card {
            background: var(--cl-card); border-radius: 28px; padding: 3rem 2rem;
            text-align: center; max-width: 380px; width: 90%;
            animation: clScaleIn 0.4s ease; box-shadow: 0 25px 70px rgba(0,0,0,0.5);
        }
        @keyframes clScaleIn {
            from { opacity: 0; transform: scale(0.85) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .cl-incoming-avatar {
            width: 100px; height: 100px; border-radius: 50%;
            background: linear-gradient(135deg, var(--cl-primary), var(--cl-secondary));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem; font-size: 2.5rem; color: white; overflow: hidden;
            animation: clPulse 2s ease-in-out infinite;
        }
        .cl-incoming-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cl-incoming-name { font-size: 1.4rem; font-weight: 600; margin-bottom: 6px; color: var(--cl-text); }
        .cl-incoming-type { font-size: 0.95rem; color: var(--cl-text-secondary); margin-bottom: 2.5rem; }
        .cl-incoming-actions { display: flex; justify-content: center; gap: 2.5rem; }
        .cl-incoming-btn-wrap { display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .cl-incoming-btn {
            width: 65px; height: 65px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; transition: var(--cl-transition); color: white;
        }
        .cl-incoming-decline { background: var(--cl-decline); }
        .cl-incoming-decline:hover { background: #c0392b; transform: scale(1.15); box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4); }
        .cl-incoming-accept { background: var(--cl-secondary); }
        .cl-incoming-accept:hover { background: #1ea84e; transform: scale(1.15); box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4); }
        .cl-incoming-label { font-size: 0.8rem; color: var(--cl-text-secondary); font-weight: 500; }

        .cl-modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); z-index: 2000; display: none;
            align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .cl-modal-overlay.cl-active { display: flex; }
        .cl-modal {
            background: var(--cl-card); border-radius: 20px; width: 90%; max-width: 380px;
            overflow: hidden; animation: clScaleIn 0.3s ease; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;
        }
        .cl-modal-top {
            background: linear-gradient(135deg, var(--cl-primary-dark), var(--cl-primary));
            padding: 2rem 1.5rem; text-align: center; color: white;
        }
        .cl-modal-avatar {
            width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 2.5rem; color: white; overflow: hidden;
        }
        .cl-modal-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cl-modal-name { font-size: 1.3rem; font-weight: 600; margin-bottom: 4px; }
        .cl-modal-phone { font-size: 0.85rem; opacity: 0.9; }
        .cl-modal-body { padding: 1.5rem; }
        .cl-modal-row {
            display: flex; align-items: center; gap: 12px; padding: 0.75rem 0;
            border-bottom: 1px solid var(--cl-border);
        }
        .cl-modal-row:last-child { border-bottom: none; }
        .cl-modal-icon {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(18, 140, 126, 0.1); display: flex; align-items: center;
            justify-content: center; color: var(--cl-primary); font-size: 0.85rem;
        }
        .cl-modal-text { font-size: 0.85rem; color: var(--cl-text-secondary); }
        .cl-modal-text strong { color: var(--cl-text); }
        .cl-modal-actions { display: flex; gap: 12px; padding: 0 1.5rem 1.5rem; }
        .cl-modal-btn {
            flex: 1; padding: 12px; border-radius: 12px; border: none; font-weight: 600;
            font-size: 0.9rem; cursor: pointer; font-family: 'Poppins', sans-serif;
            transition: var(--cl-transition); display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .cl-modal-btn-audio { background: var(--cl-secondary); color: white; }
        .cl-modal-btn-audio:hover { background: #1ea84e; transform: translateY(-2px); }
        .cl-modal-btn-video { background: var(--cl-primary); color: white; }
        .cl-modal-btn-video:hover { background: var(--cl-primary-dark); transform: translateY(-2px); }
        .cl-modal-close {
            position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;
            border-radius: 50%; background: rgba(0,0,0,0.3); border: none; color: white;
            cursor: pointer; font-size: 0.9rem; display: flex; align-items: center;
            justify-content: center; transition: var(--cl-transition);
        }
        .cl-modal-close:hover { background: rgba(0,0,0,0.5); }

        .cl-fab-history {
            position: fixed; bottom: calc(var(--nav-height) + 16px); right: 1.5rem; width: 50px; height: 50px;
            border-radius: 50%; background: var(--cl-primary); color: white; border: none;
            cursor: pointer; font-size: 1.1rem; box-shadow: 0 4px 16px rgba(18, 140, 126, 0.3);
            transition: var(--cl-transition); z-index: 50; display: flex; align-items: center; justify-content: center;
        }
        .cl-fab-history:hover { background: var(--cl-primary-dark); transform: translateY(-3px); }
        .cl-empty { text-align: center; padding: 3rem 1.5rem; color: var(--cl-text-secondary); }
        .cl-empty i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
        .cl-empty h3 { font-weight: 600; margin-bottom: 0.5rem; color: var(--cl-text); }

        .cl-toast {
            position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
            z-index: 99999; padding: 14px 28px; border-radius: 14px; color: white;
            font-weight: 600; font-size: 0.95rem; pointer-events: none;
            animation: clToastIn 0.4s ease, clToastOut 0.4s ease 2.5s forwards;
            white-space: nowrap; max-width: 90%;
        }
        @keyframes clToastIn { from { opacity: 0; transform: translateX(-50%) translateY(-20px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
        @keyframes clToastOut { from { opacity: 1; } to { opacity: 0; transform: translateX(-50%) translateY(-20px); } }
        .cl-toast-info { background: linear-gradient(135deg, #128C7E, #25D366); }
        .cl-toast-error { background: #E74C3C; }

        /* DARK MODE */
        body.dark-mode {
            --cl-bg: #0B141A;
            --cl-card: #1F2C33;
            --cl-text: #E9EDEF;
            --cl-text-secondary: #8696A0;
            --cl-border: #2A3942;
            --cl-hover: rgba(255, 255, 255, 0.04);
            --cl-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            --cl-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.5);
            background-color: #0B141A !important;
        }
        body.dark-mode .cl-page { background-color: #0B141A; }
        body.dark-mode .cl-wrapper { background-color: #1F2C33; }
        body.dark-mode .cl-search-wrap { background-color: #1F2C33; border-bottom-color: #2A3942; }
        body.dark-mode .cl-search-inner input { background-color: #2A3942 !important; border-color: #374248 !important; color: #E9EDEF !important; }
        body.dark-mode .cl-search-inner input::placeholder { color: #8696A0 !important; }
        body.dark-mode .cl-search-inner input:focus { border-color: #25D366 !important; }
        body.dark-mode .cl-search-inner i { color: #8696A0 !important; }
        body.dark-mode .cl-contact-item { border-bottom-color: #2A3942; }
        body.dark-mode .cl-contact-item:hover { background-color: rgba(255, 255, 255, 0.04); }
        body.dark-mode .cl-contact-name { color: #E9EDEF !important; }
        body.dark-mode .cl-contact-phone { color: #8696A0 !important; }
        body.dark-mode .cl-online-dot { border-color: #1F2C33; }
        body.dark-mode .cl-empty { color: #8696A0 !important; }
        body.dark-mode .cl-empty h3 { color: #E9EDEF !important; }
        body.dark-mode .cl-modal { background-color: #1F2C33 !important; }
        body.dark-mode .cl-modal-body { background-color: #1F2C33 !important; }
        body.dark-mode .cl-modal-row { border-bottom-color: #2A3942 !important; }
        body.dark-mode .cl-modal-icon { background-color: rgba(37, 211, 102, 0.15) !important; color: #25D366 !important; }
        body.dark-mode .cl-modal-text { color: #8696A0 !important; }
        body.dark-mode .cl-modal-text strong { color: #E9EDEF !important; }
        body.dark-mode .cl-incoming-card { background-color: #1F2C33 !important; }
        body.dark-mode .cl-incoming-name { color: #E9EDEF !important; }
        body.dark-mode .cl-incoming-type { color: #8696A0 !important; }
        body.dark-mode .cl-incoming-label { color: #8696A0 !important; }
        body.dark-mode .cl-fab-history { background-color: #25D366 !important; }
        body.dark-mode .cl-fab-history:hover { background-color: #1ea84e !important; }

        @media (max-width: 480px) {
            .cl-header { padding: 0.9rem 1rem; }
            .cl-contact-item { padding: 0.65rem 0.9rem; gap: 10px; }
            .cl-avatar { width: 42px; height: 42px; font-size: 1rem; }
            .cl-action-btn { width: 36px; height: 36px; font-size: 0.85rem; }
            .cl-local-video-pip { width: 90px; height: 130px; top: 50px; right: 10px; }
            .cl-ctrl-btn { width: 46px; height: 46px; font-size: 1rem; }
            .cl-ctrl-end { width: 56px; height: 56px; font-size: 1.2rem; }
            .cl-call-controls-bar { gap: 1rem; padding: 0.75rem 1rem 2rem; }
            .cl-call-info-bar { padding: 3rem 1rem 0.75rem; }
            .cl-call-name-overlay { font-size: 1.15rem; }
            .cl-call-status-overlay { font-size: 0.9rem; }
            .cl-call-timer-overlay { font-size: 1.5rem; }
            .cl-incoming-card { padding: 2.5rem 1.5rem; }
            .cl-incoming-avatar { width: 80px; height: 80px; font-size: 2rem; }
            .cl-incoming-btn { width: 55px; height: 55px; font-size: 1.2rem; }
            .cl-incoming-actions { gap: 2rem; }
            .cl-pip-avatar-placeholder i { font-size: 2.2rem; }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="cl-page">
    <div class="cl-wrapper">
        <div class="cl-header">
            <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
            <div class="cl-header-title">Calls <span class="cl-pro-badge">PRO</span></div>
        </div>
        <div class="cl-search-wrap">
            <div class="cl-search-inner">
                <i class="fas fa-search"></i>
                <input type="text" id="clSearchInput" placeholder="Search contacts..." autocomplete="off" />
            </div>
        </div>
        <div class="cl-contacts-list" id="clContactsList">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <!-- ✅ FIXED: Uses correct column names -->
                    <div class="cl-contact-item contact-item" 
                         data-user-id="<?= $row['id'] ?>" 
                         data-user-name="<?= htmlspecialchars($row['fullname'] ?? $row['username']) ?>"
                         data-user-phone="<?= htmlspecialchars($row['phone']) ?>"
                         data-user-picture="<?= htmlspecialchars($row['profile_photo'] ?? '') ?>"
                         onclick="clOpenModal(this)">
                        <div class="cl-avatar">
                            <?php if (!empty($row['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars('../../uploads/profiles/' . $row['profile_photo']) ?>" alt="<?= htmlspecialchars($row['fullname'] ?? $row['username']) ?>">
                            <?php else: ?>
                                <?= strtoupper(substr($row['fullname'] ?? $row['username'], 0, 1)) ?>
                            <?php endif; ?>
                            <div class="cl-online-dot"></div>
                        </div>
                        <div class="cl-contact-info">
                            <div class="cl-contact-name"><?= htmlspecialchars($row['fullname'] ?? $row['username']) ?></div>
                            <div class="cl-contact-phone"><?= htmlspecialchars($row['phone']) ?></div>
                        </div>
                        <div class="cl-contact-actions" onclick="event.stopPropagation();">
                            <button class="cl-action-btn cl-btn-call call-btn" title="Voice Call"><i class="fas fa-phone"></i></button>
                            <button class="cl-action-btn cl-btn-video video-call-btn" title="Video Call"><i class="fas fa-video"></i></button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="cl-empty"><i class="fas fa-users"></i><h3>No contacts found</h3><p>Your contacts will appear here</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ACTIVE CALL OVERLAY -->
<div class="cl-call-overlay" id="clCallOverlay">
    <div class="cl-video-area" id="clVideoArea">
        <video id="clRemoteVideo" class="cl-remote-video" autoplay playsinline></video>
        
        <div class="cl-audio-only-indicator" id="clAudioIndicator" style="display:none;">
            <div class="cl-audio-avatar" id="clAudioAvatar"><i class="fas fa-user"></i></div>
        </div>

        <div class="cl-local-video-pip" id="clLocalPip" style="display:none;">
            <div class="cl-pip-handle"></div>
            <video id="clLocalVideo" autoplay muted playsinline></video>
            <div class="cl-pip-avatar-placeholder" id="clPipAvatar">
                <i class="fas fa-user"></i>
            </div>
        </div>

        <div class="cl-call-info-bar">
            <div class="cl-call-name-overlay" id="clCallNameOverlay">Contact</div>
            <div class="cl-call-status-overlay cl-calling-text" id="clCallStatusOverlay">Calling</div>
            <div class="cl-call-timer-overlay" id="clCallTimerOverlay"></div>
        </div>

        <div class="cl-call-controls-bar">
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnMute" title="Mute"><i class="fas fa-microphone"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnVideo" title="Camera"><i class="fas fa-video"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnFlip" title="Switch Camera"><i class="fas fa-sync-alt"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-end" id="clBtnEnd" title="End Call"><i class="fas fa-phone-slash"></i></button>
            <button class="cl-ctrl-btn cl-ctrl-secondary" id="clBtnSpeaker" title="Speaker"><i class="fas fa-volume-up"></i></button>
        </div>
    </div>
</div>

<!-- INCOMING CALL OVERLAY -->
<div class="cl-incoming-overlay" id="clIncomingOverlay">
    <div class="cl-incoming-card">
        <div class="cl-incoming-avatar" id="clIncomingAvatar"><i class="fas fa-user"></i></div>
        <div class="cl-incoming-name" id="clIncomingName">Contact</div>
        <div class="cl-incoming-type" id="clIncomingType">Voice Call</div>
        <div class="cl-incoming-actions">
            <div class="cl-incoming-btn-wrap">
                <button class="cl-incoming-btn cl-incoming-decline" id="clBtnDecline"><i class="fas fa-phone-slash"></i></button>
                <span class="cl-incoming-label">Decline</span>
            </div>
            <div class="cl-incoming-btn-wrap">
                <button class="cl-incoming-btn cl-incoming-accept" id="clBtnAccept"><i class="fas fa-phone"></i></button>
                <span class="cl-incoming-label">Accept</span>
            </div>
        </div>
    </div>
</div>

<!-- PRE-CALL MODAL -->
<div class="cl-modal-overlay" id="clModalOverlay">
    <div class="cl-modal" onclick="event.stopPropagation();">
        <button class="cl-modal-close" onclick="clCloseModal()"><i class="fas fa-times"></i></button>
        <div class="cl-modal-top">
            <div class="cl-modal-avatar" id="clModalAvatar"><i class="fas fa-user"></i></div>
            <div class="cl-modal-name" id="clModalName">Contact</div>
            <div class="cl-modal-phone" id="clModalPhone">+256</div>
        </div>
        <div class="cl-modal-body">
            <div class="cl-modal-row"><div class="cl-modal-icon"><i class="fas fa-phone-alt"></i></div><div class="cl-modal-text">Start a secure <strong>audio call</strong></div></div>
            <div class="cl-modal-row"><div class="cl-modal-icon"><i class="fas fa-video"></i></div><div class="cl-modal-text">Start a <strong>video call</strong> with HD quality</div></div>
            <div class="cl-modal-row"><div class="cl-modal-icon"><i class="fas fa-shield-alt"></i></div><div class="cl-modal-text">End-to-end <strong>encrypted</strong></div></div>
        </div>
        <div class="cl-modal-actions">
            <button class="cl-modal-btn cl-modal-btn-audio" id="clModalAudioBtn"><i class="fas fa-phone"></i> Audio</button>
            <button class="cl-modal-btn cl-modal-btn-video" id="clModalVideoBtn"><i class="fas fa-video"></i> Video</button>
        </div>
    </div>
</div>

<button class="cl-fab-history" id="clHistoryBtn" title="Call History"><i class="fas fa-history"></i></button>
<div id="clToastContainer"></div>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

    <script>
        // ✅ User info from PHP session
        window.SELF_ID = <?= json_encode($current_user_id); ?>;
        window.SELF_NAME = <?= json_encode($_SESSION['fullname'] ?? $_SESSION['username'] ?? ''); ?>;

        const $ = (id) => document.getElementById(id);
        const callOverlay = $('clCallOverlay');
        const remoteVideo = $('clRemoteVideo');
        const localVideo = $('clLocalVideo');
        const localPip = $('clLocalPip');
        const pipAvatar = $('clPipAvatar');
        const audioIndicator = $('clAudioIndicator');
        const audioAvatar = $('clAudioAvatar');
        const callNameOverlay = $('clCallNameOverlay');
        const callStatusOverlay = $('clCallStatusOverlay');
        const callTimerOverlay = $('clCallTimerOverlay');
        const incomingOverlay = $('clIncomingOverlay');
        const incomingName = $('clIncomingName');
        const incomingType = $('clIncomingType');
        const incomingAvatar = $('clIncomingAvatar');
        const modalOverlay = $('clModalOverlay');
        const modalAvatar = $('clModalAvatar');
        const modalName = $('clModalName');
        const modalPhone = $('clModalPhone');
        const toastContainer = $('clToastContainer');

        let clCurrentContact = null;
        let callingDotsInterval = null;

        // ============= CALL API HELPER =============
        function callAPI(action, data = {}) {
            const fd = new FormData();
            fd.append('action', action);
            for (let key in data) fd.append(key, data[key]);
            
            return fetch('content/call_handler.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.call_id) {
                    state.dbCallId = res.call_id;
                }
                return res;
            })
            .catch(err => console.error('Call API error:', err));
        }

        // ============= ANIMATED DOTS =============
        function startCallingDots(baseText) {
            stopCallingDots();
            let dotCount = 0;
            callStatusOverlay.classList.add('cl-dots-anim', 'cl-calling-text');
            callStatusOverlay.textContent = baseText;
            callingDotsInterval = setInterval(() => {
                dotCount = (dotCount % 3) + 1;
                callStatusOverlay.textContent = baseText + '.'.repeat(dotCount);
            }, 800);
        }

        function stopCallingDots() {
            if (callingDotsInterval) clearInterval(callingDotsInterval);
            callingDotsInterval = null;
            callStatusOverlay.classList.remove('cl-dots-anim', 'cl-calling-text');
        }

        // ============= PIP AVATAR =============
        function updatePipAvatar() {
            const pic = state.remotePicture || (clCurrentContact ? clCurrentContact.picture : null);
            if (pic) {
                pipAvatar.innerHTML = '<img src="' + pic + '" alt="User" style="width:100%;height:100%;object-fit:cover;border-radius:14px;">';
            } else {
                pipAvatar.innerHTML = '<i class="fas fa-user"></i>';
            }
        }

        function showPipAvatar() { 
            updatePipAvatar(); 
            pipAvatar.classList.add('cl-show'); 
            localVideo.style.display = 'none'; 
        }
        
        function hidePipAvatar() { 
            pipAvatar.classList.remove('cl-show'); 
            localVideo.style.display = 'block'; 
        }

        // ============= DRAGGABLE PIP =============
        function makeDraggable(el) {
            let isDragging = false, startX, startY, startLeft, startTop;
            el.addEventListener('pointerdown', (e) => {
                isDragging = true;
                startX = e.clientX; startY = e.clientY;
                startLeft = parseInt(el.style.left) || el.getBoundingClientRect().left;
                startTop = parseInt(el.style.top) || el.getBoundingClientRect().top;
                el.style.cursor = 'grabbing';
                el.setPointerCapture(e.pointerId);
            });
            el.addEventListener('pointermove', (e) => {
                if (!isDragging) return;
                const dx = e.clientX - startX, dy = e.clientY - startY;
                const parent = el.parentElement.getBoundingClientRect();
                el.style.left = Math.min(Math.max(startLeft + dx, 0), parent.width - el.offsetWidth - 10) + 'px';
                el.style.top = Math.min(Math.max(startTop + dy, 0), parent.height - el.offsetHeight - 10) + 'px';
                el.style.right = 'auto';
            });
            el.addEventListener('pointerup', () => { isDragging = false; el.style.cursor = 'grab'; });
        }

        // ============= MODAL =============
        function clOpenModal(item) {
            clCurrentContact = {
                id: item.dataset.userId, 
                name: item.dataset.userName,
                phone: item.dataset.userPhone, 
                picture: item.dataset.userPicture
            };
            modalName.textContent = clCurrentContact.name;
            modalPhone.textContent = clCurrentContact.phone;
            modalAvatar.innerHTML = clCurrentContact.picture
                ? '<img src="' + clCurrentContact.picture + '" alt="' + clCurrentContact.name + '" style="width:100%;height:100%;object-fit:cover;">'
                : '<i class="fas fa-user"></i>';
            modalOverlay.classList.add('cl-active');
        }
        
        function clCloseModal() { modalOverlay.classList.remove('cl-active'); }
        
        modalOverlay.addEventListener('click', e => { 
            if (e.target === modalOverlay) clCloseModal(); 
        });
        
        document.addEventListener('keydown', e => { 
            if (e.key === 'Escape') clCloseModal(); 
        });

        $('clModalAudioBtn').addEventListener('click', () => { 
            if (clCurrentContact) { clCloseModal(); startCall(clCurrentContact, false); } 
        });
        
        $('clModalVideoBtn').addEventListener('click', () => { 
            if (clCurrentContact) { clCloseModal(); startCall(clCurrentContact, true); } 
        });

        $('clHistoryBtn').addEventListener('click', () => { 
            window.location.href = 'call_history'; 
        });

        $('clSearchInput').addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.cl-contact-item').forEach(item => {
                const n = item.dataset.userName.toLowerCase(), p = item.dataset.userPhone.toLowerCase();
                item.style.display = (n.includes(term) || p.includes(term)) ? 'flex' : 'none';
            });
        });

        // ============= TOAST =============
        function showToast(msg, type = 'info') {
            const t = document.createElement('div');
            t.className = 'cl-toast cl-toast-' + type;
            t.textContent = msg;
            toastContainer.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }

        // ============= CALL STATE =============
        const MY_ID = String(window.SELF_ID), MY_NAME = String(window.SELF_NAME);
        const WS_URL = 'wss://callingserver-5c0z.onrender.com/ws/signaling-server/';
        const ICE_CONFIG = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };
        const ringtone = new Audio('rington.mp3'); ringtone.loop = true;

        // Prime ringtone on first user interaction
        (function() {
            const u = () => { 
                ringtone.play().then(() => { ringtone.pause(); ringtone.currentTime = 0; }).catch(() => {}); 
                document.removeEventListener('click', u); 
                document.removeEventListener('touchstart', u); 
            };
            document.addEventListener('click', u, { once: true }); 
            document.addEventListener('touchstart', u, { once: true });
        })();

        let state = {
            callState: 'idle',      // idle | dialing | connected
            localStream: null,
            remoteStream: null,
            pc: null,
            isVideo: false,
            remoteId: null,
            remoteName: null,
            remotePicture: null,
            pendingOffer: null,
            timer: null,
            seconds: 0,
            isMuted: false,
            isCameraOff: false,
            dbCallId: null          // ✅ Database call ID
        };

        // ============= TIMER =============
        function startTimer() {
            stopTimer(); 
            stopCallingDots();
            state.seconds = 0;
            callTimerOverlay.textContent = '00:00';
            callStatusOverlay.textContent = 'Connected';
            callStatusOverlay.style.color = '#25D366';
            state.timer = setInterval(() => {
                state.seconds++;
                callTimerOverlay.textContent = 
                    String(Math.floor(state.seconds/60)).padStart(2,'0') + ':' + 
                    String(state.seconds%60).padStart(2,'0');
            }, 1000);
        }

        function stopTimer() { 
            clearInterval(state.timer); 
            callTimerOverlay.textContent = ''; 
            callStatusOverlay.style.color = ''; 
        }

        function cleanupPeer() { 
            if (state.pc) { state.pc.close(); state.pc = null; } 
        }

        function stopStreams() {
            state.localStream?.getTracks().forEach(t => t.stop());
            state.remoteStream?.getTracks().forEach(t => t.stop());
            remoteVideo.srcObject = null; 
            localVideo.srcObject = null;
        }

        // ============= AVATAR UPDATES =============
        function updateAudioAvatar() {
            audioAvatar.innerHTML = state.remotePicture
                ? '<img src="' + state.remotePicture + '" alt="' + state.remoteName + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
                : '<i class="fas fa-user"></i>';
        }

        function updateIncomingAvatarEl() {
            incomingAvatar.innerHTML = state.remotePicture
                ? '<img src="' + state.remotePicture + '" alt="' + state.remoteName + '" style="width:100%;height:100%;object-fit:cover;">'
                : '<i class="fas fa-user"></i>';
        }

        // ============= WEBSOCKET =============
        const sock = new WebSocket(WS_URL);
        
        sock.onopen = () => sock.send(JSON.stringify({ type: 'register', id: MY_ID, name: MY_NAME }));

        sock.onmessage = async ev => {
            const msg = JSON.parse(ev.data);
            if (String(msg.to) !== MY_ID) return;
            
            switch (msg.type) {
                case 'offer':
                    state.isVideo = !!msg.isVideo;
                    state.remoteId = String(msg.from);
                    state.remoteName = msg.fromName || 'User';
                    state.remotePicture = msg.fromPicture || '';
                    state.pendingOffer = msg.sdp;
                    state.dbCallId = msg.callId || null;  // ✅ Get call ID from offer
                    incomingName.textContent = state.remoteName;
                    incomingType.textContent = state.isVideo ? '📹 Video Call' : '📞 Voice Call';
                    updateIncomingAvatarEl();
                    incomingOverlay.classList.add('cl-active');
                    ringtone.currentTime = 0; 
                    ringtone.play().catch(() => {});
                    break;
                    
                case 'answer':
                    if (!state.pc) return;
                    await state.pc.setRemoteDescription(msg.sdp);
                    // ✅ Mark as answered in DB
                    if (state.dbCallId) {
                        callAPI('answer_call', { call_id: state.dbCallId });
                    }
                    startTimer();
                    ringtone.pause(); 
                    ringtone.currentTime = 0;
                    break;
                    
                case 'candidate':
                    if (state.pc && msg.candidate) await state.pc.addIceCandidate(msg.candidate);
                    break;
                    
                case 'decline': 
                    // ✅ DB already updated by the decliner
                    showToast((state.remoteName || 'User') + ' declined', 'error'); 
                    fullReset(); 
                    break;
                    
                case 'hangup': 
                    // ✅ DB already updated by the hanger
                    showToast((state.remoteName || 'User') + ' ended call', 'error'); 
                    fullReset(); 
                    break;
            }
        };
        
        sock.onclose = () => { 
            ringtone.pause(); 
            fullReset(); 
        };

        function send(payload) { 
            // ✅ Always include call_id in WebSocket messages
            sock.send(JSON.stringify({ 
                from: MY_ID, 
                fromName: MY_NAME, 
                to: state.remoteId, 
                fromPicture: '', 
                callId: state.dbCallId,  // ✅ Pass call ID
                ...payload 
            })); 
        }

        // ============= PEER CONNECTION =============
        function buildPeer() {
            state.pc = new RTCPeerConnection(ICE_CONFIG);
            state.pc.onicecandidate = e => { 
                if (e.candidate && state.remoteId) send({ type: 'candidate', candidate: e.candidate }); 
            };
            state.pc.ontrack = e => {
                state.remoteStream = e.streams[0];
                remoteVideo.srcObject = state.remoteStream;
                if (state.isVideo) {
                    audioIndicator.style.display = 'none';
                    localPip.style.display = 'block';
                    remoteVideo.style.display = 'block';
                    hidePipAvatar();
                }
            };
            state.pc.onconnectionstatechange = () => {
                const s = state.pc.connectionState;
                if (s === 'connected') {
                    startTimer();
                } else if (['failed','disconnected','closed'].includes(s)) { 
                    showToast('Call disconnected', 'error'); 
                    // ✅ End call in DB on connection failure
                    if (state.dbCallId) {
                        callAPI('end_call', { call_id: state.dbCallId, duration: state.seconds });
                    }
                    fullReset(); 
                }
            };
        }

        // ============= MEDIA =============
        async function requestMedia(video) {
            try {
                return await navigator.mediaDevices.getUserMedia(
                    video 
                        ? { audio: true, video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } } 
                        : { audio: true }
                );
            } catch (err) { 
                showToast('Media access denied', 'error'); 
                return null; 
            }
        }

        // ============= START CALL (CALLER) =============
        async function startCall(contact, video) {
            if (state.callState !== 'idle') return showToast('Already in a call', 'error');
            
            state.isVideo = !!video;
            state.remoteId = String(contact.id);
            state.remoteName = contact.name;
            state.remotePicture = contact.picture || '';
            
            callNameOverlay.textContent = contact.name;
            callTimerOverlay.textContent = '';
            callStatusOverlay.style.color = '';
            updateAudioAvatar(); 
            updatePipAvatar();
            startCallingDots('Calling');

            // ✅ Show call UI
            if (video) {
                audioIndicator.style.display = 'none';
                localPip.style.display = 'block';
                remoteVideo.style.display = 'block';
                localPip.style.left = ''; localPip.style.top = ''; localPip.style.right = '16px';
                hidePipAvatar();
                state.isCameraOff = false;
                $('clBtnVideo').classList.remove('cl-active-ctrl');
            } else {
                audioIndicator.style.display = 'block';
                localPip.style.display = 'none';
                remoteVideo.style.display = 'none';
            }
            callOverlay.classList.add('cl-active');
            makeDraggable(localPip);

            // ✅ Save call to database FIRST
            const apiResult = await callAPI('start_call', { 
                receiver_id: contact.id, 
                call_type: video ? 'video' : 'voice' 
            });

            // ✅ Get media
            state.localStream = await requestMedia(video);
            if (!state.localStream) {
                // Mark as missed if media fails
                if (state.dbCallId) {
                    await callAPI('missed_call', { call_id: state.dbCallId });
                }
                return fullReset();
            }
            
            if (video) localVideo.srcObject = state.localStream;
            buildPeer();
            state.localStream.getTracks().forEach(t => state.pc.addTrack(t, state.localStream));
            
            const offer = await state.pc.createOffer();
            await state.pc.setLocalDescription(offer);
            send({ type: 'offer', sdp: offer, isVideo: video });
            state.callState = 'dialing';
        }

        // ============= ACCEPT CALL (RECEIVER) =============
        async function acceptCall() {
            ringtone.pause(); 
            ringtone.currentTime = 0;
            
            if (!state.pendingOffer || !state.remoteId) return;
            
            incomingOverlay.classList.remove('cl-active');
            callNameOverlay.textContent = state.remoteName;
            callTimerOverlay.textContent = '';
            callStatusOverlay.style.color = '';
            updateAudioAvatar(); 
            updatePipAvatar();
            startCallingDots('Connecting');

            if (state.isVideo) {
                audioIndicator.style.display = 'none';
                localPip.style.display = 'block';
                remoteVideo.style.display = 'block';
                localPip.style.left = ''; localPip.style.top = ''; localPip.style.right = '16px';
                hidePipAvatar();
                state.isCameraOff = false;
                $('clBtnVideo').classList.remove('cl-active-ctrl');
            } else {
                audioIndicator.style.display = 'block';
                localPip.style.display = 'none';
                remoteVideo.style.display = 'none';
            }
            callOverlay.classList.add('cl-active');
            makeDraggable(localPip);

            // ✅ Mark as answered in DB
            if (state.dbCallId) {
                await callAPI('answer_call', { call_id: state.dbCallId });
            }

            state.localStream = await requestMedia(state.isVideo);
            if (!state.localStream) { 
                // Decline if media fails
                if (state.dbCallId) {
                    await callAPI('decline_call', { call_id: state.dbCallId });
                }
                send({ type: 'decline' }); 
                return fullReset(); 
            }
            
            if (state.isVideo) localVideo.srcObject = state.localStream;
            buildPeer();
            state.localStream.getTracks().forEach(t => state.pc.addTrack(t, state.localStream));
            await state.pc.setRemoteDescription(state.pendingOffer);
            const answer = await state.pc.createAnswer();
            await state.pc.setLocalDescription(answer);
            send({ type: 'answer', sdp: answer });
            state.pendingOffer = null;
            state.callState = 'connected';
        }

        // ============= DECLINE CALL =============
        async function declineCall() { 
            ringtone.pause(); 
            ringtone.currentTime = 0; 
            
            // ✅ Mark as declined in DB
            if (state.dbCallId) {
                await callAPI('decline_call', { call_id: state.dbCallId });
            }
            
            if (state.remoteId) send({ type: 'decline' }); 
            fullReset(); 
        }

        // ============= HANGUP / END CALL =============
        async function hangup() { 
            ringtone.pause(); 
            ringtone.currentTime = 0; 
            
            // ✅ End call in DB with duration
            if (state.dbCallId) {
                await callAPI('end_call', { 
                    call_id: state.dbCallId, 
                    duration: state.seconds 
                });
            }
            
            if (state.remoteId) send({ type: 'hangup' }); 
            fullReset(); 
        }

        // ============= CONTROLS =============
        function toggleMute() {
            if (!state.localStream) return;
            state.isMuted = !state.isMuted;
            state.localStream.getAudioTracks().forEach(t => t.enabled = !state.isMuted);
            $('clBtnMute').classList.toggle('cl-active-ctrl', state.isMuted);
            showToast(state.isMuted ? '🔇 Muted' : '🎤 Unmuted');
        }

        function toggleVideo() {
            if (!state.localStream || !state.isVideo) return;
            state.isCameraOff = !state.isCameraOff;
            state.localStream.getVideoTracks().forEach(t => t.enabled = !state.isCameraOff);
            $('clBtnVideo').classList.toggle('cl-active-ctrl', state.isCameraOff);
            if (state.isCameraOff) { showPipAvatar(); } else { hidePipAvatar(); }
            showToast(state.isCameraOff ? '📷 Camera off' : '📷 Camera on');
        }

        async function flipCamera() {
            if (!state.localStream || !state.isVideo) return;
            const currentTrack = state.localStream.getVideoTracks()[0];
            if (!currentTrack) return;
            const currentFacing = currentTrack.getSettings().facingMode;
            const newFacing = currentFacing === 'user' ? 'environment' : 'user';
            currentTrack.stop();
            try {
                const newStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: newFacing } });
                const newTrack = newStream.getVideoTracks()[0];
                const sender = state.pc.getSenders().find(s => s.track?.kind === 'video');
                if (sender) await sender.replaceTrack(newTrack);
                state.localStream.removeTrack(currentTrack);
                state.localStream.addTrack(newTrack);
                localVideo.srcObject = state.localStream;
                showToast('📷 Camera flipped');
            } catch (e) { showToast('Could not switch camera', 'error'); }
        }

        function toggleSpeaker() { 
            $('clBtnSpeaker').classList.toggle('cl-active-ctrl'); 
            showToast('🔊 Speaker toggled'); 
        }

        // ============= FULL RESET =============
        function fullReset() {
            ringtone.pause(); 
            ringtone.currentTime = 0;
            stopCallingDots();
            cleanupPeer(); 
            stopStreams(); 
            stopTimer();
            
            state = { 
                callState: 'idle', 
                isVideo: false, 
                remoteId: null, 
                pendingOffer: null, 
                remoteName: null, 
                remotePicture: null, 
                isMuted: false, 
                isCameraOff: false,
                localStream: null,
                remoteStream: null,
                pc: null,
                timer: null,
                seconds: 0,
                dbCallId: null       // ✅ Reset DB call ID
            };
            
            callOverlay.classList.remove('cl-active');
            incomingOverlay.classList.remove('cl-active');
            $('clBtnMute').classList.remove('cl-active-ctrl');
            $('clBtnVideo').classList.remove('cl-active-ctrl');
            $('clBtnSpeaker').classList.remove('cl-active-ctrl');
            audioIndicator.style.display = 'none';
            localPip.style.display = 'none';
            remoteVideo.style.display = 'none';
            hidePipAvatar();
            remoteVideo.srcObject = null;
            localVideo.srcObject = null;
        }

        // ============= EVENT LISTENERS =============
        document.querySelectorAll('.cl-contact-item').forEach(item => {
            const c = { 
                id: item.dataset.userId, 
                name: item.dataset.userName, 
                phone: item.dataset.userPhone, 
                picture: item.dataset.userPicture 
            };
            item.querySelector('.call-btn')?.addEventListener('click', e => { 
                e.stopPropagation(); 
                startCall(c, false); 
            });
            item.querySelector('.video-call-btn')?.addEventListener('click', e => { 
                e.stopPropagation(); 
                startCall(c, true); 
            });
        });

        $('clBtnAccept').addEventListener('click', acceptCall);
        $('clBtnDecline').addEventListener('click', declineCall);
        $('clBtnEnd').addEventListener('click', hangup);
        $('clBtnMute').addEventListener('click', toggleMute);
        $('clBtnVideo').addEventListener('click', toggleVideo);
        $('clBtnFlip').addEventListener('click', flipCamera);
        $('clBtnSpeaker').addEventListener('click', toggleSpeaker);
    </script>
</body>
</html>