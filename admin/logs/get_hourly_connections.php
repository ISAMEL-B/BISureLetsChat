<?php
// get_hourly_connections.php
include '../../register/config/db.php'; // Make sure this defines $conn as MySQLi connection

header('Content-Type: application/json');

$labels = [];
$connected = array_fill(0, 24, 0);
$disconnected = array_fill(0, 24, 0);

// Define 6 AM today to 5 AM tomorrow (or now if before 6 AM)
$start = new DateTime('today 06:00:00');
$end = new DateTime('tomorrow 06:00:00');
if ((int)date('H') < 6) {
    $start->modify('-1 day');
    $end->modify('-1 day');
}

$startStr = $start->format('Y-m-d H:i:s');
$endStr = $end->format('Y-m-d H:i:s');

// Prepare and execute query for connected
$sqlConnected = "
    SELECT HOUR(timestamp) AS hr, COUNT(*) AS count
    FROM server_logs
    WHERE action = 'connected' AND timestamp BETWEEN ? AND ?
    GROUP BY HOUR(timestamp)
";

$stmtConnected = mysqli_prepare($conn, $sqlConnected);
mysqli_stmt_bind_param($stmtConnected, 'ss', $startStr, $endStr);
mysqli_stmt_execute($stmtConnected);
$resultConnected = mysqli_stmt_get_result($stmtConnected);

while ($row = mysqli_fetch_assoc($resultConnected)) {
    $hour = (int)$row['hr'];
    $connected[$hour] = (int)$row['count'];
}
mysqli_stmt_close($stmtConnected);

// Prepare and execute query for disconnected
$sqlDisconnected = "
    SELECT HOUR(timestamp) AS hr, COUNT(*) AS count
    FROM server_logs
    WHERE action = 'disconnected' AND timestamp BETWEEN ? AND ?
    GROUP BY HOUR(timestamp)
";

$stmtDisconnected = mysqli_prepare($conn, $sqlDisconnected);
mysqli_stmt_bind_param($stmtDisconnected, 'ss', $startStr, $endStr);
mysqli_stmt_execute($stmtDisconnected);
$resultDisconnected = mysqli_stmt_get_result($stmtDisconnected);

while ($row = mysqli_fetch_assoc($resultDisconnected)) {
    $hour = (int)$row['hr'];
    $disconnected[$hour] = (int)$row['count'];
}
mysqli_stmt_close($stmtDisconnected);

// Build ordered labels and datasets from 6AM to 5AM next day
$orderedLabels = [];
$orderedConnected = [];
$orderedDisconnected = [];

for ($i = 6; $i < 30; $i++) {
    $h = $i % 24;
    $label = date('h A', strtotime("$h:00"));
    $orderedLabels[] = $label;
    $orderedConnected[] = $connected[$h] ?? 0;
    $orderedDisconnected[] = $disconnected[$h] ?? 0;
}

echo json_encode([
    'labels' => $orderedLabels,
    'connected' => $orderedConnected,
    'disconnected' => $orderedDisconnected
]);
