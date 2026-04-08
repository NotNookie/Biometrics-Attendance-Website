<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
  header('Location: login.php');
  exit;
}

$adminName = trim((string) $_SESSION['admin_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">Attendance System</div>
      <nav class="nav-group">
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="employees.php" class="nav-link">Employees</a>
        <a href="attendance.php" class="nav-link">Attendance</a>
        <a href="dtr.php" class="nav-link">DTR</a>
        <a href="logout.php" class="nav-link">Logout</a>
      </nav>
    </aside>

    <main class="main-panel">
      <header class="topbar">
        <h1 class="page-title">Admin Dashboard</h1>
        <div class="admin-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
        </div>
      </header>

      <section class="content">
        <div class="card-grid">
          <article class="stat-card">
            <p class="stat-title">Total Employees</p>
            <p class="stat-value">128</p>
          </article>

          <article class="stat-card">
            <p class="stat-title">Present Today</p>
            <p class="stat-value">117</p>
          </article>

          <article class="stat-card">
            <p class="stat-title">Absent Today</p>
            <p class="stat-value">11</p>
          </article>
        </div>

        <article class="card">
          <h2 class="card-title">Overview</h2>
          <p class="helper-text">
            This dashboard layout is ready for dynamic PHP data. Replace the sample values with database counts from your
            employees and attendance records. The design follows a clean payroll-style interface optimized for desktop and mobile.
          </p>
        </article>
      </section>
    </main>
  </div>
</body>
</html>
