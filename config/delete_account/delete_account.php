<?php
session_start(); // Start the session to use session variables

// Include your database configuration
include '../config/db.php'; 

// Check if the user is logged in (optional)
if (!isset($_SESSION['user_id'])) {
    // Display the message in a card format instead of terminating the script
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Required</title>
        <link rel="stylesheet" href="css/styles.css"> <!-- Include your CSS file -->
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 50px;
                text-align: center;
            }
            .card {
                background: white;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                width: 400px;
                margin: auto;
            }
            a {
                color: #e74c3c;
                text-decoration: none;
                font-weight: bold;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Access Denied</h2>
            <p>You must be logged in to delete your account.</p>
            <p><a href="../register.php">Click here to login first</a></p>
        </div>
    </body>
    </html>';
    exit; // End script execution
}

// Initialize variables
$errorMessage = '';
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input values
    $identifier = trim($_POST['identifier']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($identifier) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: home.php');
        exit;
    } else {
        // Check if the identifier is an email or phone number
        $query = "SELECT * FROM offices WHERE (email = ? OR phone_number = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Invalid email or phone number.';
            header('Location: home.php');
            exit;
        } else {
            // Fetch user data
            $user = $result->fetch_assoc();

            // Verify password
            if (!password_verify($password, $user['password'])) {
                $_SESSION['error'] = 'Invalid password.';
                header('Location: home.php');
                exit;
            } else {
                // Delete account
                $deleteQuery = "DELETE FROM offices WHERE office_id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $user['office_id']);
                if ($deleteStmt->execute()) {
                    $_SESSION['success'] = 'Account deleted successfully!';
                    session_destroy(); // Optionally log the user out
                    header('Location: ../register.php'); // Redirect to a different page
                    exit; // End script execution after success
                } else {
                    $_SESSION['error'] = 'Failed to delete account.';
                    header('Location: home.php');
                    exit;
                }
            }
        }
        $stmt->close();
    }
}

$conn->close(); // Close the database connection
?>
