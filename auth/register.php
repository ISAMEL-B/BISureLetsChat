<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BISureChat - Login | Signup</title>
  <link rel="icon" href="../../favicon.png" type="image/x-icon">
  <link rel="stylesheet" href="register.css">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    :root {
      --primary: #4a6bff;
      --primary-dark: #3a56d4;
      --secondary: #6a11cb;
      --success: #10b981;
      --danger: #ef4444;
      --dark: #1e293b;
      --gray: #94a3b8;
      --gray-light: #e2e8f0;
      --radius-md: 12px;
      --radius-lg: 16px;
      --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* Hide scrollbar but allow scrolling */
    .form_container {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .form_container::-webkit-scrollbar {
      display: none;
    }

    .pw_hide {
      cursor: pointer;
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
      font-size: 1.1rem;
      transition: color 0.3s;
      z-index: 2;
      background: none;
      border: none;
      padding: 5px;
    }

    .pw_hide:hover { color: var(--primary); }

    .animate-spin {
      animation: spin 1s linear infinite;
      display: inline-block;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* ============================================
       FORM CONTAINER
       ============================================ */
    .form_container {
      background: #ffffff;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 32px 36px;
      width: 100%;
      max-width: 440px;
      transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      max-height: 92vh;
      overflow-y: auto;
      margin: 0 16px;
    }

    .form_container.active {
      max-width: 660px;
    }

    .form h2 {
      font-size: 1.7rem;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 4px;
      text-align: center;
    }

    .form-subtitle {
      color: var(--gray);
      font-size: 0.85rem;
      margin-bottom: 20px;
      text-align: center;
    }

    /* ============================================
       SOCIAL LOGIN BUTTONS - COLORED ICONS
       ============================================ */
    .social-login {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .social-btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 11px 14px;
      border: 2px solid var(--gray-light);
      border-radius: var(--radius-md);
      background: #ffffff;
      cursor: pointer;
      font-size: 0.82rem;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      color: #555;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .social-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .social-btn:active {
      transform: translateY(0);
    }

    .social-btn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* Brand colored icons */
    .social-btn.google .brand-icon {
      color: #4285f4;
    }
    .social-btn.facebook .brand-icon {
      color: #1877f2;
    }
    .social-btn.github .brand-icon {
      color: #24292e;
    }

    .social-btn .brand-icon {
      font-size: 1.3rem;
      transition: transform 0.3s;
      flex-shrink: 0;
    }

    .social-btn:hover .brand-icon {
      transform: scale(1.15);
    }

    /* Google */
    .social-btn.google { border-color: #ea4335; }
    .social-btn.google:hover {
      background: #fef2f2;
      box-shadow: 0 4px 15px rgba(234, 67, 53, 0.15);
    }

    /* Facebook */
    .social-btn.facebook { border-color: #1877f2; }
    .social-btn.facebook:hover {
      background: #eff6ff;
      box-shadow: 0 4px 15px rgba(24, 119, 242, 0.15);
    }

    /* GitHub */
    .social-btn.github { border-color: #24292e; }
    .social-btn.github:hover {
      background: #f6f8fa;
      box-shadow: 0 4px 15px rgba(36, 41, 46, 0.15);
    }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }

    .divider-line {
      flex: 1;
      height: 1px;
      background: var(--gray-light);
    }

    .divider-text {
      color: var(--gray);
      font-size: 0.78rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      white-space: nowrap;
    }

    /* ============================================
       INPUT BOX
       ============================================ */
    .input_box {
      position: relative;
      margin-bottom: 16px;
    }

    .input_box input {
      width: 100%;
      padding: 13px 14px;
      padding-left: 42px;
      border: 2px solid var(--gray-light);
      border-radius: var(--radius-md);
      font-size: 0.9rem;
      font-family: 'Poppins', sans-serif;
      color: var(--dark);
      background: #ffffff;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .input_box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(74, 107, 255, 0.08);
    }

    .input_box input::placeholder {
      color: var(--gray);
      font-size: 0.85rem;
    }

    .input_box i:not(.pw_hide) {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
      font-size: 1.15rem;
      transition: color 0.3s;
    }

    .input_box:focus-within i:not(.pw_hide) {
      color: var(--primary);
    }

    .input_box input.error {
      border-color: var(--danger) !important;
      box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.06) !important;
    }

    .input_box input.success {
      border-color: var(--success) !important;
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.06) !important;
    }

    /* Error message */
    .error-message {
      font-size: 0.73rem;
      color: var(--danger);
      margin-top: 5px;
      padding-left: 2px;
      font-weight: 500;
      animation: errorSlide 0.3s ease;
    }

    @keyframes errorSlide {
      0% { opacity: 0; transform: translateY(-3px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    /* Top message */
    .message {
      font-size: 0.82rem;
      font-weight: 600;
      text-align: center;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      margin-bottom: 16px;
      display: none;
    }

    .message.error-message-top {
      display: block;
      color: var(--danger);
      background: rgba(239, 68, 68, 0.06);
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .message.success-message-top {
      display: block;
      color: var(--success);
      background: rgba(16, 185, 129, 0.06);
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    /* Two column grid */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-row .input_box {
      margin-bottom: 12px;
    }

    /* Submit button */
    .button {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: #ffffff;
      border: none;
      border-radius: var(--radius-md);
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 6px;
      letter-spacing: 0.3px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(106, 17, 203, 0.35);
    }

    .button:active { transform: translateY(0); }

    .button:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* Remember me checkbox */
    .option_field {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
    }

    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .checkbox-wrapper input[type="checkbox"] { display: none; }

    .custom-checkbox {
      width: 18px;
      height: 18px;
      border: 2px solid var(--gray-light);
      border-radius: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;
      flex-shrink: 0;
      background: #ffffff;
    }

    .custom-checkbox i {
      font-size: 10px;
      color: #ffffff;
      opacity: 0;
      transform: scale(0);
      transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox {
      background: var(--primary);
      border-color: var(--primary);
    }

    .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox i {
      opacity: 1;
      transform: scale(1);
    }

    .checkbox-label {
      font-size: 0.84rem;
      color: var(--gray);
      user-select: none;
      font-weight: 500;
    }

    .checkbox-wrapper:hover .custom-checkbox { border-color: var(--primary); }

    .forgot_pw {
      color: var(--primary);
      font-weight: 600;
      font-size: 0.83rem;
      text-decoration: none;
      transition: color 0.3s;
    }

    .forgot_pw:hover { color: var(--primary-dark); text-decoration: underline; }

    /* Toggle */
    .login_signup {
      text-align: center;
      margin-top: 18px;
      font-size: 0.87rem;
      color: var(--gray);
    }

    .login_signup a {
      color: var(--primary);
      font-weight: 700;
      text-decoration: none;
      transition: color 0.3s;
      cursor: pointer;
    }

    .login_signup a:hover { color: var(--primary-dark); text-decoration: underline; }

    /* Shake */
    .shake { animation: shake 0.4s ease; }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20% { transform: translateX(-5px); }
      40% { transform: translateX(5px); }
      60% { transform: translateX(-4px); }
      80% { transform: translateX(3px); }
    }

    /* ============================================
       SUCCESS OVERLAY
       ============================================ */
    .login-success-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
      z-index: 9999;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.4s ease, visibility 0.4s ease;
      overflow: hidden;
    }

    .login-success-overlay.active { opacity: 1; visibility: visible; }

    #particleCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    .success-circle { position: relative; width: 110px; height: 110px; z-index: 2; }

    .success-circle::before,
    .success-circle::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 110px;
      height: 110px;
      border-radius: 50%;
      border: 3px solid rgba(74, 222, 128, 0.3);
      animation: circlePulse 2s ease-out infinite;
    }

    .success-circle::after {
      border-color: rgba(74, 222, 128, 0.15);
      animation-delay: 0.5s;
    }

    @keyframes circlePulse {
      0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
      100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
    }

    .checkmark-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 3;
    }

    .checkmark-circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 3;
      stroke-miterlimit: 10;
      stroke: #4ade80;
      fill: none;
      animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.2s forwards;
    }

    .checkmark-check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      stroke: #4ade80;
      stroke-width: 3;
      animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes stroke { 100% { stroke-dashoffset: 0; } }

    .success-text-container { z-index: 2; text-align: center; margin-top: 35px; }

    .success-title {
      color: #ffffff;
      font-size: 2.3rem;
      font-weight: 700;
      margin: 0;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1s forwards;
      text-shadow: 0 0 30px rgba(74, 222, 128, 0.3);
    }

    .success-subtitle {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.05rem;
      margin-top: 8px;
      opacity: 0;
      transform: translateY(20px);
      animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.2s forwards;
    }

    @keyframes slideUpFade {
      0% { opacity: 0; transform: translateY(30px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .loading-dots {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 28px;
      opacity: 0;
      animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.4s forwards;
      z-index: 2;
    }

    .loading-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #4ade80;
      animation: dotBounce 1.4s ease-in-out infinite;
    }

    .loading-dot:nth-child(2) { animation-delay: 0.2s; background-color: #60a5fa; }
    .loading-dot:nth-child(3) { animation-delay: 0.4s; background-color: #c084fc; }

    @keyframes dotBounce {
      0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
      40% { transform: scale(1.2); opacity: 1; }
    }

    .success-ring {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 160px;
      height: 160px;
      border-radius: 50%;
      border: 2px solid transparent;
      border-top-color: rgba(74, 222, 128, 0.6);
      border-right-color: rgba(96, 165, 250, 0.4);
      animation: ringRotate 2s linear infinite;
      z-index: 1;
    }

    @keyframes ringRotate {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .decorative-line {
      position: absolute;
      width: 1px;
      height: 70px;
      background: linear-gradient(to bottom, transparent, rgba(74, 222, 128, 0.5), transparent);
      z-index: 1;
      opacity: 0;
    }

    .decorative-line.left { left: 30%; top: 40%; animation: lineAppearLeft 1s ease 1.5s forwards; }
    .decorative-line.right { right: 30%; top: 40%; animation: lineAppearRight 1s ease 1.5s forwards; }

    @keyframes lineAppearLeft {
      0% { opacity: 0; transform: translateX(-40px); }
      100% { opacity: 1; transform: translateX(0); }
    }

    @keyframes lineAppearRight {
      0% { opacity: 0; transform: translateX(40px); }
      100% { opacity: 1; transform: translateX(0); }
    }

    /* ============================================
       RESPONSIVE - FIXED
       ============================================ */
    @media (max-width: 768px) {
      .form_container {
        max-width: calc(100% - 32px) !important;
        margin: 0 16px;
        padding: 24px 20px;
        border-radius: var(--radius-lg);
        max-height: 88vh;
      }

      .form_container.active {
        max-width: calc(100% - 32px) !important;
      }

      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .social-login {
        flex-direction: row;
        gap: 8px;
      }

      .social-btn {
        padding: 10px 8px;
        font-size: 0.75rem;
        gap: 6px;
      }

      .social-btn .brand-icon {
        font-size: 1.1rem;
      }

      .form h2 { font-size: 1.5rem; }
      .form-subtitle { font-size: 0.8rem; margin-bottom: 16px; }

      .input_box input {
        padding: 11px 12px;
        padding-left: 38px;
        font-size: 0.85rem;
      }

      .success-title { font-size: 1.7rem; }
      .success-subtitle { font-size: 0.85rem; }
      .success-circle { width: 90px; height: 90px; }
      .success-circle::before,
      .success-circle::after { width: 90px; height: 90px; }
      .success-ring { width: 130px; height: 130px; }
    }

    @media (max-width: 400px) {
      .social-login {
        flex-direction: column;
        gap: 8px;
      }

      .social-btn {
        padding: 12px 14px;
        font-size: 0.82rem;
      }

      .form_container {
        padding: 20px 16px;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <nav class="nav">
      <a href="#" class="nav_logo">BISureChat</a>
      <button class="button" id="form-open" style="width:auto;padding:10px 22px;font-size:0.9rem;">Login</button>
    </nav>
  </header>

  <section class="home">
    <div class="welcome-overlay">
      <h1 class="welcome-title">Welcome To BISureChat</h1>
      <p class="welcome-subtitle">Connect with friends, family, and colleagues through secure messaging.</p>
      <div class="welcome-features">
        <div class="feature-item"><i class="uil uil-shield-check"></i><span>Secure Messaging</span></div>
        <div class="feature-item"><i class="uil uil-rocket"></i><span>Lightning Fast</span></div>
        <div class="feature-item"><i class="uil uil-cloud-share"></i><span>Cloud Sync</span></div>
      </div>
    </div>

    <div class="form_container">
      <i class="uil uil-times form_close"></i>

      <!-- ============================================
           LOGIN FORM
           ============================================ -->
      <div class="form login_form">
        <form id="loginForm" novalidate>
          <h2>Welcome Back</h2>
          <p class="form-subtitle">Sign in to continue your conversations</p>

          <!-- Social Login with colored brand icons -->
          <div class="social-login">
            <button type="button" class="social-btn google" id="googleLoginBtn">
              <i class="fab fa-google brand-icon"></i> Google
            </button>
            <button type="button" class="social-btn facebook" id="facebookLoginBtn">
              <i class="fab fa-facebook-f brand-icon"></i> Facebook
            </button>
            <button type="button" class="social-btn github" id="githubLoginBtn">
              <i class="fab fa-github brand-icon"></i> GitHub
            </button>
          </div>

          <div class="divider">
            <span class="divider-line"></span>
            <span class="divider-text">or continue with</span>
            <span class="divider-line"></span>
          </div>

          <div class="message" id="login-message"></div>

          <div class="input_box">
            <i class="uil uil-user"></i>
            <input type="text" name="user" placeholder="Username, Email, or Phone" autocomplete="username" />
            <div class="error-message" id="login-user-error"></div>
          </div>

          <div class="input_box">
            <i class="uil uil-lock"></i>
            <input type="password" name="password" placeholder="Password" autocomplete="current-password" />
            <i class="uil uil-eye-slash pw_hide"></i>
            <div class="error-message" id="login-password-error"></div>
          </div>

          <div class="option_field">
            <label class="checkbox-wrapper">
              <input type="checkbox" id="check" name="remember" />
              <span class="custom-checkbox"><i class="fas fa-check"></i></span>
              <span class="checkbox-label">Remember me</span>
            </label>
            <a href="../auth/forgot_password" class="forgot_pw">Forgot password?</a>
          </div>

          <button class="button" type="submit">
            <i class="uil uil-sign-in-alt"></i> Sign In
          </button>

          <div class="login_signup">
            Don't have an account? <a id="signup">Create one</a>
          </div>
        </form>
      </div>

      <!-- ============================================
           SIGNUP FORM
           ============================================ -->
      <div class="form signup_form">
        <form id="signupForm" novalidate>
          <h2>Create Account</h2>
          <p class="form-subtitle">Join the community and start connecting</p>

          <!-- Social Signup -->
          <div class="social-login">
            <button type="button" class="social-btn google" id="googleSignupBtn">
              <i class="fab fa-google brand-icon"></i> Google
            </button>
            <button type="button" class="social-btn facebook" id="facebookSignupBtn">
              <i class="fab fa-facebook-f brand-icon"></i> Facebook
            </button>
            <button type="button" class="social-btn github" id="githubSignupBtn">
              <i class="fab fa-github brand-icon"></i> GitHub
            </button>
          </div>

          <div class="divider">
            <span class="divider-line"></span>
            <span class="divider-text">or sign up with email</span>
            <span class="divider-line"></span>
          </div>

          <div class="message" id="signup-message"></div>

          <div class="form-row">
            <div class="input_box">
              <i class="uil uil-user"></i>
              <input type="text" name="fullname" placeholder="Full Name" />
              <div class="error-message" id="error-fullname"></div>
            </div>
            <div class="input_box">
              <i class="uil uil-phone"></i>
              <input type="tel" name="phone" placeholder="Phone Number" />
              <div class="error-message" id="error-phone"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="input_box">
              <i class="uil uil-at"></i>
              <input type="text" name="username" placeholder="Username" />
              <div class="error-message" id="error-username"></div>
            </div>
            <div class="input_box">
              <i class="uil uil-envelope-alt"></i>
              <input type="email" name="email" placeholder="Email Address" />
              <div class="error-message" id="error-email"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="input_box">
              <i class="uil uil-lock"></i>
              <input type="password" name="password" placeholder="Password" />
              <i class="uil uil-eye-slash pw_hide"></i>
              <div class="error-message" id="error-password"></div>
            </div>
            <div class="input_box">
              <i class="uil uil-lock"></i>
              <input type="password" name="confirm_password" placeholder="Confirm Password" />
              <i class="uil uil-eye-slash pw_hide"></i>
              <div class="error-message" id="error-confirm_password"></div>
            </div>
          </div>

          <label class="checkbox-wrapper" style="margin-bottom: 6px;">
            <input type="checkbox" name="terms" />
            <span class="custom-checkbox"><i class="fas fa-check"></i></span>
            <span class="checkbox-label">I agree to the <a href="#" style="color:var(--primary);font-weight:600;">Terms</a> & <a href="#" style="color:var(--primary);font-weight:600;">Privacy Policy</a></span>
          </label>

          <button class="button" type="submit">
            <i class="uil uil-user-plus"></i> Create Account
          </button>

          <div class="login_signup">
            Already have an account? <a id="login">Sign in</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Premium Login Success Overlay -->
    <div class="login-success-overlay" id="loginSuccessOverlay">
      <canvas id="particleCanvas"></canvas>
      <div class="decorative-line left"></div>
      <div class="decorative-line right"></div>
      <div class="success-circle">
        <div class="success-ring"></div>
        <div class="checkmark-container">
          <svg xmlns="http://www.w3.org/2000/svg" width="75" height="75" viewBox="0 0 52 52">
            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
          </svg>
        </div>
      </div>
      <div class="success-text-container">
        <h2 class="success-title">Welcome Back!</h2>
        <p class="success-subtitle">Preparing your secure chat experience...</p>
      </div>
      <div class="loading-dots">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
      </div>
    </div>
  </section>

  <script>
    // ============================================
    // UI Toggle
    // ============================================
    const formOpenBtn = document.querySelector("#form-open"),
      formCloseBtn = document.querySelector(".form_close"),
      home = document.querySelector(".home"),
      welcomeOverlay = document.querySelector(".welcome-overlay"),
      formContainer = document.querySelector(".form_container"),
      signupBtn = document.querySelector("#signup"),
      loginBtn = document.querySelector("#login"),
      loginSuccessOverlay = document.getElementById("loginSuccessOverlay");

    formOpenBtn.onclick = () => {
      home.classList.add("show");
      welcomeOverlay.style.display = "none";
    };
    formCloseBtn.onclick = () => {
      home.classList.remove("show");
      welcomeOverlay.style.display = "flex";
      cancelAllSocialLogins();
    };
    signupBtn.onclick = (e) => {
      e.preventDefault();
      formContainer.classList.add("active");
    };
    loginBtn.onclick = (e) => {
      e.preventDefault();
      formContainer.classList.remove("active");
    };

    // ============================================
    // SOCIAL LOGIN - CANCELABLE
    // ============================================
    let socialLoginTimers = {};
    let socialLoginActive = {};

    function socialLogin(provider, btnId) {
      const btn = document.getElementById(btnId);
      if (!btn || socialLoginActive[btnId]) return;

      socialLoginActive[btnId] = true;
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Connecting...';
      btn.disabled = true;

      // Add cancel capability
      btn.innerHTML += ' <small style="font-size:0.7rem;opacity:0.7;">(click to cancel)</small>';
      btn.style.cursor = 'pointer';
      
      const cancelHandler = function(e) {
        e.preventDefault();
        e.stopPropagation();
        cancelSocialLogin(btnId, btn, originalHTML);
        btn.removeEventListener('click', cancelHandler);
      };
      
      btn.addEventListener('click', cancelHandler, { once: false });

      // Store timer
      socialLoginTimers[btnId] = setTimeout(() => {
        btn.removeEventListener('click', cancelHandler);
        const redirectUrls = {
          googleLoginBtn: 'content/social_login.php?provider=google',
          facebookLoginBtn: 'content/social_login.php?provider=facebook',
          githubLoginBtn: 'content/social_login.php?provider=github',
          googleSignupBtn: 'content/social_login.php?provider=google&action=signup',
          facebookSignupBtn: 'content/social_login.php?provider=facebook&action=signup',
          githubSignupBtn: 'content/social_login.php?provider=github&action=signup',
        };
        window.location.href = redirectUrls[btnId] || 'content/social_login.php';
      }, 1500);
    }

    function cancelSocialLogin(btnId, btn, originalHTML) {
      if (socialLoginTimers[btnId]) {
        clearTimeout(socialLoginTimers[btnId]);
        delete socialLoginTimers[btnId];
      }
      socialLoginActive[btnId] = false;
      btn.innerHTML = originalHTML;
      btn.disabled = false;
      btn.style.cursor = 'pointer';
    }

    function cancelAllSocialLogins() {
      const buttons = {
        googleLoginBtn: document.getElementById('googleLoginBtn'),
        facebookLoginBtn: document.getElementById('facebookLoginBtn'),
        githubLoginBtn: document.getElementById('githubLoginBtn'),
        googleSignupBtn: document.getElementById('googleSignupBtn'),
        facebookSignupBtn: document.getElementById('facebookSignupBtn'),
        githubSignupBtn: document.getElementById('githubSignupBtn'),
      };

      for (let [id, btn] of Object.entries(buttons)) {
        if (btn && socialLoginActive[id]) {
          cancelSocialLogin(id, btn, btn.getAttribute('data-original') || btn.innerHTML);
        }
      }
    }

    // Attach social login handlers
    document.getElementById('googleLoginBtn').addEventListener('click', function() {
      if (!socialLoginActive['googleLoginBtn']) socialLogin('google', 'googleLoginBtn');
    });
    document.getElementById('facebookLoginBtn').addEventListener('click', function() {
      if (!socialLoginActive['facebookLoginBtn']) socialLogin('facebook', 'facebookLoginBtn');
    });
    document.getElementById('githubLoginBtn').addEventListener('click', function() {
      if (!socialLoginActive['githubLoginBtn']) socialLogin('github', 'githubLoginBtn');
    });
    document.getElementById('googleSignupBtn').addEventListener('click', function() {
      if (!socialLoginActive['googleSignupBtn']) socialLogin('google', 'googleSignupBtn');
    });
    document.getElementById('facebookSignupBtn').addEventListener('click', function() {
      if (!socialLoginActive['facebookSignupBtn']) socialLogin('facebook', 'facebookSignupBtn');
    });
    document.getElementById('githubSignupBtn').addEventListener('click', function() {
      if (!socialLoginActive['githubSignupBtn']) socialLogin('github', 'githubSignupBtn');
    });

    // ============================================
    // Password Toggle
    // ============================================
    document.querySelectorAll(".pw_hide").forEach(icon => {
      icon.addEventListener("click", () => {
        const input = icon.parentElement.querySelector("input");
        if (input.type === "password") {
          input.type = "text";
          icon.classList.remove("uil-eye-slash");
          icon.classList.add("uil-eye");
        } else {
          input.type = "password";
          icon.classList.remove("uil-eye");
          icon.classList.add("uil-eye-slash");
        }
      });
    });

    // ============================================
    // Particle Animation
    // ============================================
    const canvas = document.getElementById('particleCanvas');
    const ctx = canvas.getContext('2d');
    let particles = [], animationFrame;

    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    class Particle {
      constructor() {
        this.reset();
      }
      reset() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 2 + 0.5;
        this.speedX = (Math.random() - 0.5) * 0.5;
        this.speedY = (Math.random() - 0.5) * 0.5;
        this.opacity = Math.random() * 0.5 + 0.1;
        const colors = [
          'rgba(74, 222, 128, o)','rgba(96, 165, 250, o)','rgba(192, 132, 252, o)',
          'rgba(251, 191, 36, o)','rgba(244, 114, 182, o)',
        ];
        this.color = colors[Math.floor(Math.random() * colors.length)];
      }
      update() {
        this.x += this.speedX;
        this.y += this.speedY;
        if (this.x < 0) this.x = canvas.width;
        if (this.x > canvas.width) this.x = 0;
        if (this.y < 0) this.y = canvas.height;
        if (this.y > canvas.height) this.y = 0;
        this.opacity += (Math.random() - 0.5) * 0.01;
        this.opacity = Math.max(0.1, Math.min(0.6, this.opacity));
      }
      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = this.color.replace('o', this.opacity);
        ctx.fill();
      }
    }

    function initParticles(count = 70) {
      particles = [];
      for (let i = 0; i < count; i++) particles.push(new Particle());
    }

    function connectParticles() {
      const maxDistance = 140;
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          if (distance < maxDistance) {
            const opacity = (1 - distance / maxDistance) * 0.15;
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.strokeStyle = `rgba(255, 255, 255, ${opacity})`;
            ctx.lineWidth = 0.5;
            ctx.stroke();
          }
        }
      }
    }

    function animateParticles() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles.forEach(p => { p.update(); p.draw(); });
      connectParticles();
      animationFrame = requestAnimationFrame(animateParticles);
    }

    function startParticleAnimation() { initParticles(); animateParticles(); }
    function stopParticleAnimation() {
      if (animationFrame) cancelAnimationFrame(animationFrame);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles = [];
    }

    function showLoginSuccessAnimation() {
      loginSuccessOverlay.classList.add('active');
      startParticleAnimation();
      setTimeout(() => {
        loginSuccessOverlay.classList.remove('active');
        setTimeout(() => stopParticleAnimation(), 400);
      }, 3000);
    }

    // ============================================
    // Helpers
    // ============================================
    function shakeElements(elements) {
      elements.forEach(el => { el.classList.remove("shake"); void el.offsetWidth; el.classList.add("shake"); });
    }

    function isValidPhone(phone) { return /^[0-9]{10,15}$/.test(phone); }

    function clearErrors(form) {
      form.querySelectorAll('.error-message').forEach(el => el.innerText = '');
      form.querySelectorAll('input.error, input.success').forEach(el => {
        el.classList.remove('error', 'success');
      });
      const msg = form.querySelector('.message');
      if (msg) { msg.innerText = ''; msg.className = 'message'; }
    }

    // ============================================
    // LOGIN FORM
    // ============================================
    document.getElementById("loginForm").onsubmit = function(e) {
      e.preventDefault();
      clearErrors(this);
      const form = this;
      let hasErrors = false;

      if (!form.user.value.trim()) {
        document.getElementById("login-user-error").innerText = "Please enter your username, email, or phone";
        form.user.classList.add('error');
        hasErrors = true;
      }
      if (!form.password.value.trim()) {
        document.getElementById("login-password-error").innerText = "Please enter your password";
        form.password.classList.add('error');
        hasErrors = true;
      }
      if (hasErrors) {
        shakeElements([...form.querySelectorAll(".error-message:not(:empty)")]);
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHTML = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="uil uil-spinner animate-spin"></i> Signing in...';
      submitBtn.disabled = true;

      const formData = new FormData(form);
      formData.append('remember', form.check.checked ? 'on' : 'off');

      fetch('content/bisure_login_content.php', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.ok ? r.json() : Promise.reject('Server error'))
      .then(data => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        document.getElementById("login-message").innerText = '';
        document.getElementById("login-message").className = 'message';
        clearErrors(form);

        if (data.success) {
          showLoginSuccessAnimation();
          setTimeout(() => { window.location.href = data.redirect || '../chat/chats'; }, 3000);
        } else {
          if (data.errors) {
            for (let field in data.errors) {
              const errEl = document.getElementById("login-" + field + "-error");
              if (errEl) errEl.innerText = data.errors[field];
              const inputEl = form.querySelector(`input[name="${field}"]`);
              if (inputEl) inputEl.classList.add('error');
            }
          }
          if (data.message) {
            const msg = document.getElementById("login-message");
            msg.innerText = data.message;
            msg.classList.add('error-message-top');
          }
        }
      })
      .catch(() => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        const msg = document.getElementById("login-message");
        msg.innerText = "Network error. Please try again.";
        msg.classList.add('error-message-top');
      });
    };

    // ============================================
    // SIGNUP FORM
    // ============================================
    document.getElementById("signupForm").onsubmit = function(e) {
      e.preventDefault();
      clearErrors(this);
      const form = this;
      const f = {
        fullname: form.fullname.value.trim(),
        phone: form.phone.value.trim(),
        username: form.username.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value,
        confirm_password: form.confirm_password.value,
        terms: form.terms.checked
      };
      let hasErrors = false;

      const setErr = (field, msg) => {
        document.getElementById(`error-${field}`).innerText = msg;
        form.querySelector(`input[name="${field}"]`).classList.add('error');
        hasErrors = true;
      };

      if (!f.fullname) setErr('fullname', 'Full name is required');
      if (!f.phone) setErr('phone', 'Phone number is required');
      else if (!isValidPhone(f.phone)) setErr('phone', 'Valid 10-15 digit number required');
      if (!f.username) setErr('username', 'Username is required');
      else if (f.username.length < 3) setErr('username', 'At least 3 characters');
      if (!f.email) setErr('email', 'Email is required');
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email)) setErr('email', 'Valid email required');
      if (!f.password) setErr('password', 'Password is required');
      else if (f.password.length < 8) setErr('password', 'At least 8 characters');
      if (!f.confirm_password) setErr('confirm_password', 'Please confirm password');
      else if (f.password !== f.confirm_password) setErr('confirm_password', 'Passwords do not match');
      if (!f.terms) {
        document.getElementById("signup-message").innerText = "Please agree to the Terms & Privacy Policy";
        document.getElementById("signup-message").className = "message error-message-top";
        hasErrors = true;
      }

      if (hasErrors) {
        shakeElements([...form.querySelectorAll(".error-message:not(:empty)")]);
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHTML = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="uil uil-spinner animate-spin"></i> Creating account...';
      submitBtn.disabled = true;

      fetch('content/bisure_register_content.php', {
        method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.ok ? r.json() : Promise.reject('Server error'))
      .then(data => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        const msg = document.getElementById("signup-message");
        msg.innerText = ''; msg.className = 'message';
        clearErrors(form);

        if (data.success) {
          msg.innerText = data.message || "Account created! You can now sign in.";
          msg.classList.add('success-message-top');
          form.reset();
          setTimeout(() => {
            formContainer.classList.remove("active");
            msg.innerText = ''; msg.className = 'message';
          }, 2500);
        } else {
          if (data.errors) {
            for (let field in data.errors) {
              const errEl = document.getElementById(`error-${field}`);
              if (errEl) errEl.innerText = data.errors[field];
              const inputEl = form.querySelector(`input[name="${field}"]`);
              if (inputEl) inputEl.classList.add('error');
            }
          }
          if (data.message) {
            msg.innerText = data.message;
            msg.classList.add('error-message-top');
          }
        }
      })
      .catch(() => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        const msg = document.getElementById("signup-message");
        msg.innerText = "Network error. Please try again.";
        msg.classList.add('error-message-top');
      });
    };
  </script>
</body>
</html>