<?php
/**
 * BUSure Chat - Admin Server Logs
 * ✅ Uses reusable sidebar & footer
 * ✅ Reads from logs/errors.log
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../unauthorized");
    exit;
}

$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Log file path
$logFile = __DIR__ . '/../../logs/errors.log';
$accessLogFile = __DIR__ . '/../../logs/access.log';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

// Filters
$logType = $_GET['type'] ?? 'error'; // error | access
$search = $_GET['search'] ?? '';
$levelFilter = $_GET['level'] ?? '';

// Determine which log file to read
$currentLogFile = $logType === 'access' ? $accessLogFile : $logFile;

// Read and parse log entries
$allEntries = [];
if (file_exists($currentLogFile)) {
    $lines = file($currentLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Newest first
    
    $currentEntry = null;
    foreach ($lines as $line) {
        // Check if this line starts a new log entry (PHP error format)
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})[^\]]*\]\s*(.*)$/', $line, $matches)) {
            if ($currentEntry) {
                $allEntries[] = $currentEntry;
            }
            $level = 'INFO';
            if (stripos($line, 'Warning') !== false || stripos($line, 'WARNING') !== false) $level = 'WARNING';
            if (stripos($line, 'Fatal') !== false || stripos($line, 'Error') !== false || stripos($line, 'ERROR') !== false) $level = 'ERROR';
            if (stripos($line, 'Notice') !== false) $level = 'NOTICE';
            if (stripos($line, 'Deprecated') !== false) $level = 'DEPRECATED';
            
            $currentEntry = [
                'timestamp' => $matches[1],
                'message' => $matches[2],
                'level' => $level,
                'full' => $line
            ];
        } elseif ($currentEntry) {
            // Continuation of previous entry
            $currentEntry['full'] .= "\n" . $line;
            $currentEntry['message'] .= ' ' . trim($line);
        } else {
            // Standalone line
            $allEntries[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $line,
                'level' => 'INFO',
                'full' => $line
            ];
        }
    }
    if ($currentEntry) {
        $allEntries[] = $currentEntry;
    }
}

// Apply filters
$filteredEntries = $allEntries;
if (!empty($search)) {
    $filteredEntries = array_filter($filteredEntries, function($e) use ($search) {
        return stripos($e['message'], $search) !== false || stripos($e['full'], $search) !== false;
    });
}
if (!empty($levelFilter)) {
    $filteredEntries = array_filter($filteredEntries, function($e) use ($levelFilter) {
        return $e['level'] === strtoupper($levelFilter);
    });
}

// Re-index
$filteredEntries = array_values($filteredEntries);
$totalEntries = count($filteredEntries);
$totalPages = ceil($totalEntries / $perPage);
$entries = array_slice($filteredEntries, ($page - 1) * $perPage, $perPage);

// Count by level
$levelCounts = ['ERROR' => 0, 'WARNING' => 0, 'NOTICE' => 0, 'DEPRECATED' => 0, 'INFO' => 0];
foreach ($allEntries as $e) {
    if (isset($levelCounts[$e['level']])) $levelCounts[$e['level']]++;
}
$totalAll = array_sum($levelCounts);

// Log file size
$errorLogSize = file_exists($logFile) ? filesize($logFile) : 0;
$accessLogSize = file_exists($accessLogFile) ? filesize($accessLogFile) : 0;

// Database stats
$dbStats = [];
$dbStats['users'] = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$dbStats['messages'] = $conn->query("SELECT COUNT(*) as c FROM messages")->fetch_assoc()['c'];
$dbStats['calls'] = $conn->query("SELECT COUNT(*) as c FROM calls")->fetch_assoc()['c'];
$dbStats['groups'] = $conn->query("SELECT COUNT(*) as c FROM groups_chat")->fetch_assoc()['c'];
$dbStats['db_size'] = $conn->query("
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size 
    FROM information_schema.tables 
    WHERE table_schema = 'bisure_lets_chat'
")->fetch_assoc()['size'];

function formatBytes($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function getLevelColor($level) {
    return match($level) {
        'ERROR' => '#E74C3C',
        'WARNING' => '#F39C12',
        'NOTICE' => '#3498DB',
        'DEPRECATED' => '#9B59B6',
        default => '#95A5A6'
    };
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Logs | BISureChat Admin</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .stats-mini { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
        .stat-mini { background: #fff; padding: 1rem; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stat-mini .si { font-size: 1.3rem; margin-bottom: 4px; }
        .stat-mini .sn { font-size: 1.2rem; font-weight: 700; }
        .stat-mini .sl { font-size: 0.7rem; color: #718096; text-transform: uppercase; }

        .toolbar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; }
        .search-box { position: relative; flex-grow: 1; max-width: 350px; }
        .search-box input { width: 100%; padding: 9px 14px 9px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; }
        .search-box input:focus { outline: none; border-color: #128C7E; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }
        .toolbar select, .toolbar .btn { padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.85rem; cursor: pointer; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; }

        .log-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .log-table th, .log-table td { padding: 8px 14px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .log-table th { background: #f8f9fa; font-weight: 600; color: #718096; font-size: 0.7rem; text-transform: uppercase; position: sticky; top: 0; }
        .log-table tr:hover { background: rgba(18,140,126,0.02); }
        .log-table .log-time { white-space: nowrap; font-family: 'JetBrains Mono', monospace; font-size: 0.78rem; color: #718096; width: 140px; }
        .log-table .log-level { width: 90px; text-align: center; }
        .log-table .log-msg { font-family: 'JetBrains Mono', monospace; font-size: 0.78rem; word-break: break-all; line-height: 1.5; max-width: 600px; }

        .level-badge { padding: 2px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .level-error { background: rgba(231,76,60,0.1); color: #E74C3C; }
        .level-warning { background: rgba(243,156,18,0.1); color: #F39C12; }
        .level-notice { background: rgba(52,152,219,0.1); color: #3498DB; }
        .level-deprecated { background: rgba(155,89,182,0.1); color: #9B59B6; }
        .level-info { background: rgba(149,165,166,0.1); color: #95A5A6; }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: #128C7E; color: #fff; } .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; } .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; } .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; padding: 1rem; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 8px; font-size: 0.85rem; text-decoration: none; color: #4A5568; border: 1px solid #E2E8F0; }
        .pagination a:hover { background: #128C7E; color: #fff; border-color: #128C7E; }
        .pagination .active { background: #128C7E; color: #fff; border-color: #128C7E; font-weight: 600; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        .db-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem; margin-bottom: 1rem; }
        .db-item { background: #f8f9fa; padding: 0.75rem; border-radius: 8px; text-align: center; }
        .db-item .db-val { font-weight: 700; font-size: 1.1rem; }
        .db-item .db-lbl { font-size: 0.65rem; color: #718096; text-transform: uppercase; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; padding-top: 60px; }
            .stats-mini { grid-template-columns: repeat(3, 1fr); }
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            .log-table .log-msg { max-width: 250px; }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">Server Logs</h1>
            <span style="color:#718096;"><?= $totalEntries ?> entries</span>
        </div>

        <!-- Stats -->
        <div class="stats-mini">
            <div class="stat-mini" style="cursor:pointer;" onclick="window.location='?level=error&type=<?= $logType ?>'">
                <div class="si" style="color:#E74C3C;"><i class="fas fa-times-circle"></i></div>
                <div class="sn"><?= $levelCounts['ERROR'] ?></div>
                <div class="sl">Errors</div>
            </div>
            <div class="stat-mini" style="cursor:pointer;" onclick="window.location='?level=warning&type=<?= $logType ?>'">
                <div class="si" style="color:#F39C12;"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="sn"><?= $levelCounts['WARNING'] ?></div>
                <div class="sl">Warnings</div>
            </div>
            <div class="stat-mini" style="cursor:pointer;" onclick="window.location='?level=notice&type=<?= $logType ?>'">
                <div class="si" style="color:#3498DB;"><i class="fas fa-info-circle"></i></div>
                <div class="sn"><?= $levelCounts['NOTICE'] ?></div>
                <div class="sl">Notices</div>
            </div>
            <div class="stat-mini">
                <div class="si" style="color:#718096;"><i class="fas fa-file-alt"></i></div>
                <div class="sn"><?= formatBytes($errorLogSize) ?></div>
                <div class="sl">Error Log Size</div>
            </div>
        </div>

        <!-- Database Info -->
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header"><h2 class="card-title">System Overview</h2></div>
            <div style="padding:1rem;">
                <div class="db-info">
                    <div class="db-item"><div class="db-val"><?= number_format($dbStats['users']) ?></div><div class="db-lbl">Users</div></div>
                    <div class="db-item"><div class="db-val"><?= number_format($dbStats['messages']) ?></div><div class="db-lbl">Messages</div></div>
                    <div class="db-item"><div class="db-val"><?= number_format($dbStats['calls']) ?></div><div class="db-lbl">Calls</div></div>
                    <div class="db-item"><div class="db-val"><?= number_format($dbStats['groups']) ?></div><div class="db-lbl">Groups</div></div>
                    <div class="db-item"><div class="db-val"><?= $dbStats['db_size'] ?> MB</div><div class="db-lbl">DB Size</div></div>
                    <div class="db-item"><div class="db-val"><?= formatBytes($errorLogSize) ?></div><div class="db-lbl">Error Log</div></div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;flex:1;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="type" onchange="this.form.submit()">
                    <option value="error" <?= $logType === 'error' ? 'selected' : '' ?>>Error Log</option>
                    <option value="access" <?= $logType === 'access' ? 'selected' : '' ?>>Access Log</option>
                </select>
                <select name="level" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <option value="error" <?= $levelFilter === 'error' ? 'selected' : '' ?>>ERROR</option>
                    <option value="warning" <?= $levelFilter === 'warning' ? 'selected' : '' ?>>WARNING</option>
                    <option value="notice" <?= $levelFilter === 'notice' ? 'selected' : '' ?>>NOTICE</option>
                    <option value="deprecated" <?= $levelFilter === 'deprecated' ? 'selected' : '' ?>>DEPRECATED</option>
                    <option value="info" <?= $levelFilter === 'info' ? 'selected' : '' ?>>INFO</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="logs" class="btn btn-secondary btn-sm">Reset</a>
            </form>
            <button class="btn btn-danger btn-sm" onclick="clearLogs()"><i class="fas fa-eraser"></i> Clear <?= $logType === 'error' ? 'Error' : 'Access' ?> Log</button>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $logType === 'error' ? 'Error' : 'Access' ?> Log (<?= count($entries) ?>)</h2>
                <div>
                    <a href="<?= $currentLogFile ?>" download class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download</a>
                </div>
            </div>

            <?php if (count($entries) > 0): ?>
                <div style="overflow-x:auto;max-height:60vh;overflow-y:auto;">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th class="log-time">Timestamp</th>
                                <th class="log-level">Level</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td class="log-time"><?= htmlspecialchars($entry['timestamp']) ?></td>
                                    <td class="log-level">
                                        <span class="level-badge level-<?= strtolower($entry['level']) ?>"><?= $entry['level'] ?></span>
                                    </td>
                                    <td class="log-msg"><?= htmlspecialchars(substr($entry['message'], 0, 500)) ?></td>
                                </tr>
                            <?php endforeach; ?>
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
                    if ($start > 1) echo '<span class="info" style="border:none;padding:0 8px;">...</span>';
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?= paginationUrl($i) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    if ($end < $totalPages) echo '<span class="info" style="border:none;padding:0 8px;">...</span>';
                    ?>
                    <a href="<?= paginationUrl($page + 1) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-right"></i></a>
                    <a href="<?= paginationUrl($totalPages) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-double-right"></i></a>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color:#25D366;"></i>
                    <h3>No log entries found</h3>
                    <p><?= !empty($search) || !empty($levelFilter) ? 'No entries match your filters.' : 'The log is clean! No errors recorded.' ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function clearLogs() {
            if (confirm('Are you sure you want to clear the <?= $logType ?> log? This cannot be undone.')) {
                fetch('../api/clear_logs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'type=<?= $logType ?>'
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert('Log cleared successfully!');
                        location.reload();
                    } else {
                        alert('Failed to clear log: ' + (d.error || 'Unknown error'));
                    }
                })
                .catch(() => alert('Network error'));
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>