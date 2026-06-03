<?php
/**
 * BUSure Chat - Admin User Management
 * ✅ Role management (admin/normal)
 * ✅ Auth access control with block reason prompt
 * ✅ Toggle only changes after modal completion
 * ✅ No modal for unblocking
 * ✅ Uses reusable sidebar & footer
 * ✅ Fixed modal stacking and body overflow issues
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../../auth/register");
    exit;
}

$current_admin_id = $_SESSION['user_id'];
$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle role filter
$roleFilter = '';
$roleParam = '';
if (isset($_GET['role']) && in_array($_GET['role'], ['normal', 'admin'])) {
    $roleFilter = "AND u.role = ?";
    $roleParam = $_GET['role'];
}

// Handle auth filter
$authFilter = '';
$authParam = '';
if (isset($_GET['auth']) && in_array($_GET['auth'], ['yes', 'no'])) {
    $authFilter = "AND u.auth = ?";
    $authParam = $_GET['auth'];
}

// Handle AJAX quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $userId = intval($_POST['user_id']);
    $response = ['success' => false];
    
    if ($userId == $current_admin_id) {
        echo json_encode(['success' => false, 'error' => 'Cannot modify yourself']);
        exit;
    }
    
    switch ($_POST['ajax_action']) {
        case 'toggle_role':
            $newRole = $_POST['value'] === 'admin' ? 'admin' : 'normal';
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $newRole, $userId);
            $response['success'] = $stmt->execute();
            $stmt->close();
            break;
            
        case 'toggle_auth':
            $newAuth = $_POST['value'] === 'yes' ? 'yes' : 'no';
            $stmt = $conn->prepare("UPDATE users SET auth = ? WHERE id = ?");
            $stmt->bind_param("si", $newAuth, $userId);
            $response['success'] = $stmt->execute();
            $stmt->close();
            
            if ($newAuth === 'no' && !empty($_POST['reason'])) {
                $reason = trim($_POST['reason']);
                $adminId = $current_admin_id;
                $stmt = $conn->prepare("INSERT INTO block_reasons (user_id, reason, blocked_by) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $userId, $reason, $adminId);
                $stmt->execute();
                $stmt->close();
                $response['saved_reason'] = true;
            }
            break;
            
        case 'toggle_verify':
            $newVerify = $_POST['value'] === '1' ? 1 : 0;
            $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
            $stmt->bind_param("ii", $newVerify, $userId);
            $response['success'] = $stmt->execute();
            $stmt->close();
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    
    if (isset($_POST['update_password'])) {
        $userId = intval($_POST['user_id']);
        $newPassword = trim($_POST['new_password']);
        if (empty($userId) || empty($newPassword)) {
            $_SESSION['error'] = 'Please fill in all fields.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) $_SESSION['success'] = 'Password updated!';
            else $_SESSION['error'] = 'Failed to update password.';
            $stmt->close();
        }
    }

    if (isset($_POST['delete_user'])) {
        $userId = intval($_POST['user_id']);
        if (empty($userId)) {
            $_SESSION['error'] = 'Please provide a valid user ID.';
        } elseif ($userId == $current_admin_id) {
            $_SESSION['error'] = 'You cannot delete your own account.';
        } else {
            $conn->begin_transaction();
            try {
                $tables = ['message_reads','message_reactions','block_reasons','conversation_participants','group_members','contacts','email_verifications','password_resets','user_settings','archived_chats','inquiries'];
                foreach ($tables as $table) $conn->query("DELETE FROM $table WHERE user_id = $userId");
                $conn->query("DELETE FROM messages WHERE sender_id = $userId");
                $conn->query("DELETE FROM contacts WHERE contact_user_id = $userId");
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute(); $stmt->close();
                $conn->commit();
                $_SESSION['success'] = 'User deleted successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Failed to delete user.';
            }
        }
    }

    if (isset($_POST['update_user'])) {
        $userId = intval($_POST['user_id']);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio'] ?? '');
        $status_message = trim($_POST['status_message'] ?? '');
        $role = trim($_POST['role']);
        $auth = trim($_POST['auth'] ?? 'yes');

        if (empty($userId) || empty($fullname) || empty($email)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, bio = ?, status_message = ?, role = ?, auth = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $fullname, $email, $phone, $bio, $status_message, $role, $auth, $userId);
            if ($stmt->execute()) $_SESSION['success'] = 'User updated successfully!';
            else $_SESSION['error'] = 'Failed to update user: ' . $stmt->error;
            $stmt->close();
        }
    }

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
            $uuid = sprintf('%s-%s-4%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("INSERT INTO users (uuid, fullname, username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $uuid, $fullname, $username, $email, $phone, $hashedPassword, $role);
            if ($stmt->execute()) {
                $conn->query("INSERT INTO user_settings (user_id) VALUES ({$stmt->insert_id})");
                $_SESSION['success'] = 'User added successfully!';
            } else $_SESSION['error'] = 'Failed to add user: ' . $stmt->error;
            $stmt->close();
        }
    }

    header("Location: users");
    exit;
}

// Fetch users
$whereClause = "WHERE 1=1";
$params = []; $types = "";

if (!empty($search)) {
    $whereClause .= " AND (u.fullname LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $sp = "%$search%";
    $params = array_merge($params, [$sp, $sp, $sp, $sp]);
    $types .= "ssss";
}
if (!empty($roleFilter)) { $whereClause .= " " . $roleFilter; $params[] = $roleParam; $types .= "s"; }
if (!empty($authFilter)) { $whereClause .= " " . $authFilter; $params[] = $authParam; $types .= "s"; }

$query = "
    SELECT u.id, u.fullname, u.username, u.email, u.phone, u.profile_photo,
           u.status_message, u.bio, u.role, u.auth, u.is_verified, u.is_online, u.last_seen, u.created_at,
           COUNT(DISTINCT m.id) as message_count
    FROM users u LEFT JOIN messages m ON u.id = m.sender_id AND m.is_deleted = 0
    $whereClause GROUP BY u.id ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$roleCounts = []; $authCounts = []; $totalUsers = 0;
$rc = $conn->query("SELECT role, COUNT(*) as c FROM users GROUP BY role");
while ($r = $rc->fetch_assoc()) { $roleCounts[$r['role']] = $r['c']; $totalUsers += $r['c']; }
$ac = $conn->query("SELECT auth, COUNT(*) as c FROM users GROUP BY auth");
while ($r = $ac->fetch_assoc()) $authCounts[$r['auth']] = $r['c'];

function buildQueryString($updates = []) {
    $params = $_GET;
    foreach ($updates as $key => $value) { if ($value === null) unset($params[$key]); else $params[$key] = $value; }
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | BISureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-mini { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.5rem; margin-bottom: 1rem; }
        .stat-mini { background: #fff; padding: 0.7rem; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-decoration: none; color: inherit; border: 2px solid transparent; transition: all 0.2s; }
        .stat-mini:hover { border-color: #128C7E; }
        .stat-mini .sn { font-size: 1.2rem; font-weight: 700; }
        .stat-mini .sl { font-size: 0.65rem; color: #718096; text-transform: uppercase; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; }
        .search-box { position: relative; flex-grow: 1; max-width: 300px; }
        .search-box input { width: 100%; padding: 9px 14px 9px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; }
        .search-box input:focus { outline: none; border-color: #128C7E; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        table th, table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        table th { background: #f8f9fa; font-weight: 600; color: #718096; font-size: 0.7rem; text-transform: uppercase; white-space: nowrap; }
        table tr:hover { background: rgba(18,140,126,0.03); }

        .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .badge-success { background: rgba(37,211,102,0.1); color: #25D366; }
        .badge-warning { background: rgba(243,156,18,0.1); color: #F39C12; }
        .badge-danger { background: rgba(231,76,60,0.1); color: #E74C3C; }
        .badge-info { background: rgba(52,152,219,0.1); color: #3498DB; }

        .toggle-switch { position: relative; display: inline-block; width: 42px; height: 22px; cursor: pointer; }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .toggle-slider { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 22px; transition: 0.3s; }
        .toggle-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        input:checked + .toggle-slider { background: #25D366; }
        input:checked + .toggle-slider:before { transform: translateX(20px); }
        .toggle-slider.danger { background: #E74C3C !important; }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: #128C7E; color: #fff; } .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; } .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; } .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        /* Fixed modal styles */
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
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .modal.active { 
            display: flex;
            opacity: 1;
        }
        .modal-box { 
            background: #fff; 
            border-radius: 14px; 
            width: 90%; 
            max-width: 500px; 
            max-height: 85vh; 
            overflow-y: auto; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            transform: translateY(0);
            transition: transform 0.2s ease;
        }
        .modal:not(.active) .modal-box {
            transform: translateY(-20px);
        }
        .modal-head { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .modal-head h3 { font-size: 1.1rem; font-weight: 600; }
        .modal-close { font-size: 1.3rem; cursor: pointer; color: #A0AEC0; background: none; border: none; padding: 4px 8px; }
        .modal-close:hover { color: #E74C3C; }
        .modal-body { padding: 1.25rem; }
        .modal-foot { padding: 1rem 1.25rem; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 10px; }

        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 4px; font-size: 0.8rem; font-weight: 500; color: #718096; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #128C7E; box-shadow: 0 0 0 3px rgba(18,140,126,0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 140px; }
        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
        .required-star { color: #E74C3C; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">User Management</h1>
            <span style="color:#718096;"><?= $totalUsers ?> users</span>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="stats-mini">
            <a href="users" class="stat-mini"><div class="sn"><?= $totalUsers ?></div><div class="sl">All Users</div></a>
            <a href="?role=admin" class="stat-mini"><div class="sn" style="color:#F39C12;"><?= $roleCounts['admin'] ?? 0 ?></div><div class="sl">Admins</div></a>
            <a href="?role=normal" class="stat-mini"><div class="sn" style="color:#3498DB;"><?= $roleCounts['normal'] ?? 0 ?></div><div class="sl">Users</div></a>
            <a href="?auth=no" class="stat-mini"><div class="sn" style="color:#E74C3C;"><?= $authCounts['no'] ?? 0 ?></div><div class="sl">Blocked</div></a>
            <a href="?auth=yes" class="stat-mini"><div class="sn" style="color:#25D366;"><?= $authCounts['yes'] ?? 0 ?></div><div class="sl">Active</div></a>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Filters</h2></div>
            <div style="padding:1rem 1.25rem;">
                <form method="GET" class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search name, email, phone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <a href="users" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Users (<?= $result->num_rows ?>)</h2>
                <button class="btn btn-primary btn-sm" onclick="openAddUserModal()"><i class="fas fa-plus"></i> Add User</button>
            </div>
            <div class="table-responsive">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Access</th>
                                <th>Msgs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['id'] ?></td>
                                    <td>
                                        <div style="font-weight:500;"><?= htmlspecialchars($row['fullname']) ?></div>
                                        <div style="font-size:0.7rem;color:#A0AEC0;">@<?= htmlspecialchars($row['username']) ?></div>
                                    </td>
                                    <td><span class="truncate"><?= htmlspecialchars($row['email']) ?></span></td>
                                    <td>
                                        <div
                                            class="toggle-switch"
                                            onclick='quickAction(
                                                <?= (int)$row["id"] ?>,
                                                "toggle_role",
                                                <?= json_encode($row["role"] === "admin" ? "normal" : "admin") ?>
                                            )'
                                            title="Toggle admin/user"
                                            style="cursor:pointer;">

                                            <input
                                                type="checkbox"
                                                <?= $row['role'] === 'admin' ? 'checked' : '' ?>
                                                tabindex="-1">

                                            <span class="toggle-slider"></span>
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="toggle-switch"
                                            onclick='handleAuthToggle(
                                                event,
                                                <?= (int)$row["id"] ?>,
                                                <?= json_encode($row["auth"]) ?>,
                                                <?= json_encode($row["fullname"]) ?>
                                            )'
                                            title="Allow/Block login"
                                            style="cursor:pointer;">

                                            <input
                                                type="checkbox"
                                                <?= $row['auth'] === 'yes' ? 'checked' : '' ?>
                                                tabindex="-1">

                                            <span class="toggle-slider <?= $row['auth'] !== 'yes' ? 'danger' : '' ?>"></span>
                                        </div>
                                    </td>

                                    <td><?= (int)$row['message_count'] ?></td>

                                    <td>
                                        <div style="display:flex;gap:4px;">

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                onclick='openEditModal(
                                                    <?= (int)$row["id"] ?>,
                                                    <?= json_encode($row["fullname"]) ?>,
                                                    <?= json_encode($row["email"]) ?>,
                                                    <?= json_encode($row["phone"] ?? "") ?>,
                                                    <?= json_encode($row["status_message"] ?? "") ?>,
                                                    <?= json_encode($row["role"]) ?>,
                                                    <?= json_encode($row["auth"]) ?>,
                                                    <?= json_encode($row["bio"] ?? "") ?>
                                                )'
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                onclick='openDeleteModal(
                                                    <?= (int)$row["id"] ?>,
                                                    <?= json_encode($row["fullname"]) ?>
                                                )'
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-user-slash"></i><h3>No users found</h3></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-box">
            <div class="modal-head"><h3>Edit User</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
            <form method="POST" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit-id">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Full Name <span class="required-star">*</span></label><input type="text" name="fullname" id="edit-fullname" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Email <span class="required-star">*</span></label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="edit-phone" class="form-control"></div>
                        <div class="form-group"><label class="form-label">Status</label><input type="text" name="status_message" id="edit-status" class="form-control"></div>
                    </div>
                    <div class="form-group"><label class="form-label">Bio</label><textarea name="bio" id="edit-bio" class="form-control" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit-role" class="form-control">
                                <option value="normal">Normal User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Login Access</label>
                            <select name="auth" id="edit-auth" class="form-control">
                                <option value="yes">Allowed (Yes)</option>
                                <option value="no">Blocked (No)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-box" style="max-width:400px;">
            <div class="modal-head"><h3>Delete User</h3><button class="modal-close" onclick="closeModal('deleteModal')">&times;</button></div>
            <form method="POST" id="deleteUserForm">
                <div class="modal-body" style="text-align:center;">
                    <input type="hidden" name="user_id" id="delete-id">
                    <div style="font-size:3rem;color:#E74C3C;margin-bottom:1rem;"><i class="fas fa-trash-alt"></i></div>
                    <p>Delete <strong id="delete-name"></strong>?</p>
                    <p style="color:#718096;font-size:0.85rem;">All data will be permanently removed.</p>
                </div>
                <div class="modal-foot" style="justify-content:center;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-box">
            <div class="modal-head"><h3>Add New User</h3><button class="modal-close" onclick="closeModal('addUserModal')">&times;</button></div>
            <form method="POST" id="addUserForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Full Name <span class="required-star">*</span></label><input type="text" name="fullname" id="add-fullname" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Username <span class="required-star">*</span></label><input type="text" name="username" id="add-username" class="form-control" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Email <span class="required-star">*</span></label><input type="email" name="email" id="add-email" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="add-phone" class="form-control"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Password <span class="required-star">*</span></label><input type="password" name="password" id="add-password" class="form-control" required></div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control">
                                <option value="normal">Normal User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Block Reason Modal -->
    <div class="modal" id="blockReasonModal">
        <div class="modal-box" style="max-width:460px;">
            <div class="modal-head" style="background:linear-gradient(135deg,#c0392b,#e74c3c);color:#fff;">
                <h3><i class="fas fa-user-lock"></i> Block User Access</h3>
                <button class="modal-close" onclick="cancelBlock()" style="color:#fff;">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="blockUserId">
                <div style="text-align:center;margin-bottom:18px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:rgba(231,76,60,0.1);display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                        <i class="fas fa-user-slash" style="font-size:1.8rem;color:#E74C3C;"></i>
                    </div>
                    <p style="font-size:0.95rem;margin-bottom:4px;">You are about to <strong style="color:#E74C3C;">block</strong>:</p>
                    <p style="font-size:1.15rem;font-weight:700;color:#2D3748;" id="blockUserName"></p>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for blocking <span class="required-star">*</span></label>
                    <textarea id="blockReason" class="form-control" rows="3" placeholder="Explain why this user is being blocked from logging in..."></textarea>
                    <small style="color:#718096;display:block;margin-top:4px;">
                        <i class="fas fa-info-circle"></i> This reason will be shown to the user when they attempt to log in. Minimum 5 characters.
                    </small>
                </div>
            </div>
            <div class="modal-foot" style="justify-content:space-between;">
                <button type="button" class="btn btn-secondary" onclick="cancelBlock()"><i class="fas fa-times"></i> Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmBlockUser()"><i class="fas fa-ban"></i> Block User</button>
            </div>
        </div>
    </div>

