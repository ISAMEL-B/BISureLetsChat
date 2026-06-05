<?php

// define("MY_URL", "https://bisurechat.22web.org");

// Set PHP timezone to Uganda
date_default_timezone_set('Africa/Kampala');

// Database credentials
$host = 'sql113.byetcluster.com';
$user = 'b13_39257326';
$pass = 'Bi0757003628';
$db   = 'b13_39257326_bisurechat_db';

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    // Set MySQL session timezone to East Africa Time
    $conn->query("SET time_zone = '+03:00';");
    ?>
