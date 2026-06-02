<?php
// includes/call_system.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    exit;
}

$current_user_id = $_SESSION['user_id'];
$profile_pic_base = '../settings/uploads/profiles/';

// Get current user's profile picture
$user_sql = "SELECT profile_photo FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_picture = !empty($user_data['profile_photo']) ? $profile_pic_base . $user_data['profile_photo'] : '';
?>