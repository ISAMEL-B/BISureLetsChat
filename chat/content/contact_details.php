<?php
/**
 * BUSure Chat - Contact Fetch
 * Fetches contacts/conversations for the current user
 * 
 * ✅ Updated to match busure_lets_chat schema
 */

session_start();

require_once __DIR__ . '/../../config/db.php';

$current_user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$current_user_id) {
    exit('User not authenticated');
}

// ============================================
// FETCH CONTACTS WITH LAST MESSAGE PREVIEW
// ============================================
$sql = "
SELECT DISTINCT
    u.id,
    u.fullname,
    u.username,
    u.phone,
    u.email,
    u.profile_photo,
    u.is_online,
    u.last_seen,
    
    -- Last sent message (from current user to contact)
    (
        SELECT m.message_text
        FROM messages m
        INNER JOIN conversation_participants cp_sent
            ON cp_sent.conversation_id = m.conversation_id
            AND cp_sent.user_id = ?
        INNER JOIN conversation_participants cp_received
            ON cp_received.conversation_id = m.conversation_id
            AND cp_received.user_id = u.id
        WHERE m.sender_id = ?
          AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_sent_message,
    
    -- Last sent message time
    (
        SELECT m.created_at
        FROM messages m
        INNER JOIN conversation_participants cp_sent
            ON cp_sent.conversation_id = m.conversation_id
            AND cp_sent.user_id = ?
        INNER JOIN conversation_participants cp_received
            ON cp_received.conversation_id = m.conversation_id
            AND cp_received.user_id = u.id
        WHERE m.sender_id = ?
          AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_sent_time,
    
    -- Last received message (from contact to current user)
    (
        SELECT m.message_text
        FROM messages m
        INNER JOIN conversation_participants cp_sent
            ON cp_sent.conversation_id = m.conversation_id
            AND cp_sent.user_id = u.id
        INNER JOIN conversation_participants cp_received
            ON cp_received.conversation_id = m.conversation_id
            AND cp_received.user_id = ?
        WHERE m.sender_id = u.id
          AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_received_message,
    
    -- Last received message time
    (
        SELECT m.created_at
        FROM messages m
        INNER JOIN conversation_participants cp_sent
            ON cp_sent.conversation_id = m.conversation_id
            AND cp_sent.user_id = u.id
        INNER JOIN conversation_participants cp_received
            ON cp_received.conversation_id = m.conversation_id
            AND cp_received.user_id = ?
        WHERE m.sender_id = u.id
          AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_received_time,
    
    -- Unread message count (messages from contact not yet read by current user)
    (
        SELECT COUNT(*)
        FROM messages m
        INNER JOIN conversation_participants cp
            ON cp.conversation_id = m.conversation_id
            AND cp.user_id = u.id
        WHERE m.sender_id = u.id
          AND m.is_deleted = 0
          AND m.id NOT IN (
              SELECT mr.message_id
              FROM message_reads mr
              WHERE mr.user_id = ?
          )
          AND m.conversation_id IN (
              SELECT cp2.conversation_id
              FROM conversation_participants cp2
              WHERE cp2.user_id = ?
          )
    ) AS unread_count

FROM users u

INNER JOIN conversation_participants cp1
    ON cp1.user_id = ?

INNER JOIN conversation_participants cp2
    ON cp2.conversation_id = cp1.conversation_id

INNER JOIN conversations c
    ON c.id = cp1.conversation_id
    AND c.conversation_type = 'private'

WHERE cp2.user_id = u.id
  AND u.id != ?

ORDER BY 
    GREATEST(
        COALESCE(last_sent_time, '1970-01-01'),
        COALESCE(last_received_time, '1970-01-01')
    ) DESC,
    u.fullname ASC
";

$stmt = $conn->prepare($sql);

// All the ? placeholders in order:
// 1: current_user_id (for last_sent_message subquery)
// 2: current_user_id (for last_sent_message sender)
// 3: current_user_id (for last_sent_time subquery)
// 4: current_user_id (for last_sent_time sender)
// 5: current_user_id (for last_received_message subquery)
// 6: current_user_id (for last_received_time subquery)
// 7: current_user_id (for unread_count - user who reads)
// 8: current_user_id (for unread_count - conversation participant)
// 9: current_user_id (main JOIN cp1)
// 10: current_user_id (WHERE exclude self)

$stmt->bind_param(
    "iiiiiiiiii",
    $current_user_id,  // 1
    $current_user_id,  // 2
    $current_user_id,  // 3
    $current_user_id,  // 4
    $current_user_id,  // 5
    $current_user_id,  // 6
    $current_user_id,  // 7
    $current_user_id,  // 8
    $current_user_id,  // 9
    $current_user_id   // 10
);

$stmt->execute();

$result = $stmt->get_result();