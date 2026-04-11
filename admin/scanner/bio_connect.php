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

if (!in_array($dialog, ['add', 'connect', 'edit', 'delete', 'disconnect'], true)) {
  $dialog = '';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ensureBiometricDevicesSchema(PDO $pdo): string
{
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS biometric_devices (
      id INT NOT NULL AUTO_INCREMENT,
      device_name VARCHAR(100) DEFAULT NULL,
      ip_address VARCHAR(50) DEFAULT NULL,
      port INT DEFAULT 4370,
      status ENUM('connected','disconnected') DEFAULT 'disconnected',
      created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = [];
    $columnStmt = $pdo->query('SHOW COLUMNS FROM biometric_devices');
    while ($row = $columnStmt->fetch(PDO::FETCH_ASSOC)) {
      if (isset($row['Field'])) {
        $columns[] = (string) $row['Field'];
      }
    }

    $requiredColumns = [
      'device_name' => 'ALTER TABLE biometric_devices ADD COLUMN device_name VARCHAR(100) DEFAULT NULL',
      'ip_address' => 'ALTER TABLE biometric_devices ADD COLUMN ip_address VARCHAR(50) DEFAULT NULL',
      'port' => 'ALTER TABLE biometric_devices ADD COLUMN port INT DEFAULT 4370',
      "status" => "ALTER TABLE biometric_devices ADD COLUMN status ENUM('connected','disconnected') DEFAULT 'disconnected'",
      'created_at' => 'ALTER TABLE biometric_devices ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ];

    foreach ($requiredColumns as $columnName => $sql) {
      if (in_array($columnName, $columns, true)) {
        continue;
      }

      $pdo->exec($sql);
    }

    return '';
  } catch (Throwable $e) {
    return 'Biometric device table is not ready. Open setup.php and verify DB permissions/config.';
  }
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

$schemaError = ensureBiometricDevicesSchema($pdo);
if ($schemaError !== '') {
  $loadError = $schemaError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($schemaError !== '') {
    $feedbackMessage = 'Unable to process device actions until the database setup is fixed.';
    $feedbackColor = '#b91c1c';
  }

  $action = (string) ($_POST['action'] ?? '');

  if ($schemaError === '' && $action === 'add_device') {
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

  if ($schemaError === '' && $action === 'connect_device') {
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

  if ($schemaError === '' && $action === 'edit_device') {
    $deviceId = (int) ($_POST['device_id'] ?? 0);
    $name = trim((string) ($_POST['device_name'] ?? ''));
    $ip = trim((string) ($_POST['ip_address'] ?? ''));
    $port = (int) ($_POST['port'] ?? 0);

    if ($deviceId <= 0 || $name === '' || $ip === '' || $port <= 0) {
      $feedbackMessage = 'Please complete all required fields with valid values.';
      $feedbackColor = '#b91c1c';
      $dialog = 'edit';
    } else {
      try {
        $stmt = $pdo->prepare('UPDATE biometric_devices SET device_name = ?, ip_address = ?, port = ? WHERE id = ?');
        $stmt->execute([$name, $ip, $port, $deviceId]);

        if ($stmt->rowCount() < 1) {
          $existsStmt = $pdo->prepare('SELECT id FROM biometric_devices WHERE id = ? LIMIT 1');
          $existsStmt->execute([$deviceId]);
          if (!$existsStmt->fetch()) {
            header('Location: bio_connect.php?notice=device-not-found');
            exit;
          }
        }

        header('Location: bio_connect.php?notice=device-updated');
        exit;
      } catch (Throwable $e) {
        $feedbackMessage = 'Unable to update the biometric device right now.';
        $feedbackColor = '#b91c1c';
        $dialog = 'edit';
      }
    }
  }

  if ($schemaError === '' && $action === 'delete_device') {
    $deviceId = (int) ($_POST['device_id'] ?? 0);

    if ($deviceId <= 0) {
      header('Location: bio_connect.php?notice=device-invalid');
      exit;
    }

    $deleteStmt = $pdo->prepare('DELETE FROM biometric_devices WHERE id = ?');
    $deleteStmt->execute([$deviceId]);

    if ($deleteStmt->rowCount() > 0) {
      header('Location: bio_connect.php?notice=device-deleted');
      exit;
    }

    header('Location: bio_connect.php?notice=device-not-found');
    exit;
  }

  if ($schemaError === '' && $action === 'disconnect_device') {
    $deviceId = (int) ($_POST['device_id'] ?? 0);

    if ($deviceId <= 0) {
      header('Location: bio_connect.php?notice=device-invalid');
      exit;
    }

    $updateStmt = $pdo->prepare("UPDATE biometric_devices SET status = 'disconnected' WHERE id = ?");
    $updateStmt->execute([$deviceId]);

    if ($updateStmt->rowCount() > 0) {
      header('Location: bio_connect.php?notice=device-manually-disconnected');
      exit;
    }

    $existsStmt = $pdo->prepare('SELECT id FROM biometric_devices WHERE id = ? LIMIT 1');
    $existsStmt->execute([$deviceId]);
    if ($existsStmt->fetch()) {
      header('Location: bio_connect.php?notice=device-manually-disconnected');
      exit;
    }

    header('Location: bio_connect.php?notice=device-not-found');
    exit;
  }
}

$selectedDeviceId = (int) ($_GET['id'] ?? 0);
$selectedDevice = null;

if ($schemaError === '' && in_array($dialog, ['connect', 'edit', 'delete', 'disconnect'], true)) {
  if ($selectedDeviceId <= 0) {
    $feedbackMessage = 'Invalid device selected.';
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

$editDeviceNameValue = trim((string) ($_POST['device_name'] ?? ($selectedDevice['device_name'] ?? '')));
$editIpAddressValue = trim((string) ($_POST['ip_address'] ?? ($selectedDevice['ip_address'] ?? '')));
$editPortValue = trim((string) ($_POST['port'] ?? (($selectedDevice['port'] ?? '4370'))));

if ($schemaError === '') {
  try {
    $stmt = $pdo->query('SELECT id, device_name, ip_address, port, status FROM biometric_devices ORDER BY id DESC');
    $devices = $stmt->fetchAll();
  } catch (Throwable $e) {
    $loadError = 'Unable to load biometric devices right now. Check setup.php database settings.';
  }
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
  } elseif ($notice === 'device-updated') {
    $feedbackMessage = 'Biometric device updated successfully.';
    $feedbackColor = '#0f766e';
  } elseif ($notice === 'device-deleted') {
    $feedbackMessage = 'Biometric device deleted successfully.';
    $feedbackColor = '#166534';
  } elseif ($notice === 'device-manually-disconnected') {
    $feedbackMessage = 'Device disconnected successfully.';
    $feedbackColor = '#166534';
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
          <a href="../settings.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Settings
          </a>
          <a href="../logout.php" class="dashboard-nav-link js-logout-link">
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
                  <th style="text-align:center;">Action</th>
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
                      <td style="text-align:center;">
                        <div class="tool-actions" style="justify-content:center;">
                          <?php if (strtolower(trim((string) ($row['status'] ?? ''))) === 'connected'): ?>
                            <a class="btn-mini delete" href="bio_connect.php?dialog=disconnect&id=<?= urlencode((string) ($row['id'] ?? '')) ?>">
                              Disconnect
                            </a>
                          <?php else: ?>
                            <a class="btn-mini edit" href="connect_device.php?id=<?= urlencode((string) ($row['id'] ?? '')) ?>">
                              Connect
                            </a>
                          <?php endif; ?>
                          <a class="btn-mini edit" href="bio_connect.php?dialog=edit&id=<?= urlencode((string) ($row['id'] ?? '')) ?>">
                            Edit
                          </a>
                          <a class="btn-mini delete" href="bio_connect.php?dialog=delete&id=<?= urlencode((string) ($row['id'] ?? '')) ?>">
                            Delete
                          </a>
                        </div>
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

  <?php if ($dialog === 'edit' && $selectedDevice): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="editDeviceDialogTitle">
      <div class="dialog-card">
        <div class="dialog-head">
          <h2 id="editDeviceDialogTitle" class="dialog-title">Edit Device</h2>
          <a class="dialog-close" href="bio_connect.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogEditDeviceForm" class="dialog-form-grid" method="POST" action="bio_connect.php">
          <input type="hidden" name="action" value="edit_device">
          <input type="hidden" name="device_id" value="<?= e((string) ($selectedDevice['id'] ?? '0')) ?>">

          <label for="edit_device_name">Device Name</label>
          <input class="input" id="edit_device_name" name="device_name" type="text" value="<?= e($editDeviceNameValue) ?>" required>

          <label for="edit_ip_address">IP Address</label>
          <input class="input" id="edit_ip_address" name="ip_address" type="text" value="<?= e($editIpAddressValue) ?>" required>

          <label for="edit_port">Port</label>
          <input class="input" id="edit_port" name="port" type="number" min="1" value="<?= e($editPortValue !== '' ? $editPortValue : '4370') ?>" required>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="bio_connect.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogEditDeviceForm">Update Device</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'delete' && $selectedDevice): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="deleteDeviceDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="deleteDeviceDialogTitle" class="dialog-title">Delete Device</h2>
          <a class="dialog-close" href="bio_connect.php" aria-label="Close dialog">x</a>
        </div>

        <p class="helper-text">Are you sure you want to delete this biometric device?</p>

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
            </tbody>
          </table>
        </div>

        <form method="POST" action="bio_connect.php">
          <input type="hidden" name="action" value="delete_device">
          <input type="hidden" name="device_id" value="<?= e((string) ($selectedDevice['id'] ?? '0')) ?>">

          <div class="dialog-footer">
            <a class="btn-secondary" href="bio_connect.php">Cancel</a>
            <button class="btn-danger" type="submit">Delete Device</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'disconnect' && $selectedDevice): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="disconnectDeviceDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="disconnectDeviceDialogTitle" class="dialog-title">Disconnect Device</h2>
          <a class="dialog-close" href="bio_connect.php" aria-label="Close dialog">x</a>
        </div>

        <p class="helper-text">Disconnect this device from the system?</p>

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
            </tbody>
          </table>
        </div>

        <form method="POST" action="bio_connect.php">
          <input type="hidden" name="action" value="disconnect_device">
          <input type="hidden" name="device_id" value="<?= e((string) ($selectedDevice['id'] ?? '0')) ?>">

          <div class="dialog-footer">
            <a class="btn-secondary" href="bio_connect.php">Cancel</a>
            <button class="btn-danger" type="submit">Disconnect Device</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  <script src="../../assets/logout-confirm.js"></script>
</body>
</html>