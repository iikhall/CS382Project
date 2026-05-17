<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

if (User::isLoggedIn()) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login &middot; Smart School Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="theme-login">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="logo" aria-hidden="true">SD</div>
      <h1>Smart School Dashboard</h1>
      <p class="subtle">Sign in to continue</p>

      <div id="loginAlert" class="auth-alert" role="alert" aria-live="assertive"></div>

      <form id="loginForm" novalidate>
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control"
                 autocomplete="username" required>
          <span class="field-error" id="usernameError"></span>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="current-password" required>
          <span class="field-error" id="passwordError"></span>
        </div>
        <button type="submit" id="loginBtn" class="btn btn-primary btn-block">
          Sign In
        </button>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
  <script src="assets/js/login.js"></script>
</body>
</html>
