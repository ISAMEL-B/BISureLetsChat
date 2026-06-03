<?php
/**
 * BUSure Chat - Admin Message Center
 * Updated & Secured Version
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    header('Location: ../../auth/login');
    exit;
}

$current_admin_name =
    $_SESSION['fullname']
    ?? $_SESSION['username']
    ?? 'Admin';

/* =====================================================
   PAGINATION
===================================================== */

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'default' => 1,
            'min_range' => 1
        ]
    ]
);

$perPage = 15;
$offset  = ($page - 1) * $perPage;

/* =====================================================
   FILTERS
===================================================== */

$search = trim(
    filter_input(
        INPUT_GET,
        'search',
        FILTER_SANITIZE_SPECIAL_CHARS
    ) ?? ''
);

$status = $_GET['status'] ?? '';
$type   = $_GET['type'] ?? '';
$date   = $_GET['date'] ?? '';

$readFilter = '';
$typeFilter = '';
$dateFilter = '';
$dateParam  = '';

if (in_array($status, ['read', 'unread'], true)) {

    if ($status === 'unread') {
        $readFilter =
            "AND m.id NOT IN (
                SELECT message_id
                FROM message_reads
            )";
    } else {
        $readFilter =
            "AND m.id IN (
                SELECT message_id
                FROM message_reads
            )";
    }
}

if (in_array($type, ['text', 'file'], true)) {

    if ($type === 'text') {

        $typeFilter =
            "AND m.message_type='text'
             AND m.message_text IS NOT NULL";

    } else {

        $typeFilter =
            "AND m.message_type IN
            ('image','video','voice','file')
             AND m.attachment_path IS NOT NULL";
    }
}

if (
    !empty($date) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
) {
    $dateFilter = "AND DATE(m.created_at)=?";
    $dateParam  = $date;
}

/* =====================================================
   BULK ACTIONS
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['bulk_action']) &&
    !empty($_POST['selected_messages'])
) {

    $messageIds = array_map(
        'intval',
        (array) $_POST['selected_messages']
    );

    if (empty($messageIds)) {

        $_SESSION['error'] =
            'No messages selected.';

        header('Location: messages');
        exit;
    }

    $placeholders =
        implode(',', array_fill(
            0,
            count($messageIds),
            '?'
        ));

    $bindTypes =
        str_repeat(
            'i',
            count($messageIds)
        );

    try {

        switch ($_POST['bulk_action']) {

            case 'mark_read':

                $stmt = $conn->prepare(
                    "INSERT IGNORE INTO
                    message_reads
                    (message_id,user_id)
                    SELECT ?, id
                    FROM users"
                );

                foreach ($messageIds as $messageId) {

                    $stmt->bind_param(
                        'i',
                        $messageId
                    );

                    $stmt->execute();
                }

                $stmt->close();

                $_SESSION['success'] =
                    count($messageIds)
                    . ' messages marked as read';

                break;

            case 'mark_unread':

                $stmt = $conn->prepare(
                    "DELETE FROM message_reads
                     WHERE message_id
                     IN ($placeholders)"
                );

                $stmt->bind_param(
                    $bindTypes,
                    ...$messageIds
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['success'] =
                    count($messageIds)
                    . ' messages marked as unread';

                break;

            case 'delete':

                $stmt = $conn->prepare(
                    "UPDATE messages
                     SET is_deleted = 1
                     WHERE id
                     IN ($placeholders)"
                );

                $stmt->bind_param(
                    $bindTypes,
                    ...$messageIds
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['success'] =
                    count($messageIds)
                    . ' messages deleted';

                break;

            default:

                $_SESSION['error'] =
                    'Invalid action.';
        }

    } catch (Throwable $e) {

        error_log(
            'Message Center Error: '
            . $e->getMessage()
        );

        $_SESSION['error'] =
            'ERR-UQZ11X23';
    }

    header('Location: messages');
    exit;
}

/* =====================================================
   BUILD WHERE CLAUSE
===================================================== */

$whereConditions = [
    "m.is_deleted = 0"
];

$params = [];
$types  = '';

if (!empty($search)) {

    $whereConditions[] =
        "(m.message_text LIKE ?
        OR u1.fullname LIKE ?
        OR u1.username LIKE ?
        OR u2.fullname LIKE ?
        OR u2.username LIKE ?)";

    $searchParam = "%{$search}%";

    $params = array_merge(
        $params,
        [
            $searchParam,
            $searchParam,
            $searchParam,
            $searchParam,
            $searchParam
        ]
    );

    $types .= 'sssss';
}

