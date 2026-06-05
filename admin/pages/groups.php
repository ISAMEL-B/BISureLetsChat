<?php
/**
 * BUSure Chat - Admin Groups Management
 * ✅ Uses reusable sidebar & footer
 * ✅ Matches busure_lets_chat schema
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../unauthorized");
    exit;
}

$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle delete group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
    $group_id = intval($_POST['group_id']);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT conversation_id FROM groups_chat WHERE id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $conv_id = $stmt->get_result()->fetch_assoc()['conversation_id'];
        $stmt->close();
        
        $conn->query("DELETE FROM group_members WHERE group_id = $group_id");
        $conn->query("DELETE FROM messages WHERE conversation_id = $conv_id");
        $conn->query("DELETE FROM conversation_participants WHERE conversation_id = $conv_id");
        $conn->query("DELETE FROM groups_chat WHERE id = $group_id");
        $conn->query("DELETE FROM conversations WHERE id = $conv_id");
        
        $conn->commit();
        $_SESSION['success'] = "Group deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete group.";
    }
    header("Location: groups");
    exit;
}

// Build query
$whereSQL = "1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $whereSQL = "(g.group_name LIKE ? OR g.description LIKE ? OR u.fullname LIKE ? OR u.username LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s, $s];
    $types = "ssss";
}

// Count
$countQuery = "
    SELECT COUNT(DISTINCT g.id) as total
    FROM groups_chat g
    LEFT JOIN users u ON g.created_by = u.id
    LEFT JOIN group_members gm ON g.id = gm.group_id
    WHERE $whereSQL
";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);
$countStmt->close();

// Fetch groups
$query = "
    SELECT 
        g.id, g.group_name, g.group_photo, g.description, g.created_at,
        g.conversation_id,
        u.fullname as creator_name, u.username as creator_username,
        COUNT(DISTINCT gm.user_id) as member_count,
        COUNT(DISTINCT m.id) as message_count,
        MAX(m.created_at) as last_activity
    FROM groups_chat g
    LEFT JOIN users u ON g.created_by = u.id
    LEFT JOIN group_members gm ON g.id = gm.group_id
    LEFT JOIN messages m ON g.conversation_id = m.conversation_id AND m.is_deleted = 0
    WHERE $whereSQL
    GROUP BY g.id
    ORDER BY g.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Stats
$statsQuery = "
    SELECT 
        COUNT(*) as total_groups,
        SUM(member_count) as total_memberships,
        AVG(member_count) as avg_members,
        SUM(message_count) as total_messages
    FROM (
        SELECT g.id, COUNT(DISTINCT gm.user_id) as member_count, COUNT(DISTINCT m.id) as message_count
        FROM groups_chat g
        LEFT JOIN group_members gm ON g.id = gm.group_id
        LEFT JOIN messages m ON g.conversation_id = m.conversation_id AND m.is_deleted = 0
        GROUP BY g.id
    ) stats
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

function buildQueryString($updates = []) {
    $params = $_GET;
    foreach ($updates as $key => $value) {
        if ($value === null || $value === '') unset($params[$key]);
        else $params[$key] = $value;
    }
    return http_build_query($params);
}

function paginationUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groups | BISureChat Admin</title>
    
    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-mini { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
        .stat-mini { background: #fff; padding: 1rem; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stat-mini .si { font-size: 1.4rem; margin-bottom: 4px; }
        .stat-mini .sn { font-size: 1.3rem; font-weight: 700; }
        .stat-mini .sl { font-size: 0.7rem; color: #718096; text-transform: uppercase; }

        .search-bar { margin-bottom: 1.25rem; }
        .search-box { position: relative; max-width: 400px; }
        .search-box input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; }
        .search-box input:focus { outline: none; border-color: #128C7E; box-shadow: 0 0 0 3px rgba(18,140,126,0.1); }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; }

        .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; padding: 1.25rem; }
        .group-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; transition: all 0.2s; }
        .group-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #128C7E; }
        .group-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .group-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg,#128C7E,#25D366); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.2rem; flex-shrink: 0; overflow: hidden; }
        .group-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .group-info { flex: 1; min-width: 0; }
        .group-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .group-id { font-size: 0.7rem; color: #A0AEC0; }
        .group-desc { font-size: 0.8rem; color: #718096; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .group-stats { display: flex; gap: 12px; font-size: 0.78rem; color: #718096; margin-bottom: 10px; flex-wrap: wrap; }
        .group-stats span { display: flex; align-items: center; gap: 4px; }
        .group-actions { display: flex; gap: 6px; }

        .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .badge-primary { background: rgba(18,140,126,0.1); color: #128C7E; }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: #128C7E; color: #fff; } .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; } .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; } .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; padding: 1rem; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #4A5568; border: 1px solid #E2E8F0; }
        .pagination a:hover { background: #128C7E; color: #fff; border-color: #128C7E; }
        .pagination .active { background: #128C7E; color: #fff; border-color: #128C7E; font-weight: 600; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }
        .pagination .info { border: none; color: #718096; font-size: 0.8rem; }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 14px; width: 90%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalIn 0.25s ease; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .modal-head { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .modal-head h3 { font-size: 1.1rem; font-weight: 600; }
        .modal-close { font-size: 1.3rem; cursor: pointer; color: #A0AEC0; background: none; border: none; }
        .modal-close:hover { color: #E74C3C; }
        .modal-body { padding: 1.25rem; text-align: center; }
        .modal-foot { padding: 1rem 1.25rem; border-top: 1px solid #E2E8F0; display: flex; justify-content: center; gap: 10px; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .groups-grid { grid-template-columns: 1fr; }
            .stats-mini { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Groups Management</h1>
            <span style="color:#718096;"><?= $totalRows ?> groups</span>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-mini">
            <div class="stat-mini"><div class="si"><i class="fas fa-layer-group" style="color:#128C7E;"></i></div><div class="sn"><?= number_format($stats['total_groups'] ?? 0) ?></div><div class="sl">Total Groups</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-users" style="color:#3498DB;"></i></div><div class="sn"><?= number_format($stats['total_memberships'] ?? 0) ?></div><div class="sl">Memberships</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-user-friends" style="color:#9C27B0;"></i></div><div class="sn"><?= number_format($stats['avg_members'] ?? 0, 1) ?></div><div class="sl">Avg Members</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-comments" style="color:#25D366;"></i></div><div class="sn"><?= number_format($stats['total_messages'] ?? 0) ?></div><div class="sl">Total Messages</div></div>
        </div>

        <!-- Search -->
        <div class="search-bar">
            <form method="GET">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search groups by name, creator..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>

        <!-- Groups Grid -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Groups (<?= $result->num_rows ?>)</h2>
                <button class="btn btn-secondary btn-sm" onclick="exportTable()"><i class="fas fa-download"></i> Export</button>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="groups-grid">
                    <?php while ($group = $result->fetch_assoc()): 
                        $creatorName = $group['creator_name'] ?? $group['creator_username'] ?? 'Unknown';
                    ?>
                        <div class="group-card">
                            <div class="group-card-header">
                                <div class="group-avatar">
                                    <?php if (!empty($group['group_photo'])): ?>
                                        <img src="<?= htmlspecialchars('../../uploads/images/' . $group['group_photo']) ?>" alt="Group">
                                    <?php else: ?>
                                        <?= strtoupper(substr($group['group_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="group-info">
                                    <div class="group-name"><?= htmlspecialchars($group['group_name']) ?></div>
                                    <div class="group-id">ID: #<?= $group['id'] ?> | by <?= htmlspecialchars($creatorName) ?></div>
                                </div>
                            </div>
                            <?php if (!empty($group['description'])): ?>
                                <div class="group-desc"><?= htmlspecialchars($group['description']) ?></div>
                            <?php endif; ?>
                            <div class="group-stats">
                                <span><i class="fas fa-users"></i> <?= $group['member_count'] ?> members</span>
                                <span><i class="fas fa-comment"></i> <?= $group['message_count'] ?> msgs</span>
                                <?php if ($group['last_activity']): ?>
                                    <span><i class="fas fa-clock"></i> <?= date('M d', strtotime($group['last_activity'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="group-actions">
                                <a href="../../groups/group_chat?group_id=<?= $group['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> View</a>
                                <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $group['id'] ?>, '<?= addslashes($group['group_name']) ?>')"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="<?= paginationUrl(1) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-double-left"></i></a>
                    <a href="<?= paginationUrl($page - 1) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-left"></i></a>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1) echo '<span class="info">...</span>';
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?= paginationUrl($i) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    if ($end < $totalPages) echo '<span class="info">...</span>';
                    ?>
                    <a href="<?= paginationUrl($page + 1) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-right"></i></a>
                    <a href="<?= paginationUrl($totalPages) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-double-right"></i></a>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-layer-group"></i>
                    <h3>No groups found</h3>
                    <p>No groups match your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-head"><h3>Delete Group</h3><button class="modal-close" onclick="closeDeleteModal()">&times;</button></div>
            <div class="modal-body">
                <div style="font-size:3rem;color:#E74C3C;margin-bottom:1rem;"><i class="fas fa-trash-alt"></i></div>
                <p style="font-size:1rem;margin-bottom:0.5rem;">Delete <strong id="deleteGroupName"></strong>?</p>
                <p style="color:#718096;font-size:0.85rem;">All messages, members, and data will be permanently deleted.</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="group_id" id="deleteGroupId">
                    <button type="submit" name="delete_group" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Group</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteGroupId').value = id;
            document.getElementById('deleteGroupName').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); }
        document.getElementById('deleteModal').addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });

        function exportTable() {
            const cards = document.querySelectorAll('.group-card');
            let csv = 'ID,Name,Creator,Members,Messages,Last Activity\n';
            cards.forEach(card => {
                const id = card.querySelector('.group-id')?.textContent?.match(/#(\d+)/)?.[1] || '';
                const name = card.querySelector('.group-name')?.textContent?.trim() || '';
                const creator = card.querySelector('.group-id')?.textContent?.split('by ')[1] || '';
                const members = card.querySelector('.fa-users')?.parentElement?.textContent?.trim() || '0';
                const msgs = card.querySelector('.fa-comment')?.parentElement?.textContent?.trim() || '0';
                const last = card.querySelector('.fa-clock')?.parentElement?.textContent?.trim() || '';
                csv += `"${id}","${name}","${creator}","${members}","${msgs}","${last}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = 'groups_export.csv'; a.click();
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>