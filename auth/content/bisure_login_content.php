<?php
/**
 * BUSure Chat - Login Content Handler
 * ✅ Updated with auth + block_reason check
 * ✅ Shows real block reason from database
 * ✅ Fixed session security (regenerate before setting)
 * ✅ Uses ONLY existing database schema
 */
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/db.php';

$response = ['success' => false, 'errors' => [], 'message' => ''];

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed. Please try again later.');
    }

    // Get and sanitize inputs
    $user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

    // Validate inputs
    if (empty($user)) {
        $response['errors']['user'] = 'Please enter your username, email, or phone.';
    }
    
    if (empty($password)) {
        $response['errors']['password'] = 'Please enter your password.';
    }

    // Return validation errors if any
    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }

    // Prepare query to fetch user with auth status
    // ONLY using columns that exist in your schema
    $stmt = $conn->prepare(
        "SELECT id, uuid, fullname, username, email, phone, 
                password_hash, profile_photo, role, auth, is_verified
         FROM users 
         WHERE username = ? OR email = ? OR phone = ? 
         LIMIT 1"
    );
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed.');
    }
    
    $stmt->bind_param("sss", $user, $user, $user);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed.');
    }
    
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['password_hash'])) {
            
            // Check if account is blocked (auth = 'no')
            if (isset($row['auth']) && $row['auth'] === 'no') {
                // Log the blocked login attempt for security
                error_log("Blocked login attempt for user ID: {$row['id']} (Username: {$row['username']}) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                
                // Fetch the most recent block reason with all details
                $reasonStmt = $conn->prepare(
                    "SELECT br.reason, br.created_at, 
                            u2.fullname as blocked_by_name, 
                            u2.username as blocked_by_username,
                            u2.role as blocked_by_role
                     FROM block_reasons br 
                     LEFT JOIN users u2 ON br.blocked_by = u2.id 
                     WHERE br.user_id = ? 
                     ORDER BY br.created_at DESC 
                     LIMIT 1"
                );
                
                if ($reasonStmt) {
                    $reasonStmt->bind_param("i", $row['id']);
                    $reasonStmt->execute();
                    $reasonResult = $reasonStmt->get_result();
                    
                    if ($reasonRow = $reasonResult->fetch_assoc()) {
                        // Format the block date nicely
                        $blockDate = date('F j, Y \a\t g:i A', strtotime($reasonRow['created_at']));
                        
                        // Get the actual block reason from database
                        $realReason = $reasonRow['reason'];
                        $blockedByName = $reasonRow['blocked_by_name'] ?? 'Administrator';
                        $blockedByUsername = $reasonRow['blocked_by_username'] ?? 'admin';
                        
                        // Build detailed block message
                        $response['blocked'] = true;
                        $response['message'] = 'Your account has been blocked.';
                        $response['block_reason'] = $realReason;
                        $response['block_date'] = $blockDate;
                        $response['blocked_by'] = $blockedByName;
                        $response['blocked_by_username'] = $blockedByUsername;
                        
                        // Add additional info for better user experience
                        $response['block_details'] = [
                            'reason' => $realReason,
                            'blocked_by' => $blockedByName,
                            'date' => $blockDate,
                            'appeal_info' => 'Please contact support to appeal this decision.'
                        ];
                    } else {
                        // Block reason exists in users table but not in block_reasons (fallback)
                        $response['blocked'] = true;
                        $response['message'] = 'Your account has been blocked.';
                        $response['block_reason'] = 'No specific reason provided. Please contact support.';
                        $response['block_date'] = 'Unknown date';
                        $response['blocked_by'] = 'System Administrator';
                        
                        $response['block_details'] = [
                            'reason' => 'No specific reason provided',
                            'blocked_by' => 'System Administrator',
                            'date' => 'Unknown date',
                            'appeal_info' => 'Please contact support to appeal this decision.'
                        ];
                    }
                    $reasonStmt->close();
                } else {
                    // Generic block message if can't fetch reason
                    $response['blocked'] = true;
                    $response['message'] = 'Your account has been blocked.';
                    $response['block_reason'] = 'Unable to retrieve block reason. Please contact support.';
                    
                    $response['block_details'] = [
                        'reason' => 'Unable to retrieve block reason',
                        'blocked_by' => 'System Administrator',
                        'date' => 'Unknown date',
                        'appeal_info' => 'Please contact support to appeal this decision.'
                    ];
                }
                
                // Return blocked response
                echo json_encode($response);
                $stmt->close();
                exit;
            }
            
            // ==================== LOGIN SUCCESS ====================
            
            // Regenerate session ID BEFORE setting session data (prevents session fixation)
            session_regenerate_id(true);
            
            // Set session data
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_uuid'] = $row['uuid'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['phone'] = $row['phone'] ?? null;
            $_SESSION['profile_photo'] = $row['profile_photo'] ?? null;
            $_SESSION['is_verified'] = $row['is_verified'] ?? 0;
            $_SESSION['user_role'] = $row['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Set remember me cookie if checked (30 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (86400 * 30); // 30 days
                
                // Set secure cookies
                setcookie('remember_token', $token, [
                    'expires' => $expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                setcookie('remember_user_id', $row['id'], [
                    'expires' => $expiry,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
            
            // Update online status and last_seen (columns that exist in your schema)
            $updateStmt = $conn->prepare(
                "UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?"
            );
            if ($updateStmt) {
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            // Log successful login
            error_log("Successful login for user: {$row['username']} (ID: {$row['id']}) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            // Prepare success response
            $response['success'] = true;
            $response['message'] = 'Welcome back, ' . htmlspecialchars($row['fullname']) . '!';
            $response['redirect'] = '../chat/contacts';
            $response['user'] = [
                'id' => $row['id'],
                'fullname' => $row['fullname'],
                'username' => $row['username'],
                'email' => $row['email'],
                'role' => $row['role'],
                'profile_photo' => $row['profile_photo'] ?? null
            ];
            
        } else {
            // Invalid password
            $response['errors']['password'] = 'Incorrect password. Please try again.';
            
            // Log failed attempt for security monitoring
            error_log("Failed login attempt (wrong password) for identifier: " . $user . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    } else {
        // No user found
        $response['errors']['user'] = 'No account found with that username, email, or phone.';
        
        // Log failed attempt for security monitoring
        error_log("Failed login attempt (user not found) for identifier: " . $user . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    $stmt->close();

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Login Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return generic error message to user
    $response['message'] = 'An error occurred during login. Please try again later.';
}

// Close database connection if it exists
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
exit;
?>