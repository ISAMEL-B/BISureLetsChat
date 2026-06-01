<?php
$host = 'localhost'; 
$user = 'root';
$pass = '';
$db = 'bisure_lets_chat'; 

// Create a connection using mysqli
$conn = mysqli_connect($host, $user, $pass, $db);

// Check the connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
