<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = get_pdo_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$adminName = trim((string) $_SESSION['admin_name']);
$error = '';
$success = '';
$notice = trim((string) ($_GET['notice'] ?? ''));

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ensureAdminsTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_admins_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function getAdminByUsername(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function verifyStoredPassword(string $stored, string $input): bool
{
    if ($stored === '') {
        return false;
    }

    $info = password_get_info($stored);
    if (($info['algo'] ?? null) !== null && (int) ($info['algo'] ?? 0) !== 0) {
        return password_verify($input, $stored);
    }

    return hash_equals($stored, $input);
}

ensureAdminsTable($pdo);
$currentAdmin = getAdminByUsername($pdo, $adminName);
$currentUsername = $currentAdmin['username'] ?? $adminName;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'change_username') {
        $newUsername = trim((string) ($_POST['new_username'] ?? ''));

        if ($newUsername === '') {
            $error = 'Username is required.';
        } elseif (strlen($newUsername) < 3) {
            $error = 'Username must be at least 3 characters.';
        } else {
            try {
                $excludeId = $currentAdmin ? (int) $currentAdmin['id'] : 0;
                $dupStmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1');
                $dupStmt->execute([$newUsername, $excludeId]);
                if ($dupStmt->fetch()) {
                    $error = 'That username is already in use.';
                } else {
                    if ($currentAdmin) {
                        $updateStmt = $pdo->prepare('UPDATE admins SET username = ? WHERE id = ?');
                        $updateStmt->execute([$newUsername, (int) $currentAdmin['id']]);
                    } else {
                        $allStmt = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 2');
                        $rows = $allStmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($rows) === 1) {
                            $updateStmt = $pdo->prepare('UPDATE admins SET username = ? WHERE id = ?');
                            $updateStmt->execute([$newUsername, (int) $rows[0]['id']]);
                        } else {
                            $insertStmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
                            $insertStmt->execute([$newUsername, '']);
                        }
                    }

                    $_SESSION['admin_name'] = $newUsername;
                    header('Location: settings.php?notice=username-updated');
                    exit;
                }
            } catch (Throwable $e) {
                $error = 'Unable to update username right now.';
            }
        }
    }

    if ($action === 'change_password') {
      $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

      if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Old password, new password, and confirmation are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            try {
                $targetAdmin = $currentAdmin ?: getAdminByUsername($pdo, $adminName);

                if ($targetAdmin) {
                    $stored = (string) ($targetAdmin['password'] ?? '');
                  if ($stored === '') {
                    $error = 'Current password is not set yet. Please contact admin support.';
                  } elseif (!verifyStoredPassword($stored, $currentPassword)) {
                        $error = 'Current password is incorrect.';
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
                        $updateStmt->execute([$newHash, (int) $targetAdmin['id']]);
                        header('Location: settings.php?notice=password-updated');
                        exit;
                    }
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
                    $insertStmt->execute([$adminName, $newHash]);
                    header('Location: settings.php?notice=password-updated');
                    exit;
                }
            } catch (Throwable $e) {
                $error = 'Unable to update password right now.';
            }
        }
    }
}

if ($notice === 'username-updated') {
    $success = 'Username updated successfully.';
}

if ($notice === 'password-updated') {
    $success = 'Password updated successfully.';
}

