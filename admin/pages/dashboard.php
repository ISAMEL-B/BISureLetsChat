<?php
/**
 * BUSure Chat - Admin Dashboard
 * ✅ Updated with reusable sidebar & footer
 * ✅ Matches bisure_lets_chat schema
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../unauthorized");
    exit;
}

$current_admin_id = $_SESSION['user_id'];
$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Password update
    if (isset($_POST['update_password'])) {
        $userId = intval($_POST['user_id']);
        $newPassword = trim($_POST['new_password']);

        if (empty($userId) || empty($newPassword)) {
            $_SESSION['error'] = 'Please fill in all fields.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Password updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update password.';
            }
            $stmt->close();
        }
    }

    // User deletion
    if (isset($_POST['delete_user'])) {
        $userId = intval($_POST['user_id']);

        if (empty($userId)) {
            $_SESSION['error'] = 'Please provide a valid user ID.';
        } elseif ($userId == $current_admin_id) {
            $_SESSION['error'] = 'You cannot delete your own account.';
        } else {
            $conn->begin_transaction();
            try {
                $conn->query("DELETE FROM message_reads WHERE user_id = $userId");
                $conn->query("DELETE FROM message_reactions WHERE user_id = $userId");
                $conn->query("DELETE FROM messages WHERE sender_id = $userId");
                $conn->query("DELETE FROM conversation_participants WHERE user_id = $userId");
                $conn->query("DELETE FROM group_members WHERE user_id = $userId");
                $conn->query("DELETE FROM contacts WHERE user_id = $userId OR contact_user_id = $userId");
                $conn->query("DELETE FROM email_verifications WHERE user_id = $userId");
                $conn->query("DELETE FROM password_resets WHERE user_id = $userId");
                $conn->query("DELETE FROM user_settings WHERE user_id = $userId");
                $conn->query("DELETE FROM archived_chats WHERE user_id = $userId");
                $conn->query("DELETE FROM inquiries WHERE user_id = $userId");
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $_SESSION['success'] = 'User and all associated data deleted successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Failed to delete user: ' . $e->getMessage();
            }
        }
    }

    // Update user information
    if (isset($_POST['update_user'])) {
        $userId = intval($_POST['user_id']);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status_message = trim($_POST['status_message']);
        $role = trim($_POST['role']);

        if (empty($userId) || empty($fullname) || empty($email)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, status_message = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $fullname, $email, $phone, $status_message, $role, $userId);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'User information updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update user information.';
            }
            $stmt->close();
        }
    }

    // Add new user
    if (isset($_POST['add_user'])) {
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);

        if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
        } else {
            $uuid = sprintf('%s-%s-4%s-%s-%s',
                bin2hex(random_bytes(4)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(6))
            );
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $conn->prepare("INSERT INTO users (uuid, fullname, username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $uuid, $fullname, $username, $email, $phone, $hashedPassword, $role);

            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                $conn->query("INSERT INTO user_settings (user_id) VALUES ($new_user_id)");
                $_SESSION['success'] = 'User added successfully!';
            } else {
                $_SESSION['error'] = 'Failed to add user: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all users with statistics
$query = "
    SELECT 
        u.id, u.uuid, u.fullname, u.username, u.email, u.phone, u.profile_photo,
        u.status_message, u.role, u.is_verified, u.is_online, u.last_seen,
        u.created_at, u.updated_at,
        COUNT(DISTINCT m1.id) as sent_messages,
        COUNT(DISTINCT m2.id) as received_messages
    FROM users u
    LEFT JOIN messages m1 ON u.id = m1.sender_id AND m1.is_deleted = 0
    LEFT JOIN messages m2 ON u.id = m2.sender_id AND m2.is_deleted = 0
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$result = $conn->query($query);

// Statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
        (SELECT COUNT(*) FROM messages WHERE is_deleted = 0) as total_messages,
        (SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE() AND is_deleted = 0) as messages_today,
        (SELECT COUNT(*) FROM messages WHERE attachment_path IS NOT NULL AND is_deleted = 0) as total_files,
        (SELECT COUNT(*) FROM messages m WHERE m.is_deleted = 0 AND m.id NOT IN (SELECT mr.message_id FROM message_reads mr)) as unread_messages
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Recent activity
$activityQuery = "
    SELECT 
        m.*, 
        s.fullname as sender_name, 
        s.username as sender_username
    FROM messages m
    LEFT JOIN users s ON m.sender_id = s.id
    WHERE m.is_deleted = 0
    ORDER BY m.created_at DESC 
    LIMIT 10
";
$activityResult = $conn->query($activityQuery);

// Top messagers
$messageDistributionQuery = "
    SELECT 
        u.id, u.fullname, u.username,
        COUNT(m.id) as message_count
    FROM users u
    LEFT JOIN messages m ON u.id = m.sender_id AND m.is_deleted = 0
    GROUP BY u.id
    ORDER BY message_count DESC 
    LIMIT 5
";
$messageDistributionResult = $conn->query($messageDistributionQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | BISureChat</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content {
            margin-left: 260px;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: #fff;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        .stat-title {
            font-size: 0.8rem;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #2D3748; margin-bottom: 4px; }
        .stat-change { font-size: 0.8rem; color: #25D366; }
        .stat-change.danger { color: #E74C3C; }
        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.08;
            color: #128C7E;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title { font-size: 1rem; font-weight: 600; color: #2D3748; }
        .chart-container { height: 300px; padding: 1rem; }

        .table-responsive { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        table th, table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #718096;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        table tr:hover { background: rgba(18,140,126,0.03); }

        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-primary { background: rgba(18,140,126,0.1); color: #128C7E; }
        .badge-secondary { background: rgba(52,152,219,0.1); color: #3498DB; }
        .badge-warning { background: rgba(243,156,18,0.1); color: #F39C12; }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #128C7E; color: #fff; }
        .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 4px; font-size: 0.8rem; font-weight: 500; color: #718096; }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #128C7E;
            box-shadow: 0 0 0 3px rgba(18,140,126,0.1);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title { font-size: 1.1rem; font-weight: 600; }
        .modal-close { font-size: 1.3rem; cursor: pointer; color: #718096; }
        .modal-close:hover { color: #E74C3C; }
        .modal-body { padding: 1.25rem; }
        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #E2E8F0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .flex { display: flex; }
        .gap-2 { gap: 0.5rem; }
        .w-full { width: 100%; }
        .mt-3 { margin-top: 1rem; }
        .mr-1 { margin-right: 0.25rem; }
        .mr-2 { margin-right: 0.5rem; }
        .text-muted { color: #718096; }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 60px;
            }
            .charts-row {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="flex items-center gap-2">
                <span class="text-muted">Welcome, <?= htmlspecialchars($current_admin_name) ?></span>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-change"><i class="fas fa-arrow-up mr-1"></i> <?= number_format($stats['new_users_today']) ?> today</div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Messages</div>
                <div class="stat-value"><?= number_format($stats['total_messages']) ?></div>
                <div class="stat-change"><i class="fas fa-arrow-up mr-1"></i> <?= number_format($stats['messages_today']) ?> today</div>
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Files Shared</div>
                <div class="stat-value"><?= number_format($stats['total_files']) ?></div>
                <div class="stat-change"><i class="fas fa-exchange-alt mr-1"></i> <?= round(($stats['total_files'] / max(1, $stats['total_messages'])) * 100) ?>% of messages</div>
                <div class="stat-icon"><i class="fas fa-file-upload"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Unread Messages</div>
                <div class="stat-value"><?= number_format($stats['unread_messages']) ?></div>
                <div class="stat-change <?= $stats['unread_messages'] > 0 ? 'danger' : '' ?>">
                    <?php if ($stats['unread_messages'] > 0): ?>
                        <i class="fas fa-exclamation-circle mr-1"></i> Needs attention
                    <?php else: ?>
                        <i class="fas fa-check-circle mr-1"></i> All caught up
                    <?php endif; ?>
                </div>
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-row">
            <div class="card">
                <div class="card-header"><h2 class="card-title">User Activity</h2></div>
                <div class="chart-container"><canvas id="userActivityChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Top Messagers</h2></div>
                <div class="chart-container"><canvas id="topMessagersChart"></canvas></div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mt-3">
            <div class="card-header">
                <h2 class="card-title">Recent Activity</h2>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Time</th><th>Sender</th><th>Type</th><th>Content</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $activityResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($activity['created_at'])) ?></td>
                                <td><?= htmlspecialchars($activity['sender_name'] ?? $activity['sender_username'] ?? 'Unknown') ?></td>
                                <td>
                                    <?php if (!empty($activity['attachment_path'])): ?>
                                        <span class="badge badge-secondary"><?= ucfirst($activity['message_type']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">Message</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(substr($activity['message_text'] ?? '', 0, 40)) ?>...</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card mt-3">
            <div class="card-header">
                <h2 class="card-title">User Management</h2>
                <div class="flex gap-2">
                    <button class="btn btn-primary btn-sm" onclick="openAddUserModal()"><i class="fas fa-plus mr-1"></i> Add User</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td>@<?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                                <td>
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="badge badge-warning">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-primary btn-sm" onclick="openEditModal(<?= $row['id'] ?>, '<?= addslashes($row['fullname']) ?>', '<?= $row['email'] ?>', '<?= $row['phone'] ?? '' ?>', '<?= addslashes($row['status_message'] ?? '') ?>', '<?= $row['role'] ?>')"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $row['id'] ?>, '<?= addslashes($row['fullname']) ?>')"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="charts-row mt-3">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Update User Password</h2></div>
                <div style="padding: 1.25rem;">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">User ID</label>
                            <input type="number" name="user_id" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-primary w-full"><i class="fas fa-key mr-2"></i> Update Password</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Delete User Account</h2></div>
                <div style="padding: 1.25rem;">
                    <form method="POST" onsubmit="return confirm('Are you sure? This cannot be undone!')">
                        <div class="form-group">
                            <label class="form-label">User ID</label>
                            <input type="number" name="user_id" class="form-control" required>
                        </div>
                        <button type="submit" name="delete_user" class="btn btn-danger w-full"><i class="fas fa-trash mr-2"></i> Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <span class="modal-close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit-id">
                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="fullname" id="edit-fullname" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="edit-phone" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Status Message</label><input type="text" name="status_message" id="edit-status" class="form-control"></div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit-role" class="form-control" required>
                            <option value="normal">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 class="modal-title">Delete User</h3><span class="modal-close" onclick="closeModal('deleteModal')">&times;</span></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="delete-id">
                    <p>Are you sure you want to delete <strong id="delete-name"></strong>?</p>
                    <div class="alert alert-error mt-3"><i class="fas fa-exclamation-triangle mr-2"></i> This action cannot be undone!</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 class="modal-title">Add New User</h3><span class="modal-close" onclick="closeModal('addUserModal')">&times;</span></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="fullname" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="normal">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function openEditModal(id, fullname, email, phone, status, role) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-fullname').value = fullname;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-phone').value = phone || '';
            document.getElementById('edit-status').value = status || '';
            document.getElementById('edit-role').value = role;
            document.getElementById('editModal').style.display = 'flex';
        }
        function openDeleteModal(id, name) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        function openAddUserModal() { document.getElementById('addUserModal').style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        // Charts
        new Chart(document.getElementById('userActivityChart'), {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun'],
                datasets: [{
                    label: 'New Users', data: [12,19,15,27,34,42],
                    borderColor: '#128C7E', backgroundColor: 'rgba(18,140,126,0.1)', tension: 0.3, fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        new Chart(document.getElementById('topMessagersChart'), {
            type: 'bar',
            data: {
                labels: [<?php $messageDistributionResult->data_seek(0); while($u = $messageDistributionResult->fetch_assoc()): echo "'".addslashes(substr($u['fullname']??$u['username'],0,12))."',"; endwhile; ?>],
                datasets: [{
                    label: 'Messages', 
                    data: [<?php $messageDistributionResult->data_seek(0); while($u = $messageDistributionResult->fetch_assoc()): echo $u['message_count'].","; endwhile; ?>],
                    backgroundColor: ['rgba(18,140,126,0.7)','rgba(37,211,102,0.7)','rgba(52,152,219,0.7)','rgba(243,156,18,0.7)','rgba(231,76,60,0.7)'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>