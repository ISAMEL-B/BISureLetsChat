<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access | BISureChat Admin</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../favicon.png" type="image/x-icon">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e8b57;
            --primary-dark: #1f6b3d;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --text: #333;
            --text-muted: #6c757d;
            --radius-md: 8px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .unauthorized-container {
            max-width: 500px;
            width: 90%;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .unauthorized-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            position: relative;
        }
        
        .unauthorized-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .unauthorized-body {
            padding: 2rem;
        }
        
        h1 {
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .premium-feature {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
        }
        
        .premium-feature h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 576px) {
            .unauthorized-header {
                padding: 1.5rem;
            }
            
            .unauthorized-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="unauthorized-container">
        <div class="unauthorized-header">
            <i class="fas fa-shield-alt"></i>
            <h1>Access Restricted</h1>
        </div>
        <div class="unauthorized-body">
            <p>You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
            
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
            
            <div class="premium-feature">
                <h3><i class="fas fa-crown"></i> Premium Feature</h3>
                <p>This feature requires administrator privileges. Upgrade your account or contact support for access.</p>
            </div>
        </div>
    </div>
</body>

</html>