$currentAdmin = getAdminByUsername($pdo, trim((string) $_SESSION['admin_name']));
$currentUsername = $currentAdmin['username'] ?? trim((string) $_SESSION['admin_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .settings-shell {
      width: min(760px, 100%);
    }

    .settings-merged-panel .panel-title {
      margin-bottom: 14px;
    }

    .settings-section-title {
      margin: 0 0 12px;
      font-size: 1.2rem;
      font-weight: 800;
      color: #0f172a;
    }

    .settings-subcard {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      padding: 16px;
      margin-bottom: 16px;
    }

    .settings-subcard:last-child {
      margin-bottom: 0;
    }

    .settings-label {
      display: block;
      margin: 0 0 8px;
      font-weight: 700;
      color: #475569;
      font-size: 0.92rem;
    }

    .settings-inline-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 12px;
      align-items: center;
    }

    .settings-form-stack {
      display: grid;
      gap: 14px;
    }

    .password-field-wrap {
      position: relative;
    }

    .password-field-wrap .input {
      padding-right: 44px;
    }

    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 28px;
      height: 28px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background: #f8fafc;
      color: #1f2937;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 0;
    }

    .password-toggle:hover {
      background: #eef2f7;
    }

    @media (max-width: 640px) {
      .settings-inline-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
          <a href="dashboard.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3z"></path><path d="M13 21h8v-6h-8z"></path><path d="M13 3h8v6h-8z"></path><path d="M3 21h8v-6H3z"></path></svg>
            Dashboard
          </a>
          <a href="employees.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
            Employees
          </a>
          <a href="departments.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h5l2 3h11v10a2 2 0 0 1-2 2H3z"></path><path d="M3 7V5a2 2 0 0 1 2-2h4l2 3"></path></svg>
            Departments
          </a>
          <a href="attendance.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><path d="M8 14h3"></path></svg>
            Attendance
          </a>
          <a href="dtr.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg>
            DTR
          </a>
          <a href="scanner/bio_connect.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l2.12-2.12a5 5 0 1 0-7.07-7.07L11.4 5.5"></path><path d="M14 11a5 5 0 0 0-7.54-.54L4.34 12.6a5 5 0 1 0 7.07 7.07l1.13-1.13"></path></svg>
            Bio Connect
          </a>
        </div>

        <div class="dashboard-nav-bottom">
          <a href="settings.php" class="dashboard-nav-link active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Settings
          </a>
          <a href="logout.php" class="dashboard-nav-link js-logout-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
            Logout
          </a>
        </div>
      </nav>
    </aside>

    <main class="dashboard-main">
      <header class="dashboard-header">
        <div>
          <h1 class="dashboard-title">Settings</h1>
          <p class="dashboard-subtitle">Update your admin account username and password</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e(trim((string) $_SESSION['admin_name'])) ?>
        </div>
      </header>

      <section class="dashboard-content">
        <?php if ($success !== ''): ?>
          <div style="background:#dcfce7;padding:12px;border-radius:10px;"> <?= e($success) ?> </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div style="background:#fee2e2;padding:12px;border-radius:10px;"> <?= e($error) ?> </div>
        <?php endif; ?>

        <article class="dashboard-panel employee-panel settings-merged-panel settings-shell">
          <h2 class="panel-title">Account Settings</h2>

          <div class="settings-subcard">
            <h3 class="settings-section-title">Change Username</h3>
            <form method="POST" action="settings.php">
              <input type="hidden" name="action" value="change_username">
              <label class="settings-label" for="new_username">Username</label>
              <div class="settings-inline-row">
                <input class="input" id="new_username" name="new_username" type="text" value="<?= e($currentUsername) ?>" placeholder="Username" required>
                <button class="btn-secondary" type="submit">Save</button>
              </div>
            </form>
          </div>

          <div class="settings-subcard">
            <h3 class="settings-section-title">Change Password</h3>
            <form method="POST" action="settings.php" class="settings-form-stack">
              <input type="hidden" name="action" value="change_password">

              <div>
                <label class="settings-label" for="current_password">Old Password</label>
                <div class="password-field-wrap">
                  <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                  <button class="password-toggle" type="button" data-toggle-password data-target="current_password" aria-label="Toggle password visibility">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>

              <div>
                <label class="settings-label" for="new_password">New Password</label>
                <div class="password-field-wrap">
                  <input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                  <button class="password-toggle" type="button" data-toggle-password data-target="new_password" aria-label="Toggle password visibility">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>

              <div>
                <label class="settings-label" for="confirm_password">Confirm New Password</label>
                <div class="password-field-wrap">
                  <input class="input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                  <button class="password-toggle" type="button" data-toggle-password data-target="confirm_password" aria-label="Toggle password visibility">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>

              <div class="form-actions">
                <button class="btn-secondary" type="submit">Change</button>
              </div>
            </form>
          </div>
        </article>
      </section>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var toggles = document.querySelectorAll('[data-toggle-password]');
      toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
          var targetId = toggle.getAttribute('data-target') || '';
          var target = document.getElementById(targetId);
          if (!target) {
            return;
          }

          target.type = target.type === 'password' ? 'text' : 'password';
        });
      });
    });
  </script>
  <script src="../assets/logout-confirm.js"></script>
</body>
</html>
