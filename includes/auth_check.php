<?php

session_start();
    
// Check if user is logged in
 if (
     !isset($_SESSION['user_id']) ||
     empty($_SESSION['user_id'])
 ) {
    header('Location: /bisurechat/auth/register');
    exit();
}

?>