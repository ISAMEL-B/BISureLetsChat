<?php 
require_once __DIR__ . '/../includes/auth_check.php';

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// This allows the current script to use the database
require_once __DIR__ . '/../config/db.php';
// This ensures the user is authenticated or has the correct permissions before accessing this page
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Terms and Policies - BISure Chat</title>
    <link rel="icon" href="../favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --tp-primary: #128C7E;
            --tp-primary-dark: #075E54;
            --tp-secondary: #25D366;
            --tp-text-light: #FFFFFF;
            --tp-text-dark: #3B4A54;
            --tp-text-secondary: #718096;
            --tp-accent: #34B7F1;
            --tp-shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.08);
            --tp-shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.12);
            --tp-transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --tp-card-bg: #ffffff;
            --tp-card-hover: rgba(18, 140, 126, 0.05);
            --tp-card-active: rgba(18, 140, 126, 0.1);
            --tp-body-bg: #e5ddd5;
        }

        .tp-reset, .tp-reset * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .tp-page {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--tp-body-bg);
            color: var(--tp-text-dark);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .tp-wrapper {
            width: 100%;
            max-width: 1000px;
            background: var(--tp-card-bg);
            min-height: 100vh;
            box-shadow: var(--tp-shadow-lg);
            position: relative;
            transition: background 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .tp-header {
            background: linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-dark) 100%);
            padding: 1.5rem;
            color: var(--tp-text-light);
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .tp-header-inner {
            position: relative;
        }

        .tp-header-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0.3rem 0;
        }

        .tp-header-subtitle {
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 0;
        }

        .tp-cards-outer {
            flex: 1;
            width: 100%;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-bottom: 2rem;
        }

        .tp-card {
            background-color: var(--tp-card-bg);
            border-radius: 12px;
            padding: 0;
            box-shadow: var(--tp-shadow-sm);
            transition: var(--tp-transition);
            border-left: 4px solid var(--tp-accent);
            overflow: hidden;
        }

        .tp-card:active {
            transform: scale(0.99);
        }

        .tp-card-top {
            padding: 1rem 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--tp-transition);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .tp-card-top:hover {
            background-color: var(--tp-card-hover);
        }

        .tp-card-top.tp-active {
            background-color: var(--tp-card-active);
        }

        .tp-card-top h2 {
            color: var(--tp-primary-dark);
            margin: 0;
            font-size: 1.15rem;
            font-weight: 600;
            transition: color 0.3s ease;
            flex: 1;
            padding-right: 10px;
        }

        .tp-card-arrow {
            transition: var(--tp-transition);
            color: var(--tp-primary);
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .tp-card-top.tp-active .tp-card-arrow {
            transform: rotate(180deg);
        }

        .tp-card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.3s ease;
            opacity: 0;
            padding: 0 1.25rem;
        }

        .tp-card-body.tp-open {
            max-height: 800px;
            opacity: 1;
            padding: 0 1.25rem 1.25rem;
        }

        .tp-card-body p {
            line-height: 1.7;
            text-align: left;
            color: var(--tp-text-dark);
            margin: 0;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .tp-link {
            color: var(--tp-accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--tp-transition);
        }

        .tp-link:hover {
            color: var(--tp-primary-dark);
            text-decoration: underline;
        }

        .tp-scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 48px;
            height: 48px;
            background: var(--tp-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: var(--tp-transition);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            border: none;
        }

        .tp-scroll-top.tp-visible {
            opacity: 1;
            visibility: visible;
        }

        .tp-scroll-top:hover {
            background: var(--tp-primary-dark);
            transform: translateY(-3px);
        }

        /* =============================================
           DARK MODE - using .dark-mode on body
           ============================================= */
        body.dark-mode .tp-page {
            --tp-body-bg: #0B141A;
            --tp-text-dark: #E9EDEF;
            --tp-text-secondary: #8696A0;
            --tp-shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.3);
            --tp-shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.4);
            --tp-card-bg: #1F2C33;
            --tp-card-hover: rgba(37, 211, 102, 0.06);
            --tp-card-active: rgba(37, 211, 102, 0.1);
        }

        body.dark-mode .tp-card {
            border-left-color: var(--tp-secondary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .tp-card-top h2 {
            color: var(--tp-secondary);
        }

        body.dark-mode .tp-card-arrow {
            color: var(--tp-secondary);
        }

        body.dark-mode .tp-card-body p {
            color: #BCC4C9;
        }

        body.dark-mode .tp-link {
            color: var(--tp-secondary);
        }

        body.dark-mode .tp-link:hover {
            color: #1ea84e;
        }

        body.dark-mode .tp-scroll-top {
            background: var(--tp-secondary);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        body.dark-mode .tp-scroll-top:hover {
            background: #1ea84e;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .tp-wrapper {
                max-width: 100%;
            }

            .tp-header {
                padding: 1.25rem 1rem;
            }

            .tp-header-title {
                font-size: 1.35rem;
            }

            .tp-cards-outer {
                padding: 0.75rem;
                gap: 0.6rem;
            }

            .tp-card-top {
                padding: 0.9rem 1rem;
            }

            .tp-card-top h2 {
                font-size: 1.05rem;
            }

            .tp-card-body {
                padding: 0 1rem;
            }

            .tp-card-body.tp-open {
                padding: 0 1rem 1rem;
            }

            .tp-card-body p {
                font-size: 0.85rem;
                line-height: 1.6;
            }

            .tp-scroll-top {
                bottom: 1.5rem;
                right: 1.25rem;
                width: 44px;
                height: 44px;
            }
        }

        @media (max-width: 400px) {
            .tp-header-title {
                font-size: 1.2rem;
            }

            .tp-header-subtitle {
                font-size: 0.75rem;
            }

            .tp-card-top h2 {
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<!-- Main page uses unique tp- prefixed classes -->
<div class="tp-page">
    <div class="tp-wrapper">
        <div class="tp-header">
            <div class="tp-header-inner">
                <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
                <h1 class="tp-header-title">BISure Chat Terms and Policies</h1>
                <p class="tp-header-subtitle">Please review our terms and policies carefully</p>
            </div>
        </div>

        <div class="tp-cards-outer">
            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Introduction</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>Welcome to BISure Chat! We are excited to provide you with a premium communication platform. By using our service, you agree to comply with and be bound by the following terms and policies. These terms constitute a legally binding agreement between you and BISure Chat regarding your use of the service. We reserve the right to modify these terms at any time, and your continued use constitutes acceptance of those changes. It's your responsibility to review these terms periodically for updates.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>User Accounts</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>To access BISure Chat's premium features, you must create an account with accurate information. You are solely responsible for maintaining account confidentiality and all activities under your account. We implement advanced security measures, but you must notify us immediately of any unauthorized access. Accounts may be deactivated through your profile settings. We reserve the right to refuse service or terminate accounts at our discretion.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Privacy Policy</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>Your privacy is paramount. Our comprehensive Privacy Policy details how we collect, use, and protect your data. We employ industry-standard encryption for all communications and store minimal necessary data. By using BISure Chat, you consent to our data practices. We never sell your information and only share data when legally required. You may request data access or deletion at any time through your account settings.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Acceptable Use</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>BISure Chat maintains a respectful community. Prohibited activities include harassment, hate speech, spamming, illegal content sharing, or any disruptive behavior. We employ AI and human moderation to enforce these standards. Violations may result in content removal, account suspension, or termination. Users may report concerns through our in-app reporting system for prompt review by our trust and safety team.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Termination</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>We reserve the right to suspend or terminate accounts for policy violations. Termination notifications include specific violation details and appeal instructions. Data retention follows our privacy policy after termination. For business accounts, designated administrators may manage user access. We maintain discretion in all enforcement actions to protect our community and service integrity.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Limitation of Liability</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>BISure Chat provides service "as is" without warranties. We're not liable for indirect, incidental, or consequential damages. Our maximum liability is limited to fees paid for services. Some jurisdictions don't allow liability limitations, so these terms apply to the fullest extent permitted by law. We continuously work to maintain service reliability and security through enterprise-grade infrastructure.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Changes to Terms</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>We may update these terms periodically. Significant changes will be communicated via email or in-app notifications, with continued use constituting acceptance. The "Last Updated" date reflects the most recent revision. Archived versions are available upon request. For business users, we provide additional notice for material changes affecting contractual obligations.</p>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-card-top" onclick="tpToggleCard(this)">
                    <h2>Contact Us</h2>
                    <i class="fas fa-chevron-down tp-card-arrow"></i>
                </div>
                <div class="tp-card-body">
                    <p>For questions or concerns, contact our support team at <a href="mailto:support@bisurechat.com" class="tp-link">support@bisurechat.com</a>. Business inquiries should be directed to <a href="mailto:enterprise@bisurechat.com" class="tp-link">enterprise@bisurechat.com</a>. We typically respond within 24 hours. For legal notices, please use our registered agent address available in the full legal terms. Your feedback helps us improve our services.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="tp-scroll-top" id="tpScrollTop" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<?php include '../footer/footer.php'; ?>

<script>
    // Back to top button
    var tpScrollTop = document.getElementById('tpScrollTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            tpScrollTop.classList.add('tp-visible');
        } else {
            tpScrollTop.classList.remove('tp-visible');
        }
    });
    
    tpScrollTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Card toggle function
    function tpToggleCard(header) {
        var body = header.nextElementSibling;
        
        header.classList.toggle('tp-active');
        body.classList.toggle('tp-open');
        
        // Accordion behavior (only one open at a time)
        if (body.classList.contains('tp-open')) {
            document.querySelectorAll('.tp-card-body').forEach(function(item) {
                if (item !== body && item.classList.contains('tp-open')) {
                    item.previousElementSibling.classList.remove('tp-active');
                    item.classList.remove('tp-open');
                }
            });
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        var firstTop = document.querySelector('.tp-card-top');
        var firstBody = document.querySelector('.tp-card-body');
        
        if (firstTop && firstBody) {
            firstTop.classList.add('tp-active');
            firstBody.classList.add('tp-open');
        }
        
        // Keyboard accessibility
        document.querySelectorAll('.tp-card-top').forEach(function(header) {
            header.setAttribute('tabindex', '0');
            header.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    tpToggleCard(header);
                }
            });
        });
    });
</script>
</body>
</html>