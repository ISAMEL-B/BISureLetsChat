<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=bisurechat_db','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$action = $_GET['action'] ?? '';

$sql = "SELECT id,user_id,action,status,ip_address,user_agent,details,DATE_FORMAT(timestamp,'%Y-%m-%d %H:%i:%s') AS timestamp
        FROM server_logs WHERE 1";
$params = [];

if($start && $end){
  $sql .= " AND DATE(timestamp) BETWEEN ? AND ?";
  $params[] = $start; $params[] = $end;
}
if($action){
  $sql .= " AND action = ?";
  $params[] = $action;
}

$sql .= " ORDER BY timestamp DESC LIMIT 1000";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status'=>'success','logs'=>$logs]);
