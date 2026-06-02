<?php
session_start();

// Include database configuration
require __DIR__ . '/../../config/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Required</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: "Poppins", sans-serif;
                background: linear-gradient(135deg, #075E54, #128C7E);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: white;
                padding: 40px 30px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                width: 100%;
                max-width: 420px;
                text-align: center;
            }
            .card i { font-size: 60px; color: #E74C3C; margin-bottom: 20px; }
            .card h2 { font-size: 1.5rem; color: #2D3748; margin-bottom: 12px; }
            .card p { font-size: 0.95rem; color: #718096; margin-bottom: 24px; line-height: 1.6; }
            .card a {
                display: inline-block;
                padding: 12px 28px;
                background: linear-gradient(135deg, #128C7E, #25D366);
                color: white;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                font-size: 0.95rem;
                transition: all 0.3s ease;
            }
            .card a:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(18, 140, 126, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="card">
            <i class="fas fa-lock"></i>
            <h2>Access Denied</h2>
            <p>You must be logged in to delete your account.</p>
            <a href="../register.php"><i class="fas fa-sign-in-alt"></i> Login to Continue</a>
        </div>
    </body>
    </html>';
    exit;
}

$current_user_id = $_SESSION['user_id'];
$errorMessage = '';
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input values
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate inputs
    if (empty($identifier) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: delete_account.php');
        exit;
    }

    // ✅ Check if identifier matches email OR username (using your users table)
    $query = "SELECT id, email, username, password_hash FROM users WHERE (email = ? OR username = ?) AND id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $identifier, $identifier, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid credentials. Please check your email/username.';
        header('Location: delete_account.php');
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // ✅ Verify password using password_hash column
    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['error'] = 'Invalid password. Please try again.';
        header('Location: delete_account.php');
        exit;
    }

    // ✅ Start transaction for safe deletion
    mysqli_begin_transaction($conn);

    try {
        $user_id = $user['id'];

        // ✅ Delete related records first (respecting foreign key constraints)
        
        // 1. Delete from archived_chats
        $stmt = $conn->prepare("DELETE FROM archived_chats WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 2. Delete from contacts (both directions)
        $stmt = $conn->prepare("DELETE FROM contacts WHERE user_id = ? OR contact_user_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 3. Delete from message_reads
        $stmt = $conn->prepare("DELETE FROM message_reads WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 4. Delete from message_reactions
        $stmt = $conn->prepare("DELETE FROM message_reactions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 5. Delete from email_logs
        $stmt = $conn->prepare("DELETE FROM email_logs WHERE sender_id = ? OR recipient_user_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 6. Delete from email_verifications
        $stmt = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 7. Delete from password_resets
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 8. Delete from group_members
        $stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 9. Delete from conversation_participants
        $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 10. Delete messages sent by the user
        $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 11. Delete conversations created by the user
        $stmt = $conn->prepare("DELETE FROM conversations WHERE created_by = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 12. Delete groups created by the user
        $stmt = $conn->prepare("DELETE FROM groups_chat WHERE created_by = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 13. Delete calls (both directions)
        $stmt = $conn->prepare("DELETE FROM calls WHERE caller_id = ? OR receiver_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 14. Delete user settings
        $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 15. ✅ Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        mysqli_commit($conn);

        // Clear session and redirect
        session_destroy();
        
        // Start new session for success message
        session_start();
        $_SESSION['success'] = 'Your account has been permanently deleted. We\'re sorry to see you go.';
        
        header('Location: ../register.php');
        exit;

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        $_SESSION['error'] = 'Failed to delete account. Please try again later.';
        header('Location: delete_account.php');
        exit;
    }
}

$conn->close();
?>