<?php

  declare(strict_types=1);

  session_start();

  if (isset($_SESSION['admin_name']) && $_SESSION['admin_name'] !== '') {
    header('Location: dashboard.php');
    exit;
  }

  $error = '';
  $loggedOut = ($_GET['logged_out'] ?? '') === '1';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));
    

    if ($username === '' || $password === '') {
      $error = 'Please enter your username and password.';
    } else {
      $_SESSION['admin_name'] = $username;
      header('Location: dashboard.php');
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <main class="login-shell">
    <section class="login-stage" aria-label="Admin login">
      <aside class="login-aside">
        <div class="login-aside-brand">
          <strong>Attendance</strong>
          <span>System</span>
        </div>
        <h2 class="login-aside-title">Admin Access Portal</h2>
        <p class="login-aside-text">Manage workforce activity, attendance logs, and DTR reports from one secure dashboard.</p>

        <ul class="login-feature-list" aria-label="System highlights">
          <li>Real-time attendance visibility</li>
          <li>Employee and schedule management</li>
          <li>Daily and period report tracking</li>
        </ul>
      </aside>

      <section class="login-form-card" aria-label="Login form">
        <p class="login-kicker">Admin Sign In</p>
        <h1 class="login-heading">Welcome Back</h1>
        <p class="login-subtext">Enter your credentials to access the Attendance System.</p>

        <?php if ($loggedOut): ?>
          <p class="login-alert success">You have been logged out successfully.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <p class="login-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
          <div class="form-group">
            <label for="username">Username</label>
            <input class="input" type="text" id="username" name="username" placeholder="Enter username" autocomplete="username" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
          </div>

          <button class="btn-primary" type="submit">Sign In to Dashboard</button>
        </form>

        <p class="login-footer">Biometric Fingerprint Employee Attendance System</p>
      </section>
    </section>
  </main>
</body>
</html>
