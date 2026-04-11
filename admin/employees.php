<?php

declare(strict_types=1);

session_start();

/* ================= AUTH ================= */
if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
  header('Location: login.php');
  exit;
}

$adminName = trim((string) $_SESSION['admin_name']);

/* ================= DB CONNECTION ================= */
require_once __DIR__ . '/../config/database.php';
$pdo = get_pdo_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= FUNCTIONS ================= */
function e(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function generateEmployeeKey(int $length = 6): string
{
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $key = '';
  for ($i = 0; $i < $length; $i++) {
    $key .= $chars[random_int(0, strlen($chars) - 1)];
  }

  return $key;
}

function formatTime(?string $time): string
{
  if (!$time) {
    return '-';
  }

  return date('h:i A', strtotime($time));
}

function displayOrDash(?string $value): string
{
  $text = trim((string) $value);
  return $text !== '' ? $text : '-';
}

function getEmployeeTableColumns(PDO $pdo): array
{
  $columns = [];

  $stmt = $pdo->query('SHOW COLUMNS FROM employees');
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($row['Field'])) {
      $columns[] = (string) $row['Field'];
    }
  }

  return $columns;
}

function tableExists(PDO $pdo, string $tableName): bool
{
  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$tableName]);
    return (bool) $stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function ensureEmployeeDetailColumns(PDO $pdo, array $currentColumns): array
{
  $requiredColumns = [
    'department' => "ALTER TABLE employees ADD COLUMN department VARCHAR(100) NOT NULL DEFAULT ''",
    'position' => "ALTER TABLE employees ADD COLUMN position VARCHAR(100) NOT NULL DEFAULT ''",
    'email' => "ALTER TABLE employees ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT ''",
    'mobile' => "ALTER TABLE employees ADD COLUMN mobile VARCHAR(20) NOT NULL DEFAULT ''",
    'shift_in' => 'ALTER TABLE employees ADD COLUMN shift_in TIME NULL',
    'shift_out' => 'ALTER TABLE employees ADD COLUMN shift_out TIME NULL',
    'status' => "ALTER TABLE employees ADD COLUMN status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'",
  ];

  $columnMap = [];
  foreach ($currentColumns as $columnName) {
    $columnMap[$columnName] = true;
  }

  foreach ($requiredColumns as $columnName => $sql) {
    if (isset($columnMap[$columnName])) {
      continue;
    }

    try {
      $pdo->exec($sql);
      $columnMap[$columnName] = true;
    } catch (Throwable $e) {
      // Keep running even when schema updates are not permitted.
    }
  }

  return getEmployeeTableColumns($pdo);
}

$employeeTableColumns = ensureEmployeeDetailColumns($pdo, getEmployeeTableColumns($pdo));
$supportsDepartment = in_array('department', $employeeTableColumns, true);
$supportsPosition = in_array('position', $employeeTableColumns, true);
$supportsEmail = in_array('email', $employeeTableColumns, true);
$supportsMobile = in_array('mobile', $employeeTableColumns, true);
$supportsShiftIn = in_array('shift_in', $employeeTableColumns, true);
$supportsShiftOut = in_array('shift_out', $employeeTableColumns, true);
$supportsStatus = in_array('status', $employeeTableColumns, true);
$hasDepartmentsTable = tableExists($pdo, 'departments');
$hasPositionsTable = tableExists($pdo, 'positions');

$missingEmployeeColumns = [];
foreach (['department', 'position', 'email', 'mobile', 'shift_in', 'shift_out', 'status'] as $columnName) {
  if (!in_array($columnName, $employeeTableColumns, true)) {
    $missingEmployeeColumns[] = $columnName;
  }
}

