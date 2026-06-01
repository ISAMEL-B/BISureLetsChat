<?php
// Start of PHP code with no preceding whitespace
date_default_timezone_set('Africa/Kampala'); // Set to Uganda's timezone

// Enable error reporting during development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library files
require __DIR__ . '/../plugins/PHPMailer/src/Exception.php';
require __DIR__ . '/../plugins/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../plugins/PHPMailer/src/SMTP.php';

// Database connection
require_once __DIR__ . '/../../register/config/db.php';

// Initialize message variables
$message = '';
$messageType = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Retrieve and sanitize form data
        $recipient_email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8');
        $message_content = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
        
        // Get sender ID from session if available
        $sender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // Initialize attachment variables
        $attachmentPath = null;
        $attachment_name = null;

        // Validate email format
        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Validate required fields
        if (empty($subject)) {
            throw new Exception("Subject is required.");
        }
        
        if (empty($message_content)) {
            throw new Exception("Message content is required.");
        }

        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/email_attachments/';
            
            // Ensure the uploads directory exists
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }

            // Validate file size (max 5MB)
            $file_size = $_FILES['attachment']['size'];
            if ($file_size > 5 * 1024 * 1024) {
                throw new Exception("File size must be less than 5MB.");
            }

            // Generate unique filename to prevent overwrites
            $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $attachment_name = time() . '_' . uniqid() . '.' . $file_extension;
            $attachmentPath = $uploadDir . $attachment_name;
            
            // Web-relative path for database storage
            $attachment_db_path = 'uploads/email_attachments/' . $attachment_name;

            if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $attachmentPath)) {
                throw new Exception("Failed to upload attachment.");
            }
        }

        // Begin transaction
        $conn->begin_transaction();

        // Find or create user for this email if they exist in our system
        $recipient_id = null;
        if ($sender_id) {
            $user_check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $user_check_stmt->bind_param("s", $recipient_email);
            $user_check_stmt->execute();
            $user_result = $user_check_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $recipient_id = $user_data['id'];
            }
            $user_check_stmt->close();
        }

        // Insert into email log table (create this table if it doesn't exist)
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS email_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_id BIGINT UNSIGNED NULL,
                recipient_email VARCHAR(255) NOT NULL,
                recipient_user_id BIGINT UNSIGNED NULL,
                subject VARCHAR(255) NOT NULL,
                message_text LONGTEXT NOT NULL,
                attachment_path VARCHAR(255) NULL,
                status ENUM('pending','sent','failed') DEFAULT 'pending',
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $conn->query($create_table_sql);

        // Insert email log
        $insert_stmt = $conn->prepare("
            INSERT INTO email_logs (sender_id, recipient_email, recipient_user_id, subject, message_text, attachment_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare database statement: " . $conn->error);
        }

        $insert_stmt->bind_param(
            "isiss", 
            $sender_id, 
            $recipient_email, 
            $recipient_id, 
            $subject, 
            $message_content, 
            $attachment_db_path ?? null
        );

        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to save email log: " . $insert_stmt->error);
        }
        
        $email_log_id = $conn->insert_id;
        $insert_stmt->close();

        // If recipient exists in our system, also create a chat message
        if ($recipient_id && $sender_id) {
            // Find or create conversation
            $conv_query = "
                SELECT cp1.conversation_id 
                FROM conversation_participants cp1
                JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
                JOIN conversations c ON cp1.conversation_id = c.id
                WHERE cp1.user_id = ? 
                AND cp2.user_id = ? 
                AND c.conversation_type = 'private'
                LIMIT 1
            ";
            
            $conv_stmt = $conn->prepare($conv_query);
            $conv_stmt->bind_param("ii", $sender_id, $recipient_id);
            $conv_stmt->execute();
            $conv_result = $conv_stmt->get_result();
            
            if ($conv_result->num_rows > 0) {
                $conversation = $conv_result->fetch_assoc();
                $conversation_id = $conversation['conversation_id'];
            } else {
                // Create new conversation
                $create_conv = $conn->prepare("INSERT INTO conversations (conversation_type, created_by) VALUES ('private', ?)");
                $create_conv->bind_param("i", $sender_id);
                $create_conv->execute();
                $conversation_id = $conn->insert_id;
                $create_conv->close();
                
                // Add participants
                $add_participants = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
                $add_participants->bind_param("iiii", $conversation_id, $sender_id, $conversation_id, $recipient_id);
                $add_participants->execute();
                $add_participants->close();
            }
            $conv_stmt->close();
            
            // Create chat message with email content
            $chat_message = "📧 Email: {$subject}\n\n{$message_content}";
            
            $msg_insert = $conn->prepare("
                INSERT INTO messages (conversation_id, sender_id, message_type, message_text, attachment_path) 
                VALUES (?, ?, 'text', ?, ?)
            ");
            $msg_insert->bind_param("iiss", $conversation_id, $sender_id, $chat_message, $attachment_db_path ?? null);
            $msg_insert->execute();
            $msg_insert->close();
        }

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'byaruhangaisamelk@gmail.com';
        $mail->Password   = 'gjmb sylv wpvx ygrh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('byaruhangaisamelk@gmail.com', 'BISureChat HELP DESK');
        $mail->addAddress($recipient_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Customized HTML email body
        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>BISure Chat Message</title>
                <style>
                    body {
                        font-family: 'Poppins', Arial, sans-serif;
                        background-color: #f5f7fa;
                        margin: 0;
                        padding: 0;
                        color: #333;
                        line-height: 1.6;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background: white;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    }
                    .email-header {
                        background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
                        padding: 25px;
                        text-align: center;
                        color: white;
                    }
                    .email-logo {
                        font-size: 24px;
                        font-weight: 600;
                        margin-bottom: 10px;
                    }
                    .email-content {
                        padding: 30px;
                    }
                    .message-content {
                        background-color: #f9f9f9;
                        padding: 20px;
                        border-radius: 8px;
                        border-left: 4px solid #34B7F1;
                        margin-bottom: 25px;
                    }
                    .footer {
                        text-align: center;
                        padding: 20px;
                        background-color: #f5f5f5;
                        font-size: 12px;
                        color: #999;
                        border-top: 1px solid #eee;
                    }
                    .signature {
                        margin-top: 25px;
                        padding-top: 25px;
                        border-top: 1px dashed #ddd;
                        font-style: italic;
                        color: #666;
                    }
                    .btn {
                        display: inline-block;
                        padding: 12px 24px;
                        background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
                        color: white;
                        text-decoration: none;
                        border-radius: 6px;
                        font-weight: 500;
                        margin: 15px 0;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <div class='email-logo'>BISure Chat</div>
                        <h2>New Message Notification</h2>
                    </div>
                    
                    <div class='email-content'>
                        <p>Dear <strong>" . htmlspecialchars($recipient_email) . "</strong>,</p>
                        
                        <div class='message-content'>
                            <p>" . nl2br(htmlspecialchars($message_content)) . "</p>
                        </div>
                        
                        <div style='text-align: center;'>
                            <a href='https://bisurechat.22web.org' class='btn'>Login to BISure Chat</a>
                        </div>
                        
                        <div class='signature'>
                            <p>Best regards,</p>
                            <p>The BISure Chat Team</p>
                        </div>
                    </div>
                    
                    <div class='footer'>
                        <p>@B.Isamel</p>
                        <p>&copy; " . date('Y') . " BISure Chat. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Plain text version
        $mail->AltBody = "Dear {$recipient_email},\n\n{$message_content}\n\nBest regards,\nThe BISure Chat Team\n\n@B.Isamel\n© " . date('Y') . " BISure Chat. All rights reserved.";

        // Add attachment if it exists
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        // Send the email
        $mail->send();

        // Update email log status to sent
        $update_stmt = $conn->prepare("UPDATE email_logs SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $email_log_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Commit transaction
        $conn->commit();

        // Success message
        $message = "Email sent successfully!";
        $messageType = "success";

    } catch (Exception $e) {
        // Rollback transaction if active
        if ($conn->in_transaction) {
            // Update email log status to failed
            if (isset($email_log_id)) {
                $fail_stmt = $conn->prepare("UPDATE email_logs SET status = 'failed' WHERE id = ?");
                $fail_stmt->bind_param("i", $email_log_id);
                $fail_stmt->execute();
                $fail_stmt->close();
            }
            $conn->rollback();
        }
        
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
        error_log("Email Error: " . $e->getMessage());
    }
}
?>