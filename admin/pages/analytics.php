<?php
/**
 * BUSure Chat - Admin Analytics
 * ✅ Uses reusable sidebar & footer
 * ✅ Matches busure_lets_chat schema
 * ✅ Session uses user_role
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../../auth/login");
    exit;
}

$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Get time period (default to 30 days)
$timePeriod = isset($_GET['period']) ? intval($_GET['period']) : 30;
$timePeriod = in_array($timePeriod, [7, 30, 90, 180, 365]) ? $timePeriod : 30;

$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime("-$timePeriod days"));

// ✅ User Growth Data (using users table)
$userGrowthQuery = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
    FROM users
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
";
$stmt = $conn->prepare($userGrowthQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$userGrowthResult = $stmt->get_result();

$userGrowthLabels = [];
$userGrowthCounts = [];
$adminCounts = [];

while ($row = $userGrowthResult->fetch_assoc()) {
    $userGrowthLabels[] = $row['date'];
    $userGrowthCounts[] = $row['count'];
    $adminCounts[] = $row['admins'];
}
$stmt->close();

// ✅ Message Activity Data (using messages table)
$messageActivityQuery = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        COUNT(DISTINCT sender_id) as active_senders,
        SUM(CASE WHEN attachment_path IS NOT NULL THEN 1 ELSE 0 END) as files_count
    FROM messages
    WHERE created_at BETWEEN ? AND ? AND is_deleted = 0
    GROUP BY DATE(created_at)
    ORDER BY date
";
$stmt = $conn->prepare($messageActivityQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$messageActivityResult = $stmt->get_result();

$messageActivityLabels = [];
$messageActivityCounts = [];
$fileMessageCounts = [];

while ($row = $messageActivityResult->fetch_assoc()) {
    $messageActivityLabels[] = $row['date'];
    $messageActivityCounts[] = $row['total'];
    $fileMessageCounts[] = $row['files_count'];
}
$stmt->close();

// ✅ User Engagement Stats
$engagementQuery = "
    SELECT
        COUNT(DISTINCT sender_id) as active_senders,
        COUNT(DISTINCT m.id) as total_messages,
        COUNT(DISTINCT CASE WHEN is_edited = 1 THEN m.id END) as edited_messages
    FROM messages m
    WHERE m.created_at BETWEEN ? AND ? AND m.is_deleted = 0
";
$stmt = $conn->prepare($engagementQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$engagementResult = $stmt->get_result();
$engagementStats = $engagementResult->fetch_assoc();
$stmt->close();

// ✅ Top Users
$topUsersQuery = "
    SELECT 
        u.id,
        u.fullname,
        u.username,
        u.email,
        COUNT(m.id) as message_count
    FROM users u
    LEFT JOIN messages m ON u.id = m.sender_id AND m.is_deleted = 0
    WHERE m.created_at BETWEEN ? AND ? OR m.created_at IS NULL
    GROUP BY u.id
    ORDER BY message_count DESC
    LIMIT 5
";
$stmt = $conn->prepare($topUsersQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topUsersResult = $stmt->get_result();

// ✅ Platform Statistics
$platformStatsQuery = "
    SELECT
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM messages WHERE is_deleted = 0) as total_messages,
        (SELECT COUNT(*) FROM messages WHERE attachment_path IS NOT NULL AND is_deleted = 0) as total_files,
        (SELECT COUNT(*) FROM messages m WHERE m.is_deleted = 0 AND m.id NOT IN (SELECT mr.message_id FROM message_reads mr)) as unread_messages
";
$platformStatsResult = $conn->query($platformStatsQuery);
$platformStats = $platformStatsResult->fetch_assoc();

// ✅ Daily Active Users
$dauQuery = "
    SELECT COUNT(DISTINCT sender_id) as dau
    FROM messages
    WHERE DATE(created_at) = CURDATE() AND is_deleted = 0
";
$dauResult = $conn->query($dauQuery);
$dau = $dauResult->fetch_assoc()['dau'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | BISureChat</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
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

        .time-period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .time-period-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background: #fff;
            color: #4A5568;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .time-period-btn:hover { background: #f7fafc; border-color: #128C7E; color: #128C7E; }
        .time-period-btn.active { background: #128C7E; color: #fff; border-color: #128C7E; }

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
        .stat-card .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.08;
            color: #128C7E;
        }
        .stat-title { font-size: 0.8rem; color: #718096; text-transform: uppercase; margin-bottom: 8px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #2D3748; margin-bottom: 4px; }
        .stat-change { font-size: 0.8rem; color: #25D366; }
        .stat-change.danger { color: #E74C3C; }

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

        .top-users-list { list-style: none; padding: 1rem; }
        .top-users-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .top-users-list li:last-child { border-bottom: none; }
        .user-info { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar-sm {
            width: 36px; height: 36px; border-radius: 50%;
            background: #128C7E; color: #fff; display: flex;
            align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.85rem;
        }
        .user-stats { display: flex; gap: 1.5rem; }
        .user-stat { text-align: center; }
        .user-stat-value { font-weight: 600; font-size: 1rem; }
        .user-stat-label { font-size: 0.7rem; color: #A0AEC0; }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.3; }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 60px;
            }
            .charts-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
            <h1 class="page-title">Analytics Dashboard</h1>
            <span style="color:#718096;"><?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?></span>
        </div>

        <!-- Time Period Selector -->
        <div class="time-period-selector">
            <a href="?period=7" class="time-period-btn <?= $timePeriod === 7 ? 'active' : '' ?>">7 Days</a>
            <a href="?period=30" class="time-period-btn <?= $timePeriod === 30 ? 'active' : '' ?>">30 Days</a>
            <a href="?period=90" class="time-period-btn <?= $timePeriod === 90 ? 'active' : '' ?>">90 Days</a>
            <a href="?period=180" class="time-period-btn <?= $timePeriod === 180 ? 'active' : '' ?>">6 Months</a>
            <a href="?period=365" class="time-period-btn <?= $timePeriod === 365 ? 'active' : '' ?>">1 Year</a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?= number_format($platformStats['total_users']) ?></div>
                <div class="stat-change"><i class="fas fa-arrow-up mr-1"></i> <?= array_sum($userGrowthCounts) ?> new</div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Messages</div>
                <div class="stat-value"><?= number_format($platformStats['total_messages']) ?></div>
                <div class="stat-change"><i class="fas fa-arrow-up mr-1"></i> <?= array_sum($messageActivityCounts) ?> in period</div>
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Files Shared</div>
                <div class="stat-value"><?= number_format($platformStats['total_files']) ?></div>
                <div class="stat-change"><i class="fas fa-exchange-alt mr-1"></i> <?= array_sum($fileMessageCounts) ?> in period</div>
                <div class="stat-icon"><i class="fas fa-file-upload"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Unread Messages</div>
                <div class="stat-value"><?= number_format($platformStats['unread_messages']) ?></div>
                <div class="stat-change <?= $platformStats['unread_messages'] > 0 ? 'danger' : '' ?>">
                    <?php if ($platformStats['unread_messages'] > 0): ?>
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
                <div class="card-header"><h2 class="card-title">User Growth</h2></div>
                <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Message Activity</h2></div>
                <div class="chart-container"><canvas id="messageActivityChart"></canvas></div>
            </div>
        </div>

        <!-- Engagement & Top Users -->
        <div class="charts-row">
            <div class="card">
                <div class="card-header"><h2 class="card-title">User Engagement</h2></div>
                <div style="padding: 1rem;">
                    <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="stat-card">
                            <div class="stat-title">Active Senders</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= number_format($engagementStats['active_senders']) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Daily Active</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= number_format($dau) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Total Messages</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= number_format($engagementStats['total_messages']) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">Edited</div>
                            <div class="stat-value" style="font-size:1.4rem;"><?= number_format($engagementStats['edited_messages']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Top Active Users</h2></div>
                <?php if ($topUsersResult->num_rows > 0): ?>
                    <ul class="top-users-list">
                        <?php $rank = 1; while ($user = $topUsersResult->fetch_assoc()): ?>
                            <li>
                                <div class="user-info">
                                    <div class="user-avatar-sm"><?= strtoupper(substr($user['fullname'] ?? $user['username'] ?? 'U', 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:500;"><?= htmlspecialchars($user['fullname'] ?? $user['username']) ?></div>
                                        <div style="font-size:0.75rem;color:#718096;">@<?= htmlspecialchars($user['username']) ?></div>
                                    </div>
                                </div>
                                <div class="user-stats">
                                    <div class="user-stat">
                                        <span class="user-stat-value"><?= $user['message_count'] ?></span>
                                        <span class="user-stat-label">Messages</span>
                                    </div>
                                    <div class="user-stat">
                                        <span style="color:#128C7E;font-weight:600;">#<?= $rank++ ?></span>
                                        <span class="user-stat-label">Rank</span>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>No active users</h3>
                        <p>No message activity in the selected period</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // User Growth Chart
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($userGrowthLabels) ?>,
                datasets: [
                    {
                        label: 'New Users',
                        data: <?= json_encode($userGrowthCounts) ?>,
                        borderColor: '#128C7E',
                        backgroundColor: 'rgba(18,140,126,0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Admins',
                        data: <?= json_encode($adminCounts) ?>,
                        borderColor: '#F39C12',
                        backgroundColor: 'rgba(243,156,18,0.1)',
                        tension: 0.3,
                        fill: true,
                        hidden: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Message Activity Chart
        new Chart(document.getElementById('messageActivityChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($messageActivityLabels) ?>,
                datasets: [
                    {
                        label: 'Messages',
                        data: <?= json_encode($messageActivityCounts) ?>,
                        backgroundColor: 'rgba(18,140,126,0.7)',
                        borderColor: '#128C7E',
                        borderWidth: 1
                    },
                    {
                        label: 'Files',
                        data: <?= json_encode($fileMessageCounts) ?>,
                        backgroundColor: 'rgba(37,211,102,0.7)',
                        borderColor: '#25D366',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>