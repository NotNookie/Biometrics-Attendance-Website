<?php
declare(strict_types=1);
session_start();

/* ================= AUTH ================= */
if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
    header('Location: login.php');
    exit;
}

$adminName = trim((string) $_SESSION['admin_name']);

/* ================= DB ================= */
require_once __DIR__ . '/../config/database.php';
$pdo = get_pdo_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= FUNCTIONS ================= */
function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* ================= HANDLE POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_department'])) {
        $name = trim($_POST['department_name']);
        if ($name !== '') {
            $pdo->prepare("INSERT INTO departments (department_name) VALUES (?)")->execute([$name]);
        }
        header("Location: departments.php");
        exit;
    }

    if (isset($_POST['add_position'])) {
        $deptId = (int)$_POST['department_id'];
        $name = trim($_POST['position_name']);
        if ($deptId > 0 && $name !== '') {
            $pdo->prepare("INSERT INTO positions (department_id, position_name) VALUES (?, ?)")->execute([$deptId, $name]);
        }
        header("Location: departments.php");
        exit;
    }

    if (isset($_POST['delete_department'])) {
        $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$_POST['id']]);
        header("Location: departments.php");
        exit;
    }

    if (isset($_POST['delete_position'])) {
        $pdo->prepare("DELETE FROM positions WHERE id=?")->execute([$_POST['id']]);
        header("Location: departments.php");
        exit;
    }
}

/* ================= LOAD DATA ================= */
$stmt = $pdo->query("
    SELECT d.id AS dept_id, d.department_name,
           p.id AS pos_id, p.position_name
    FROM departments d
    LEFT JOIN positions p ON d.id = p.department_id
    ORDER BY d.department_name ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = [];
foreach ($rows as $r) {
    if (!isset($departments[$r['dept_id']])) {
        $departments[$r['dept_id']] = [
            'name' => $r['department_name'],
            'positions' => []
        ];
    }
    if ($r['pos_id']) {
        $departments[$r['dept_id']]['positions'][] = [
            'id' => $r['pos_id'],
            'name' => $r['position_name']
        ];
    }
}

/* ================= DIALOG ================= */
$dialog = (string) ($_GET['dialog'] ?? '');
if (!in_array($dialog, ['add_department', 'add_position'], true)) {
    $dialog = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Departments | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="<?= $dialog !== '' ? 'modal-open' : '' ?>">

<div class="dashboard-layout">

<!-- SIDEBAR -->
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
          <a href="departments.php" class="dashboard-nav-link active">
  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M3 7h5l2 3h11v10a2 2 0 0 1-2 2H3z"></path>
    <path d="M3 7V5a2 2 0 0 1 2-2h4l2 3"></path>
  </svg>
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
          <a href="logout.php" class="dashboard-nav-link js-logout-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
            Logout
          </a>
        </div>
      </nav>
</aside>

<!-- MAIN -->
<main class="dashboard-main">

<header class="dashboard-header">
  <div>
    <h1 class="dashboard-title">Departments</h1>
    <p class="dashboard-subtitle">Manage departments and their positions</p>
  </div>

  <div class="dashboard-profile-pill">
    <span class="dot" aria-hidden="true"></span>
    Admin: <?= e($adminName) ?>
  </div>
</header>

<section class="dashboard-content">

<article class="dashboard-panel employee-panel">
  <div class="employee-panel-head">
    <h2 class="panel-title">Department Directory</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="?dialog=add_department" class="qa-btn qa-primary">+ Add Department</a>
      <a href="?dialog=add_position" class="qa-btn qa-secondary">+ Add Position</a>
    </div>
  </div>

  <div class="table-wrap">
<table class="timecard employee-table">
  <thead>
    <tr>
      <th>Department</th>
      <th>Position</th>
      <th>Position Actions</th>
      <th>Department Actions</th>
    </tr>
  </thead>

  <tbody>
  <?php foreach ($departments as $deptId => $dept): ?>

    <?php if (count($dept['positions']) > 0): ?>
      <?php foreach ($dept['positions'] as $pos): ?>
      <tr>
        <td><?= e($dept['name']) ?></td>
        <td><?= e($pos['name']) ?></td>

        <td>
          <div class="tool-actions">
            <a class="btn-mini edit" href="#">Edit</a>

            <form method="POST">
              <input type="hidden" name="id" value="<?= $pos['id'] ?>">
              <button class="btn-mini delete" name="delete_position">Delete</button>
            </form>
     </div>
        </td>

        <td>
          <div class="tool-actions">
            <a class="btn-mini edit" href="#">Edit</a>

            <form method="POST">
              <input type="hidden" name="id" value="<?= $deptId ?>">
              <button class="btn-mini delete" name="delete_department">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>

    <?php else: ?>
      <tr>
        <td><?= e($dept['name']) ?></td>
        <td colspan="2">No positions</td>

        <td>
          <form method="POST">
            <input type="hidden" name="id" value="<?= $deptId ?>">
            <button class="btn-mini delete" name="delete_department">Delete</button>
          </form>
        </td>
      </tr>
    <?php endif; ?>

  <?php endforeach; ?>
  </tbody>
</table>
</div>

</article>

</section>
</main>
</div>

<!-- ADD DEPARTMENT MODAL -->
<?php if ($dialog === 'add_department'): ?>
<div class="dialog-overlay">
  <div class="dialog-card">

    <div class="dialog-head">
      <h2 class="dialog-title">Add Department</h2>
      <a class="dialog-close" href="departments.php">x</a>
    </div>

    <form id="deptForm" class="dialog-form-grid" method="POST">
      <label>Department Name</label>
      <input class="input" name="department_name" required>
    </form>

    <div class="dialog-footer">
      <a class="btn-secondary" href="departments.php">Cancel</a>
      <button class="btn-primary" form="deptForm" name="add_department">Save</button>
    </div>

  </div>
</div>
<?php endif; ?>

<!-- ADD POSITION MODAL -->
<?php if ($dialog === 'add_position'): ?>
<div class="dialog-overlay">
  <div class="dialog-card">

    <div class="dialog-head">
      <h2 class="dialog-title">Add Position</h2>
      <a class="dialog-close" href="departments.php">x</a>
    </div>

    <form id="posForm" class="dialog-form-grid" method="POST">

      <label>Department</label>
      <select name="department_id" required>
        <option value="">Select Department</option>
        <?php foreach ($departments as $id => $d): ?>
          <option value="<?= $id ?>"><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Position Name</label>
      <input class="input" name="position_name" required>

    </form>

    <div class="dialog-footer">
      <a class="btn-secondary" href="departments.php">Cancel</a>
      <button class="btn-primary" form="posForm" name="add_position">Save</button>
    </div>

  </div>
</div>
<?php endif; ?>

<script src="../assets/logout-confirm.js"></script>
</body>
</html>