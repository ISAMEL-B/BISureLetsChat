<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=bisurechat_db','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Count logs with action 'connected' in the last minute
$stmt = $pdo->prepare("
  SELECT DATE_FORMAT(timestamp, '%H:%i') AS minute, COUNT(*) AS count
  FROM server_logs
  WHERE action='connected' AND timestamp > NOW() - INTERVAL 1 HOUR
  GROUP BY minute
  ORDER BY minute DESC
  LIMIT 30
");
$stmt->execute();
$latest = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(!$latest) {
  echo json_encode(['minute'=>date('H:i'),'count'=>0]);
} else {
  $row = $latest[0];
  echo json_encode($row);
}
