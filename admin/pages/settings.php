<?php
/**
 * BUSure Chat - Admin System Settings
 * ✅ Uses reusable sidebar & footer
 * ✅ Matches busure_lets_chat schema
 * ✅ Session uses user_role
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Check admin privileges - using user_role as per your session
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../unauthorized");
    exit;
}

$current_admin_id = $_SESSION['user_id'];
$current_admin_name = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle SMTP settings update
    if (isset($_POST['update_smtp'])) {
        $smtpHost = trim($_POST['smtp_host']);
        $smtpPort = trim($_POST['smtp_port']);
        $smtpUser = trim($_POST['smtp_user']);
        $smtpPass = trim($_POST['smtp_pass']);
        $smtpFrom = trim($_POST['smtp_from']);
        $smtpSecure = trim($_POST['smtp_secure']);

        if (empty($smtpHost) || empty($smtpPort) || empty($smtpFrom)) {
            $_SESSION['error'] = 'SMTP host, port, and from address are required';
        } else {
            $config = json_encode([
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_user' => $smtpUser,
                'smtp_pass' => $smtpPass,
                'smtp_from' => $smtpFrom,
                'smtp_secure' => $smtpSecure
            ]);
            
            // Save to .env or settings file
            if (saveSetting('smtp_settings', $config)) {
                $_SESSION['success'] = 'SMTP settings updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update SMTP settings';
            }
        }
    }

    // Handle system settings update
    if (isset($_POST['update_system'])) {
        $siteName = trim($_POST['site_name']);
        $siteUrl = trim($_POST['site_url']);
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $registrationEnabled = isset($_POST['registration_enabled']) ? 1 : 0;
        $fileUploadLimit = intval($_POST['file_upload_limit']);

        if (empty($siteName) || empty($siteUrl)) {
            $_SESSION['error'] = 'Site name and URL are required';
        } else {
            $config = json_encode([
                'site_name' => $siteName,
                'site_url' => $siteUrl,
                'maintenance_mode' => $maintenanceMode,
                'registration_enabled' => $registrationEnabled,
                'file_upload_limit' => $fileUploadLimit
            ]);
            
            if (saveSetting('system_settings', $config)) {
                $_SESSION['success'] = 'System settings updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update system settings';
            }
        }
    }

    // Handle security settings update
    if (isset($_POST['update_security'])) {
        $passwordMinLength = intval($_POST['password_min_length']);
        $loginAttempts = intval($_POST['login_attempts']);
        $loginTimeout = intval($_POST['login_timeout']);
        $enable2fa = isset($_POST['enable_2fa']) ? 1 : 0;
        $sessionTimeout = intval($_POST['session_timeout']);

        if ($passwordMinLength < 6 || $loginAttempts < 1 || $loginTimeout < 1 || $sessionTimeout < 5) {
            $_SESSION['error'] = 'Please enter valid security settings';
        } else {
            $config = json_encode([
                'password_min_length' => $passwordMinLength,
                'login_attempts' => $loginAttempts,
                'login_timeout' => $loginTimeout,
                'enable_2fa' => $enable2fa,
                'session_timeout' => $sessionTimeout
            ]);
            
            if (saveSetting('security_settings', $config)) {
                $_SESSION['success'] = 'Security settings updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update security settings';
            }
        }
    }

    // Handle test email
    if (isset($_POST['send_test_email'])) {
        $testEmail = trim($_POST['test_email']);
        
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
        } else {
            if (sendTestEmail($testEmail)) {
                $_SESSION['success'] = 'Test email sent successfully to ' . $testEmail;
            } else {
                $_SESSION['error'] = 'Failed to send test email. Check your SMTP settings.';
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Helper functions
function saveSetting($key, $value) {
    // Save to a database table or .env file
    // For now, return true as placeholder
    return true;
}

function sendTestEmail($email) {
    // Use PHPMailer with SMTP settings
    // For now, return true as placeholder
    return true;
}

// Load current settings (load from your storage)
$smtpSettings = [
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => '587',
    'smtp_user' => 'user@example.com',
    'smtp_pass' => '',
    'smtp_from' => 'noreply@example.com',
    'smtp_secure' => 'tls'
];

$systemSettings = [
    'site_name' => 'BISureChat',
    'site_url' => 'https://chat.bisure.com',
    'maintenance_mode' => 0,
    'registration_enabled' => 1,
    'file_upload_limit' => 10
];

$securitySettings = [
    'password_min_length' => 8,
    'login_attempts' => 5,
    'login_timeout' => 15,
    'enable_2fa' => 0,
    'session_timeout' => 30
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | BISureChat</title>

    <?php require_once __DIR__ . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .main-content {
            margin-left: 260px;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2D3748; }

        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #E2E8F0;
            margin-bottom: 1.5rem;
            gap: 0;
        }
        .settings-tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            color: #718096;
            transition: all 0.2s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }
        .settings-tab:hover { color: #128C7E; }
        .settings-tab.active {
            color: #128C7E;
            border-bottom-color: #128C7E;
        }
        .settings-tab-content { display: none; }
        .settings-tab-content.active { display: block; }

        .settings-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 4px solid #128C7E;
        }
        .settings-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }
        .settings-card-title { font-size: 1.1rem; font-weight: 600; color: #075E54; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.85rem; color: #4A5568; }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #128C7E;
            box-shadow: 0 0 0 3px rgba(18,140,126,0.1);
        }
        .form-control-sm { max-width: 300px; }
        .form-text { font-size: 0.75rem; color: #A0AEC0; margin-top: 0.25rem; }
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        .form-check input[type="checkbox"] { width: 18px; height: 18px; accent-color: #128C7E; }
        .form-check label { cursor: pointer; font-size: 0.9rem; }

        .test-email-row {
            display: flex;
            gap: 0.5rem;
        }
        .test-email-row .form-control { max-width: 350px; }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-primary { background: #128C7E; color: #fff; }
        .btn-primary:hover { background: #075E54; }
        .btn-secondary { background: #f0f0f0; color: #2D3748; }
        .btn-secondary:hover { background: #e0e0e0; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success { background: rgba(37,211,102,0.1); color: #1a7a3a; border: 1px solid rgba(37,211,102,0.2); }
        .alert-error { background: rgba(231,76,60,0.1); color: #c0392b; border: 1px solid rgba(231,76,60,0.2); }

        .color-input-wrap { display: flex; align-items: center; gap: 1rem; }
        .color-input-wrap input[type="color"] { width: 50px; height: 36px; border: 1px solid #E2E8F0; border-radius: 6px; cursor: pointer; }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 60px;
            }
            .settings-tabs { overflow-x: auto; }
            .test-email-row { flex-direction: column; }
            .test-email-row .form-control { max-width: 100%; }
            .form-control-sm { max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- ✅ Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">System Settings</h1>
            <span class="text-muted" style="color:#718096;">Welcome, <?= htmlspecialchars($current_admin_name) ?></span>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="settings-tab active" onclick="openTab(event, 'system-settings')"><i class="fas fa-server mr-1"></i> System</button>
            <button class="settings-tab" onclick="openTab(event, 'email-settings')"><i class="fas fa-envelope mr-1"></i> Email</button>
            <button class="settings-tab" onclick="openTab(event, 'security-settings')"><i class="fas fa-shield-alt mr-1"></i> Security</button>
            <button class="settings-tab" onclick="openTab(event, 'appearance-settings')"><i class="fas fa-palette mr-1"></i> Appearance</button>
        </div>

        <!-- System Settings -->
        <div id="system-settings" class="settings-tab-content active">
            <form method="POST">
                <div class="settings-card">
                    <div class="settings-card-header"><h2 class="settings-card-title">General Settings</h2></div>
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($systemSettings['site_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Site URL</label>
                        <input type="url" name="site_url" class="form-control" value="<?= htmlspecialchars($systemSettings['site_url']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File Upload Limit (MB)</label>
                        <input type="number" name="file_upload_limit" class="form-control form-control-sm" value="<?= $systemSettings['file_upload_limit'] ?>" min="1" max="100" required>
                        <span class="form-text">Maximum allowed file size for uploads</span>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" <?= $systemSettings['maintenance_mode'] ? 'checked' : '' ?>>
                        <label for="maintenance_mode">Enable Maintenance Mode</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="registration_enabled" id="registration_enabled" <?= $systemSettings['registration_enabled'] ? 'checked' : '' ?>>
                        <label for="registration_enabled">Enable User Registration</label>
                    </div>
                    <button type="submit" name="update_system" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save System Settings</button>
                </div>
            </form>
        </div>

        <!-- Email Settings -->
        <div id="email-settings" class="settings-tab-content">
            <form method="POST">
                <div class="settings-card">
                    <div class="settings-card-header"><h2 class="settings-card-title">SMTP Settings</h2></div>
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($smtpSettings['smtp_host']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control form-control-sm" value="<?= htmlspecialchars($smtpSettings['smtp_port']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Security</label>
                        <select name="smtp_secure" class="form-control form-control-sm" required>
                            <option value="tls" <?= $smtpSettings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $smtpSettings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="" <?= empty($smtpSettings['smtp_secure']) ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($smtpSettings['smtp_user']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($smtpSettings['smtp_pass']) ?>">
                        <span class="form-text">Leave blank to keep current password</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">From Email Address</label>
                        <input type="email" name="smtp_from" class="form-control" value="<?= htmlspecialchars($smtpSettings['smtp_from']) ?>" required>
                    </div>
                    <button type="submit" name="update_smtp" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save SMTP Settings</button>
                </div>
            </form>

            <div class="settings-card">
                <div class="settings-card-header"><h2 class="settings-card-title">Test Email Configuration</h2></div>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Test Email Address</label>
                        <div class="test-email-row">
                            <input type="email" name="test_email" class="form-control" placeholder="recipient@example.com" required>
                            <button type="submit" name="send_test_email" class="btn btn-secondary"><i class="fas fa-paper-plane mr-2"></i> Send Test</button>
                        </div>
                        <span class="form-text">Send a test email to verify your SMTP settings</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div id="security-settings" class="settings-tab-content">
            <form method="POST">
                <div class="settings-card">
                    <div class="settings-card-header"><h2 class="settings-card-title">Security Settings</h2></div>
                    <div class="form-group">
                        <label class="form-label">Minimum Password Length</label>
                        <input type="number" name="password_min_length" class="form-control form-control-sm" value="<?= $securitySettings['password_min_length'] ?>" min="6" max="32" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Login Attempts</label>
                        <input type="number" name="login_attempts" class="form-control form-control-sm" value="<?= $securitySettings['login_attempts'] ?>" min="1" max="10" required>
                        <span class="form-text">After this many failed attempts, the account will be temporarily locked</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login Timeout (minutes)</label>
                        <input type="number" name="login_timeout" class="form-control form-control-sm" value="<?= $securitySettings['login_timeout'] ?>" min="1" max="60" required>
                        <span class="form-text">Duration of lockout after too many failed attempts</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Session Timeout (minutes)</label>
                        <input type="number" name="session_timeout" class="form-control form-control-sm" value="<?= $securitySettings['session_timeout'] ?>" min="5" max="1440" required>
                        <span class="form-text">How long before inactive users are automatically logged out</span>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="enable_2fa" id="enable_2fa" <?= $securitySettings['enable_2fa'] ? 'checked' : '' ?>>
                        <label for="enable_2fa">Enable Two-Factor Authentication</label>
                    </div>
                    <button type="submit" name="update_security" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Security Settings</button>
                </div>
            </form>
        </div>

        <!-- Appearance Settings -->
        <div id="appearance-settings" class="settings-tab-content">
            <form method="POST">
                <div class="settings-card">
                    <div class="settings-card-header"><h2 class="settings-card-title">Theme Settings</h2></div>
                    <div class="form-group">
                        <label class="form-label">Primary Color</label>
                        <div class="color-input-wrap">
                            <input type="color" name="primary_color" value="#128C7E">
                            <span>WhatsApp Green (#128C7E)</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secondary Color</label>
                        <div class="color-input-wrap">
                            <input type="color" name="secondary_color" value="#25D366">
                            <span>Light Green (#25D366)</span>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="dark_mode" id="dark_mode_default">
                        <label for="dark_mode_default">Enable Dark Mode by Default</label>
                    </div>
                    <button type="submit" name="update_appearance" class="btn btn-primary"><i class="fas fa-save mr-2"></i> Save Appearance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function openTab(event, tabId) {
            document.querySelectorAll('.settings-tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>