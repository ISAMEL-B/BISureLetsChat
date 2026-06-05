<?php
/**
 * BUSure Chat - Admin Call Logs
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
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = $_GET['call_type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query
$whereConditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(u1.fullname LIKE ? OR u1.username LIKE ? OR u2.fullname LIKE ? OR u2.username LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
    $types .= "ssss";
}
if (!empty($typeFilter) && in_array($typeFilter, ['voice', 'video'])) {
    $whereConditions[] = "c.call_type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}
if (!empty($statusFilter) && in_array($statusFilter, ['ringing', 'answered', 'missed', 'declined', 'ended'])) {
    $whereConditions[] = "c.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if (!empty($dateFilter) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
    $whereConditions[] = "DATE(c.created_at) = ?";
    $params[] = $dateFilter;
    $types .= "s";
}

$whereSQL = implode(" AND ", $whereConditions);

// Count total
$countQuery = "SELECT COUNT(*) as total FROM calls c LEFT JOIN users u1 ON c.caller_id = u1.id LEFT JOIN users u2 ON c.receiver_id = u2.id WHERE $whereSQL";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);
$countStmt->close();

// Fetch calls
$query = "
    SELECT 
        c.id, c.call_type, c.status, c.started_at, c.ended_at, c.created_at,
        TIMESTAMPDIFF(SECOND, c.started_at, c.ended_at) as duration_seconds,
        u1.fullname as caller_name, u1.username as caller_username, u1.profile_photo as caller_photo,
        u2.fullname as receiver_name, u2.username as receiver_username, u2.profile_photo as receiver_photo,
        CASE WHEN c.caller_id = ? THEN 'outgoing' ELSE 'incoming' END as direction
    FROM calls c
    LEFT JOIN users u1 ON c.caller_id = u1.id
    LEFT JOIN users u2 ON c.receiver_id = u2.id
    WHERE $whereSQL
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";
array_unshift($params, $_SESSION['user_id']);
$types = "i" . $types . "ii";
$params[] = $perPage;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Stats
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN call_type = 'voice' THEN 1 ELSE 0 END) as voice_calls,
        SUM(CASE WHEN call_type = 'video' THEN 1 ELSE 0 END) as video_calls,
        SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed,
        SUM(CASE WHEN status = 'answered' THEN 1 ELSE 0 END) as answered,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
        AVG(TIMESTAMPDIFF(SECOND, started_at, ended_at)) as avg_duration
    FROM calls 
    WHERE started_at IS NOT NULL AND ended_at IS NOT NULL
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Recent dates
$recentDates = [];
$dr = $conn->query("SELECT DISTINCT DATE(created_at) as date FROM calls ORDER BY date DESC LIMIT 7");
while ($row = $dr->fetch_assoc()) $recentDates[] = $row['date'];

function formatDuration($seconds) {
    if ($seconds < 60) return $seconds . 's';
    $m = floor($seconds / 60);
    $s = $seconds % 60;
    if ($m < 60) return $m . 'm ' . $s . 's';
    $h = floor($m / 60);
    $m = $m % 60;
    return $h . 'h ' . $m . 'm';
}

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

$avgDuration = $stats['avg_duration'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Logs | BISureChat Admin</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-mini { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
        .stat-mini { background: #fff; padding: 1rem; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stat-mini .si { font-size: 1.4rem; margin-bottom: 4px; }
        .stat-mini .sn { font-size: 1.3rem; font-weight: 700; }
        .stat-mini .sl { font-size: 0.7rem; color: #718096; text-transform: uppercase; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; }
        .search-box { position: relative; flex-grow: 1; max-width: 300px; }
        .search-box input { width: 100%; padding: 9px 14px 9px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; }
        .search-box input:focus { outline: none; border-color: #128C7E; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }
        .filter-select { padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.85rem; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table th, table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        table th { background: #f8f9fa; font-weight: 600; color: #718096; font-size: 0.75rem; text-transform: uppercase; }
        table tr:hover { background: rgba(18,140,126,0.03); }

        .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .badge-success { background: rgba(37,211,102,0.1); color: #25D366; }
        .badge-danger { background: rgba(231,76,60,0.1); color: #E74C3C; }
        .badge-warning { background: rgba(243,156,18,0.1); color: #F39C12; }
        .badge-info { background: rgba(52,152,219,0.1); color: #3498DB; }
        .badge-secondary { background: rgba(149,165,166,0.1); color: #95A5A6; }
        .badge-primary { background: rgba(18,140,126,0.1); color: #128C7E; }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: #128C7E; color: #fff; } .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; } .btn-secondary:hover { background: #e0e0e0; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; padding: 1rem; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #4A5568; border: 1px solid #E2E8F0; }
        .pagination a:hover { background: #128C7E; color: #fff; border-color: #128C7E; }
        .pagination .active { background: #128C7E; color: #fff; border-color: #128C7E; font-weight: 600; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }
        .pagination .info { border: none; color: #718096; font-size: 0.8rem; }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .flex { display: flex; } .gap-2 { gap: 0.5rem; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .stats-mini { grid-template-columns: repeat(2, 1fr); }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Call Logs</h1>
            <span style="color:#718096;"><?= $totalRows ?> calls</span>
        </div>

        <!-- Stats -->
        <div class="stats-mini">
            <div class="stat-mini"><div class="si"><i class="fas fa-phone-alt" style="color:#128C7E;"></i></div><div class="sn"><?= number_format($stats['total'] ?? 0) ?></div><div class="sl">Total Calls</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-phone" style="color:#3498DB;"></i></div><div class="sn"><?= number_format($stats['voice_calls'] ?? 0) ?></div><div class="sl">Voice</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-video" style="color:#9C27B0;"></i></div><div class="sn"><?= number_format($stats['video_calls'] ?? 0) ?></div><div class="sl">Video</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-clock" style="color:#F39C12;"></i></div><div class="sn"><?= formatDuration($avgDuration) ?></div><div class="sl">Avg Duration</div></div>
            <div class="stat-mini"><div class="si"><i class="fas fa-times-circle" style="color:#E74C3C;"></i></div><div class="sn"><?= number_format($stats['missed'] ?? 0) ?></div><div class="sl">Missed</div></div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Filters</h2></div>
            <div style="padding:1rem 1.25rem;">
                <form method="GET" class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by caller or receiver..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="call_type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="voice" <?= $typeFilter === 'voice' ? 'selected' : '' ?>>Voice</option>
                        <option value="video" <?= $typeFilter === 'video' ? 'selected' : '' ?>>Video</option>
                    </select>
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="answered" <?= $statusFilter === 'answered' ? 'selected' : '' ?>>Answered</option>
                        <option value="missed" <?= $statusFilter === 'missed' ? 'selected' : '' ?>>Missed</option>
                        <option value="declined" <?= $statusFilter === 'declined' ? 'selected' : '' ?>>Declined</option>
                        <option value="ended" <?= $statusFilter === 'ended' ? 'selected' : '' ?>>Ended</option>
                    </select>
                    <select name="date" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Dates</option>
                        <?php foreach ($recentDates as $date): ?>
                            <option value="<?= $date ?>" <?= $dateFilter === $date ? 'selected' : '' ?>><?= date('M d, Y', strtotime($date)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="calls" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>
        </div>

        <!-- Calls Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Call History (<?= $result->num_rows ?>)</h2>
                <button class="btn btn-secondary btn-sm" onclick="exportTable()"><i class="fas fa-download"></i> Export</button>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Caller</th>
                                <th>Receiver</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($call = $result->fetch_assoc()): 
                                $callerName = $call['caller_name'] ?? $call['caller_username'] ?? 'Unknown';
                                $receiverName = $call['receiver_name'] ?? $call['receiver_username'] ?? 'Unknown';
                                $duration = $call['duration_seconds'] ?? 0;
                            ?>
                                <tr>
                                    <td>#<?= $call['id'] ?></td>
                                    <td><span class="truncate" style="max-width:130px;" title="<?= htmlspecialchars($callerName) ?>"><?= htmlspecialchars($callerName) ?></span></td>
                                    <td><span class="truncate" style="max-width:130px;" title="<?= htmlspecialchars($receiverName) ?>"><?= htmlspecialchars($receiverName) ?></span></td>
                                    <td>
                                        <?php if ($call['call_type'] === 'video'): ?>
                                            <span class="badge badge-primary"><i class="fas fa-video"></i> Video</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><i class="fas fa-phone"></i> Voice</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($call['status']) {
                                            'answered' => '<span class="badge badge-success">Answered</span>',
                                            'missed' => '<span class="badge badge-danger">Missed</span>',
                                            'declined' => '<span class="badge badge-warning">Declined</span>',
                                            'ended' => '<span class="badge badge-secondary">Ended</span>',
                                            default => '<span class="badge badge-secondary">' . ucfirst($call['status']) . '</span>'
                                        };
                                        echo $statusBadge;
                                        ?>
                                    </td>
                                    <td><?= $duration > 0 ? formatDuration($duration) : '—' ?></td>
                                    <td style="white-space:nowrap;"><?= date('M d, H:i', strtotime($call['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
                    <i class="fas fa-phone-slash"></i>
                    <h3>No calls found</h3>
                    <p>No call records match your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function exportTable() {
            const rows = document.querySelectorAll('table tbody tr');
            let csv = 'ID,Caller,Receiver,Type,Status,Duration,Date\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                csv += `"${cells[0]?.textContent?.trim()||''}","${cells[1]?.textContent?.trim()||''}","${cells[2]?.textContent?.trim()||''}","${cells[3]?.textContent?.trim()||''}","${cells[4]?.textContent?.trim()||''}","${cells[5]?.textContent?.trim()||''}","${cells[6]?.textContent?.trim()||''}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = 'call_logs_export.csv'; a.click();
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>