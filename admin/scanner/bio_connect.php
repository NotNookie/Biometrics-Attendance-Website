<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
  header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$pdo = get_pdo_connection();
$adminName = trim((string) $_SESSION['admin_name']);
$dialog = (string) ($_GET['dialog'] ?? '');
$notice = (string) ($_GET['notice'] ?? '');
$devices = [];
$loadError = '';
$feedbackMessage = '';
$feedbackColor = '#64748b';
$deviceNameValue = trim((string) ($_POST['device_name'] ?? ''));
$ipAddressValue = trim((string) ($_POST['ip_address'] ?? ''));
$portValue = trim((string) ($_POST['port'] ?? '4370'));

if (!in_array($dialog, ['add', 'connect'], true)) {
  $dialog = '';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function connectBiometric(string $ip, int $port): bool
{
  $zkLibraryPath = __DIR__ . '/../../zk/ZKLibrary.php';
  if (is_file($zkLibraryPath)) {
    require_once $zkLibraryPath;

    if (class_exists('ZKLibrary')) {
      try {
        $zk = new ZKLibrary();
        $result = $zk->connect($ip, $port);
        if ($result) {
          return true;
        }
      } catch (Throwable $e) {
        // Fall back to socket probing when SDK connection is unavailable.
      }
    }
  }

  $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
  if ($socket) {
    fclose($socket);
    return true;
  }

  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');

  if ($action === 'add_device') {
    $name = trim((string) ($_POST['device_name'] ?? ''));
    $ip = trim((string) ($_POST['ip_address'] ?? ''));
    $port = (int) ($_POST['port'] ?? 0);

    if ($name === '' || $ip === '' || $port <= 0) {
      $feedbackMessage = 'Please complete all required fields with valid values.';
      $feedbackColor = '#b91c1c';
      $dialog = 'add';
    } else {
      try {
        $stmt = $pdo->prepare('INSERT INTO biometric_devices (device_name, ip_address, port) VALUES (?, ?, ?)');
        $stmt->execute([$name, $ip, $port]);
        header('Location: bio_connect.php?notice=device-added');
        exit;
      } catch (Throwable $e) {
        $feedbackMessage = 'Unable to save the biometric device right now.';
        $feedbackColor = '#b91c1c';
        $dialog = 'add';
      }
    }
  }

  if ($action === 'connect_device') {
    $deviceId = (int) ($_POST['device_id'] ?? 0);

    if ($deviceId <= 0) {
      header('Location: bio_connect.php?notice=device-invalid');
      exit;
    }

    $stmt = $pdo->prepare('SELECT id, ip_address, port FROM biometric_devices WHERE id = ? LIMIT 1');
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();

    if (!$device) {
      header('Location: bio_connect.php?notice=device-not-found');
      exit;
    }

    $isConnected = connectBiometric((string) $device['ip_address'], (int) $device['port']);
    $status = $isConnected ? 'connected' : 'disconnected';

    $update = $pdo->prepare('UPDATE biometric_devices SET status = ? WHERE id = ?');
    $update->execute([$status, $deviceId]);

    header('Location: bio_connect.php?notice=' . ($isConnected ? 'device-connected' : 'device-disconnected'));
    exit;
  }
}

$selectedDeviceId = (int) ($_GET['id'] ?? 0);
$selectedDevice = null;

if ($dialog === 'connect') {
  if ($selectedDeviceId <= 0) {
    $feedbackMessage = 'Invalid device selected for connection.';
    $feedbackColor = '#b91c1c';
    $dialog = '';
  } else {
    $deviceStmt = $pdo->prepare('SELECT id, device_name, ip_address, port, status FROM biometric_devices WHERE id = ? LIMIT 1');
    $deviceStmt->execute([$selectedDeviceId]);
    $selectedDevice = $deviceStmt->fetch();

    if (!$selectedDevice) {
      $feedbackMessage = 'Device not found.';
      $feedbackColor = '#b91c1c';
      $dialog = '';
    }
  }
}

try {
    $stmt = $pdo->query('SELECT id, device_name, ip_address, port, status FROM biometric_devices ORDER BY id DESC');
    $devices = $stmt->fetchAll();
} catch (Throwable $e) {
    $loadError = 'Unable to load biometric devices right now.';
}

$totalDevices = count($devices);
$connectedDevices = 0;
$disconnectedDevices = 0;

foreach ($devices as $device) {
    $status = strtolower(trim((string) ($device['status'] ?? '')));
    if ($status === 'connected') {
        $connectedDevices++;
    } elseif ($status === 'disconnected') {
        $disconnectedDevices++;
    }
}

  if ($notice === 'device-added') {
    $feedbackMessage = 'Biometric device added successfully.';
    $feedbackColor = '#0f766e';
  } elseif ($notice === 'device-connected') {
    $feedbackMessage = 'Device connected successfully.';
    $feedbackColor = '#166534';
  } elseif ($notice === 'device-disconnected') {
    $feedbackMessage = 'Connection failed. Device marked as disconnected.';
    $feedbackColor = '#b45309';
  } elseif ($notice === 'device-invalid') {
    $feedbackMessage = 'Invalid device selected.';
    $feedbackColor = '#b91c1c';
  } elseif ($notice === 'device-not-found') {
    $feedbackMessage = 'Selected device was not found.';
    $feedbackColor = '#b91c1c';
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bio Connect | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/style.css">
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
          <a href="../dashboard.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3z"></path><path d="M13 21h8v-6h-8z"></path><path d="M13 3h8v6h-8z"></path><path d="M3 21h8v-6H3z"></path></svg>
            Dashboard
          </a>
          <a href="../employees.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
            Employees
          </a>
          <a href="../departments.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h5l2 3h11v10a2 2 0 0 1-2 2H3z"></path><path d="M3 7V5a2 2 0 0 1 2-2h4l2 3"></path></svg>
            Departments
          </a>
          <a href="../attendance.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><path d="M8 14h3"></path></svg>
            Attendance
          </a>
          <a href="../dtr.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg>
            DTR
          </a>
          <a href="bio_connect.php" class="dashboard-nav-link active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l2.12-2.12a5 5 0 1 0-7.07-7.07L11.4 5.5"></path><path d="M14 11a5 5 0 0 0-7.54-.54L4.34 12.6a5 5 0 1 0 7.07 7.07l1.13-1.13"></path></svg>
            Bio Connect
          </a>
        </div>

        <div class="dashboard-nav-bottom">
          <a href="../logout.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
            Logout
          </a>
        </div>
      </nav>
    </aside>

    <main class="dashboard-main">
      <header class="dashboard-header">
        <div>
          <h1 class="dashboard-title">Bio Connect</h1>
          <p class="dashboard-subtitle">Manage and connect biometric hardware devices</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e($adminName) ?>
        </div>
      </header>

      <section class="dashboard-content">
        <div class="dashboard-stats stats-3">
          <article class="dashboard-stat-card accent-slate">
            <div class="stat-icon-wrap slate">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="3" width="10" height="18" rx="2"></rect><path d="M9 7h6"></path><path d="M9 11h6"></path><path d="M9 15h4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Total Devices</p>
              <p class="stat-number"><?= e((string) $totalDevices) ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-green">
            <div class="stat-icon-wrap green">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
            </div>
            <div>
              <p class="stat-label">Connected</p>
              <p class="stat-number"><?= e((string) $connectedDevices) ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-red">
            <div class="stat-icon-wrap red">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </div>
            <div>
              <p class="stat-label">Disconnected</p>
              <p class="stat-number"><?= e((string) $disconnectedDevices) ?></p>
            </div>
          </article>
        </div>

        <article class="dashboard-panel employee-panel">
          <div class="employee-panel-head">
            <h2 class="panel-title">Biometric Devices</h2>
            <a class="qa-btn qa-primary employee-add-btn" href="add_device.php">+ Add Device</a>
          </div>

          <?php if ($feedbackMessage !== ''): ?>
            <p class="table-footnote" style="color:<?= e($feedbackColor) ?>;"><?= e($feedbackMessage) ?></p>
          <?php endif; ?>

          <?php if ($loadError !== ''): ?>
            <p class="table-footnote" style="color:#b91c1c;"><?= e($loadError) ?></p>
          <?php endif; ?>

          <div class="table-wrap">
            <table class="timecard employee-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Device Name</th>
                  <th>IP Address</th>
                  <th>Port</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($devices === []): ?>
                  <tr>
                    <td colspan="6" style="text-align:center;">No biometric devices found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($devices as $row): ?>
                    <?php
                      $rawStatus = trim((string) ($row['status'] ?? ''));
                      $status = strtolower($rawStatus);
                      $statusClass = 'warning';
                      if ($status === 'connected') {
                          $statusClass = 'success';
                      } elseif ($status === 'disconnected') {
                          $statusClass = 'danger';
                      }
                    ?>
                    <tr>
                      <td><?= e((string) ($row['id'] ?? '')) ?></td>
                      <td><?= e((string) ($row['device_name'] ?? '')) ?></td>
                      <td><?= e((string) ($row['ip_address'] ?? '')) ?></td>
                      <td><?= e((string) ($row['port'] ?? '')) ?></td>
                      <td>
                        <span class="badge <?= $statusClass ?>">
                          <?= e($rawStatus !== '' ? ucfirst($rawStatus) : 'Unknown') ?>
                        </span>
                      </td>
                      <td>
                        <a class="btn-mini edit" href="connect_device.php?id=<?= urlencode((string) ($row['id'] ?? '')) ?>">
                          Connect
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>

  <?php if ($dialog === 'add'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="addDeviceDialogTitle">
      <div class="dialog-card">
        <div class="dialog-head">
          <h2 id="addDeviceDialogTitle" class="dialog-title">Add Biometric Device</h2>
          <a class="dialog-close" href="bio_connect.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogAddDeviceForm" class="dialog-form-grid" method="POST" action="bio_connect.php">
          <input type="hidden" name="action" value="add_device">

          <label for="dialog_device_name">Device Name</label>
          <input class="input" id="dialog_device_name" name="device_name" type="text" value="<?= e($deviceNameValue) ?>" required>

          <label for="dialog_ip_address">IP Address</label>
          <input class="input" id="dialog_ip_address" name="ip_address" type="text" value="<?= e($ipAddressValue) ?>" required>

          <label for="dialog_port">Port</label>
          <input class="input" id="dialog_port" name="port" type="number" min="1" value="<?= e($portValue !== '' ? $portValue : '4370') ?>" required>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="bio_connect.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogAddDeviceForm">Save Device</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'connect' && $selectedDevice): ?>
    <?php
      $selectedRawStatus = trim((string) ($selectedDevice['status'] ?? ''));
      $selectedStatus = strtolower($selectedRawStatus);
      $selectedStatusClass = 'warning';
      if ($selectedStatus === 'connected') {
          $selectedStatusClass = 'success';
      } elseif ($selectedStatus === 'disconnected') {
          $selectedStatusClass = 'danger';
      }
    ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="connectDeviceDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="connectDeviceDialogTitle" class="dialog-title">Connect Device</h2>
          <a class="dialog-close" href="bio_connect.php" aria-label="Close dialog">x</a>
        </div>

        <div class="summary-box" style="margin: 14px 16px 0;">
          <h3 class="summary-title">Device Information</h3>
          <table class="summary-table">
            <tbody>
              <tr>
                <th scope="row">Name</th>
                <td><?= e((string) ($selectedDevice['device_name'] ?? '')) ?></td>
              </tr>
              <tr>
                <th scope="row">IP Address</th>
                <td><?= e((string) ($selectedDevice['ip_address'] ?? '')) ?></td>
              </tr>
              <tr>
                <th scope="row">Port</th>
                <td><?= e((string) ($selectedDevice['port'] ?? '')) ?></td>
              </tr>
              <tr>
                <th scope="row">Current Status</th>
                <td><span class="badge <?= $selectedStatusClass ?>"><?= e($selectedRawStatus !== '' ? ucfirst($selectedRawStatus) : 'Unknown') ?></span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <form method="POST" action="bio_connect.php">
          <input type="hidden" name="action" value="connect_device">
          <input type="hidden" name="device_id" value="<?= e((string) ($selectedDevice['id'] ?? '0')) ?>">

          <div class="dialog-footer">
            <a class="btn-secondary" href="bio_connect.php">Cancel</a>
            <button class="btn-primary" type="submit">Connect Device</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>