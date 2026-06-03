<?php
require '../../register/config/db.php'; // make sure this defines $conn as mysqli connection

header('Content-Type: application/json');

// Query last 7 days connected logs
$query = "
    SELECT DATE(timestamp) as log_date, COUNT(*) as total 
    FROM server_logs 
    WHERE action = 'connected' AND timestamp >= DATE(NOW() - INTERVAL 6 DAY)
    GROUP BY log_date 
    ORDER BY log_date ASC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$results = [];
while ($row = mysqli_fetch_assoc($result)) {
    $results[] = $row;
}
mysqli_stmt_close($stmt);

// Initialize days (in case some days have no connections)
$labels = [];
$data = [];

$today = new DateTime();
$interval = new DateInterval('P1D');
$period = new DatePeriod($today->modify('-6 days'), $interval, 7);

$map = [];
foreach ($results as $row) {
    $map[$row['log_date']] = $row['total'];
}

foreach ($period as $date) {
    $d = $date->format('Y-m-d');
    $labels[] = $date->format('D (j M)'); // e.g. Mon (1 Jul)
    $data[] = isset($map[$d]) ? (int)$map[$d] : 0;
}

echo json_encode(['labels' => $labels, 'data' => $data]);
