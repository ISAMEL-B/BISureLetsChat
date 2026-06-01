<?php
//==============================
// PHP SESSION AND CONFIGURATION START
// Starts session, sets error reporting, and includes database configuration
//==============================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include $_SERVER['DOCUMENT_ROOT'] . '/register/config/db.php';
include $_SERVER['DOCUMENT_ROOT'] . '/register/security/check.php';
// Get current user ID from session
$current_office_id = $_SESSION['user_id'];

// SQL query to fetch contacts excluding the current user
$sql = "
SELECT o.office_id, o.username, phone_number, email, profile_picture
FROM offices o
WHERE o.office_id != ?
ORDER BY username ASC
";

// Prepare and execute the SQL statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_office_id);
$stmt->execute();
$result = $stmt->get_result();
//==============================
// PHP SESSION AND CONFIGURATION END
//==============================
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BISure | Contacts</title>
    <link rel="icon" href="../../../favicon.png" type="image/x-icon">
    <!-- External CSS and icon libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/call_css.css">
    <style>
        .floating-history-btn {
            position: fixed;
            bottom: 10%;
            right: 20px;
            background-color: #28a745; /* Green color */
            color: #fff;
            border: none;
            outline: none;
            padding: 15px 22px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .floating-history-btn:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        .floating-history-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- Header section with navigation and title -->
        <header>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/sharing/chat/cd_hamburger.php'; ?>
            <div class="header-left">
                <a href="#" class="back-button"><i class="fas fa-arrow-right"></i></a>
                <h1 class="header-title">Contacts <span class="pro-badge">PRO</span></h1>
            </div>
        </header>

        <!-- Search container for filtering contacts -->
        <div class="search-container">
            <div class="input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search contacts..." />
            </div>
        </div>

        <!-- Contacts list populated from database -->
        <div class="contacts-list" id="contactsList">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="contact-item" data-user-id="<?php echo $row['office_id']; ?>" data-user-name="<?php echo htmlspecialchars($row['username']); ?>">
                        <div class="profile-picture">
                            <?php if (!empty($row['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="<?php echo htmlspecialchars($row['username']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                            <?php endif; ?>
                            <div class="contact-status"></div>
                        </div>
                        <div class="contact-details">
                            <div class="contact-name"><?php echo htmlspecialchars($row['username']); ?></div>
                            <div class="contact-info"><?php echo htmlspecialchars($row['phone_number']); ?></div>
                        </div>
                        <div class="contact-actions">
                            <div class="call-btn" title="Voice Call"><i class="fas fa-phone"></i></div>
                            <div class="video-call-btn" title="Video Call"><i class="fas fa-video"></i></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Empty state when no contacts are found -->
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No contacts found</h3>
                    <p>Your contacts will appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active call interface (hidden by default) -->
    <div class="call-interface" id="callInterface">
        <div class="call-header">
            <div id="callContactName">Isamel Admin</div>
            <div id="callStatus">Calling...</div>
            <div id="callTimer">00:00</div>
        </div>
        <div class="call-video-container">
            <video id="remoteVideo" autoplay playsinline></video>
            <div class="local-video">
                <video id="localVideo" autoplay muted playsinline></video>
            </div>
        </div>
        <div class="call-controls">
            <button id="btnMute" class="call-control-btn call-mute" title="Mute"><i class="fas fa-microphone"></i></button>
            <button id="btnVideoToggle" class="call-control-btn call-video-toggle" title="Toggle Video"><i class="fas fa-video"></i></button>
            <button id="btnEnd" class="call-control-btn call-end" title="End Call"><i class="fas fa-phone"></i></button>
        </div>
    </div>

    <!-- Incoming call interface (hidden by default) -->
    <div class="incoming-call" id="incomingCall">
        <div class="pro-feature-badge">PRO</div>
        <div class="incoming-call-content">
            <div class="caller-info">
                <div class="caller-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div id="incomingContactName">Praise M</div>
                <p>Incoming Call</p>
            </div>

            <div class="incoming-call-buttons">
                <div class="call-button decline-call" id="btnDecline" title="Decline">
                    <i class="fas fa-phone-slash"></i>
                </div>
                <div class="call-button accept-call" id="btnAccept" title="Accept">
                    <i class="fas fa-phone"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating History Button -->
    <button id="historyBtn" class="floating-history-btn">History</button>
<?php include '../navbar/navbar.php'; ?>
    <script>
        document.getElementById("historyBtn").addEventListener("click", function() {
            // Redirect to history page
            window.location.href = "call_history.php";
        });
    </script>

    <!-- Pass PHP variable to JavaScript -->
    <script>
        window.SELF_ID = <?php echo json_encode($current_office_id); ?>;
        window.SELF_NAME = <?php echo json_encode($_SESSION['username'] ?? ''); ?>;
    </script>
    
    <!-- Main application JavaScript -->
    <script src="call_app.js"></script>
</body>

</html>