/* ================= AJAX: GET POSITIONS ================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'positions') {
  header('Content-Type: application/json');

  $departmentId = (int) ($_GET['department_id'] ?? 0);

  if ($hasDepartmentsTable && $hasPositionsTable && $departmentId > 0) {
    $stmt = $pdo->prepare('SELECT id, position_name FROM positions WHERE department_id = ? ORDER BY position_name ASC');
    $stmt->execute([$departmentId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  } else {
    echo json_encode([]);
  }
  exit;
}

/* ================= HANDLE ADD DEPARTMENT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
  if (!$hasDepartmentsTable) {
    header('Location: employees.php?error=departments_table_missing');
    exit;
  }

  $departmentName = trim((string) ($_POST['department_name'] ?? ''));

  if ($departmentName !== '') {
    $checkDept = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE department_name = ?');
    $checkDept->execute([$departmentName]);

    if ((int) $checkDept->fetchColumn() === 0) {
      $stmt = $pdo->prepare('INSERT INTO departments (department_name) VALUES (?)');
      $stmt->execute([$departmentName]);

      header('Location: employees.php?success=department_added');
      exit;
    }

    header('Location: employees.php?error=department_exists');
    exit;
  }
}

/* ================= HANDLE ADD POSITION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_position'])) {
  if (!$hasDepartmentsTable || !$hasPositionsTable) {
    header('Location: employees.php?error=positions_table_missing');
    exit;
  }

  $departmentId = (int) ($_POST['department_id'] ?? 0);
  $positionName = trim((string) ($_POST['position_name'] ?? ''));

  if ($departmentId > 0 && $positionName !== '') {
    $checkPos = $pdo->prepare('SELECT COUNT(*) FROM positions WHERE department_id = ? AND position_name = ?');
    $checkPos->execute([$departmentId, $positionName]);

    if ((int) $checkPos->fetchColumn() === 0) {
      $stmt = $pdo->prepare('INSERT INTO positions (department_id, position_name) VALUES (?, ?)');
      $stmt->execute([$departmentId, $positionName]);

      header('Location: employees.php?success=position_added');
      exit;
    }

    header('Location: employees.php?error=position_exists');
    exit;
  }
}

/* ================= HANDLE ADD EMPLOYEE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
  $name = trim((string) ($_POST['name'] ?? ''));
  $department = trim((string) ($_POST['department'] ?? ''));
  $position = trim((string) ($_POST['position'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $mobile = trim((string) ($_POST['mobile'] ?? ''));
  $shiftIn = trim((string) ($_POST['shift_in'] ?? ''));
  $shiftOut = trim((string) ($_POST['shift_out'] ?? ''));
  $status = trim((string) ($_POST['status'] ?? 'Active'));

  if ($name !== '') {
    do {
      $employeeKey = generateEmployeeKey(6);
      $check = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE employee_key = ?');
      $check->execute([$employeeKey]);
    } while ((int) $check->fetchColumn() > 0);

    $insertData = [
      'employee_key' => $employeeKey,
      'name' => $name,
    ];

    if ($supportsDepartment) {
      $insertData['department'] = $department;
    }

    if ($supportsPosition) {
      $insertData['position'] = $position;
    }

    if ($supportsEmail) {
      $insertData['email'] = $email;
    }

    if ($supportsMobile) {
      $insertData['mobile'] = $mobile;
    }

    if ($supportsShiftIn) {
      $insertData['shift_in'] = $shiftIn;
    }

    if ($supportsShiftOut) {
      $insertData['shift_out'] = $shiftOut;
    }

    if ($supportsStatus) {
      $insertData['status'] = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
    }

    $insertColumns = array_keys($insertData);
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));

    $stmt = $pdo->prepare(
      'INSERT INTO employees (' . implode(', ', $insertColumns) . ') VALUES (' . $placeholders . ')'
    );
    $stmt->execute(array_values($insertData));

    header('Location: employees.php?success=added');
    exit;
  }
}

/* ================= HANDLE DELETE EMPLOYEE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
  $employeeId = trim((string) ($_POST['employee_key'] ?? ''));

  if ($employeeId !== '') {
    $stmt = $pdo->prepare('DELETE FROM employees WHERE employee_key = ?');
    $stmt->execute([$employeeId]);

    header('Location: employees.php?success=deleted');
    exit;
  }
}

/* ================= HANDLE UPDATE EMPLOYEE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
  $employeeId = trim((string) ($_POST['employee_key'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $department = trim((string) ($_POST['department'] ?? ''));
  $position = trim((string) ($_POST['position'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $mobile = trim((string) ($_POST['mobile'] ?? ''));
  $shiftIn = trim((string) ($_POST['shift_in'] ?? ''));
  $shiftOut = trim((string) ($_POST['shift_out'] ?? ''));
  $status = trim((string) ($_POST['status'] ?? 'Active'));

  if ($employeeId !== '' && $name !== '') {
    $updateData = [
      'name' => $name,
    ];

    if ($supportsDepartment) {
      $updateData['department'] = $department;
    }

    if ($supportsPosition) {
      $updateData['position'] = $position;
    }

    if ($supportsEmail) {
      $updateData['email'] = $email;
    }

    if ($supportsMobile) {
      $updateData['mobile'] = $mobile;
    }

    if ($supportsShiftIn) {
      $updateData['shift_in'] = $shiftIn;
    }

    if ($supportsShiftOut) {
      $updateData['shift_out'] = $shiftOut;
    }

    if ($supportsStatus) {
      $updateData['status'] = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
    }

    $setClauses = [];
    foreach (array_keys($updateData) as $columnName) {
      $setClauses[] = $columnName . ' = ?';
    }

    $stmt = $pdo->prepare('UPDATE employees SET ' . implode(', ', $setClauses) . ' WHERE employee_key = ?');
    $params = array_values($updateData);
    $params[] = $employeeId;
    $stmt->execute($params);

    header('Location: employees.php?success=updated');
    exit;
  }
}

/* ================= LOAD DEPARTMENTS ================= */
$departments = [];
if ($hasDepartmentsTable) {
  $deptStmt = $pdo->query('SELECT id, department_name FROM departments ORDER BY department_name ASC');
  $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($supportsDepartment) {
  $deptStmt = $pdo->query('SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != "" ORDER BY department ASC');
  while ($row = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = [
      'id' => 0,
      'department_name' => (string) ($row['department'] ?? ''),
    ];
  }
}

/* ================= SEARCH / FILTER ================= */
$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

if (!$supportsStatus) {
  $statusFilter = '';
}

$selectParts = [
  in_array('id', $employeeTableColumns, true) ? 'id' : '0 AS id',
  'employee_key',
  'name',
  $supportsDepartment ? 'department' : "'' AS department",
  $supportsPosition ? 'position' : "'' AS position",
  $supportsEmail ? 'email' : "'' AS email",
  $supportsMobile ? 'mobile' : "'' AS mobile",
  $supportsShiftIn ? 'shift_in' : 'NULL AS shift_in',
  $supportsShiftOut ? 'shift_out' : 'NULL AS shift_out',
  $supportsStatus ? 'status' : "'Active' AS status",
];

$orderColumn = in_array('id', $employeeTableColumns, true) ? 'id' : 'employee_key';
$sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM employees WHERE 1=1';
$params = [];

if ($search !== '') {
  $searchConditions = ['name LIKE ?', 'employee_key LIKE ?'];
  if ($supportsDepartment) {
    $searchConditions[] = 'department LIKE ?';
  }
  if ($supportsPosition) {
    $searchConditions[] = 'position LIKE ?';
  }

  $sql .= ' AND (' . implode(' OR ', $searchConditions) . ')';
  $params[] = '%' . $search . '%';
  $params[] = '%' . $search . '%';

  if ($supportsDepartment) {
    $params[] = '%' . $search . '%';
  }
  if ($supportsPosition) {
    $params[] = '%' . $search . '%';
  }
}

if ($supportsStatus && $statusFilter !== '' && in_array($statusFilter, ['Active', 'Inactive'], true)) {
  $sql .= ' AND status = ?';
  $params[] = $statusFilter;
}

$sql .= ' ORDER BY ' . $orderColumn . ' ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= COUNTS ================= */
$totalStmt = $pdo->query('SELECT COUNT(*) FROM employees');
$totalEmployees = (int) $totalStmt->fetchColumn();

if ($supportsStatus) {
  $activeStmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'");
  $activeEmployees = (int) $activeStmt->fetchColumn();

  $inactiveStmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'Inactive'");
  $inactiveEmployees = (int) $inactiveStmt->fetchColumn();
} else {
  $activeEmployees = $totalEmployees;
  $inactiveEmployees = 0;
}

/* ================= DIALOG ================= */
$dialog = (string) ($_GET['dialog'] ?? '');
if (!in_array($dialog, ['add', 'delete', 'edit', 'department', 'position'], true)) {
  $dialog = '';
}

$employeeName = trim((string) ($_GET['name'] ?? 'Employee Name'));
$employeeId = trim((string) ($_GET['emp_id'] ?? 'EMP0000'));

if ($employeeName === '') {
  $employeeName = 'Employee Name';
}

if ($employeeId === '') {
  $employeeId = 'EMP0000';
}

$editName = trim((string) ($_GET['name'] ?? ''));
$editEmpId = trim((string) ($_GET['emp_id'] ?? ''));
$editDepartment = trim((string) ($_GET['department'] ?? ''));
$editPosition = trim((string) ($_GET['position'] ?? ''));
$editEmail = trim((string) ($_GET['email'] ?? ''));
$editMobile = trim((string) ($_GET['mobile'] ?? ''));
$editShiftIn = trim((string) ($_GET['shift_in'] ?? '08:00'));
$editShiftOut = trim((string) ($_GET['shift_out'] ?? '17:00'));
$editStatus = trim((string) ($_GET['status'] ?? 'Active'));

if ($editName === '') {
  $editName = 'Employee Name';
}

if ($editEmpId === '') {
  $editEmpId = 'EMP0000';
}

if ($editStatus !== 'Active' && $editStatus !== 'Inactive') {
  $editStatus = 'Active';
}

$departmentNameToId = [];
foreach ($departments as $dept) {
  $deptId = (int) ($dept['id'] ?? 0);
  $deptName = trim((string) ($dept['department_name'] ?? ''));
  if ($deptId > 0 && $deptName !== '') {
    $departmentNameToId[strtolower($deptName)] = $deptId;
  }
}

$editDepartmentId = 0;
if ($hasDepartmentsTable && $hasPositionsTable && $editDepartment !== '') {
  $editDepartmentId = (int) ($departmentNameToId[strtolower($editDepartment)] ?? 0);
}

$editPositions = [];
if ($hasDepartmentsTable && $hasPositionsTable && $editDepartmentId > 0) {
  $posStmt = $pdo->prepare('SELECT id, position_name FROM positions WHERE department_id = ? ORDER BY position_name ASC');
  $posStmt->execute([$editDepartmentId]);
  $editPositions = $posStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employees | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="<?= $dialog !== '' ? 'modal-open' : '' ?>">
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
          <a href="employees.php" class="dashboard-nav-link active">
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
          <a href="settings.php" class="dashboard-nav-link">
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
          <h1 class="dashboard-title">Employee List</h1>
          <p class="dashboard-subtitle">Manage your workforce and view employee details</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e($adminName) ?>
        </div>
      </header>

      <section class="dashboard-content">

        <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
          <div style="background:#d1fae5;padding:12px;border-radius:10px;margin-bottom:15px;">Employee added successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
          <div style="background:#fee2e2;padding:12px;border-radius:10px;margin-bottom:15px;">Employee deleted successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
          <div style="background:#dbeafe;padding:12px;border-radius:10px;margin-bottom:15px;">Employee updated successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'department_added'): ?>
          <div style="background:#ede9fe;padding:12px;border-radius:10px;margin-bottom:15px;">Department added successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'position_added'): ?>
          <div style="background:#dcfce7;padding:12px;border-radius:10px;margin-bottom:15px;">Position added successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'department_exists'): ?>
          <div style="background:#fef3c7;padding:12px;border-radius:10px;margin-bottom:15px;">Department already exists.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'position_exists'): ?>
          <div style="background:#fef3c7;padding:12px;border-radius:10px;margin-bottom:15px;">Position already exists in this department.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'departments_table_missing'): ?>
          <div style="background:#fee2e2;padding:12px;border-radius:10px;margin-bottom:15px;">Departments table is missing. Run setup for department management.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'positions_table_missing'): ?>
          <div style="background:#fee2e2;padding:12px;border-radius:10px;margin-bottom:15px;">Positions table is missing. Run setup for position management.</div>
        <?php endif; ?>

        <div class="dashboard-stats stats-3">
          <article class="dashboard-stat-card accent-teal">
            <div class="stat-icon-wrap teal">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>
            </div>
            <div>
              <p class="stat-label">Total Employees</p>
              <p class="stat-number"><?= $totalEmployees ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-green">
            <div class="stat-icon-wrap green">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m17 11 2 2 4-4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Active</p>
              <p class="stat-number"><?= $activeEmployees ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-slate">
            <div class="stat-icon-wrap slate">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m20 8-4 4"></path><path d="m16 8 4 4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Inactive</p>
              <p class="stat-number"><?= $inactiveEmployees ?></p>
            </div>
          </article>
        </div>

        <article class="dashboard-panel employee-panel">
          <div class="employee-panel-head">
            <h2 class="panel-title">Manage Employees</h2>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <a class="qa-btn qa-primary employee-add-btn" href="employees.php?dialog=add">+ Add New Employee</a>
              
            </div>
          </div>

          <form class="employee-toolbar" method="GET" action="employees.php" role="group" aria-label="Employee toolbar">
            <div class="search-input-wrap">
              <span class="search-input-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
              </span>
              <input class="input employee-search-input" type="search" name="search" value="<?= e($search) ?>" placeholder="Search name, employee ID, department...">
            </div>

            <div class="employee-toolbar-actions">
              <select name="status" aria-label="Status filter">
                <option value="">All Status</option>
                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
              <button class="qa-btn qa-primary employee-search-btn" type="submit">Search</button>
            </div>
          </form>

          <div class="table-wrap">
            <table class="timecard employee-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Employee ID</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Email</th>
                  <th>Mobile</th>
                  <th>Shift In</th>
                  <th>Shift Out</th>
                  <th>Status</th>
                  <th>Tools</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($employees) > 0): ?>
                  <?php foreach ($employees as $index => $emp): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td><?= e($emp['name']) ?></td>
                      <td><?= e($emp['employee_key']) ?></td>
                      <td><?= e(displayOrDash((string) ($emp['department'] ?? ''))) ?></td>
                      <td><?= e(displayOrDash((string) ($emp['position'] ?? ''))) ?></td>
                      <td><?= e(displayOrDash((string) ($emp['email'] ?? ''))) ?></td>
                      <td><?= e(displayOrDash((string) ($emp['mobile'] ?? ''))) ?></td>
                      <td><?= e(formatTime($emp['shift_in'])) ?></td>
                      <td><?= e(formatTime($emp['shift_out'])) ?></td>
                      <td>
                        <span class="badge <?= $emp['status'] === 'Active' ? 'success' : 'slate' ?>">
                          <?= e($emp['status']) ?>
                        </span>
                      </td>
                      <td>
                        <div class="tool-actions">
                          <a class="btn-mini edit"
                              href="employees.php?dialog=edit&emp_id=<?= urlencode((string) ($emp['employee_key'] ?? '')) ?>&name=<?= urlencode((string) ($emp['name'] ?? '')) ?>&department=<?= urlencode((string) ($emp['department'] ?? '')) ?>&position=<?= urlencode((string) ($emp['position'] ?? '')) ?>&email=<?= urlencode((string) ($emp['email'] ?? '')) ?>&mobile=<?= urlencode((string) ($emp['mobile'] ?? '')) ?>&shift_in=<?= urlencode((string) ($emp['shift_in'] ?? '')) ?>&shift_out=<?= urlencode((string) ($emp['shift_out'] ?? '')) ?>&status=<?= urlencode((string) ($emp['status'] ?? '')) ?>">
                             Edit
                          </a>
                          <a class="btn-mini delete"
                              href="employees.php?dialog=delete&emp_id=<?= urlencode((string) ($emp['employee_key'] ?? '')) ?>&name=<?= urlencode((string) ($emp['name'] ?? '')) ?>">
                             Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="11" style="text-align:center;">No employees found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="table-footnote">Showing <?= count($employees) ?> of <?= $totalEmployees ?> entries</p>
        </article>
      </section>
    </main>
  </div>

  <?php if ($dialog === 'add'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="addDialogTitle">
      <div class="dialog-card">
        <div class="dialog-head">
          <h2 id="addDialogTitle" class="dialog-title">Add New Employee</h2>
          <a class="dialog-close" href="employees.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogAddForm" class="dialog-form-grid" method="POST" action="employees.php">
          <label for="dialog_name">Name</label>
          <input class="input" id="dialog_name" name="name" type="text" required>

          <label for="dialog_department">Department</label>
          <?php if ($hasDepartmentsTable && count($departments) > 0): ?>
            <select id="dialog_department" name="department" required>
              <option value="">Select Department</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?= e((string) ($dept['department_name'] ?? '')) ?>" data-id="<?= e((string) ($dept['id'] ?? '0')) ?>">
                  <?= e((string) ($dept['department_name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input class="input" id="dialog_department" name="department" type="text" required>
          <?php endif; ?>

          <label for="dialog_position">Position</label>
          <?php if ($hasDepartmentsTable && $hasPositionsTable): ?>
            <select id="dialog_position" name="position" required disabled>
              <option value="">Select Department First</option>
            </select>
          <?php else: ?>
            <input class="input" id="dialog_position" name="position" type="text" required>
          <?php endif; ?>

          <label for="dialog_email">Email Address</label>
          <input class="input" id="dialog_email" name="email" type="email" required>

          <label for="dialog_mobile">Mobile No.</label>
          <input class="input" id="dialog_mobile" name="mobile" type="text" required>

          <label for="dialog_shift_in">Shift In</label>
          <input class="input" id="dialog_shift_in" name="shift_in" type="time" required>

          <label for="dialog_shift_out">Shift Out</label>
          <input class="input" id="dialog_shift_out" name="shift_out" type="time" required>

          <label for="dialog_status">Status</label>
          <select id="dialog_status" name="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Close</a>
          <button class="btn-primary" type="submit" form="dialogAddForm" name="add_employee">Save</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'edit'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="editDialogTitle">
      <div class="dialog-card">
        <div class="dialog-head">
          <h2 id="editDialogTitle" class="dialog-title">Edit Employee</h2>
          <a class="dialog-close" href="employees.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogEditForm" class="dialog-form-grid" method="POST" action="employees.php">
          <input type="hidden" name="employee_key" value="<?= e($editEmpId) ?>">

          <label for="edit_name">Name</label>
          <input class="input" id="edit_name" name="name" type="text" value="<?= e($editName) ?>" required>

          <label for="edit_emp_id">Employee ID</label>
          <input class="input" id="edit_emp_id" type="text" value="<?= e($editEmpId) ?>" readonly>

          <label for="edit_department">Department</label>
          <?php if ($hasDepartmentsTable && count($departments) > 0): ?>
            <select id="edit_department" name="department" required>
              <option value="">Select Department</option>
              <?php foreach ($departments as $dept): ?>
                <?php
                  $deptName = (string) ($dept['department_name'] ?? '');
                  $deptId = (string) ($dept['id'] ?? '0');
                ?>
                <option value="<?= e($deptName) ?>" data-id="<?= e($deptId) ?>" <?= $editDepartment === $deptName ? 'selected' : '' ?>>
                  <?= e($deptName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input class="input" id="edit_department" name="department" type="text" value="<?= e($editDepartment) ?>" required>
          <?php endif; ?>

          <label for="edit_position">Position</label>
          <?php if ($hasDepartmentsTable && $hasPositionsTable): ?>
            <select id="edit_position" name="position" required <?= $editDepartmentId > 0 ? '' : 'disabled' ?>>
              <option value=""><?= $editDepartmentId > 0 ? 'Select Position' : 'Select Department First' ?></option>
              <?php foreach ($editPositions as $position): ?>
                <?php $positionName = (string) ($position['position_name'] ?? ''); ?>
                <option value="<?= e($positionName) ?>" <?= $editPosition === $positionName ? 'selected' : '' ?>>
                  <?= e($positionName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input class="input" id="edit_position" name="position" type="text" value="<?= e($editPosition) ?>" required>
          <?php endif; ?>

          <label for="edit_email">Email Address</label>
          <input class="input" id="edit_email" name="email" type="email" value="<?= e($editEmail) ?>" required>

          <label for="edit_mobile">Mobile No.</label>
          <input class="input" id="edit_mobile" name="mobile" type="text" value="<?= e($editMobile) ?>" required>

          <label for="edit_shift_in">Shift In</label>
          <input class="input" id="edit_shift_in" name="shift_in" type="time" value="<?= e($editShiftIn) ?>" required>

          <label for="edit_shift_out">Shift Out</label>
          <input class="input" id="edit_shift_out" name="shift_out" type="time" value="<?= e($editShiftOut) ?>" required>

          <label for="edit_status">Status</label>
          <select id="edit_status" name="status">
            <option value="Active" <?= $editStatus === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $editStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogEditForm" name="update_employee">Update Employee</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'department'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="departmentDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="departmentDialogTitle" class="dialog-title">Add Department</h2>
          <a class="dialog-close" href="employees.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogDepartmentForm" class="dialog-form-grid" method="POST" action="employees.php">
          <label for="department_name">Department Name</label>
          <input class="input" id="department_name" name="department_name" type="text" placeholder="e.g. Kitchen" required>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogDepartmentForm" name="add_department">Save Department</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'position'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="positionDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="positionDialogTitle" class="dialog-title">Add Position</h2>
          <a class="dialog-close" href="employees.php" aria-label="Close dialog">x</a>
        </div>

        <form id="dialogPositionForm" class="dialog-form-grid" method="POST" action="employees.php">
          <label for="position_department_id">Department</label>
          <select id="position_department_id" name="department_id" required>
            <option value="">Select Department</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= e((string) ($dept['id'] ?? '0')) ?>"><?= e((string) ($dept['department_name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>

          <label for="position_name">Position Name</label>
          <input class="input" id="position_name" name="position_name" type="text" placeholder="e.g. Chef" required>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogPositionForm" name="add_position">Save Position</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($dialog === 'delete'): ?>
    <div class="dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="deleteDialogTitle">
      <div class="dialog-card dialog-card-sm">
        <div class="dialog-head">
          <h2 id="deleteDialogTitle" class="dialog-title">Delete Employee</h2>
          <a class="dialog-close" href="employees.php" aria-label="Close dialog">x</a>
        </div>

        <p class="helper-text">Are you sure you want to remove this employee record?</p>

        <div class="delete-employee-meta" role="group" aria-label="Employee details">
          <div class="meta-item">
            <span class="meta-label">Employee Name</span>
            <strong><?= e($employeeName) ?></strong>
          </div>
          <div class="meta-item">
            <span class="meta-label">Employee ID</span>
            <strong><?= e($employeeId) ?></strong>
          </div>
        </div>

        <form method="POST" action="employees.php">
          <input type="hidden" name="employee_key" value="<?= e($employeeId) ?>">
          <div class="dialog-footer">
            <a class="btn-secondary" href="employees.php">Cancel</a>
            <button class="btn-danger" type="submit" name="delete_employee">Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      function bindDepartmentPositionSelect(departmentId, positionId, selectedPosition) {
        const departmentSelect = document.getElementById(departmentId);
        const positionSelect = document.getElementById(positionId);

        if (!departmentSelect || !positionSelect || departmentSelect.tagName !== 'SELECT' || positionSelect.tagName !== 'SELECT') {
          return;
        }

        function loadPositions() {
          const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
          const departmentKey = selectedOption ? selectedOption.getAttribute('data-id') : '';

          positionSelect.disabled = true;
          positionSelect.innerHTML = '<option value="">Loading...</option>';

          if (!departmentKey || departmentKey === '0') {
            positionSelect.innerHTML = '<option value="">Select Department First</option>';
            return;
          }

          fetch('employees.php?ajax=positions&department_id=' + encodeURIComponent(departmentKey))
            .then(function (response) { return response.json(); })
            .then(function (data) {
              positionSelect.innerHTML = '<option value="">Select Position</option>';

              if (!Array.isArray(data) || data.length === 0) {
                positionSelect.innerHTML = '<option value="">No positions found</option>';
                positionSelect.disabled = true;
                return;
              }

              data.forEach(function (position) {
                const option = document.createElement('option');
                option.value = position.position_name;
                option.textContent = position.position_name;
                if (selectedPosition && selectedPosition === position.position_name) {
                  option.selected = true;
                }
                positionSelect.appendChild(option);
              });

              positionSelect.disabled = false;
            })
            .catch(function () {
              positionSelect.innerHTML = '<option value="">Error loading positions</option>';
              positionSelect.disabled = true;
            });
        }

        departmentSelect.addEventListener('change', function () {
          selectedPosition = '';
          loadPositions();
        });

        loadPositions();
      }

      bindDepartmentPositionSelect('dialog_department', 'dialog_position', '');
      bindDepartmentPositionSelect('edit_department', 'edit_position', <?= json_encode($editPosition) ?>);
    });
  </script>
  <script src="../assets/logout-confirm.js"></script>

</body>
</html>