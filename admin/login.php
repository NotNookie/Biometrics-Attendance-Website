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
    <section class="login-form-card" aria-label="Login form">
      <p class="login-kicker">Attendance System</p>
      <h1 class="login-heading">Login</h1>

      <form action="dashboard.php" method="post">
        <div class="form-group">
          <input class="input" type="text" id="username" name="username" placeholder="Enter username" required>
        </div>

        <div class="form-group">
          <input class="input" type="password" id="password" name="password" placeholder="Enter password" required>
        </div>

        <button class="btn-primary" type="submit">Sign In</button>
      </form>

      <p class="login-footer">Biometric Fingerprint Employee Attendance System</p>
    </section>
  </main>
</body>
</html>
