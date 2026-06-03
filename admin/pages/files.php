<?php
/**
 * BUSure Chat - Admin File Management
 * ✅ Uses reusable sidebar & footer
 * ✅ Matches busure_lets_chat schema
 * ✅ Professional advanced features
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../../auth/login");
    exit;
}

$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle type filter
$typeFilter = '';
$typeParam = [];
$typeTypes = '';
if (isset($_GET['type']) && in_array($_GET['type'], ['image', 'document', 'audio', 'video', 'other'])) {
    switch ($_GET['type']) {
        case 'image':
            $typeFilter = "AND m.message_type = 'image'";
            break;
        case 'document':
            $typeFilter = "AND m.message_type = 'file' AND m.attachment_path IS NOT NULL";
            break;
        case 'audio':
            $typeFilter = "AND m.message_type = 'voice'";
            break;
        case 'video':
            $typeFilter = "AND m.message_type = 'video'";
            break;
        case 'other':
            $typeFilter = "AND m.message_type NOT IN ('image','video','voice') AND m.attachment_path IS NOT NULL";
            break;
    }
}

// Handle date filter
$dateFilter = '';
$dateParam = '';
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $dateFilter = "AND DATE(m.created_at) = ?";
    $dateParam = $_GET['date'];
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['selected_files'])) {
    $fileIds = array_map('intval', $_POST['selected_files']);
    
    if ($_POST['bulk_action'] === 'delete') {
        // Get file paths first
        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $conn->prepare("SELECT attachment_path FROM messages WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($fileIds)), ...$fileIds);
        $stmt->execute();
        $pathsResult = $stmt->get_result();
        
        $filesToDelete = [];
        while ($row = $pathsResult->fetch_assoc()) {
            if (!empty($row['attachment_path'])) {
                $filesToDelete[] = __DIR__ . '/../../' . $row['attachment_path'];
            }
        }
        $stmt->close();
        
        // Soft delete messages
        $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($fileIds)), ...$fileIds);
        $stmt->execute();
        $stmt->close();
        
        // Delete physical files
        $deletedCount = 0;
        foreach ($filesToDelete as $filePath) {
            if (file_exists($filePath) && unlink($filePath)) {
                $deletedCount++;
            }
        }
        
        $_SESSION['success'] = count($fileIds) . " files marked as deleted ($deletedCount physical files removed)";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ✅ Fetch files with filters using messages table
$whereConditions = ["m.attachment_path IS NOT NULL", "m.is_deleted = 0"];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(u1.fullname LIKE ? OR u1.username LIKE ? OR u2.fullname LIKE ? OR u2.username LIKE ? OR m.attachment_path LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "sssss";
}

if (!empty($typeFilter)) {
    $whereConditions[] = substr($typeFilter, 4); // Remove "AND " prefix
}

if (!empty($dateFilter)) {
    $whereConditions[] = substr($dateFilter, 4);
    $params[] = $dateParam;
    $types .= "s";
}

$whereSQL = implode(" AND ", $whereConditions);

$query = "
    SELECT 
        m.id, m.message_type, m.message_text, m.attachment_path, m.created_at,
        u1.fullname as sender_name, u1.username as sender_username,
        u2.fullname as receiver_name, u2.username as receiver_username
    FROM messages m
    LEFT JOIN users u1 ON m.sender_id = u1.id
    LEFT JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id != m.sender_id
    LEFT JOIN users u2 ON cp.user_id = u2.id
    WHERE $whereSQL
    ORDER BY m.created_at DESC
    LIMIT 100
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ✅ Count files by type
$typeCountsQuery = "
    SELECT 
        SUM(CASE WHEN message_type = 'image' THEN 1 ELSE 0 END) as images,
        SUM(CASE WHEN message_type = 'video' THEN 1 ELSE 0 END) as videos,
        SUM(CASE WHEN message_type = 'voice' THEN 1 ELSE 0 END) as audio,
        SUM(CASE WHEN message_type = 'file' THEN 1 ELSE 0 END) as documents,
        SUM(CASE WHEN message_type NOT IN ('image','video','voice','file') THEN 1 ELSE 0 END) as other
    FROM messages 
    WHERE attachment_path IS NOT NULL AND is_deleted = 0
";
$typeCountsResult = $conn->query($typeCountsQuery);
$typeCounts = $typeCountsResult->fetch_assoc();
$totalFiles = array_sum($typeCounts);

// Recent dates for date filter
$recentDatesQuery = "
    SELECT DISTINCT DATE(created_at) as date 
    FROM messages 
    WHERE attachment_path IS NOT NULL 
    ORDER BY date DESC 
    LIMIT 7
";
$recentDatesResult = $conn->query($recentDatesQuery);
$recentDates = [];
while ($row = $recentDatesResult->fetch_assoc()) {
    $recentDates[] = $row['date'];
}

// Storage statistics
$storageQuery = "
    SELECT 
        COUNT(*) as total_files,
        SUM(CASE WHEN message_type = 'image' THEN 1 ELSE 0 END) as images,
        SUM(CASE WHEN message_type = 'video' THEN 1 ELSE 0 END) as videos,
        SUM(CASE WHEN message_type = 'voice' THEN 1 ELSE 0 END) as audio
    FROM messages 
    WHERE attachment_path IS NOT NULL AND is_deleted = 0
";
$storageResult = $conn->query($storageQuery);
$storageStats = $storageResult->fetch_assoc();

function getFileIconClass($messageType) {
    return match($messageType) {
        'image' => 'fa-image',
        'video' => 'fa-video',
        'voice' => 'fa-music',
        'file' => 'fa-file-alt',
        default => 'fa-paperclip'
    };
}

function getFileIconColor($messageType) {
    return match($messageType) {
        'image' => 'image',
        'video' => 'video',
        'voice' => 'audio',
        'file' => 'document',
        default => 'other'
    };
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

function buildQueryString($updates = []) {
    $params = $_GET;
    foreach ($updates as $key => $value) {
        if ($value === null) unset($params[$key]);
        else $params[$key] = $value;
    }
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Management | BISureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Stats Mini */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .stat-mini {
            background: #fff;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }
        .stat-mini:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .stat-mini.active { border-color: #128C7E; background: rgba(18,140,126,0.03); }
        .stat-mini .stat-icon { font-size: 1.5rem; margin-bottom: 4px; }
        .stat-mini .stat-num { font-size: 1.3rem; font-weight: 700; }
        .stat-mini .stat-lbl { font-size: 0.7rem; color: #718096; text-transform: uppercase; }
        .stat-mini.image .stat-icon { color: #E91E63; }
        .stat-mini.video .stat-icon { color: #FF5722; }
        .stat-mini.audio .stat-icon { color: #9C27B0; }
        .stat-mini.document .stat-icon { color: #2196F3; }
        .stat-mini.other .stat-icon { color: #795548; }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }
        .search-box {
            position: relative;
            flex-grow: 1;
            max-width: 300px;
        }
        .search-box input {
            width: 100%;
            padding: 9px 14px 9px 38px;
            border: 1px solid #E2E8F0;
            border-radius: 24px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
        }
        .search-box input:focus { outline: none; border-color: #128C7E; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title { font-size: 1rem; font-weight: 600; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table th, table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        table th { background: #f8f9fa; font-weight: 600; color: #718096; font-size: 0.75rem; text-transform: uppercase; }
        table tr:hover { background: rgba(18,140,126,0.03); }

        .file-icon-cell {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .file-icon-cell.image { background: rgba(233,30,99,0.1); color: #E91E63; }
        .file-icon-cell.video { background: rgba(255,87,34,0.1); color: #FF5722; }
        .file-icon-cell.audio { background: rgba(156,39,176,0.1); color: #9C27B0; }
        .file-icon-cell.document { background: rgba(33,150,243,0.1); color: #2196F3; }
        .file-icon-cell.other { background: rgba(121,85,72,0.1); color: #795548; }

        .badge {
            padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;
            display: inline-block;
        }
        .badge-primary { background: rgba(18,140,126,0.1); color: #128C7E; }
        .badge-warning { background: rgba(243,156,18,0.1); color: #F39C12; }
        .badge-danger { background: rgba(231,76,60,0.1); color: #E74C3C; }

        .btn {
            padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer;
            font-size: 0.85rem; font-weight: 500; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-primary { background: #128C7E; color: #fff; }
        .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .alert {
            padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem;
            font-size: 0.9rem; display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .bulk-actions {
            display: flex; gap: 0.5rem; align-items: center;
            padding: 0.75rem 1.25rem; background: #f8f9fa;
            border-bottom: 1px solid #E2E8F0;
        }
        .bulk-actions select {
            padding: 6px 12px; border: 1px solid #E2E8F0; border-radius: 6px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem;
        }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        .file-name-cell { max-width: 200px; }
        .file-name-cell a { color: #2D3748; text-decoration: none; }
        .file-name-cell a:hover { color: #128C7E; text-decoration: underline; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }

        .text-muted { color: #718096; font-size: 0.8rem; }
        .flex { display: flex; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .items-center { align-items: center; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .stats-mini { grid-template-columns: repeat(3, 1fr); }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
        @media (max-width: 480px) {
            .stats-mini { grid-template-columns: repeat(2, 1fr); }
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
            <h1 class="page-title">File Management</h1>
            <span style="color:#718096;"><?= $totalFiles ?> files total</span>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- File Type Stats -->
        <div class="stats-mini">
            <a href="?<?= buildQueryString(['type' => null]) ?>" class="stat-mini image <?= !isset($_GET['type']) ? 'active' : '' ?>">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-num"><?= $totalFiles ?></div>
                <div class="stat-lbl">All Files</div>
            </a>
            <a href="?<?= buildQueryString(['type' => 'image']) ?>" class="stat-mini image <?= ($_GET['type'] ?? '') === 'image' ? 'active' : '' ?>">
                <div class="stat-icon"><i class="fas fa-image"></i></div>
                <div class="stat-num"><?= $typeCounts['images'] ?></div>
                <div class="stat-lbl">Images</div>
            </a>
            <a href="?<?= buildQueryString(['type' => 'video']) ?>" class="stat-mini video <?= ($_GET['type'] ?? '') === 'video' ? 'active' : '' ?>">
                <div class="stat-icon"><i class="fas fa-video"></i></div>
                <div class="stat-num"><?= $typeCounts['videos'] ?></div>
                <div class="stat-lbl">Videos</div>
            </a>
            <a href="?<?= buildQueryString(['type' => 'audio']) ?>" class="stat-mini audio <?= ($_GET['type'] ?? '') === 'audio' ? 'active' : '' ?>">
                <div class="stat-icon"><i class="fas fa-music"></i></div>
                <div class="stat-num"><?= $typeCounts['audio'] ?></div>
                <div class="stat-lbl">Audio</div>
            </a>
            <a href="?<?= buildQueryString(['type' => 'document']) ?>" class="stat-mini document <?= ($_GET['type'] ?? '') === 'document' ? 'active' : '' ?>">
                <div class="stat-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="stat-num"><?= $typeCounts['documents'] ?></div>
                <div class="stat-lbl">Documents</div>
            </a>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Filters</h2></div>
            <div style="padding: 1rem 1.25rem;">
                <form method="GET" class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search files, senders..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="date" class="btn btn-secondary btn-sm" onchange="this.form.submit()" style="padding:8px 12px;">
                        <option value="">All Dates</option>
                        <?php foreach ($recentDates as $date): ?>
                            <option value="<?= $date ?>" <?= (isset($_GET['date']) && $_GET['date'] === $date) ? 'selected' : '' ?>>
                                <?= date('M d, Y', strtotime($date)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="files" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>
        </div>

        <!-- Files Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Files (<?= $result->num_rows ?>)</h2>
                <div class="flex gap-2">
                    <button class="btn btn-secondary btn-sm" onclick="exportTable()"><i class="fas fa-download mr-1"></i> Export</button>
                </div>
            </div>

            <form method="POST">
                <?php if ($result->num_rows > 0): ?>
                    <div class="bulk-actions">
                        <label style="cursor:pointer;font-size:0.85rem;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> Select All
                        </label>
                        <select name="bulk_action" style="padding:6px 12px;border:1px solid #E2E8F0;border-radius:6px;font-family:'Poppins',sans-serif;">
                            <option value="">Bulk Action</option>
                            <option value="delete">🗑 Delete Selected</option>
                        </select>
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmBulkDelete()">Apply</button>
                        <span style="font-size:0.75rem;color:#718096;margin-left:auto;">Showing <?= $result->num_rows ?> files</span>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th width="30"></th>
                                    <th width="50">Type</th>
                                    <th>File</th>
                                    <th>Sender</th>
                                    <th>Receiver</th>
                                    <th>Date</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($file = $result->fetch_assoc()): 
                                    $filePath = '../../' . $file['attachment_path'];
                                    $fileExists = !empty($file['attachment_path']) && file_exists(__DIR__ . '/../../' . $file['attachment_path']);
                                    $fileName = basename($file['attachment_path'] ?? 'unknown');
                                    $fileSize = $fileExists ? filesize(__DIR__ . '/../../' . $file['attachment_path']) : 0;
                                    $iconColor = getFileIconColor($file['message_type']);
                                ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_files[]" value="<?= $file['id'] ?>"></td>
                                        <td>
                                            <div class="file-icon-cell <?= $iconColor ?>">
                                                <i class="fas <?= getFileIconClass($file['message_type']) ?>"></i>
                                            </div>
                                        </td>
                                        <td class="file-name-cell">
                                            <span class="truncate" title="<?= htmlspecialchars($fileName) ?>">
                                                <?= htmlspecialchars($fileName) ?>
                                            </span>
                                            <?php if ($fileExists): ?>
                                                <span class="text-muted"><?= formatFileSize($fileSize) ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="truncate" style="max-width:120px;"><?= htmlspecialchars($file['sender_name'] ?? $file['sender_username'] ?? 'Unknown') ?></span></td>
                                        <td><span class="truncate" style="max-width:120px;"><?= htmlspecialchars($file['receiver_name'] ?? $file['receiver_username'] ?? 'Unknown') ?></span></td>
                                        <td><?= date('M d, H:i', strtotime($file['created_at'])) ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <?php if ($fileExists): ?>
                                                    <a href="<?= htmlspecialchars('../../' . $file['attachment_path']) ?>" download class="btn btn-primary btn-sm"><i class="fas fa-download"></i></a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteFile(<?= $file['id'] ?>, '<?= addslashes($fileName) ?>')"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No files found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleSelectAll() {
            const master = document.getElementById('selectAll');
            document.querySelectorAll('input[name="selected_files[]"]').forEach(cb => cb.checked = master.checked);
        }

        function confirmBulkDelete() {
            const selected = document.querySelectorAll('input[name="selected_files[]"]:checked');
            if (selected.length === 0) {
                alert('Please select files to delete.');
                return false;
            }
            return confirm(`Delete ${selected.length} selected files? This cannot be undone.`);
        }

        function deleteFile(id, name) {
            if (confirm(`Delete "${name}"? This cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="selected_files[]" value="${id}"><input type="hidden" name="bulk_action" value="delete">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportTable() {
            const rows = document.querySelectorAll('table tbody tr');
            let csv = 'Type,File,Sender,Receiver,Date\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const type = cells[1]?.querySelector('i')?.className.replace('fas ', '') || '';
                const file = cells[2]?.querySelector('span')?.textContent?.trim() || '';
                const sender = cells[3]?.querySelector('span')?.textContent?.trim() || '';
                const receiver = cells[4]?.querySelector('span')?.textContent?.trim() || '';
                const date = cells[5]?.textContent?.trim() || '';
                csv += `"${type}","${file}","${sender}","${receiver}","${date}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'files_export.csv'; a.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>