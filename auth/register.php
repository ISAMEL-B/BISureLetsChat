<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BISureChat - Login | Signup</title>
  <link rel="icon" href="../../favicon.png" type="image/x-icon">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
<style>
    :root {
      --primary-color: #128C7E;
      --primary-dark: #0D7B6C;
      --secondary-color: #25D366;
      --accent-gold: #D4AF37;
      --background-light: #F5F5F5;
      --background-dark: #121E25;
      --text-light: #FFFFFF;
      --text-dark: #2D3748;
      --text-secondary: #718096;
      --border-color: #E2E8F0;
      --unread-badge: #25D366;
      --sent-message: #DCF8C6;
      --received-message: #FFFFFF;
      --hover-light: rgba(0, 0, 0, 0.02);
      --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.05);
      --shadow-dark: 0 4px 20px rgba(0, 0, 0, 0.15);
      --tick-sent: #9C27B0;
      --tick-delivered: #9C27B0;
      --tick-read: #25D366;
      
      /* Dynamic theme variables */
      --bg-primary: #F5F5F5;
      --bg-secondary: #FFFFFF;
      --bg-tertiary: #F9FAFB;
      --text-primary: #2D3748;
      --text-secondary: #718096;
      --border-color-theme: #E2E8F0;
      --header-bg: rgba(255, 255, 255, 0.85);
      --form-bg: #FFFFFF;
      --input-bg: #F9FAFB;
      --shadow-color: rgba(0, 0, 0, 0.05);
      --overlay-bg: rgba(0, 0, 0, 0.8);
      /* Header height for calculations */
      --header-height: 70px;
    }

    /* Dark theme class applied to html element */
    html.dark {
      --bg-primary: #0F172A;
      --bg-secondary: #1E293B;
      --bg-tertiary: #1E293B;
      --text-primary: #F1F5F9;
      --text-secondary: #94A3B8;
      --border-color-theme: #334155;
      --header-bg: rgba(15, 23, 42, 0.9);
      --form-bg: #1E293B;
      --input-bg: #0F172A;
      --shadow-color: rgba(0, 0, 0, 0.3);
      --overlay-bg: rgba(0, 0, 0, 0.95);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: var(--bg-primary);
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
      color: var(--text-primary);
      transition: background 0.3s ease, color 0.3s ease;
    }

    /* Floating background dots */
    .bg-dots {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
      opacity: 0.4;
    }

    .dot {
      position: absolute;
      background: var(--primary-color);
      border-radius: 50%;
      animation: float 20s infinite;
      opacity: 0.15;
    }

    html.dark .dot {
      opacity: 0.08;
      background: var(--secondary-color);
    }

    .dot:nth-child(1) { width: 8px; height: 8px; top: 10%; left: 10%; animation-delay: 0s; }
    .dot:nth-child(2) { width: 6px; height: 6px; top: 20%; left: 80%; animation-delay: 2s; }
    .dot:nth-child(3) { width: 10px; height: 10px; top: 50%; left: 20%; animation-delay: 4s; }
    .dot:nth-child(4) { width: 5px; height: 5px; top: 70%; left: 60%; animation-delay: 6s; }
    .dot:nth-child(5) { width: 7px; height: 7px; top: 30%; left: 40%; animation-delay: 8s; }
    .dot:nth-child(6) { width: 9px; height: 9px; top: 80%; left: 15%; animation-delay: 10s; }
    .dot:nth-child(7) { width: 4px; height: 4px; top: 15%; left: 55%; animation-delay: 12s; }
    .dot:nth-child(8) { width: 6px; height: 6px; top: 60%; left: 85%; animation-delay: 14s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) translateX(0) scale(1); }
      25% { transform: translateY(-30px) translateX(20px) scale(1.2); }
      50% { transform: translateY(-15px) translateX(-15px) scale(0.9); }
      75% { transform: translateY(-40px) translateX(10px) scale(1.1); }
    }

    /* Header */
    .header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 100;
      background: var(--header-bg);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border-bottom: 1px solid rgba(18, 140, 126, 0.1);
      box-shadow: 0 2px 20px var(--shadow-color);
      transition: all 0.3s ease;
      height: var(--header-height);
    }

    .nav {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .nav_logo {
      font-size: 24px;
      font-weight: 700;
      text-decoration: none;
      color: var(--text-primary);
      letter-spacing: -0.5px;
    }

    .nav_logo span {
      background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Dark Mode Toggle */
    .dark-toggle {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      border: 2px solid var(--border-color-theme);
      background: var(--bg-secondary);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      transition: all 0.3s ease;
      color: var(--text-primary);
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }

    .dark-toggle:hover {
      border-color: var(--primary-color);
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(18, 140, 126, 0.2);
    }

    .dark-toggle i {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: absolute;
    }

    .dark-toggle .fa-moon {
      opacity: 1;
      transform: rotate(0deg) scale(1);
    }

    .dark-toggle .fa-sun {
      opacity: 0;
      transform: rotate(90deg) scale(0.5);
    }

    html.dark .dark-toggle .fa-moon {
      opacity: 0;
      transform: rotate(-90deg) scale(0.5);
    }

    html.dark .dark-toggle .fa-sun {
      opacity: 1;
      transform: rotate(0deg) scale(1);
      color: var(--accent-gold);
    }

    .btn {
      padding: 8px 18px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
    }

    .btn-outline:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(18, 140, 126, 0.25);
    }

    /* Main Content */
    .home {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: calc(var(--header-height) + 30px) 20px 40px;
      z-index: 1;
    }

    .home::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(18, 140, 126, 0.03) 0%, rgba(37, 211, 102, 0.05) 100%);
      z-index: -1;
    }

    html.dark .home::before {
      background: linear-gradient(135deg, rgba(18, 140, 126, 0.05) 0%, rgba(37, 211, 102, 0.03) 100%);
    }

    /* Welcome Overlay */
    .welcome-overlay {
      text-align: center;
      max-width: 600px;
      animation: fadeInUp 0.8s ease;
      padding-top: 20px;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .welcome-icon {
      font-size: 48px;
      margin-bottom: 12px;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .welcome-title {
      font-size: 34px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 10px;
      line-height: 1.2;
    }

    .welcome-title span {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .welcome-subtitle {
      font-size: 15px;
      color: var(--text-secondary);
      margin-bottom: 20px;
      line-height: 1.6;
      padding: 0 10px;
    }

    .welcome-features {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-secondary);
      padding: 10px 18px;
      border-radius: 12px;
      box-shadow: 0 4px 15px var(--shadow-color);
      border: 1px solid var(--border-color-theme);
      transition: all 0.3s ease;
    }

    .feature-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(18, 140, 126, 0.15);
      border-color: var(--primary-color);
    }

    .feature-item i {
      font-size: 20px;
      color: var(--secondary-color);
    }

    .feature-item span {
      font-weight: 500;
      color: var(--text-primary);
      font-size: 14px;
    }

    /* Form Container */
    .form_container {
      position: fixed;
      max-width: 440px;
      width: calc(100% - 32px);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0);
      background: var(--form-bg);
      border-radius: 20px;
      box-shadow: 0 25px 60px var(--shadow-color);
      z-index: 1000;
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid var(--border-color-theme);
      max-height: calc(100vh - 32px);
      overflow-y: auto;
    }

    .home.show .form_container {
      transform: translate(-50%, -50%) scale(1);
    }

    .form_close {
      position: absolute;
      top: 12px;
      right: 12px;
      font-size: 20px;
      cursor: pointer;
      color: var(--text-secondary);
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
      z-index: 10;
      background: var(--bg-tertiary);
    }

    .form_close:hover {
      background: #fee2e2;
      color: #ef4444;
      transform: rotate(90deg);
    }

    .form {
      padding: 28px 24px;
    }

    .signup_form {
      display: none;
    }

    .form_container.active .login_form {
      display: none;
    }

    .form_container.active .signup_form {
      display: block;
    }

    .form-header {
      text-align: center;
      margin-bottom: 18px;
      padding-top: 8px;
    }

    .form-logo {
      font-size: 38px;
      margin-bottom: 8px;
    }

    .form-header h2 {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 3px;
    }

    .form-subtitle {
      color: var(--text-secondary);
      font-size: 12px;
    }

    /* Social Login */
    .social-login {
      display: flex;
      gap: 6px;
      margin-bottom: 14px;
    }

    .social-btn {
      flex: 1;
      padding: 9px 6px;
      border-radius: 9px;
      border: 1.5px solid var(--border-color-theme);
      background: var(--bg-secondary);
      cursor: pointer;
      font-weight: 500;
      font-size: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: all 0.3s ease;
      color: var(--text-primary);
    }

    .social-btn:hover {
      border-color: var(--primary-color);
      background: rgba(18, 140, 126, 0.05);
      transform: translateY(-2px);
    }

    .social-btn.google:hover { border-color: #ea4335; background: rgba(234, 67, 53, 0.05); }
    .social-btn.facebook:hover { border-color: #1877f2; background: rgba(24, 119, 242, 0.05); }
    .social-btn.github:hover { border-color: #333; background: rgba(51, 51, 51, 0.05); }

    .brand-icon {
      font-size: 13px;
    }

    .fa-google { color: #ea4335; }
    .fa-facebook-f { color: #1877f2; }
    .fa-github { color: #333; }

    html.dark .fa-github { color: #fff; }

    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 12px 0;
      color: var(--text-secondary);
      font-size: 11px;
    }

    .divider-line {
      flex: 1;
      height: 1px;
      background: var(--border-color-theme);
    }

    .divider-text {
      white-space: nowrap;
    }

    /* Input Box */
    .input_box {
      margin-bottom: 10px;
    }

    .input_box label {
      display: block;
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 4px;
      font-size: 12px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 10px;
      color: var(--text-secondary);
      font-size: 15px;
      z-index: 1;
    }

    .input-wrapper input {
      width: 100%;
      padding: 10px 10px 10px 34px;
      border: 2px solid var(--border-color-theme);
      border-radius: 9px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
      background: var(--input-bg);
      color: var(--text-primary);
    }

    .input-wrapper input:focus {
      outline: none;
      border-color: var(--primary-color);
      background: var(--bg-secondary);
      box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.08);
    }

    .input-wrapper input.error {
      border-color: #ef4444;
      background: rgba(239, 68, 68, 0.05);
    }

    .input-wrapper input.success {
      border-color: var(--secondary-color);
    }

    .pw_hide {
      position: absolute;
      right: 10px;
      cursor: pointer;
      color: var(--text-secondary);
      font-size: 15px;
      transition: color 0.3s;
    }

    .pw_hide:hover {
      color: var(--primary-color);
    }

    .error-message {
      color: #ef4444;
      font-size: 10px;
      margin-top: 2px;
      min-height: 14px;
      font-weight: 500;
    }

    .message {
      padding: 8px 12px;
      border-radius: 7px;
      margin-bottom: 10px;
      font-size: 11px;
      font-weight: 500;
      display: none;
    }

    .message.error-message-top {
      display: block;
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .message.success-message-top {
      display: block;
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    /* Password Strength */
    .password-strength {
      margin-top: 5px;
    }

    .strength-bar {
      height: 3px;
      background: var(--border-color-theme);
      border-radius: 10px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
      border-radius: 10px;
    }

    .strength-text {
      font-size: 10px;
      margin-top: 2px;
      font-weight: 500;
      min-height: 12px;
    }

    .form-row {
      display: flex;
      gap: 10px;
    }

    .form-row .input_box {
      flex: 1;
    }

    /* Checkbox */
    .option_field {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
    }

    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      font-size: 12px;
      color: var(--text-primary);
      position: relative;
    }

    .checkbox-wrapper input {
      display: none;
    }

    .custom-checkbox {
      width: 16px;
      height: 16px;
      border: 2px solid var(--border-color-theme);
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      background: var(--bg-secondary);
    }

    .checkbox-wrapper input:checked + .custom-checkbox {
      background: var(--primary-color);
      border-color: var(--primary-color);
    }

    .checkbox-wrapper input:checked + .custom-checkbox i {
      color: white;
      font-size: 9px;
    }

    .custom-checkbox i {
      color: transparent;
      font-size: 9px;
      transition: color 0.3s;
    }

    .checkbox-label {
      color: var(--text-primary);
      font-size: 12px;
    }

    .checkbox-label a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
    }

    .forgot_pw {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 12px;
      font-weight: 500;
    }

    .forgot_pw:hover {
      text-decoration: underline;
    }

    /* Button */
    .button {
      width: 100%;
      padding: 11px;
      background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
      color: white;
      border: none;
      border-radius: 9px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      font-family: 'Poppins', sans-serif;
      margin-bottom: 14px;
    }

    .button:hover {
      background: linear-gradient(135deg, #0a6b5e, #0e7d6e);
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(18, 140, 126, 0.3);
    }

    .button:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    .login_signup {
      text-align: center;
      font-size: 12px;
      color: var(--text-secondary);
    }

    .login_signup a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
    }

    .login_signup a:hover {
      text-decoration: underline;
    }

    /* Success Overlay */
    .login-success-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--overlay-bg);
      z-index: 2000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
    }

    .login-success-overlay.active {
      opacity: 1;
      pointer-events: all;
    }

    #particleCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .success-circle {
      position: relative;
      z-index: 1;
      margin-bottom: 20px;
    }

    .success-ring {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      border: 3px solid var(--secondary-color);
      animation: ringPulse 1.5s ease-out infinite;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    @keyframes ringPulse {
      0% { width: 80px; height: 80px; opacity: 1; }
      100% { width: 180px; height: 180px; opacity: 0; }
    }

    .checkmark-container {
      position: relative;
      z-index: 2;
      width: 80px;
      height: 80px;
    }

    .checkmark-circle {
      stroke: var(--secondary-color);
      stroke-width: 3;
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards 0.3s;
    }

    .checkmark-check {
      stroke: var(--secondary-color);
      stroke-width: 3;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) forwards 0.8s;
    }

    @keyframes stroke {
      to { stroke-dashoffset: 0; }
    }

    .success-text-container {
      text-align: center;
      position: relative;
      z-index: 1;
      margin-bottom: 20px;
    }

    .success-title {
      color: white;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .success-subtitle {
      color: rgba(255, 255, 255, 0.7);
      font-size: 13px;
    }

    .loading-dots {
      display: flex;
      gap: 7px;
      position: relative;
      z-index: 1;
    }

    .loading-dot {
      width: 9px;
      height: 9px;
      background: var(--secondary-color);
      border-radius: 50%;
      animation: dotBounce 1.4s infinite ease-in-out both;
    }

    .loading-dot:nth-child(1) { animation-delay: -0.32s; }
    .loading-dot:nth-child(2) { animation-delay: -0.16s; }

    @keyframes dotBounce {
      0%, 80%, 100% { transform: scale(0); }
      40% { transform: scale(1); }
    }

    /* Blocked Overlay */
    .blocked-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 3000;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(5px);
      padding: 20px;
    }

    .blocked-modal {
      background: var(--form-bg);
      border-radius: 20px;
      padding: 28px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    }

    .blocked-icon {
      font-size: 44px;
      text-align: center;
      margin-bottom: 12px;
    }

    .blocked-title {
      font-size: 20px;
      font-weight: 700;
      text-align: center;
      color: #ef4444;
      margin-bottom: 6px;
    }

    .blocked-subtitle {
      text-align: center;
      color: var(--text-secondary);
      margin-bottom: 18px;
      line-height: 1.5;
      font-size: 13px;
    }

    .blocked-details {
      background: var(--bg-tertiary);
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 14px;
    }

    .blocked-detail-item {
      display: flex;
      gap: 8px;
      margin-bottom: 10px;
    }

    .blocked-detail-icon {
      font-size: 16px;
      color: var(--primary-color);
      min-width: 20px;
      text-align: center;
    }

    .blocked-detail-label {
      font-size: 10px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .blocked-detail-value {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 12px;
    }

    .blocked-appeal-message {
      background: rgba(234, 179, 8, 0.1);
      border: 1px solid rgba(234, 179, 8, 0.3);
      border-radius: 9px;
      padding: 10px;
      font-size: 11px;
      color: #eab308;
      margin-bottom: 14px;
      display: flex;
      gap: 7px;
      align-items: flex-start;
    }

    html.dark .blocked-appeal-message {
      color: #fbbf24;
    }

    .blocked-actions {
      display: flex;
      gap: 7px;
    }

    .blocked-action-btn {
      flex: 1;
      padding: 9px;
      border-radius: 9px;
      font-weight: 600;
      font-size: 12px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    .blocked-action-btn.primary {
      background: var(--primary-color);
      color: white;
    }

    .blocked-action-btn.primary:hover {
      background: var(--primary-dark);
    }

    .blocked-action-btn.secondary {
      background: var(--bg-tertiary);
      color: var(--text-primary);
      border: 1px solid var(--border-color-theme);
    }

    .blocked-action-btn.secondary:hover {
      background: var(--border-color-theme);
    }

    /* Shake animation */
    .shake {
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      50% { transform: translateX(10px); }
      75% { transform: translateX(-5px); }
    }

    /* ============================================
       RESPONSIVE STYLES - MOBILE OPTIMIZED
       ============================================ */
    @media (max-width: 768px) {
      :root {
        --header-height: 60px;
      }

      /* Header adjustments */
      .nav {
        padding: 0 14px;
      }
      
      .nav_logo {
        font-size: 20px;
      }

      .btn {
        padding: 7px 14px;
        font-size: 12px;
        gap: 5px;
      }

      .dark-toggle {
        width: 34px;
        height: 34px;
        font-size: 14px;
      }

      /* Main content - extra top padding to clear header */
      .home {
        padding: calc(var(--header-height) + 150px) 16px 30px;
        align-items: flex-start;
      }

      /* Welcome overlay - pushed down from header */
      .welcome-overlay {
        padding-top: 10px;
      }

      .welcome-icon {
        font-size: 40px;
        margin-bottom: 10px;
      }

      .welcome-title {
        font-size: 26px;
        margin-bottom: 8px;
      }

      .welcome-subtitle {
        font-size: 13px;
        margin-bottom: 16px;
        padding: 0 5px;
      }

      .welcome-features {
        gap: 8px;
      }

      .feature-item {
        padding: 8px 14px;
        font-size: 13px;
      }

      .feature-item i {
        font-size: 18px;
      }

      .feature-item span {
        font-size: 12px;
      }

      /* Form container - mobile positioning */
      .form_container {
        max-width: 100%;
        width: calc(100% - 10px);
        border-radius: 18px;
        max-height: calc(100vh - 150px);
        top: 56%;
      }

      .form {
        padding: 50px 25px;
      }

      .form-header {
        margin-bottom: 5px;
        padding-top: 4px;
      }

      .form-logo {
        font-size: 32px;
        margin-bottom: 6px;
      }

      .form-header h2 {
        font-size: 21px;
      }

      .form-subtitle {
        font-size: 11px;
      }

      /* Stack social buttons on mobile */
      .social-login {
        flex-direction: column;
        gap: 5px;
      }

      .social-btn {
        padding: 8px;
        font-size: 12px;
      }

      /* Form rows stack vertically */
      .form-row {
        flex-direction: column;
        gap: 0;
      }

      /* Reduce spacing */
      .input_box {
        margin-bottom: 8px;
      }

      .input-wrapper input {
        padding: 10px 10px 10px 32px;
        font-size: 14px; /* Larger font for mobile readability */
      }

      .button {
        padding: 12px;
        font-size: 15px;
        margin-bottom: 12px;
      }

      .option_field {
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
      }

      .forgot_pw {
        font-size: 11px;
      }

      /* Form close button */
      .form_close {
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
        font-size: 18px;
      }

      /* Success overlay mobile */
      .success-title {
        font-size: 24px;
      }

      .success-subtitle {
        font-size: 12px;
      }

      .success-ring {
        width: 110px;
        height: 110px;
      }

      @keyframes ringPulse {
        0% { width: 70px; height: 70px; opacity: 1; }
        100% { width: 160px; height: 160px; opacity: 0; }
      }

      /* Blocked modal */
      .blocked-modal {
        padding: 22px 18px;
      }

      .blocked-icon {
        font-size: 36px;
      }

      .blocked-title {
        font-size: 18px;
      }

      .blocked-actions {
        flex-direction: column;
      }
    }

    /* Very small screens */
    @media (max-width: 380px) {
      :root {
        --header-height: 56px;
      }

      .nav_logo {
        font-size: 18px;
      }

      .btn {
        padding: 6px 12px;
        font-size: 11px;
        gap: 4px;
      }

      .dark-toggle {
        width: 30px;
        height: 30px;
        font-size: 13px;
      }

      .home {
        padding: calc(var(--header-height) + 35px) 10px 20px;
      }

      .welcome-title {
        font-size: 22px;
      }

      .welcome-icon {
        font-size: 34px;
      }

      .form {
        padding: 18px 12px;
      }

      .form-header h2 {
        font-size: 19px;
      }

      .input-wrapper input {
        padding: 9px 9px 9px 30px;
        font-size: 13px;
      }
    }

    /* Landscape mode on mobile */
    @media (max-width: 768px) and (orientation: landscape) {
      .home {
        padding: calc(var(--header-height) + 20px) 16px 20px;
        min-height: auto;
      }

      .welcome-overlay {
        padding-top: 5px;
      }

      .welcome-icon {
        font-size: 30px;
        margin-bottom: 6px;
      }

      .welcome-title {
        font-size: 22px;
        margin-bottom: 4px;
      }

      .welcome-subtitle {
        font-size: 11px;
        margin-bottom: 10px;
      }

      .welcome-features {
        gap: 6px;
      }

      .feature-item {
        padding: 6px 10px;
      }

      .form_container {
        max-height: calc(100vh - 40px);
      }
    }
  </style>
</head>

<body>
  <!-- Floating background dots -->
  <div class="bg-dots">
    <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
    <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
  </div>

  <!-- Header -->
  <header class="header">
    <nav class="nav">
      <a href="#" class="nav_logo">BISure<span>Chat</span></a>
      <div class="nav-actions">
        <!-- Dark Mode Toggle -->
        <button class="dark-toggle" id="darkModeToggle" title="Toggle dark mode">
          <i class="fas fa-moon"></i>
          <i class="fas fa-sun"></i>
        </button>
        <button class="btn btn-outline" id="form-open">
          <i class="uil uil-sign-in-alt"></i> Sign In
        </button>
      </div>
    </nav>
  </header>

  <!-- Main Content -->
  <section class="home">
    <!-- Welcome Screen -->
    <div class="welcome-overlay" id="welcomeOverlay">
      <div class="welcome-icon">💬</div>
      <h1 class="welcome-title">Welcome to <span>BISureChat</span></h1>
      <p class="welcome-subtitle">
        Connect with friends, family, and colleagues through secure, lightning-fast messaging.
      </p>
      <div class="welcome-features">
        <div class="feature-item">
          <i class="uil uil-shield-check"></i>
          <span>Secure Messaging</span>
        </div>
        <div class="feature-item">
          <i class="uil uil-rocket"></i>
          <span>Lightning Fast</span>
        </div>
        <div class="feature-item">
          <i class="uil uil-cloud-share"></i>
          <span>Cloud Sync</span>
        </div>
      </div>
    </div>

    <!-- Form Container -->
    <div class="form_container">
      <i class="uil uil-times form_close"></i>

      <!-- ============================================
           LOGIN FORM
           ============================================ -->
      <div class="form login_form">
        <form id="loginForm" novalidate>
          <div class="form-header">
            <div class="form-logo">🔐</div>
            <h2>Welcome Back</h2>
            <p class="form-subtitle">Sign in to continue your conversations</p>
          </div>

          <!-- Social Login -->
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
            <span class="divider-text">or continue with email</span>
            <span class="divider-line"></span>
          </div>

          <div class="message" id="login-message"></div>

          <div class="input_box">
            <label>Username, Email or Phone</label>
            <div class="input-wrapper">
              <i class="uil uil-user input-icon"></i>
              <input type="text" name="user" placeholder="Enter your username, email or phone" autocomplete="username" />
            </div>
            <div class="error-message" id="login-user-error"></div>
          </div>

          <div class="input_box">
            <label>Password</label>
            <div class="input-wrapper">
              <i class="uil uil-lock input-icon"></i>
              <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" />
              <i class="uil uil-eye-slash pw_hide"></i>
            </div>
            <div class="error-message" id="login-password-error"></div>
          </div>

          <div class="option_field">
            <label class="checkbox-wrapper">
              <input type="checkbox" id="check" name="remember" />
              <span class="custom-checkbox"><i class="fas fa-check"></i></span>
              <span class="checkbox-label">Remember me</span>
            </label>
            <a href="../auth/forgot/forgot_password" class="forgot_pw">Forgot password?</a>
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
          <div class="form-header">
            <div class="form-logo">🚀</div>
            <h2>Create Account</h2>
            <p class="form-subtitle">Join the community and start connecting</p>
          </div>

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
              <label>Full Name</label>
              <div class="input-wrapper">
                <i class="uil uil-user input-icon"></i>
                <input type="text" name="fullname" placeholder="Isamel Bisuretech" />
              </div>
              <div class="error-message" id="error-fullname"></div>
            </div>
            <div class="input_box">
              <label>Phone Number</label>
              <div class="input-wrapper">
                <i class="uil uil-phone input-icon"></i>
                <input type="tel" name="phone" placeholder="0712345678" />
              </div>
              <div class="error-message" id="error-phone"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="input_box">
              <label>Username</label>
              <div class="input-wrapper">
                <i class="uil uil-at input-icon"></i>
                <input type="text" name="username" placeholder="bisuretech" />
              </div>
              <div class="error-message" id="error-username"></div>
            </div>
            <div class="input_box">
              <label>Email Address</label>
              <div class="input-wrapper">
                <i class="uil uil-envelope-alt input-icon"></i>
                <input type="email" name="email" placeholder="bisure@bisuretech.com" />
              </div>
              <div class="error-message" id="error-email"></div>
            </div>
          </div>

          <div class="form-row">
            <div class="input_box">
              <label>Password</label>
              <div class="input-wrapper">
                <i class="uil uil-lock input-icon"></i>
                <input type="password" name="password" placeholder="Min 8 characters" id="signupPassword" />
                <i class="uil uil-eye-slash pw_hide"></i>
              </div>
              <div class="password-strength">
                <div class="strength-bar">
                  <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
              </div>
              <div class="error-message" id="error-password"></div>
            </div>
            <div class="input_box">
              <label>Confirm Password</label>
              <div class="input-wrapper">
                <i class="uil uil-lock input-icon"></i>
                <input type="password" name="confirm_password" placeholder="Re-enter password" />
                <i class="uil uil-eye-slash pw_hide"></i>
              </div>
              <div class="error-message" id="error-confirm_password"></div>
            </div>
          </div>

          <label class="checkbox-wrapper" style="margin-bottom: 8px;">
            <input type="checkbox" name="terms" />
            <span class="custom-checkbox"><i class="fas fa-check"></i></span>
            <span class="checkbox-label">I agree to the <a href="#">Terms</a> & <a href="#">Privacy Policy</a></span>
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

    <!-- Login Success Overlay -->
    <div class="login-success-overlay" id="loginSuccessOverlay">
      <canvas id="particleCanvas"></canvas>
      <div class="success-circle">
        <div class="success-ring"></div>
        <div class="checkmark-container">
          <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 52 52">
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
    // DARK MODE TOGGLE
    // ============================================
    const darkToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;
    
    // Check for saved dark mode preference
    if (localStorage.getItem('darkMode') === 'true') {
      htmlElement.classList.add('dark');
    }
    
    darkToggle.addEventListener('click', () => {
      htmlElement.classList.toggle('dark');
      localStorage.setItem('darkMode', htmlElement.classList.contains('dark'));
    });

    // ============================================
    // GLOBAL STATE
    // ============================================
    const formOpenBtn = document.querySelector("#form-open"),
      formCloseBtn = document.querySelector(".form_close"),
      home = document.querySelector(".home"),
      welcomeOverlay = document.getElementById("welcomeOverlay"),
      formContainer = document.querySelector(".form_container"),
      signupBtn = document.querySelector("#signup"),
      loginBtn = document.querySelector("#login"),
      loginSuccessOverlay = document.getElementById("loginSuccessOverlay");

    let socialLoginTimers = {};
    let socialLoginActive = {};
    let particleAnimationFrame;

    // ============================================
    // UI TOGGLE
    // ============================================
    formOpenBtn.onclick = () => {
      home.classList.add("show");
      welcomeOverlay.style.display = "none";
    };
    
    formCloseBtn.onclick = () => {
      home.classList.remove("show");
      welcomeOverlay.style.display = "block";
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
    function socialLogin(provider, btnId) {
      const btn = document.getElementById(btnId);
      if (!btn || socialLoginActive[btnId]) return;

      socialLoginActive[btnId] = true;
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Connecting... <small style="font-size:0.7rem;opacity:0.7;">(click to cancel)</small>';
      btn.disabled = true;
      btn.style.cursor = 'pointer';
      
      const cancelHandler = function(e) {
        e.preventDefault();
        e.stopPropagation();
        cancelSocialLogin(btnId, btn, originalHTML);
        btn.removeEventListener('click', cancelHandler);
      };
      
      btn.addEventListener('click', cancelHandler, { once: false });

      socialLoginTimers[btnId] = setTimeout(() => {
        btn.removeEventListener('click', cancelHandler);
        const redirectUrls = {
          googleLoginBtn: '#content/social_login.php?provider=google',
          facebookLoginBtn: '#content/social_login.php?provider=facebook',
          githubLoginBtn: '#content/social_login.php?provider=github',
          googleSignupBtn: '#content/social_login.php?provider=google&action=signup',
          facebookSignupBtn: '#content/social_login.php?provider=facebook&action=signup',
          githubSignupBtn: '#content/social_login.php?provider=github&action=signup',
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
    ['googleLoginBtn','facebookLoginBtn','githubLoginBtn','googleSignupBtn','facebookSignupBtn','githubSignupBtn'].forEach(id => {
      const btn = document.getElementById(id);
      if (btn) {
        btn.addEventListener('click', function() {
          if (!socialLoginActive[id]) socialLogin(id.includes('google') ? 'google' : id.includes('facebook') ? 'facebook' : 'github', id);
        });
      }
    });

    // ============================================
    // PASSWORD TOGGLE
    // ============================================
    document.querySelectorAll(".pw_hide").forEach(icon => {
      icon.addEventListener("click", () => {
        const input = icon.closest('.input-wrapper').querySelector("input");
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
    // PASSWORD STRENGTH METER
    // ============================================
    const signupPasswordInput = document.getElementById('signupPassword');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    if (signupPasswordInput) {
      signupPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        
        const strengthLevels = [
          { width: '0%', color: '#E2E8F0', text: '' },
          { width: '25%', color: '#EF4444', text: 'Weak' },
          { width: '50%', color: '#F59E0B', text: 'Fair' },
          { width: '75%', color: '#3B82F6', text: 'Good' },
          { width: '100%', color: '#10B981', text: 'Strong' }
        ];
        
        const level = strengthLevels[strength];
        strengthFill.style.width = level.width;
        strengthFill.style.background = level.color;
        strengthText.textContent = level.text;
        strengthText.style.color = level.color;
      });
    }

    // ============================================
    // HELPERS
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

    function setFieldError(form, field, message) {
      const errEl = document.getElementById(form === 'login' ? `login-${field}-error` : `error-${field}`);
      if (errEl) errEl.innerText = message;
      const inputEl = document.querySelector(`input[name="${field}"]`);
      if (inputEl) inputEl.classList.add('error');
    }

    // ============================================
    // BLOCKED MESSAGE FUNCTIONS
    // ============================================
    function showBlockedMessage(blockDetails) {
      const existingOverlay = document.querySelector('.blocked-overlay');
      if (existingOverlay) existingOverlay.remove();

      const overlay = document.createElement('div');
      overlay.className = 'blocked-overlay';
      
      const blockedDate = blockDetails.date || blockDetails.blocked_date || 'Unknown date';
      const blockedBy = blockDetails.blocked_by || 'Administrator';
      const blockedByUsername = blockDetails.blocked_by_username || 'admin';
      const reason = blockDetails.reason || 'No specific reason provided';
      const appealMsg = blockDetails.appeal_info || 'If you believe this is a mistake, please contact our support team to appeal this decision.';
      const supportEmail = blockDetails.support_email || 'byaruhangaisamelk@gmail.com';
      
      overlay.innerHTML = `
        <div class="blocked-modal">
          <div class="blocked-message-container">
            <div class="blocked-icon">🚫</div>
            <h3 class="blocked-title">Account Blocked</h3>
            <p class="blocked-subtitle">
              Your account has been suspended and you are unable to access the platform at this time.
            </p>
            
            <div class="blocked-details">
              <div class="blocked-detail-item">
                <div class="blocked-detail-icon">
                  <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="blocked-detail-content">
                  <div class="blocked-detail-label">Reason for Block</div>
                  <div class="blocked-detail-value">${reason}</div>
                </div>
              </div>
              
              <div class="blocked-detail-item">
                <div class="blocked-detail-icon">
                  <i class="fas fa-calendar-times"></i>
                </div>
                <div class="blocked-detail-content">
                  <div class="blocked-detail-label">Blocked On</div>
                  <div class="blocked-detail-value">${blockedDate}</div>
                </div>
              </div>
              
              <div class="blocked-detail-item">
                <div class="blocked-detail-icon">
                  <i class="fas fa-user-shield"></i>
                </div>
                <div class="blocked-detail-content">
                  <div class="blocked-detail-label">Blocked By</div>
                  <div class="blocked-detail-value">${blockedBy} (@${blockedByUsername})</div>
                </div>
              </div>
            </div>
            
            <div class="blocked-appeal-message">
              <i class="fas fa-info-circle"></i>
              ${appealMsg}
            </div>
            
            <div class="blocked-actions">
              <a href="mailto:${supportEmail}" class="blocked-action-btn primary">
                <i class="fas fa-envelope"></i> Appeal Decision
              </a>
              <button class="blocked-action-btn secondary" onclick="closeBlockedMessage()">
                <i class="fas fa-times"></i> Close
              </button>
            </div>
          </div>
        </div>
      `;

      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeBlockedMessage();
      });

      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';
    }

    function closeBlockedMessage() {
      const overlay = document.querySelector('.blocked-overlay');
      if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
          overlay.remove();
          document.body.style.overflow = '';
        }, 300);
      }
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeBlockedMessage();
    });

    // ============================================
    // PARTICLE ANIMATION
    // ============================================
    const canvas = document.getElementById('particleCanvas');
    const ctx = canvas.getContext('2d');
    let particles = [];

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
        this.size = Math.random() * 2.5 + 0.5;
        this.speedX = (Math.random() - 0.5) * 0.6;
        this.speedY = (Math.random() - 0.5) * 0.6;
        this.opacity = Math.random() * 0.5 + 0.1;
        const colors = [
          'rgba(37, 211, 102, o)',
          'rgba(18, 140, 126, o)',
          'rgba(212, 175, 55, o)',
          'rgba(59, 130, 246, o)',
          'rgba(244, 114, 182, o)',
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

    function initParticles(count = 80) {
      particles = [];
      for (let i = 0; i < count; i++) particles.push(new Particle());
    }

    function connectParticles() {
      const maxDistance = 150;
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          if (distance < maxDistance) {
            const opacity = (1 - distance / maxDistance) * 0.12;
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
      particleAnimationFrame = requestAnimationFrame(animateParticles);
    }

    function startParticleAnimation() { 
      initParticles(); 
      animateParticles(); 
    }
    
    function stopParticleAnimation() {
      if (particleAnimationFrame) cancelAnimationFrame(particleAnimationFrame);
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
    // LOGIN FORM SUBMIT
    // ============================================
    document.getElementById("loginForm").onsubmit = function(e) {
      e.preventDefault();
      clearErrors(this);
      const form = this;
      let hasErrors = false;

      if (!form.user.value.trim()) {
        setFieldError('login', 'user', 'Please enter your username, email, or phone');
        hasErrors = true;
      }
      if (!form.password.value.trim()) {
        setFieldError('login', 'password', 'Please enter your password');
        hasErrors = true;
      }
      if (hasErrors) {
        shakeElements([...form.querySelectorAll(".error-message:not(:empty)")]);
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHTML = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Signing in...';
      submitBtn.disabled = true;

      const formData = new FormData(form);
      formData.append('remember', form.check.checked ? 'on' : 'off');

      fetch('content/bisure_login_content.php', {
        method: 'POST', 
        body: formData, 
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.ok ? r.json() : Promise.reject('Server error'))
      .then(data => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        clearErrors(form);
        
        const msg = document.getElementById("login-message");
        msg.innerText = '';
        msg.className = 'message';

        if (data.success) {
          showLoginSuccessAnimation();
          setTimeout(() => { 
            window.location.href = data.redirect || '../chat/contacts'; 
          }, 3000);
          
        } else if (data.blocked) {
          showBlockedMessage(data.block_details || {
            reason: data.block_reason || 'No reason provided',
            date: data.block_date || 'Unknown',
            blocked_by: data.blocked_by || 'Administrator',
            blocked_by_username: data.blocked_by_username || 'admin',
            appeal_info: data.block_details?.appeal_info || 'Please contact support to appeal this decision.'
          });
          
        } else {
          if (data.errors) {
            for (let field in data.errors) {
              setFieldError('login', field, data.errors[field]);
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
        const msg = document.getElementById("login-message");
        msg.innerText = "⚠ Network error. Please check your connection and try again.";
        msg.classList.add('error-message-top');
      });
    };

    // ============================================
    // SIGNUP FORM SUBMIT
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

      if (!f.fullname) { setFieldError('signup', 'fullname', 'Full name is required'); hasErrors = true; }
      if (!f.phone) { setFieldError('signup', 'phone', 'Phone number is required'); hasErrors = true; }
      else if (!isValidPhone(f.phone)) { setFieldError('signup', 'phone', 'Valid 10-15 digit number required'); hasErrors = true; }
      if (!f.username) { setFieldError('signup', 'username', 'Username is required'); hasErrors = true; }
      else if (f.username.length < 3) { setFieldError('signup', 'username', 'At least 3 characters required'); hasErrors = true; }
      if (!f.email) { setFieldError('signup', 'email', 'Email address is required'); hasErrors = true; }
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email)) { setFieldError('signup', 'email', 'Valid email address required'); hasErrors = true; }
      if (!f.password) { setFieldError('signup', 'password', 'Password is required'); hasErrors = true; }
      else if (f.password.length < 8) { setFieldError('signup', 'password', 'At least 8 characters required'); hasErrors = true; }
      if (!f.confirm_password) { setFieldError('signup', 'confirm_password', 'Please confirm your password'); hasErrors = true; }
      else if (f.password !== f.confirm_password) { setFieldError('signup', 'confirm_password', 'Passwords do not match'); hasErrors = true; }
      if (!f.terms) {
        document.getElementById("signup-message").innerText = "⚠ Please agree to the Terms & Privacy Policy";
        document.getElementById("signup-message").className = "message error-message-top";
        hasErrors = true;
      }

      if (hasErrors) {
        shakeElements([...form.querySelectorAll(".error-message:not(:empty)")]);
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHTML = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Creating account...';
      submitBtn.disabled = true;

      fetch('content/bisure_register_content.php', {
        method: 'POST', 
        body: new FormData(form), 
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.ok ? r.json() : Promise.reject('Server error'))
      .then(data => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
        const msg = document.getElementById("signup-message");
        msg.innerText = ''; 
        msg.className = 'message';
        clearErrors(form);

        if (data.success) {
          msg.innerText = data.message || "✅ Account created successfully! Redirecting to login...";
          msg.classList.add('success-message-top');
          form.reset();
          if (strengthFill) strengthFill.style.width = '0%';
          if (strengthText) strengthText.textContent = '';
          setTimeout(() => {
            formContainer.classList.remove("active");
            msg.innerText = ''; 
            msg.className = 'message';
          }, 2500);
        } else {
          if (data.errors) {
            for (let field in data.errors) {
              setFieldError('signup', field, data.errors[field]);
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
        msg.innerText = "⚠ Network error. Please check your connection and try again.";
        msg.classList.add('error-message-top');
      });
    };

    // ============================================
    // INITIALIZATION
    // ============================================
    console.log('%c🔐 BISureChat %cReady',
      'font-size:1.2rem;font-weight:bold;color:#25D366;',
      'font-size:0.8rem;color:#128C7E;');
    console.log('%cSecure Login & Signup System v3.0 - Dark Mode Ready',
      'font-size:0.7rem;color:#718096;');
  </script>
</body>
</html>