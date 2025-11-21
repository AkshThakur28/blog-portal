<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login - Blog Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>">
</head>
<body class="login-page">
  <script>
  (function(){
    try {
      var t = localStorage.getItem('jwt_token') || '';
      if (t) {
        var p = null;
        try {
          var b = t.split('.')[1] || '';
          b = b.replace(/-/g,'+').replace(/_/g,'/');
          p = JSON.parse(atob(b));
        } catch(e){}
        var now = Math.floor(Date.now()/1000);
        if (p && p.exp && p.exp > now) {
          window.location.href = "<?php echo site_url('dashboard'); ?>";
        }
      }
    } catch(e){}
  })();
  </script>

  <div class="container auth-wrap">
    <h1>Sign in</h1>
    <div id="err" class="error"></div>
    <form id="loginForm" onsubmit="return false;">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="Enter email" autocomplete="username" required>

      <label for="password">Password</label>
      <div class="password-wrap">
        <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required>
        <button type="button" id="togglePassword" class="toggle-eye" aria-label="Show password" aria-pressed="false" title="Show password">
          <svg class="icon eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <svg class="icon eye-off" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.94"/>
            <path d="M1 1l22 22"/>
            <path d="M9.88 9.88A3 3 0 0 0 12 15c.66 0 1.27-.21 1.76-.57"/>
          </svg>
        </button>
      </div>

      <button id="btnLogin" type="submit" class="btn">Login</button>
      <div class="hint">After login, you will be redirected to the dashboard.</div>
    </form>
  </div>

  <script>
  (function(){
    const form = document.getElementById('loginForm');
    const errBox = document.getElementById('err');
    const pwdInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');

    if (toggleBtn && pwdInput) {
      toggleBtn.addEventListener('click', function () {
        const isPwd = pwdInput.getAttribute('type') === 'password';
        const showing = isPwd;
        pwdInput.setAttribute('type', showing ? 'text' : 'password');
        const eye = toggleBtn.querySelector('.eye');
        const eyeOff = toggleBtn.querySelector('.eye-off');
        if (eye && eyeOff) {
          eye.style.display = showing ? 'none' : 'block';
          eyeOff.style.display = showing ? 'block' : 'none';
        }
        toggleBtn.setAttribute('aria-pressed', showing ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        toggleBtn.title = showing ? 'Hide password' : 'Show password';
      });
    }

    function showError(msg){
      errBox.textContent = msg || 'Login failed';
      errBox.style.display = 'block';
    }

    form.addEventListener('submit', async function(){
      errBox.style.display = 'none';
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      if(!email || !password){ return showError('Email and password are required'); }
      try{
        const resp = await fetch('<?php echo site_url('api/login'); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ email, password })
        });
        const data = await resp.json();
        if(!resp.ok || !data.token){
          return showError(data && data.error ? data.error : 'Invalid credentials');
        }
        localStorage.setItem('jwt_token', data.token);
        localStorage.setItem('user_role', data.user && data.user.role ? data.user.role : '');
        localStorage.setItem('user_name', data.user && data.user.name ? data.user.name : '');
        window.location.href = '<?php echo site_url('dashboard'); ?>';
      }catch(e){
        showError('Network or server error');
      }
    });
  })();
  </script>
</body>
</html>
