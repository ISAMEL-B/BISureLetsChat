<?php 

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// This allows the current script to use the database
require_once __DIR__ . '/../config/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>LetsChat Guide</title>
    <!-- PWA Meta Tags -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bisurechat/install_pwa_head_tags.php'; ?>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --tg-primary: #128C7E;
            --tg-primary-dark: #075E54;
            --tg-secondary: #25D366;
            --tg-accent: #34B7F1;
            --tg-text-light: #FFFFFF;
            --tg-text-dark: #3B4A54;
            --tg-text-secondary: #718096;
            --tg-shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.08);
            --tg-shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.12);
            --tg-transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --tg-card-bg: #ffffff;
            --tg-card-hover: rgba(18, 140, 126, 0.05);
            --tg-card-active: rgba(18, 140, 126, 0.1);
            --tg-body-bg: #e5ddd5;
        }

        .tg-reset, .tg-reset * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .tg-page {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--tg-body-bg);
            color: var(--tg-text-dark);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .tg-wrapper {
            width: 100%;
            max-width: 1000px;
            background: var(--tg-card-bg);
            min-height: 100vh;
            box-shadow: var(--tg-shadow-lg);
            position: relative;
            transition: background 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .tg-header {
            background: linear-gradient(135deg, var(--tg-primary) 0%, var(--tg-primary-dark) 100%);
            padding: 1.5rem;
            color: var(--tg-text-light);
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .tg-header-inner {
            position: relative;
        }

        .tg-header-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin: 0.3rem 0;
        }

        .tg-header-subtitle {
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 0;
        }

        .tg-cards-outer {
            flex: 1;
            width: 100%;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-bottom: 2rem;
        }

        .tg-card {
            background-color: var(--tg-card-bg);
            border-radius: 12px;
            padding: 0;
            box-shadow: var(--tg-shadow-sm);
            transition: var(--tg-transition);
            border-left: 4px solid var(--tg-accent);
            overflow: hidden;
        }

        .tg-card:active {
            transform: scale(0.99);
        }

        .tg-card-top {
            padding: 1rem 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--tg-transition);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .tg-card-top:hover {
            background-color: var(--tg-card-hover);
        }

        .tg-card-top.tg-active {
            background-color: var(--tg-card-active);
        }

        .tg-card-top h2 {
            color: var(--tg-primary-dark);
            margin: 0;
            font-size: 1.15rem;
            font-weight: 600;
            transition: color 0.3s ease;
            flex: 1;
            padding-right: 10px;
        }

        .tg-card-arrow {
            transition: var(--tg-transition);
            color: var(--tg-primary);
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .tg-card-top.tg-active .tg-card-arrow {
            transform: rotate(180deg);
        }

        .tg-card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.3s ease;
            opacity: 0;
            padding: 0 1.25rem;
        }

        .tg-card-body.tg-open {
            max-height: 2000px;
            opacity: 1;
            padding: 0 1.25rem 1.25rem;
        }

        .tg-card-body p {
            line-height: 1.7;
            text-align: left;
            color: var(--tg-text-dark);
            margin: 0.5rem 0;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .tg-card-body h3 {
            text-align: left;
            color: var(--tg-primary-dark);
            font-size: 1rem;
            margin: 0.75rem 0 0.5rem;
            transition: color 0.3s ease;
        }

        .tg-card-body ul {
            line-height: 1.8;
            padding-left: 20px;
            margin: 0.5rem 0;
        }

        .tg-card-body ul li {
            color: var(--tg-text-dark);
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .tg-link {
            color: var(--tg-accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--tg-transition);
        }

        .tg-link:hover {
            color: var(--tg-primary-dark);
            text-decoration: underline;
        }

        .tg-scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 48px;
            height: 48px;
            background: var(--tg-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: var(--tg-transition);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            border: none;
        }

        .tg-scroll-top.tg-visible {
            opacity: 1;
            visibility: visible;
        }

        .tg-scroll-top:hover {
            background: var(--tg-primary-dark);
            transform: translateY(-3px);
        }

        .tg-strong {
            font-weight: 600;
        }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode .tg-page {
            --tg-body-bg: #0B141A;
            --tg-text-dark: #E9EDEF;
            --tg-text-secondary: #8696A0;
            --tg-shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.3);
            --tg-shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.4);
            --tg-card-bg: #1F2C33;
            --tg-card-hover: rgba(37, 211, 102, 0.06);
            --tg-card-active: rgba(37, 211, 102, 0.1);
        }

        body.dark-mode .tg-card {
            border-left-color: var(--tg-secondary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .tg-card-top h2 {
            color: var(--tg-secondary);
        }

        body.dark-mode .tg-card-arrow {
            color: var(--tg-secondary);
        }

        body.dark-mode .tg-card-body p,
        body.dark-mode .tg-card-body ul li {
            color: #BCC4C9;
        }

        body.dark-mode .tg-card-body h3 {
            color: var(--tg-secondary);
        }

        body.dark-mode .tg-link {
            color: var(--tg-secondary);
        }

        body.dark-mode .tg-link:hover {
            color: #1ea84e;
        }

        body.dark-mode .tg-scroll-top {
            background: var(--tg-secondary);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        body.dark-mode .tg-scroll-top:hover {
            background: #1ea84e;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .tg-wrapper {
                max-width: 100%;
            }

            .tg-header {
                padding: 1.25rem 1rem;
            }

            .tg-header-title {
                font-size: 1.4rem;
            }

            .tg-cards-outer {
                padding: 0.75rem;
                gap: 0.6rem;
            }

            .tg-card-top {
                padding: 0.9rem 1rem;
            }

            .tg-card-top h2 {
                font-size: 1.05rem;
            }

            .tg-card-body {
                padding: 0 1rem;
            }

            .tg-card-body.tg-open {
                padding: 0 1rem 1rem;
            }

            .tg-card-body p,
            .tg-card-body ul li {
                font-size: 0.85rem;
                line-height: 1.6;
            }

            .tg-scroll-top {
                bottom: 1.5rem;
                right: 1.25rem;
                width: 44px;
                height: 44px;
            }
        }

        @media (max-width: 400px) {
            .tg-header-title {
                font-size: 1.2rem;
            }

            .tg-header-subtitle {
                font-size: 0.75rem;
            }

            .tg-card-top h2 {
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="tg-page">
    <div class="tg-wrapper">
        <div class="tg-header">
            <div class="tg-header-inner">
                <?php require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
                <h1 class="tg-header-title">LetsChat</h1>
                <p class="tg-header-subtitle">A web-based messaging application that allows users to communicate seamlessly.</p>
            </div>
        </div>

        <div class="tg-cards-outer">
            <!-- Features Overview -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>Features Overview</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <p><span class="tg-strong">User Accounts System:</span> <a href="../register/register.php" class="tg-link">Create your personalized account</a> with just a few simple steps. Our secure authentication system allows you to access your messages from any device while keeping your conversations private and protected.</p>
                    
                    <p><span class="tg-strong">Real-time Messaging:</span> Experience lightning-fast message delivery with our advanced WebSocket technology. See when others are typing in real-time and receive instant notifications for new messages, ensuring you never miss important conversations.</p>
                    
                    <p><span class="tg-strong">Intuitive Message Threads:</span> Our sophisticated reply system helps maintain context in busy conversations. Simply hover over any message and click the reply button to keep discussions organized and easy to follow, even in group chats.</p>
                    
                    <p><span class="tg-strong">Rich Media Sharing:</span> Beyond text, share what matters most. Upload and send high-quality images (JPG, PNG), documents (PDF, DOCX), and other file types (up to 25MB) with our optimized file transfer system that maintains quality while minimizing data usage.</p>
                    
                    <p><span class="tg-strong">Cross-Platform Accessibility:</span> LetsChat works flawlessly across all modern web browsers on desktop and mobile devices. Our responsive design automatically adapts to your screen size, providing an optimal viewing experience whether you're using a phone, tablet, or computer.</p>
                    
                    <p><span class="tg-strong">Message History:</span> All your conversations are securely stored in the cloud, allowing you to access your complete chat history from any device at any time. Our efficient data management ensures quick loading even for lengthy conversations.</p>
                </div>
            </div>

            <!-- Getting Started -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>Getting Started Guide</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <h3>System Requirements</h3>
                    <p><span class="tg-strong">Browser Compatibility:</span> For optimal performance, we recommend using the latest versions of Chrome (v90+), Firefox (v88+), Safari (v14+), or Edge (v90+). These browsers support all advanced features including notifications and real-time updates.</p>
                    
                    <p><span class="tg-strong">Internet Connection:</span> A stable broadband connection with minimum speeds of 2Mbps is recommended for smooth operation. While LetsChat can work on slower connections, some features like file sharing may perform better with faster speeds.</p>
                    
                    <h3>First-Time Setup</h3>
                    <p><span class="tg-strong">Browser Settings:</span> Enable JavaScript and allow cookies for the best experience. For desktop notifications, when prompted, click "Allow" to receive message alerts even when the browser is minimized.</p>
                    
                    <p><span class="tg-strong">Mobile Access:</span> On smartphones, you can add LetsChat to your home screen for app-like convenience. In Chrome or Safari, use the "Add to Home Screen" option from the browser menu.</p>
                </div>
            </div>

            <!-- Start Chatting -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>Start Chatting Today</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <p><span class="tg-strong">Account Creation:</span> Begin by clicking the prominent "Sign Up" button on our homepage. You'll need to provide a valid email address (which we verify for security), create a strong password (minimum 8 characters with mixed cases and numbers), and set up your profile information including an optional profile picture.</p>
                    
                    <p><span class="tg-strong">Secure Login:</span> After registration, log in using your credentials. For added security, we recommend enabling two-factor authentication in your account settings. This optional feature provides an extra layer of protection for your account.</p>
                    
                    <p><span class="tg-strong">Navigating the Interface:</span> The intuitive dashboard shows all your active conversations on the left panel. Click any conversation to view its history or start a new chat using the "+" button. The right panel displays detailed conversation history and message composition tools.</p>
                    
                    <p><span class="tg-strong">Sending Your First Message:</span> Select a contact from your list (or add new contacts using their registered email address). Type your message in the composition box at the bottom. Use the attachment icon (paperclip) to add files. Press Enter to send or use the send button for additional options.</p>
                    
                    <p><span class="tg-strong">Advanced Features:</span> Explore message formatting options (bold, italics), message reactions, and read receipts. Long-press messages on mobile or right-click on desktop for additional options like forwarding or saving media.</p>
                </div>
            </div>

            <!-- Contribute -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>Contribute to LetsChat</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <p>LetsChat is built by a community of passionate developers, and we welcome your contributions to help improve the platform for everyone.</p>
                    
                    <p><span class="tg-strong">Reporting Issues:</span> Found a bug or experiencing unexpected behavior? Open a detailed issue on our GitHub repository including steps to reproduce, expected behavior, actual results, and screenshots if applicable. Our team typically responds within 48 hours.</p>
                    
                    <p><span class="tg-strong">Feature Requests:</span> Have an idea to make LetsChat better? Submit your feature suggestions through our feedback portal. Please include a clear description of the proposed feature, its benefits, and any relevant examples from other platforms.</p>
                    
                    <p><span class="tg-strong">Code Contributions:</span> Developers can fork our repository and submit pull requests. We follow standard Git workflows and require comprehensive tests for all new code. Before starting major work, please discuss your plans via GitHub Issues to ensure alignment with our roadmap.</p>
                    
                    <p><span class="tg-strong">Documentation:</span> Help improve our user guides, developer documentation, or translation files. Even small fixes for typos or unclear instructions are greatly appreciated.</p>
                    
                    <p><span class="tg-strong">Community Support:</span> Help other users in our community forums by answering questions and sharing your expertise. Active contributors may be invited to join our moderator team.</p>
                </div>
            </div>

            <!-- License -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>License Information</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <p>LetsChat is released under the MUST (Modern Universal Software Terms) License, a permissive open-source license that balances developer rights with user protections.</p>
                    
                    <p><span class="tg-strong">Key License Provisions:</span></p>
                    <ul>
                        <li>Free to use for personal and commercial purposes</li>
                        <li>Allows modification and private use</li>
                        <li>Requires preservation of copyright notices</li>
                        <li>Includes a patent grant from contributors</li>
                        <li>Provides no warranty or liability protection</li>
                        <li>Requires attribution in derivative works</li>
                    </ul>
                    
                    <p><span class="tg-strong">Commercial Use:</span> Businesses may use LetsChat internally or integrate it into commercial products without royalty payments. However, you may not resell LetsChat as-is without significant modification.</p>
                    
                    <p><span class="tg-strong">Complete Terms:</span> For the full legal text and detailed explanations of your rights and responsibilities, please review the <a href="LICENSE" class="tg-link">LICENSE</a> file included with the source code or available on our official website.</p>
                    
                    <p><span class="tg-strong">Third-Party Components:</span> Certain components of LetsChat may be subject to additional licenses. These are clearly marked in our documentation and source code headers.</p>
                </div>
            </div>

            <!-- Contact -->
            <div class="tg-card">
                <div class="tg-card-top" onclick="tgToggleCard(this)">
                    <h2>Contact & Support</h2>
                    <i class="fas fa-chevron-down tg-card-arrow"></i>
                </div>
                <div class="tg-card-body">
                    <p>We're committed to providing excellent support and welcome your feedback, questions, and suggestions.</p>
                    
                    <p><span class="tg-strong">Technical Support:</span> For account issues, bug reports, or technical questions, email our support team at <a href="mailto:support@letschat.com" class="tg-link">support@letschat.com</a>. Please include your username, browser/device details, and a clear description of your issue for fastest resolution.</p>
                    
                    <p><span class="tg-strong">Business Inquiries:</span> Organizations interested in enterprise solutions, white-label versions, or custom integrations should contact <a href="mailto:enterprise@letschat.com" class="tg-link">enterprise@letschat.com</a>.</p>
                    
                    <p><span class="tg-strong">Community Manager:</span> For partnership opportunities, community events, or media inquiries, reach out to our community team at <a href="mailto:community@letschat.com" class="tg-link">community@letschat.com</a>.</p>
                    
                    <p><span class="tg-strong">Direct Contact:</span> You can also reach our founder directly at <a href="mailto:byaruhangaisamelk@gmail.com" class="tg-link">byaruhangaisamelk@gmail.com</a> or by phone at +256757074854 (available Monday-Friday, 9AM-5PM EAT).</p>
                    
                    <p><span class="tg-strong">Response Times:</span> We strive to respond to all inquiries within 24 hours on business days. Critical issues are typically addressed within 4 hours during our standard support window (8AM-8PM EAT).</p>
                    
                    <p><span class="tg-strong">Emergency Support:</span> For urgent security issues, please begin your subject line with "[SECURITY]" and we'll prioritize your message accordingly.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="tg-scroll-top" id="tgScrollTop" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<?php include '../footer/footer.php'; ?>

<script>
    // Back to top button
    var tgScrollTop = document.getElementById('tgScrollTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            tgScrollTop.classList.add('tg-visible');
        } else {
            tgScrollTop.classList.remove('tg-visible');
        }
    });
    
    tgScrollTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Card toggle with accordion behavior
    function tgToggleCard(header) {
        var body = header.nextElementSibling;
        var wasActive = header.classList.contains('tg-active');
        
        // Close all other cards
        document.querySelectorAll('.tg-card-top').forEach(function(otherHeader) {
            if (otherHeader !== header) {
                otherHeader.classList.remove('tg-active');
                otherHeader.nextElementSibling.classList.remove('tg-open');
            }
        });
        
        // Toggle clicked card
        if (wasActive) {
            header.classList.remove('tg-active');
            body.classList.remove('tg-open');
        } else {
            header.classList.add('tg-active');
            body.classList.add('tg-open');
        }
    }

    // Initialize first card as open
    document.addEventListener('DOMContentLoaded', function() {
        var firstTop = document.querySelector('.tg-card-top');
        var firstBody = document.querySelector('.tg-card-body');
        
        if (firstTop && firstBody) {
            firstTop.classList.add('tg-active');
            firstBody.classList.add('tg-open');
        }
        
        // Keyboard accessibility
        document.querySelectorAll('.tg-card-top').forEach(function(header) {
            header.setAttribute('tabindex', '0');
            header.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    tgToggleCard(header);
                }
            });
        });
    });
</script>
</body>
</html>