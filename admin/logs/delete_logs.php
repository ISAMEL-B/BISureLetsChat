<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=bisurechat_db','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$days = intval($_POST['days'] ?? -1);
if($days < 0) return json_encode(['status'=>'error','message'=>'Invalid days']);

$stmt = $pdo->prepare("DELETE FROM server_logs WHERE timestamp < NOW() - INTERVAL ? DAY");
$stmt->execute([$days]);
$deleted = $stmt->rowCount();

echo json_encode(['status'=>'success','message'=>"Deleted {$deleted} logs older than {$days} day(s)."]);