if (!empty($readFilter)) {
    $whereConditions[] = substr($readFilter, 4);
}

if (!empty($typeFilter)) {
    $whereConditions[] = substr($typeFilter, 4);
}

if (!empty($dateFilter)) {

    $whereConditions[] =
        substr($dateFilter, 4);

    $params[] = $dateParam;
    $types .= 's';
}

$whereSQL =
    implode(
        ' AND ',
        $whereConditions
    );

/* =====================================================
   COUNT RECORDS
===================================================== */

$countQuery = "
SELECT COUNT(DISTINCT m.id) AS total
FROM messages m
LEFT JOIN users u1
ON m.sender_id = u1.id
LEFT JOIN conversation_participants cp
ON m.conversation_id = cp.conversation_id
AND cp.user_id != m.sender_id
LEFT JOIN users u2
ON cp.user_id = u2.id
WHERE $whereSQL
";

$countStmt =
    $conn->prepare($countQuery);

if (!empty($params)) {
    $countStmt->bind_param(
        $types,
        ...$params
    );
}

$countStmt->execute();

$totalRows =
    (int)
    $countStmt
    ->get_result()
    ->fetch_assoc()['total'];

$countStmt->close();

$totalPages =
    max(
        1,
        (int) ceil(
            $totalRows / $perPage
        )
    );

/* =====================================================
   FETCH RECORDS
===================================================== */

$query = "
SELECT
m.id,
m.message_type,
m.message_text,
m.attachment_path,
m.is_edited,
m.created_at,
m.updated_at,
u1.fullname AS sender_name,
u1.username AS sender_username,
u2.fullname AS receiver_name,
u2.username AS receiver_username,
CASE
WHEN mr.message_id IS NOT NULL
THEN 1
ELSE 0
END AS is_read
FROM messages m
LEFT JOIN users u1
ON m.sender_id=u1.id
LEFT JOIN conversation_participants cp
ON m.conversation_id=cp.conversation_id
AND cp.user_id!=m.sender_id
LEFT JOIN users u2
ON cp.user_id=u2.id
LEFT JOIN message_reads mr
ON m.id=mr.message_id
WHERE $whereSQL
GROUP BY m.id
ORDER BY m.created_at DESC
LIMIT ? OFFSET ?
";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$result =
    $stmt->get_result();

$stmt->close();

/* =====================================================
   STATS
===================================================== */

