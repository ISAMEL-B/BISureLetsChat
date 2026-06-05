<?php require_once __DIR__ . 'delete_account.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Delete Account | BisureChat</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/install_pwa_head_tags.php'; ?>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --da-primary: #128C7E; --da-primary-dark: #075E54; --da-secondary: #25D366;
            --da-bg: #e5ddd5; --da-card: #ffffff; --da-text: #2D3748;
            --da-text-secondary: #718096; --da-border: #E2E8F0;
            --da-shadow: 0 2px 10px rgba(0,0,0,.06); --da-danger: #E74C3C;
            --da-danger-dark: #C0392B; --da-warning: #F39C12; --da-input-bg: #f9f9f9;
            --nav-height: 60px;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:var(--da-bg);color:var(--da-text);min-height:100vh;transition:background .3s,color .3s}
        .da-header{background:linear-gradient(135deg,var(--da-primary-dark),var(--da-primary));color:#FFF;padding:18px 24px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.1);min-height:60px}
        .da-header-title{font-size:1.35rem;font-weight:600;flex:1;text-align:center}
        .da-container{max-width:800px;margin:0 auto;background:var(--da-card);min-height:calc(100vh - 60px);box-shadow:var(--da-shadow);padding-bottom:var(--nav-height)}
        .da-content{padding:2rem 1.5rem;display:flex;flex-direction:column;align-items:center}
        .da-form-card{width:100%;max-width:480px;background:var(--da-card);border-radius:16px;padding:2rem;box-shadow:var(--da-shadow);border:1px solid var(--da-border)}
        .da-form-icon{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#e74c3c20,#c0392b20);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem}
        .da-form-icon i{font-size:2rem;color:var(--da-danger)}
        .da-form-title{font-size:1.4rem;font-weight:600;text-align:center;margin-bottom:.5rem}
        .da-form-subtitle{font-size:.85rem;color:var(--da-text-secondary);text-align:center;margin-bottom:1.5rem}
        .da-warning-box{background:#FFF8E6;border-left:4px solid var(--da-warning);padding:1rem;margin-bottom:1.5rem;border-radius:8px;display:flex;gap:.8rem}
        .da-warning-box i{color:var(--da-warning);font-size:1.2rem;margin-top:2px}
        .da-warning-box p{font-size:.85rem;color:#856404}
        .da-form-group{margin-bottom:1.25rem}
        .da-form-label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.4rem}
        .da-input{width:100%;padding:12px 14px;border:2px solid var(--da-border);border-radius:12px;font-size:.9rem;font-family:'Poppins',sans-serif;background:var(--da-input-bg);color:var(--da-text);outline:none;transition:all .3s}
        .da-input:focus{border-color:var(--da-primary);box-shadow:0 0 0 3px rgba(18,140,126,.1)}
        .da-checkbox-group{display:flex;align-items:flex-start;gap:.8rem;margin:1.5rem 0;padding:1rem;background:#fdf2f2;border-radius:10px;border:1px solid #f5c6cb}
        .da-checkbox-group input[type="checkbox"]{margin-top:.15rem;width:18px;height:18px;accent-color:var(--da-danger);cursor:pointer}
        .da-checkbox-label{font-size:.8rem;color:#721c24;line-height:1.5;cursor:pointer}
        .da-btn{width:100%;padding:14px;border:none;border-radius:12px;font-size:.95rem;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:all .3s}
        .da-btn-delete{background:var(--da-danger);color:white;box-shadow:0 4px 15px rgba(231,76,60,.3)}
        .da-btn-delete:hover{background:var(--da-danger-dark);transform:translateY(-2px)}
        .da-btn-delete:disabled{opacity:.6;cursor:not-allowed;transform:none}
        .da-btn-cancel{background:transparent;color:var(--da-text-secondary);border:2px solid var(--da-border);margin-top:.75rem;text-decoration:none;text-align:center}
        .da-btn-cancel:hover{border-color:var(--da-primary);color:var(--da-primary)}
        .da-alert{display:none;padding:.85rem 1rem;border-radius:10px;margin-bottom:1.25rem;font-size:.85rem;align-items:center;gap:.6rem}
        .da-alert-error{background:#FDECEA;color:#C0392B;border-left:4px solid #E74C3C}
        .da-alert-success{background:#E8F5E9;color:#1B5E20;border-left:4px solid #25D366}
        /* Modal */
        .modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center}
        .modal-overlay.show{display:flex}
        .modal-box{background:var(--da-card);border-radius:20px;padding:2.5rem 2rem;text-align:center;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.4);animation:popIn .3s ease}
        @keyframes popIn{from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}
        .modal-icon{font-size:5rem;margin-bottom:1rem}
        .modal-icon.success{color:var(--da-secondary)}
        .modal-icon.error{color:var(--da-danger)}
        .modal-title{font-size:1.4rem;font-weight:700;margin-bottom:.5rem;color:var(--da-text)}
        .modal-text{font-size:.9rem;color:var(--da-text-secondary);margin-bottom:1.5rem;line-height:1.6}
        .modal-btn{padding:14px 40px;border:none;border-radius:30px;font-size:1rem;font-weight:600;cursor:pointer;background:linear-gradient(135deg,var(--da-primary),var(--da-secondary));color:white;transition:all .3s}
        .modal-btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,211,102,.3)}
        /* Spinner */
        .spinner-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:1001;align-items:center;justify-content:center;flex-direction:column;gap:1.2rem}
        .spinner-overlay.show{display:flex}
        .spinner-circle{width:55px;height:55px;border:4px solid rgba(255,255,255,.25);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        .spinner-label{color:white;font-size:1rem;font-weight:500}
        /* Dark */
        body.dark-mode{--da-bg:#0B141A;--da-card:#1F2C33;--da-text:#E9EDEF;--da-text-secondary:#8696A0;--da-border:#2A3942;--da-input-bg:#2A3942;background:var(--da-bg)}
        body.dark-mode .da-warning-box{background:#2A1A00}
        body.dark-mode .da-warning-box p{color:#F39C12}
        body.dark-mode .da-checkbox-group{background:#2A1A1A;border-color:#5C1A1A}
        body.dark-mode .da-checkbox-label{color:#F5C6CB}
        body.dark-mode .modal-box{background:#1F2C33}
        @media(max-width:480px){.da-header{padding:14px 16px;min-height:54px}.da-header-title{font-size:1.15rem}.da-content{padding:1.5rem 1rem}.da-form-card{padding:1.5rem}.modal-box{padding:2rem 1.5rem}}
    </style>
</head>
<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<!-- Header -->
<div class="da-header">
    <div style="flex-shrink:0;width:40px;">
        <?php require_once __DIR__ . '/../../includes/cd_hamburger.php'; ?>
    </div>
    <div class="da-header-title">Delete Account</div>
    <div style="flex-shrink:0;width:40px;"></div>
</div>

<!-- Spinner -->
<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner-circle"></div>
    <div class="spinner-label">Deleting your account...</div>
</div>

<!-- Result Modal -->
<div class="modal-overlay" id="resultModal">
    <div class="modal-box">
        <div class="modal-icon" id="modalIcon"></div>
        <div class="modal-title" id="modalTitle"></div>
        <div class="modal-text" id="modalText"></div>
        <button class="modal-btn" id="modalBtn">Continue</button>
    </div>
</div>

<!-- Main -->
<div class="da-container">
    <div class="da-content">
        <div class="da-form-card">
            <div class="da-form-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2 class="da-form-title">Delete Your Account</h2>
            <p class="da-form-subtitle">This action is permanent and cannot be undone</p>

            <div class="da-warning-box">
                <i class="fas fa-shield-alt"></i>
                <p><strong>Warning:</strong> All your messages, contacts, groups, call history, and account data will be permanently deleted.</p>
            </div>

            <div class="da-alert da-alert-error" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText"></span>
            </div>

            <form id="daDeleteForm" novalidate>
                <div class="da-form-group">
                    <label class="da-form-label">Email or Username</label>
                    <input type="text" class="da-input" id="identifier" placeholder="Enter your email or username" required autocomplete="email">
                </div>
                <div class="da-form-group">
                    <label class="da-form-label">Password</label>
                    <input type="password" class="da-input" id="password" placeholder="Enter your password" required minlength="6" autocomplete="current-password">
                </div>
                <div class="da-checkbox-group">
                    <input type="checkbox" id="confirmDelete" required>
                    <label for="confirmDelete" class="da-checkbox-label">I understand this cannot be undone and all my data will be permanently deleted from BisureChat servers.</label>
                </div>
                <button type="submit" class="da-btn da-btn-delete" id="daDeleteBtn"><i class="fas fa-trash-alt"></i> Delete Account Permanently</button>
                <a href="../../settings/settings" class="da-btn da-btn-cancel"><i class="fas fa-arrow-left"></i> Cancel & Go Back</a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<script>
(function(){
    var form = document.getElementById('daDeleteForm');
    var deleteBtn = document.getElementById('daDeleteBtn');
    var spinner = document.getElementById('spinnerOverlay');
    var resultModal = document.getElementById('resultModal');
    var modalIcon = document.getElementById('modalIcon');
    var modalTitle = document.getElementById('modalTitle');
    var modalText = document.getElementById('modalText');
    var modalBtn = document.getElementById('modalBtn');
    var errorAlert = document.getElementById('errorAlert');
    var errorText = document.getElementById('errorText');
    var identifierInput = document.getElementById('identifier');
    var passwordInput = document.getElementById('password');
    var confirmCheckbox = document.getElementById('confirmDelete');

    function showError(msg) {
        errorText.textContent = msg;
        errorAlert.style.display = 'flex';
        setTimeout(function(){ errorAlert.style.display = 'none'; }, 4000);
    }

    function showResult(success, title, text, redirectUrl) {
        spinner.classList.remove('show');
        
        if (success) {
            modalIcon.className = 'modal-icon success';
            modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            modalTitle.textContent = title;
            modalText.textContent = text;
            modalBtn.textContent = 'Go to Register';
            modalBtn.onclick = function() { window.location.href = redirectUrl; };
            resultModal.classList.add('show');
            
            // Auto redirect after 2.5 seconds
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 2500);
        } else {
            modalIcon.className = 'modal-icon error';
            modalIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
            modalTitle.textContent = title;
            modalText.textContent = text;
            modalBtn.textContent = 'Close';
            modalBtn.onclick = function() { resultModal.classList.remove('show'); };
            resultModal.classList.add('show');
            deleteBtn.disabled = false;
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        errorAlert.style.display = 'none';

        var identifier = identifierInput.value.trim();
        var password = passwordInput.value;

        if (!identifier || identifier.length < 2) { showError('Please enter a valid email or username.'); return; }
        if (!password || password.length < 6) { showError('Password must be at least 6 characters.'); return; }
        if (!confirmCheckbox.checked) { showError('You must confirm you understand this is permanent.'); return; }

        // Show spinner
        spinner.classList.add('show');
        deleteBtn.disabled = true;

        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('identifier', identifier);
        fd.append('password', password);

        fetch('delete_account.php', { method: 'POST', body: fd })
        .then(function(r) { 
            if (!r.ok) throw new Error('Server error');
            return r.json(); 
        })
        .then(function(data) {
            if (data.success) {
                showResult(true, 'Account Deleted', data.message || 'Your account has been permanently deleted.', data.redirect || '../register.php?deleted=1');
            } else {
                spinner.classList.remove('show');
                deleteBtn.disabled = false;
                showError(data.message || 'Failed to delete account. Please try again.');
            }
        })
        .catch(function(err) {
            spinner.classList.remove('show');
            deleteBtn.disabled = false;
            // If deletion succeeded but network error (session destroyed), check if we should redirect
            showError('Processing... If your account was deleted, you will be redirected.');
            console.error('Error:', err);
            
            // Fallback: try redirecting after a delay anyway
            setTimeout(function() {
                window.location.href = '../register.php?deleted=1';
            }, 2000);
        });
    });

    // Close modal on outside click
    resultModal.addEventListener('click', function(e) {
        if (e.target === resultModal) resultModal.classList.remove('show');
    });
})();
</script>
</body>
</html>