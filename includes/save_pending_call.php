<?php
session_start();
$_SESSION['pending_call'] = [
    'from' => $_POST['from'] ?? '',
    'fromName' => $_POST['fromName'] ?? 'User',
    'fromPicture' => $_POST['fromPicture'] ?? '',
    'isVideo' => ($_POST['isVideo'] ?? '0') === '1',
    'callId' => $_POST['callId'] ?? null
];
echo 'ok';