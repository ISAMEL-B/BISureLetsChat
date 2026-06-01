<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>footer</title>
  <!-- Include Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* =============================================
       FOOTER STYLES - All classes prefixed with ft-
       ============================================= */
    :root {
        --ft-primary: #128C7E;
        --ft-primary-dark: #075E54;
        --ft-primary-light: #25D366;
        --ft-secondary: #34B7F1;
        --ft-dark: #111B21;
        --ft-text-light: #FFFFFF;
        --ft-text-muted: rgba(255, 255, 255, 0.8);
        --ft-border-light: rgba(255, 255, 255, 0.1);
        --ft-bg-overlay: rgba(255, 255, 255, 0.1);
        --ft-transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .ft-footer {
        background: linear-gradient(135deg, var(--ft-primary-dark) 0%, var(--ft-dark) 100%);
        color: var(--ft-text-light);
        padding: 4rem 2rem;
        position: relative;
        z-index: 10;
        font-family: 'Poppins', 'Roboto', sans-serif;
        animation: ftSlideInUp 0.8s ease-out forwards;
        animation-delay: 0.3s;
        opacity: 0;
        width: 100%;
    }

    @keyframes ftSlideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ft-footer-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 3rem;
    }

    .ft-logo-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .ft-logo-img {
        height: 40px;
        width: auto;
    }

    .ft-logo-text {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(90deg, var(--ft-primary-light) 0%, var(--ft-secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .ft-about-text {
        opacity: 0.8;
        line-height: 1.7;
        margin-bottom: 1.5rem;
    }

    .ft-social-list {
        display: flex;
        gap: 1rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ft-social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--ft-bg-overlay);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--ft-transition);
        color: var(--ft-text-light);
        font-size: 1.1rem;
        text-decoration: none;
    }

    .ft-social-link:hover {
        background: var(--ft-primary-light);
        transform: translateY(-3px);
        color: var(--ft-dark);
    }

    .ft-heading {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        position: relative;
        display: inline-block;
    }

    .ft-heading::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--ft-primary-light);
        border-radius: 2px;
    }

    .ft-links-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .ft-links-list li {
        margin-bottom: 0.8rem;
    }

    .ft-links-list a {
        color: var(--ft-text-muted);
        text-decoration: none;
        transition: var(--ft-transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .ft-links-list a:hover {
        color: var(--ft-primary-light);
        transform: translateX(5px);
    }

    .ft-links-list i {
        font-size: 0.8rem;
    }

    .ft-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
        opacity: 0.8;
    }

    .ft-contact-item i {
        margin-top: 3px;
        color: var(--ft-primary-light);
        flex-shrink: 0;
    }

    .ft-bottom-bar {
        text-align: center;
        padding-top: 3rem;
        margin-top: 3rem;
        border-top: 1px solid var(--ft-border-light);
        opacity: 0.7;
        font-size: 0.9rem;
    }

    .ft-bottom-bar a {
        color: var(--ft-primary-light);
        text-decoration: none;
        transition: var(--ft-transition);
    }

    .ft-bottom-bar a:hover {
        text-decoration: underline;
        opacity: 1;
    }

    /* =============================================
       DARK MODE - body.dark-mode parent
       ============================================= */
    body.dark-mode .ft-footer {
        background: linear-gradient(135deg, #0A151C 0%, #0B141A 100%);
        border-top: 1px solid #2A3942;
    }

    body.dark-mode .ft-footer-container {
        /* inherits from parent */
    }

    body.dark-mode .ft-social-link {
        background: rgba(255, 255, 255, 0.06);
    }

    body.dark-mode .ft-social-link:hover {
        background: var(--ft-primary-light);
    }

    body.dark-mode .ft-bottom-bar {
        border-top-color: rgba(255, 255, 255, 0.06);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ft-footer-container {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        .ft-heading {
            margin-bottom: 1rem;
        }
        
        .ft-footer {
            padding: 3rem 1.5rem;
        }
    }
  </style>
</head>

<body>
  <footer class="ft-footer">
    <div class="ft-footer-container">
      <!-- About Column -->
      <div class="ft-about-col">
        <div class="ft-logo-wrap">
          <img src="../../favicon.png" alt="LetsChat Logo" class="ft-logo-img">
          <span class="ft-logo-text">LetsChat</span>
        </div>
        <p class="ft-about-text">Premium communication platform revolutionizing how the world connects. Secure, private, and built for the future.</p>
        <ul class="ft-social-list">
          <li><a href="#" class="ft-social-link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
          <li><a href="#" class="ft-social-link" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
          <li><a href="#" class="ft-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
          <li><a href="#" class="ft-social-link" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a></li>
        </ul>
      </div>

      <!-- Quick Links Column -->
      <div class="ft-links-col">
        <h3 class="ft-heading">Quick Links</h3>
        <ul class="ft-links-list">
          <li><a href="#"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Features</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Pricing</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> About Us</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Contact</a></li>
        </ul>
      </div>

      <!-- Resources Column -->
      <div class="ft-links-col">
        <h3 class="ft-heading">Resources</h3>
        <ul class="ft-links-list">
          <li><a href="#"><i class="fas fa-chevron-right"></i> Documentation</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Help Center</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Community</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Webinars</a></li>
          <li><a href="#"><i class="fas fa-chevron-right"></i> Status</a></li>
        </ul>
      </div>

      <!-- Contact Column -->
      <div class="ft-contact-col">
        <h3 class="ft-heading">Contact Us</h3>
        <div class="ft-contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>1200 Business, Mbarara Tech City, TC 10001</span>
        </div>
        <div class="ft-contact-item">
          <i class="fas fa-phone-alt"></i>
          <span>+1 (555) 123-4567</span>
        </div>
        <div class="ft-contact-item">
          <i class="fas fa-envelope"></i>
          <span>support@BISurechat.com</span>
        </div>
        <div class="ft-contact-item">
          <i class="fas fa-clock"></i>
          <span>Mon-Fri: 9AM - 6PM</span>
        </div>
      </div>
    </div>

    <div class="ft-bottom-bar">
      <p>&copy; <?php echo date('Y'); ?> BISurechat. All rights reserved. |
        <a href="#">Privacy Policy</a> |
        <a href="#">Terms of Service</a>
      </p>
    </div>
  </footer>
</body>

</html>