<script>

    let ajaxBusy = false;
    let activeModals = new Set();

    /* ==========================
    Modal Management System
    ========================== */

    function openModal(id) {
        const modal = document.getElementById(id);

        if (!modal) return;

        // Remove any existing active state first
        modal.classList.remove('active');
        
        // Force reflow to reset animation
        void modal.offsetWidth;
        
        // Add to tracking set
        activeModals.add(id);
        
        // Set body overflow only if not already set
        if (document.body.style.overflow !== 'hidden') {
            document.body.style.overflow = 'hidden';
        }
        
        // Activate modal
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });
    }

    function closeModal(id) {
        const modal = document.getElementById(id);

        if (!modal) return;

        // Remove from tracking
        activeModals.delete(id);
        
        // Deactivate modal
        modal.classList.remove('active');
        
        // Only restore body overflow if no other modals are open
        if (activeModals.size === 0) {
            document.body.style.overflow = '';
        }
    }

    function closeAllModals() {
        const modals = document.querySelectorAll('.modal.active');
        
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
        
        // Clear tracking
        activeModals.clear();
        
        // Always restore body overflow when closing all
        document.body.style.overflow = '';
    }

    /* ==========================
    Auth Toggle
    ========================== */

    function handleAuthToggle(event, userId, currentAuth, userName) {
        event.preventDefault();
        event.stopPropagation();

        if (ajaxBusy) return false;

        if (currentAuth === 'yes') {
            // Prepare block reason modal
            document.getElementById('blockUserId').value = userId;
            document.getElementById('blockUserName').textContent = userName;

            const reasonField = document.getElementById('blockReason');

            reasonField.value = '';
            reasonField.style.borderColor = '';
            reasonField.style.boxShadow = '';
            reasonField.placeholder = 'Explain why this user is being blocked from logging in...';

            openModal('blockReasonModal');

            // Focus after modal is visible
            setTimeout(() => {
                if (document.getElementById('blockReasonModal').classList.contains('active')) {
                    reasonField.focus();
                }
            }, 300);
        } else {
            // Unblock directly
            if (confirm('Unblock "' + userName + '"? They will be able to log in again.')) {
                doQuickAction(userId, 'toggle_auth', 'yes');
            }
        }

        return false;
    }

    /* ==========================
    Block User
    ========================== */

    function cancelBlock() {
        // Clear block modal fields
        document.getElementById('blockUserId').value = '';
        document.getElementById('blockUserName').textContent = '';
        document.getElementById('blockReason').value = '';
        
        // Reset validation styles
        const reasonField = document.getElementById('blockReason');
        reasonField.style.borderColor = '';
        reasonField.style.boxShadow = '';

        closeModal('blockReasonModal');
    }

    function confirmBlockUser() {
        const userId = document.getElementById('blockUserId').value;
        const reasonField = document.getElementById('blockReason');
        const reason = reasonField.value.trim();

        // Reset styles
        reasonField.style.borderColor = '';
        reasonField.style.boxShadow = '';

        // Validation
        if (!reason) {
            reasonField.style.borderColor = '#E74C3C';
            reasonField.style.boxShadow = '0 0 0 3px rgba(231,76,60,.15)';
            reasonField.focus();
            return;
        }

        if (reason.length < 5) {
            reasonField.style.borderColor = '#F39C12';
            reasonField.style.boxShadow = '0 0 0 3px rgba(243,156,18,.15)';
            reasonField.focus();
            return;
        }

        // Close block modal
        closeModal('blockReasonModal');

        // Perform the action
        doQuickAction(userId, 'toggle_auth', 'no', reason);
    }

    /* ==========================
    AJAX Actions
    ========================== */

    function quickAction(userId, action, value) {
        doQuickAction(userId, action, value);
    }

    function doQuickAction(userId, action, value, reason = '') {
        if (ajaxBusy) {
            console.warn('AJAX request already in progress');
            return;
        }

        ajaxBusy = true;

        const formData = new URLSearchParams();
        formData.append('ajax_action', action);
        formData.append('user_id', userId);
        formData.append('value', value);

        if (reason) {
            formData.append('reason', reason);
        }

        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            ajaxBusy = false;

            if (data.success) {
                // Success - reload to reflect changes
                location.reload();
                return;
            }

            alert(data.error || 'Action failed. Please try again.');
        })
        .catch(error => {
            ajaxBusy = false;
            console.error('AJAX Error:', error);
            alert('Network or server error. Please try again.');
        });
    }

    /* ==========================
    Edit User Modal
    ========================== */

    function openEditModal(id, fullname, email, phone, status, role, auth, bio) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-fullname').value = fullname || '';
        document.getElementById('edit-email').value = email || '';
        document.getElementById('edit-phone').value = phone || '';
        document.getElementById('edit-status').value = status || '';
        document.getElementById('edit-role').value = role || 'normal';
        document.getElementById('edit-auth').value = auth || 'yes';
        document.getElementById('edit-bio').value = bio || '';

        // Reset form
        const form = document.getElementById('editUserForm');
        if (form) form.reset();
        
        // Re-set values after reset
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-fullname').value = fullname || '';
        document.getElementById('edit-email').value = email || '';
        document.getElementById('edit-phone').value = phone || '';
        document.getElementById('edit-status').value = status || '';
        document.getElementById('edit-role').value = role || 'normal';
        document.getElementById('edit-auth').value = auth || 'yes';
        document.getElementById('edit-bio').value = bio || '';

        openModal('editModal');
    }

    /* ==========================
    Delete Modal
    ========================== */

    function openDeleteModal(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        
        openModal('deleteModal');
    }

    /* ==========================
    Add User Modal
    ========================== */

    function openAddUserModal() {
        const form = document.getElementById('addUserForm');
        
        if (form) {
            form.reset();
        }
        
        // Also clear fields manually in case reset doesn't work
        const fields = ['add-fullname', 'add-username', 'add-email', 'add-phone', 'add-password'];
        fields.forEach(field => {
            const el = document.getElementById(field);
            if (el) {
                el.value = '';
            }
        });

        openModal('addUserModal');
    }

    /* ==========================
    Event Listeners
    ========================== */

    document.addEventListener('DOMContentLoaded', () => {
        // Handle clicking outside modal to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                // Only close if clicking the modal backdrop (not the modal box)
                if (e.target === this) {
                    const modalId = this.id;
                    closeModal(modalId);
                    
                    // If closing block reason modal, also clean up
                    if (modalId === 'blockReasonModal') {
                        cancelBlock();
                    }
                }
            });
            
            // Prevent modal box click from closing
            const modalBox = modal.querySelector('.modal-box');
            if (modalBox) {
                modalBox.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // Handle form submissions to close modals properly
        document.querySelectorAll('.modal form').forEach(form => {
            form.addEventListener('submit', function() {
                // Modal will close naturally on page reload
                // But we ensure body overflow is restored
                document.body.style.overflow = '';
                activeModals.clear();
            });
        });
    });

    /* ==========================
    ESC Key Support
    ========================== */

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Check if block reason modal is open
            if (document.getElementById('blockReasonModal').classList.contains('active')) {
                cancelBlock();
            } else {
                closeAllModals();
            }
        }
    });

    /* ==========================
    Safety Cleanup on Page Unload
    ========================== */

    window.addEventListener('beforeunload', function() {
        // Ensure body overflow is restored
        document.body.style.overflow = '';
        activeModals.clear();
    });
</script>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>