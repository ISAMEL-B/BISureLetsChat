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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

  <style>
    .message,
    .error-message {
      font-size: 0.8rem;
      margin-top: 4px;
      color: red;
      text-align: center;
    }

    .success-message {
      color: green;
      text-align: center;
    }

    .shake {
      animation: shake 0.3s ease-in-out;
    }

    @keyframes shake {
      0% {
        transform: translateX(0);
      }

      25% {
        transform: translateX(-5px);
      }

      50% {
        transform: translateX(5px);
      }

      75% {
        transform: translateX(-5px);
      }

      100% {
        transform: translateX(0);
      }
    }

    .pw_hide {
      cursor: pointer;
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
    }

    .input_box {
      position: relative;
    }

    /* Loading spinner */
    .animate-spin {
      animation: spin 1s linear infinite;
      display: inline-block;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* ============================================
       PREMIUM LOGIN SUCCESS OVERLAY
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

    .login-success-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Particle canvas */
    #particleCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    /* Central circle pulse */
    .success-circle {
      position: relative;
      width: 120px;
      height: 120px;
      z-index: 2;
    }

    .success-circle::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid rgba(74, 222, 128, 0.3);
      animation: circlePulse 2s ease-out infinite;
    }

    .success-circle::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid rgba(74, 222, 128, 0.15);
      animation: circlePulse 2s ease-out 0.5s infinite;
    }

    @keyframes circlePulse {
      0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 1;
      }

      100% {
        transform: translate(-50%, -50%) scale(2.5);
        opacity: 0;
      }
    }

    /* Checkmark animation */
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
      animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards 0.2s;
    }

    .checkmark-check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      stroke: #4ade80;
      stroke-width: 3;
      animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }

    /* Success text */
    .success-text-container {
      z-index: 2;
      text-align: center;
      margin-top: 40px;
    }

    .success-title {
      color: #ffffff;
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1s forwards;
      text-shadow: 0 0 30px rgba(74, 222, 128, 0.3);
    }

    .success-subtitle {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.1rem;
      margin-top: 10px;
      opacity: 0;
      transform: translateY(20px);
      animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.2s forwards;
    }

    @keyframes slideUpFade {
      0% {
        opacity: 0;
        transform: translateY(30px);
      }

      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Loading dots */
    .loading-dots {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 30px;
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

    .loading-dot:nth-child(2) {
      animation-delay: 0.2s;
      background-color: #60a5fa;
    }

    .loading-dot:nth-child(3) {
      animation-delay: 0.4s;
      background-color: #c084fc;
    }

    @keyframes dotBounce {
      0%,
      80%,
      100% {
        transform: scale(0.6);
        opacity: 0.4;
      }

      40% {
        transform: scale(1.2);
        opacity: 1;
      }
    }

    /* Ring animation around circle */
    .success-ring {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 180px;
      height: 180px;
      border-radius: 50%;
      border: 2px solid transparent;
      border-top-color: rgba(74, 222, 128, 0.6);
      border-right-color: rgba(96, 165, 250, 0.4);
      animation: ringRotate 2s linear infinite;
      z-index: 1;
    }

    @keyframes ringRotate {
      0% {
        transform: translate(-50%, -50%) rotate(0deg);
      }

      100% {
        transform: translate(-50%, -50%) rotate(360deg);
      }
    }

    /* Decorative lines */
    .decorative-line {
      position: absolute;
      width: 1px;
      height: 80px;
      background: linear-gradient(to bottom, transparent, rgba(74, 222, 128, 0.5), transparent);
      z-index: 1;
      opacity: 0;
      animation: lineAppear 1s ease 1.5s forwards;
    }

    .decorative-line.left {
      left: 30%;
      top: 40%;
      animation: lineAppearLeft 1s ease 1.5s forwards;
    }

    .decorative-line.right {
      right: 30%;
      top: 40%;
      animation: lineAppearRight 1s ease 1.5s forwards;
    }

    @keyframes lineAppearLeft {
      0% {
        opacity: 0;
        transform: translateX(-50px);
      }

      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes lineAppearRight {
      0% {
        opacity: 0;
        transform: translateX(50px);
      }

      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Premium button styles */
    .button {
      transition: all 0.3s ease;
      background: linear-gradient(135deg, #4a6bff, #6a11cb);
      box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
    }

    .button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
    }

    .button:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* Form container animation */
    .form_container {
      transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    /* Input focus effects */
    .input_box input:focus {
      border-color: #4a6bff;
      box-shadow: 0 0 0 2px rgba(74, 107, 255, 0.2);
      outline: none;
    }

    /* Input with error */
    .input_box input.error {
      border-color: red;
      box-shadow: 0 0 0 2px rgba(255, 0, 0, 0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .success-title {
        font-size: 1.8rem;
      }
      
      .success-subtitle {
        font-size: 0.9rem;
      }
      
      .success-circle {
        width: 100px;
        height: 100px;
      }
      
      .success-ring {
        width: 150px;
        height: 150px;
      }
    }
  </style>
</head>

<body>
  <header class="header">
    <nav class="nav">
      <a href="#" class="nav_logo">BISureChat</a>
      <button class="button" id="form-open">Login</button>
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

      <!-- Login Form -->
      <div class="form login_form">
        <form id="loginForm" novalidate>
          <h2>Login</h2>
          <div class="message" id="login-message"></div>

          <div class="input_box">
            <input type="text" name="user" placeholder="Enter Username, Email, or Phone" />
            <i class="uil uil-envelope-alt email"></i>
            <div class="error-message" id="login-user-error"></div>
          </div>
          <div class="input_box">
            <input type="password" name="password" placeholder="Enter Password" />
            <i class="uil uil-lock password"></i>
            <i class="uil uil-eye-slash pw_hide"></i>
            <div class="error-message" id="login-password-error"></div>
          </div>

          <div class="option_field">
            <span class="checkbox">
              <input type="checkbox" id="check" name="remember" />
              <label for="check">Remember me</label>
            </span>
            <a href="../auth/forgot_password" class="forgot_pw">Forgot password?</a>
          </div>
          <button class="button" type="submit">Login Now</button>
          <div class="login_signup">Don't have an account? <a href="#" id="signup">Signup</a></div>
        </form>
      </div>

      <!-- Signup Form -->
      <div class="form signup_form">
        <form id="signupForm" novalidate>
          <h2>Signup</h2>
          <div class="message" id="signup-message"></div>

          <div class="input_box">
            <i class="uil uil-user name"></i>
            <input type="text" name="fullname" placeholder="Enter Full Name" />
            <div class="error-message" id="error-fullname"></div>
          </div>
          <div class="input_box">
            <i class="uil uil-phone phone"></i>
            <input type="tel" name="phone" placeholder="Enter Phone Number" />
            <div class="error-message" id="error-phone"></div>
          </div>
          <div class="input_box">
            <i class="uil uil-user username"></i>
            <input type="text" name="username" placeholder="Enter Username" />
            <div class="error-message" id="error-username"></div>
          </div>
          <div class="input_box">
            <input type="email" name="email" placeholder="Enter your email" />
            <i class="uil uil-envelope-alt email"></i>
            <div class="error-message" id="error-email"></div>
          </div>
          <div class="input_box">
            <input type="password" name="password" placeholder="Create a password" />
            <i class="uil uil-lock password"></i>
            <i class="uil uil-eye-slash pw_hide"></i>
            <div class="error-message" id="error-password"></div>
          </div>
          <div class="input_box">
            <input type="password" name="confirm_password" placeholder="Confirm password" />
            <i class="uil uil-lock password"></i>
            <i class="uil uil-eye-slash pw_hide"></i>
            <div class="error-message" id="error-confirm_password"></div>
          </div>

          <button class="button" type="submit">Signup Now</button>
          <div class="login_signup">Already have an account? <a href="#" id="login">Login</a></div>
        </form>
      </div>
    </div>

    <!-- Premium Login Success Overlay -->
    <div class="login-success-overlay" id="loginSuccessOverlay">
      <canvas id="particleCanvas"></canvas>
      
      <!-- Decorative elements -->
      <div class="decorative-line left"></div>
      <div class="decorative-line right"></div>
      
      <!-- Central animation -->
      <div class="success-circle">
        <div class="success-ring"></div>
        <div class="checkmark-container">
          <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 52 52">
            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
          </svg>
        </div>
      </div>
      
      <!-- Success text -->
      <div class="success-text-container">
        <h2 class="success-title">Welcome Back!</h2>
        <p class="success-subtitle">Preparing your secure chat experience...</p>
      </div>
      
      <!-- Loading dots -->
      <div class="loading-dots">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
      </div>
    </div>
  </section>

  <script>
    // ============================================
    // UI Toggle Functions
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
    // Password Visibility Toggle
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
    // Particle Animation System
    // ============================================
    const canvas = document.getElementById('particleCanvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationFrame;
    
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
        this.color = this.getRandomColor();
      }
      
      getRandomColor() {
        const colors = [
          'rgba(74, 222, 128, opacity)',   // Green
          'rgba(96, 165, 250, opacity)',   // Blue
          'rgba(192, 132, 252, opacity)',  // Purple
          'rgba(251, 191, 36, opacity)',   // Amber
          'rgba(244, 114, 182, opacity)',  // Pink
        ];
        return colors[Math.floor(Math.random() * colors.length)];
      }
      
      update() {
        this.x += this.speedX;
        this.y += this.speedY;
        
        // Wrap around edges
        if (this.x < 0) this.x = canvas.width;
        if (this.x > canvas.width) this.x = 0;
        if (this.y < 0) this.y = canvas.height;
        if (this.y > canvas.height) this.y = 0;
        
        // Slowly change opacity
        this.opacity += (Math.random() - 0.5) * 0.01;
        this.opacity = Math.max(0.1, Math.min(0.6, this.opacity));
      }
      
      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = this.color.replace('opacity', this.opacity);
        ctx.fill();
      }
    }
    
    // Create particles
    function initParticles(count = 80) {
      particles = [];
      for (let i = 0; i < count; i++) {
        particles.push(new Particle());
      }
    }
    
    // Connect nearby particles with lines
    function connectParticles() {
      const maxDistance = 150;
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
      
      particles.forEach(particle => {
        particle.update();
        particle.draw();
      });
      
      connectParticles();
      animationFrame = requestAnimationFrame(animateParticles);
    }
    
    // Start/Stop particle animation
    function startParticleAnimation() {
      initParticles();
      animateParticles();
    }
    
    function stopParticleAnimation() {
      if (animationFrame) {
        cancelAnimationFrame(animationFrame);
      }
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles = [];
    }

    // ============================================
    // Login Success Animation
    // ============================================
    function showLoginSuccessAnimation() {
      loginSuccessOverlay.classList.add('active');
      startParticleAnimation();
      
      // Calculate animation duration
      const totalAnimationDuration = 3000; // 3 seconds
      
      setTimeout(() => {
        hideLoginSuccessAnimation();
      }, totalAnimationDuration);
    }
    
    function hideLoginSuccessAnimation() {
      loginSuccessOverlay.classList.remove('active');
      
      // Delay stopping particles to allow fade out
      setTimeout(() => {
        stopParticleAnimation();
      }, 400);
    }

    // ============================================
    // Helper Functions
    // ============================================
    function shakeElements(elements) {
      elements.forEach(el => {
        el.classList.remove("shake");
        void el.offsetWidth;
        el.classList.add("shake");
      });
    }

    function isValidPhone(phone) {
      return /^[0-9]{10,15}$/.test(phone);
    }

    function clearErrors(form) {
      form.querySelectorAll('.error-message').forEach(el => {
        el.innerText = '';
        el.classList.remove('shake');
      });
      form.querySelectorAll('input.error').forEach(el => el.classList.remove('error'));
      
      const msg = form.querySelector('.message');
      if (msg) {
        msg.innerText = '';
        msg.classList.remove('success-message', 'error-message', 'shake');
      }
    }

    // ============================================
    // LOGIN FORM SUBMISSION
    // ============================================
    document.getElementById("loginForm").onsubmit = function(e) {
      e.preventDefault();
      clearErrors(this);

      const form = this;
      const userInput = form.user.value.trim();
      const passInput = form.password.value.trim();
      const remember = form.querySelector('#check').checked;

      let hasErrors = false;

      if (!userInput) {
        form.querySelector("#login-user-error").innerText = "Please enter your username, email, or phone.";
        form.querySelector("input[name='user']").classList.add('error');
        hasErrors = true;
      }
      if (!passInput) {
        form.querySelector("#login-password-error").innerText = "Please enter your password.";
        form.querySelector("input[name='password']").classList.add('error');
        hasErrors = true;
      }

      if (hasErrors) {
        const errorEls = form.querySelectorAll(".error-message:not(:empty)");
        shakeElements([...errorEls]);
        return;
      }

      // --- AJAX Submit ---
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerText;
      submitBtn.innerHTML = '<i class="uil uil-spinner animate-spin"></i> Logging in...';
      submitBtn.disabled = true;

      const formData = new FormData(form);
      formData.append('remember', remember ? 'on' : 'off');

      fetch('content/bisure_login_content.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Server returned ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;

          const loginMsg = form.querySelector("#login-message");
          loginMsg.innerText = "";
          loginMsg.classList.remove("success-message", "error-message");

          form.querySelector("#login-user-error").innerText = "";
          form.querySelector("#login-password-error").innerText = "";
          form.querySelectorAll('input.error').forEach(el => el.classList.remove('error'));

          if (data.success) {
            // Show premium login success animation
            showLoginSuccessAnimation();
            
            // Redirect after animation completes
            setTimeout(() => {
              window.location.href = data.redirect || '../chat/chats';
            }, 3000);
          } else {
            if (data.errors) {
              for (let field in data.errors) {
                const errEl = form.querySelector("#login-" + field + "-error");
                if (errEl) {
                  errEl.innerText = data.errors[field];
                }
                const inputEl = form.querySelector("input[name='" + field + "']");
                if (inputEl) {
                  inputEl.classList.add('error');
                }
              }
              const errorEls = form.querySelectorAll(".error-message:not(:empty)");
              if (errorEls.length > 0) {
                shakeElements([...errorEls]);
              }
            }
            if (data.message) {
              loginMsg.innerText = data.message;
              loginMsg.classList.add("error-message");
            }
          }
        })
        .catch(error => {
          console.error('Login Error:', error);
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;

          const loginMsg = form.querySelector("#login-message");
          loginMsg.innerText = "Network error. Please check your connection and try again.";
          loginMsg.classList.add("error-message", "shake");
        });
    };

    // ============================================
    // SIGNUP FORM SUBMISSION
    // ============================================
    document.getElementById("signupForm").onsubmit = function(e) {
      e.preventDefault();
      clearErrors(this);

      const form = this;
      const fullname = form.fullname.value.trim();
      const phone = form.phone.value.trim();
      const username = form.username.value.trim();
      const email = form.email.value.trim();
      const password = form.password.value;
      const confirm_password = form.confirm_password.value;

      let hasErrors = false;

      if (!fullname) {
        form.querySelector("#error-fullname").innerText = "Full name is required.";
        form.querySelector("input[name='fullname']").classList.add('error');
        hasErrors = true;
      }
      if (!phone) {
        form.querySelector("#error-phone").innerText = "Phone number is required.";
        form.querySelector("input[name='phone']").classList.add('error');
        hasErrors = true;
      } else if (!isValidPhone(phone)) {
        form.querySelector("#error-phone").innerText = "Enter a valid phone number (10-15 digits).";
        form.querySelector("input[name='phone']").classList.add('error');
        hasErrors = true;
      }
      if (!username) {
        form.querySelector("#error-username").innerText = "Username is required.";
        form.querySelector("input[name='username']").classList.add('error');
        hasErrors = true;
      } else if (username.length < 3) {
        form.querySelector("#error-username").innerText = "Username must be at least 3 characters.";
        form.querySelector("input[name='username']").classList.add('error');
        hasErrors = true;
      }
      if (!email) {
        form.querySelector("#error-email").innerText = "Email is required.";
        form.querySelector("input[name='email']").classList.add('error');
        hasErrors = true;
      } else {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          form.querySelector("#error-email").innerText = "Enter a valid email address.";
          form.querySelector("input[name='email']").classList.add('error');
          hasErrors = true;
        }
      }
      if (!password) {
        form.querySelector("#error-password").innerText = "Password is required.";
        form.querySelector("input[name='password']").classList.add('error');
        hasErrors = true;
      } else if (password.length < 8) {
        form.querySelector("#error-password").innerText = "Password must be at least 8 characters.";
        form.querySelector("input[name='password']").classList.add('error');
        hasErrors = true;
      }
      if (!confirm_password) {
        form.querySelector("#error-confirm_password").innerText = "Please confirm your password.";
        form.querySelector("input[name='confirm_password']").classList.add('error');
        hasErrors = true;
      } else if (password !== confirm_password) {
        form.querySelector("#error-confirm_password").innerText = "Passwords do not match.";
        form.querySelector("input[name='confirm_password']").classList.add('error');
        hasErrors = true;
      }

      if (hasErrors) {
        const errorEls = form.querySelectorAll(".error-message:not(:empty)");
        shakeElements([...errorEls]);
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerText;
      submitBtn.innerHTML = '<i class="uil uil-spinner animate-spin"></i> Creating account...';
      submitBtn.disabled = true;

      const formData = new FormData(form);

      fetch('content/bisure_register_content.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Server returned ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;

          const msg = form.querySelector("#signup-message");
          msg.innerText = "";
          msg.classList.remove("success-message", "error-message");

          if (data.success) {
            msg.innerText = data.message || "Signup successful! You can now login.";
            msg.classList.add("success-message");
            form.reset();
            form.querySelectorAll('input.error').forEach(el => el.classList.remove('error'));

            setTimeout(() => {
              formContainer.classList.remove("active");
              msg.innerText = "";
              msg.classList.remove("success-message");
            }, 2000);
          } else {
            if (data.errors) {
              for (let field in data.errors) {
                const errEl = form.querySelector(`#error-${field}`);
                if (errEl) {
                  errEl.innerText = data.errors[field];
                }
                const inputEl = form.querySelector(`input[name='${field}']`);
                if (inputEl) {
                  inputEl.classList.add('error');
                }
              }
              const errorEls = form.querySelectorAll(".error-message:not(:empty)");
              if (errorEls.length > 0) {
                shakeElements([...errorEls]);
              }
            }
            if (data.message) {
              msg.innerText = data.message;
              msg.classList.add("error-message");
            }
          }
        })
        .catch(error => {
          console.error('Signup Error:', error);
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;

          const msg = form.querySelector("#signup-message");
          msg.innerText = "Network error. Please check your connection and try again.";
          msg.classList.add("error-message", "shake");
        });
    };
  </script>
</body>

</html>