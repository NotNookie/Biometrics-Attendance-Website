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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="dashboard-layout">
    <aside class="dashboard-sidebar">
      <div class="dashboard-brand">
        <div>
          <strong>Attendance</strong>
          <span>System</span>
        </div>
      </div>

      <nav class="dashboard-nav" aria-label="Main Navigation">
        <div class="dashboard-nav-main">
          <a href="dashboard.php" class="dashboard-nav-link active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3z"></path><path d="M13 21h8v-6h-8z"></path><path d="M13 3h8v6h-8z"></path><path d="M3 21h8v-6H3z"></path></svg>
            Dashboard
          </a>
          <a href="employees.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
            Employees
          </a>
          <a href="attendance.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><path d="M8 14h3"></path></svg>
            Attendance
          </a>
          <a href="dtr.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg>
            DTR
          </a>
          <a href="../biometrics%20scanner/index.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a4 4 0 0 1 4 4"></path><path d="M12 3a4 4 0 0 0-4 4"></path><path d="M12 21a8 8 0 0 0 8-8"></path><path d="M12 21a8 8 0 0 1-8-8"></path><path d="M12 9a4 4 0 0 1 4 4"></path><path d="M12 9a4 4 0 0 0-4 4"></path></svg>
            Biometric Scanner
          </a>
        </div>

        <div class="dashboard-nav-bottom">
          <a href="logout.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
            Logout
          </a>
        </div>
      </nav>
    </aside>

    <main class="dashboard-main">
      <header class="dashboard-header">
        <div>
          <h1 class="dashboard-title">Admin Dashboard</h1>
          <p class="dashboard-subtitle">Overview of today's attendance and system metrics</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
        </div>
      </header>

      <section class="dashboard-content">
        <div class="dashboard-stats">
          <article class="dashboard-stat-card accent-teal">
            <div class="stat-icon-wrap teal">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
            </div>
            <div>
              <p class="stat-label">Total Employees</p>
              <p class="stat-number">128</p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-green">
            <div class="stat-icon-wrap green">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m17 11 2 2 4-4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Present Today</p>
              <p class="stat-number">117</p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-red">
            <div class="stat-icon-wrap red">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m20 8-4 4"></path><path d="m16 8 4 4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Absent Today</p>
              <p class="stat-number">11</p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-amber">
            <div class="stat-icon-wrap amber">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
            </div>
            <div>
              <p class="stat-label">Late Today</p>
              <p class="stat-number">3</p>
            </div>
          </article>
        </div>

        <div class="dashboard-main-grid">
          <article class="dashboard-panel panel-chart">
            <h2 class="panel-title">Weekly Attendance Overview</h2>
            <div class="chart-wrap">
              <canvas id="attendanceChart" aria-label="Weekly attendance chart"></canvas>
            </div>
          </article>

          <article class="dashboard-panel panel-activity">
            <h2 class="panel-title">Recent Activity</h2>
            <ul class="activity-list">
              <li class="activity-item">
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <p class="activity-text">John Doe clocked in</p>
                  <p class="activity-time">08:03 AM</p>
                </div>
              </li>
              <li class="activity-item">
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <p class="activity-text">Ilele Ray clocked in</p>
                  <p class="activity-time">07:12 AM</p>
                </div>
              </li>
              <li class="activity-item">
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <p class="activity-text">Angela Smith clocked in</p>
                  <p class="activity-time">08:29 AM</p>
                </div>
              </li>
              <li class="activity-item">
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <p class="activity-text">Maria Santos clocked out</p>
                  <p class="activity-time">06:10 PM</p>
                </div>
              </li>
              <li class="activity-item">
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <p class="activity-text">Juan Cruz clocked out</p>
                  <p class="activity-time">04:30 PM</p>
                </div>
              </li>
            </ul>
          </article>
        </div>

        <article class="dashboard-panel quick-actions-panel">
          <h2 class="panel-title">Quick Actions</h2>
          <div class="quick-actions">
            <a href="employees.php?dialog=add" class="qa-btn qa-primary">+ Add Employee</a>
            <a href="attendance.php" class="qa-btn qa-secondary">View Attendance</a>
            <a href="dtr.php" class="qa-btn qa-secondary">Generate Report</a>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script>
    (function () {
      var canvas = document.getElementById('attendanceChart');
      if (!canvas || typeof Chart === 'undefined') {
        return;
      }

      var chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
          datasets: [
            {
              label: 'Present',
              data: [117, 120, 122, 119, 120],
              backgroundColor: '#14B8A6',
              borderRadius: 6,
              borderSkipped: false
            },
            {
              label: 'Absent',
              data: [12, 10, 8, 11, 11],
              backgroundColor: '#EF4444',
              borderRadius: 6,
              borderSkipped: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                usePointStyle: true,
                boxWidth: 8,
                color: '#475569',
                font: {
                  size: 12,
                  family: 'Manrope'
                }
              }
            },
            tooltip: {
              backgroundColor: '#0F172A'
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#64748B'
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: '#E2E8F0',
                borderDash: [4, 4]
              },
              ticks: {
                color: '#64748B'
              }
            }
          }
        }
      });

      function forceChartResize() {
        if (chartInstance) {
          chartInstance.resize();
          chartInstance.update('none');
        }
      }

      window.addEventListener('resize', forceChartResize);
      window.addEventListener('orientationchange', forceChartResize);

      if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', forceChartResize);
      }

      if (typeof ResizeObserver !== 'undefined') {
        var chartWrap = canvas.closest('.chart-wrap');
        if (chartWrap) {
          var observer = new ResizeObserver(function () {
            forceChartResize();
          });
          observer.observe(chartWrap);
        }
      }

      setTimeout(forceChartResize, 80);
    })();
  </script>
</body>
</html>