$statusCounts =
$conn->query("
SELECT
SUM(CASE WHEN mr.message_id IS NOT NULL THEN 1 ELSE 0 END) AS read_count,
SUM(CASE WHEN mr.message_id IS NULL THEN 1 ELSE 0 END) AS unread_count
FROM messages m
LEFT JOIN message_reads mr
ON m.id = mr.message_id
WHERE m.is_deleted = 0
")->fetch_assoc();

$typeCounts =
$conn->query("
SELECT
SUM(CASE WHEN message_type='text' THEN 1 ELSE 0 END) AS text_count,
SUM(CASE WHEN message_type IN ('image','video','voice','file') THEN 1 ELSE 0 END) AS file_count
FROM messages
WHERE is_deleted = 0
")->fetch_assoc();

$totalMessages =
(int)$statusCounts['read_count']
+
(int)$statusCounts['unread_count'];

/* =====================================================
   RECENT DATES
===================================================== */

$recentDates = [];

$dr = $conn->query(
"SELECT DISTINCT DATE(created_at) AS date
FROM messages
WHERE is_deleted = 0
ORDER BY date DESC
LIMIT 7"
);

while ($row = $dr->fetch_assoc()) {
    $recentDates[] = $row['date'];
}

/* =====================================================
   HELPERS
===================================================== */

function buildQueryString(
    array $updates = []
): string {

    $params = $_GET;

    foreach (
        $updates as $key => $value
    ) {

        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return http_build_query(
        $params
    );
}

function paginationUrl(
    int $page
): string {

    $params = $_GET;
    $params['page'] = $page;

    return '?'
        . http_build_query(
            $params
        );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Center | BISureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        /* Stats Mini */
        .stats-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
        .stat-mini { background: #fff; padding: 1rem; border-radius: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; border: 2px solid transparent; }
        .stat-mini:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .stat-mini.active { border-color: #128C7E; background: rgba(18,140,126,0.03); }
        .stat-mini .si { font-size: 1.4rem; margin-bottom: 4px; }
        .stat-mini .sn { font-size: 1.3rem; font-weight: 700; }
        .stat-mini .sl { font-size: 0.7rem; color: #718096; text-transform: uppercase; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
        .search-box { position: relative; flex-grow: 1; max-width: 300px; }
        .search-box input { width: 100%; padding: 9px 14px 9px 38px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; }
        .search-box input:focus { outline: none; border-color: #128C7E; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #A0AEC0; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table th, table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        table th { background: #f8f9fa; font-weight: 600; color: #718096; font-size: 0.75rem; text-transform: uppercase; }
        table tr:hover { background: rgba(18,140,126,0.03); }
        tr.unread { background: rgba(37,211,102,0.04); }
        tr.unread td { font-weight: 500; }

        .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
        .badge-success { background: rgba(37,211,102,0.1); color: #25D366; }
        .badge-warning { background: rgba(243,156,18,0.1); color: #F39C12; }
        .badge-info { background: rgba(52,152,219,0.1); color: #3498DB; }
        .badge-primary { background: rgba(18,140,126,0.1); color: #128C7E; }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: #128C7E; color: #fff; } .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; } .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger { background: #E74C3C; color: #fff; } .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 5px 10px; font-size: 0.75rem; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .bulk-actions { display: flex; gap: 0.5rem; align-items: center; padding: 0.75rem 1.25rem; background: #f8f9fa; border-bottom: 1px solid #E2E8F0; }
        .bulk-actions select { padding: 6px 12px; border: 1px solid #E2E8F0; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 0.85rem; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; padding: 1rem; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 8px; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; color: #4A5568; border: 1px solid #E2E8F0; }
        .pagination a:hover { background: #128C7E; color: #fff; border-color: #128C7E; }
        .pagination .active { background: #128C7E; color: #fff; border-color: #128C7E; font-weight: 600; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }
        .pagination .info { border: none; color: #718096; font-size: 0.8rem; }

        /* Modals */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 14px; width: 90%; max-width: 550px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalIn 0.25s ease; }
        .modal-box.sm { max-width: 400px; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-head { padding: 1rem 1.25rem; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; }
        .modal-head h3 { font-size: 1.1rem; font-weight: 600; }
        .modal-close { font-size: 1.3rem; cursor: pointer; color: #A0AEC0; background: none; border: none; padding: 4px; }
        .modal-close:hover { color: #E74C3C; }
        .modal-body { padding: 1.25rem; }
        .modal-foot { padding: 1rem 1.25rem; border-top: 1px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 10px; }

        .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-lbl { color: #718096; font-size: 0.85rem; min-width: 90px; flex-shrink: 0; }
        .detail-val { font-weight: 500; font-size: 0.9rem; word-break: break-word; }
        .msg-body { background: #f7fafc; padding: 1rem; border-radius: 10px; margin-top: 0.5rem; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6; }

        .empty-state { text-align: center; padding: 3rem; color: #718096; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .flex { display: flex; } .gap-2 { gap: 0.5rem; } .items-center { align-items: center; }

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
            <h1 class="page-title">Message Center</h1>
            <span style="color:#718096;"><?= $totalMessages ?> messages total</span>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-mini">
            <a href="?<?= buildQueryString(['status' => null]) ?>" class="stat-mini <?= !isset($_GET['status']) ? 'active' : '' ?>">
                <div class="si"><i class="fas fa-envelope"></i></div><div class="sn"><?= $totalMessages ?></div><div class="sl">All</div>
            </a>
            <a href="?<?= buildQueryString(['status' => 'read']) ?>" class="stat-mini <?= ($_GET['status'] ?? '') === 'read' ? 'active' : '' ?>">
                <div class="si" style="color:#25D366;"><i class="fas fa-check-double"></i></div><div class="sn"><?= $statusCounts['read_count'] ?></div><div class="sl">Read</div>
            </a>
            <a href="?<?= buildQueryString(['status' => 'unread']) ?>" class="stat-mini <?= ($_GET['status'] ?? '') === 'unread' ? 'active' : '' ?>">
                <div class="si" style="color:#3498DB;"><i class="fas fa-envelope-open-text"></i></div><div class="sn"><?= $statusCounts['unread_count'] ?></div><div class="sl">Unread</div>
            </a>
            <a href="?<?= buildQueryString(['type' => 'file']) ?>" class="stat-mini <?= ($_GET['type'] ?? '') === 'file' ? 'active' : '' ?>">
                <div class="si" style="color:#F39C12;"><i class="fas fa-paperclip"></i></div><div class="sn"><?= $typeCounts['file_count'] ?></div><div class="sl">Files</div>
            </a>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Filters</h2></div>
            <div style="padding:1rem 1.25rem;">
                <form method="GET" class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search messages..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="date" style="padding:8px 12px;border:1px solid #E2E8F0;border-radius:8px;font-family:'Poppins',sans-serif;" onchange="this.form.submit()">
                        <option value="">All Dates</option>
                        <?php foreach ($recentDates as $date): ?>
                            <option value="<?= $date ?>" <?= (isset($_GET['date']) && $_GET['date'] === $date) ? 'selected' : '' ?>><?= date('M d, Y', strtotime($date)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="messages" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Messages (<?= $totalRows ?>)</h2>
                <button class="btn btn-secondary btn-sm" onclick="exportTable()"><i class="fas fa-download"></i> Export</button>
            </div>
            <form method="POST">
                <?php if ($result->num_rows > 0): ?>
                    <div class="bulk-actions">
                        <label style="cursor:pointer;font-size:0.85rem;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> Select All</label>
                        <select name="bulk_action">
                            <option value="">Bulk Action</option>
                            <option value="mark_read">✓ Mark Read</option>
                            <option value="mark_unread">○ Mark Unread</option>
                            <option value="delete">🗑 Delete</option>
                        </select>
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmBulk()">Apply</button>
                        <span style="font-size:0.75rem;color:#718096;margin-left:auto;">Page <?= $page ?> of <?= $totalPages ?></span>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th width="30"></th><th>Date</th><th>Sender</th><th>Receiver</th><th>Content</th><th width="80">Status</th><th width="90">Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($msg = $result->fetch_assoc()): ?>
                                    <tr class="<?= $msg['is_read'] == 0 ? 'unread' : '' ?>">
                                        <td><input type="checkbox" name="selected_messages[]" value="<?= $msg['id'] ?>"></td>
                                        <td style="white-space:nowrap;"><?= date('M d, H:i', strtotime($msg['created_at'])) ?></td>
                                        <td><span class="truncate" style="max-width:120px;" title="<?= htmlspecialchars($msg['sender_name'] ?? $msg['sender_username'] ?? 'Unknown') ?>"><?= htmlspecialchars($msg['sender_name'] ?? $msg['sender_username'] ?? 'Unknown') ?></span></td>
                                        <td><span class="truncate" style="max-width:120px;" title="<?= htmlspecialchars($msg['receiver_name'] ?? $msg['receiver_username'] ?? 'Unknown') ?>"><?= htmlspecialchars($msg['receiver_name'] ?? $msg['receiver_username'] ?? 'Unknown') ?></span></td>
                                        <td>
                                            <?php if ($msg['message_type'] !== 'text' && !empty($msg['attachment_path'])): ?>
                                                <span class="badge badge-warning"><i class="fas fa-paperclip"></i> <?= ucfirst($msg['message_type']) ?></span>
                                            <?php else: ?>
                                                <span class="truncate" style="max-width:250px;"><?= htmlspecialchars(substr($msg['message_text'] ?? '', 0, 80)) ?></span>
                                                <?php if ($msg['is_edited']): ?><span class="badge badge-info" style="font-size:0.6rem;">edited</span><?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $msg['is_read'] ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-info">Unread</span>' ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="viewMessage(<?= $msg['id'] ?>)"><i class="fas fa-eye"></i></button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $msg['id'] ?>)"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
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
                    <div class="empty-state"><i class="fas fa-comment-slash"></i><h3>No messages found</h3><p>Try adjusting your search or filters</p></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ✅ View Message Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-head"><h3>Message Details</h3><button class="modal-close" onclick="closeViewModal()">&times;</button></div>
            <div class="modal-body" id="viewContent">
                <div style="text-align:center;padding:2rem;color:#718096;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-secondary" onclick="closeViewModal()">Close</button>
                <a href="#" id="viewDownload" class="btn btn-primary" style="display:none;"><i class="fas fa-download"></i> Download</a>
            </div>
        </div>
    </div>

    <!-- ✅ Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box sm">
            <div class="modal-head"><h3>Delete Message</h3><button class="modal-close" onclick="closeDeleteModal()">&times;</button></div>
            <div class="modal-body" style="text-align:center;">
                <div style="font-size:3rem;color:#E74C3C;margin-bottom:1rem;"><i class="fas fa-trash-alt"></i></div>
                <p style="font-size:1rem;margin-bottom:0.5rem;">Are you sure?</p>
                <p style="color:#718096;font-size:0.85rem;">This message will be permanently deleted. This action cannot be undone.</p>
                <input type="hidden" id="deleteMsgId">
            </div>
            <div class="modal-foot" style="justify-content:center;">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleSelectAll() { const m = document.getElementById('selectAll'); document.querySelectorAll('input[name="selected_messages[]"]').forEach(cb => cb.checked = m.checked); }
        function confirmBulk() { const s = document.querySelectorAll('input[name="selected_messages[]"]:checked'); if (s.length === 0) { alert('Select messages first.'); return false; } return confirm(`Apply action to ${s.length} messages?`); }

        // View Modal
        function viewMessage(id) {
            document.getElementById('viewModal').classList.add('active');
            document.getElementById('viewContent').innerHTML = '<div style="text-align:center;padding:2rem;color:#718096;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            fetch(`../api/get_message?id=${id}`)
                .then(r => r.json())
                .then(d => {
                    const dl = document.getElementById('viewDownload');
                    dl.style.display = d.attachment_path ? 'inline-flex' : 'none';
                    if (d.attachment_path) dl.href = '../../' + d.attachment_path;
                    
                    document.getElementById('viewContent').innerHTML = `
                        <div class="detail-row"><span class="detail-lbl">Message ID</span><span class="detail-val">#${d.id}</span></div>
                        <div class="detail-row"><span class="detail-lbl">Date</span><span class="detail-val">${new Date(d.created_at).toLocaleString()}</span></div>
                        <div class="detail-row"><span class="detail-lbl">Sender</span><span class="detail-val">${d.sender_name || d.sender_username || 'Unknown'}</span></div>
                        <div class="detail-row"><span class="detail-lbl">Receiver</span><span class="detail-val">${d.receiver_name || d.receiver_username || 'Unknown'}</span></div>
                        <div class="detail-row"><span class="detail-lbl">Type</span><span class="detail-val">${(d.message_type||'text').toUpperCase()} ${d.is_edited ? '<span class="badge badge-info">Edited</span>' : ''}</span></div>
                        <div class="detail-row"><span class="detail-lbl">Status</span><span class="detail-val">${d.is_read ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-info">Unread</span>'}</span></div>
                        ${d.attachment_path ? `<div class="detail-row"><span class="detail-lbl">File</span><span class="detail-val">${d.attachment_path.split('/').pop()}</span></div>` : ''}
                        <div class="msg-body">${d.message_text || '(No text content)'}</div>
                    `;
                })
                .catch(() => {
                    document.getElementById('viewContent').innerHTML = '<div style="text-align:center;padding:2rem;color:#E74C3C;">Failed to load message.</div>';
                });
        }
        function closeViewModal() { document.getElementById('viewModal').classList.remove('active'); }

        // Delete Modal
        function openDeleteModal(id) { document.getElementById('deleteMsgId').value = id; document.getElementById('deleteModal').classList.add('active'); }
        function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); }
        function confirmDelete() {
            const id = document.getElementById('deleteMsgId').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="selected_messages[]" value="${id}"><input type="hidden" name="bulk_action" value="delete">`;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
        });

        function exportTable() {
            const rows = document.querySelectorAll('table tbody tr');
            let csv = 'Date,Sender,Receiver,Content,Status\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                csv += `"${cells[1]?.textContent?.trim()||''}","${cells[2]?.textContent?.trim()||''}","${cells[3]?.textContent?.trim()||''}","${cells[4]?.textContent?.trim()||''}","${cells[5]?.textContent?.trim()||''}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = 'messages_export.csv'; a.click();
        }
    </script>
</body>
</html>
