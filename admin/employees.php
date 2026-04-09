<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
  header('Location: login.php');
  exit;
}

$adminName = trim((string) $_SESSION['admin_name']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$dialog = (string) ($_GET['dialog'] ?? '');
if ($dialog !== 'add' && $dialog !== 'delete' && $dialog !== 'edit') {
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
          <a href="attendance.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><path d="M8 14h3"></path></svg>
            Attendance
          </a>
          <a href="dtr.php" class="dashboard-nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path></svg>
            DTR
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
          <h1 class="dashboard-title">Employee List</h1>
          <p class="dashboard-subtitle">Manage your workforce and view employee details</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e($adminName) ?>
        </div>
      </header>

      <section class="dashboard-content">
        <div class="dashboard-stats stats-3">
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
              <p class="stat-label">Active</p>
              <p class="stat-number">121</p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-slate">
            <div class="stat-icon-wrap slate">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m20 8-4 4"></path><path d="m16 8 4 4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Inactive</p>
              <p class="stat-number">7</p>
            </div>
          </article>
        </div>

        <article class="dashboard-panel employee-panel">
          <div class="employee-panel-head">
            <h2 class="panel-title">Manage Employees</h2>
            <a class="qa-btn qa-primary employee-add-btn" href="employees.php?dialog=add">+ Add New Employee</a>
          </div>

          <div class="employee-toolbar" role="group" aria-label="Employee toolbar">
            <div class="search-input-wrap">
              <span class="search-input-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
              </span>
              <input class="input employee-search-input" type="search" placeholder="Search name, employee ID, department...">
            </div>

            <div class="employee-toolbar-actions">
              <select aria-label="Status filter">
                <option>All Status</option>
                <option>Active</option>
                <option>Inactive</option>
              </select>
              <button class="qa-btn qa-primary employee-search-btn" type="button">Search</button>
            </div>
          </div>

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
                <tr>
                  <td>1</td>
                  <td>John Doe</td>
                  <td>OHST8U</td>
                  <td>Marketing</td>
                  <td>Sales Representative</td>
                  <td>johndoe@email.com</td>
                  <td>0975788557</td>
                  <td>08:00 AM</td>
                  <td>05:00 PM</td>
                  <td><span class="badge success">Active</span></td>
                  <td>
                    <div class="tool-actions">
                      <a class="btn-mini edit" href="edit_employees.php?emp_id=OHST8U&amp;name=John+Doe&amp;department=Marketing&amp;position=Sales+Representative&amp;email=johndoe%40email.com&amp;mobile=0975788557&amp;shift_in=08%3A00&amp;shift_out=17%3A00&amp;status=Active">Edit</a>
                      <a class="btn-mini delete" href="employees.php?dialog=delete&amp;emp_id=OHST8U&amp;name=John+Doe">Delete</a>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>2</td>
                  <td>Ilele Ray</td>
                  <td>2DU0IS</td>
                  <td>Production</td>
                  <td>Product Manager</td>
                  <td>ileleray@email.com</td>
                  <td>3545766868</td>
                  <td>07:00 AM</td>
                  <td>04:00 PM</td>
                  <td><span class="badge success">Active</span></td>
                  <td>
                    <div class="tool-actions">
                      <a class="btn-mini edit" href="edit_employees.php?emp_id=2DU0IS&amp;name=Ilele+Ray&amp;department=Production&amp;position=Product+Manager&amp;email=ileleray%40email.com&amp;mobile=3545766868&amp;shift_in=07%3A00&amp;shift_out=16%3A00&amp;status=Active">Edit</a>
                      <a class="btn-mini delete" href="employees.php?dialog=delete&amp;emp_id=2DU0IS&amp;name=Ilele+Ray">Delete</a>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>3</td>
                  <td>Angela Smith</td>
                  <td>AK91QE</td>
                  <td>HR</td>
                  <td>HR Officer</td>
                  <td>angela.smith@email.com</td>
                  <td>09171234567</td>
                  <td>08:30 AM</td>
                  <td>05:30 PM</td>
                  <td><span class="badge slate">Inactive</span></td>
                  <td>
                    <div class="tool-actions">
                      <a class="btn-mini edit" href="edit_employees.php?emp_id=AK91QE&amp;name=Angela+Smith&amp;department=HR&amp;position=HR+Officer&amp;email=angela.smith%40email.com&amp;mobile=09171234567&amp;shift_in=08%3A30&amp;shift_out=17%3A30&amp;status=Inactive">Edit</a>
                      <a class="btn-mini delete" href="employees.php?dialog=delete&amp;emp_id=AK91QE&amp;name=Angela+Smith">Delete</a>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p class="table-footnote">Showing 1 to 3 of 128 entries</p>
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

        <form id="dialogAddForm" class="dialog-form-grid" action="#">
          <label for="dialog_name">Name</label>
          <input class="input" id="dialog_name" type="text" value="Cletus Igbe" required>

          <label for="dialog_title">Job Title</label>
          <select id="dialog_title">
            <option>IT Officer</option>
            <option>HR Officer</option>
            <option>Product Manager</option>
          </select>

          <label for="dialog_email">Email Address</label>
          <input class="input" id="dialog_email" type="email" value="cletus.igbe@gmail.com" required>

          <label for="dialog_mobile">Mobile No.</label>
          <input class="input" id="dialog_mobile" type="text" value="07060722008" required>

          <label for="dialog_photo">Photo</label>
          <input class="input" id="dialog_photo" type="file">
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Close</a>
          <button class="btn-primary" type="submit" form="dialogAddForm">Save</button>
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

        <form id="dialogEditForm" class="dialog-form-grid" action="#">
          <label for="edit_name">Name</label>
          <input class="input" id="edit_name" type="text" value="<?= e($editName) ?>" required>

          <label for="edit_emp_id">Employee ID</label>
          <input class="input" id="edit_emp_id" type="text" value="<?= e($editEmpId) ?>" readonly>

          <label for="edit_department">Department</label>
          <input class="input" id="edit_department" type="text" value="<?= e($editDepartment) ?>" required>

          <label for="edit_position">Position</label>
          <input class="input" id="edit_position" type="text" value="<?= e($editPosition) ?>" required>

          <label for="edit_email">Email Address</label>
          <input class="input" id="edit_email" type="email" value="<?= e($editEmail) ?>" required>

          <label for="edit_mobile">Mobile No.</label>
          <input class="input" id="edit_mobile" type="text" value="<?= e($editMobile) ?>" required>

          <label for="edit_shift_in">Shift In</label>
          <input class="input" id="edit_shift_in" type="time" value="<?= e($editShiftIn) ?>" required>

          <label for="edit_shift_out">Shift Out</label>
          <input class="input" id="edit_shift_out" type="time" value="<?= e($editShiftOut) ?>" required>

          <label for="edit_status">Status</label>
          <select id="edit_status">
            <option value="Active" <?= $editStatus === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $editStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </form>

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Cancel</a>
          <button class="btn-primary" type="submit" form="dialogEditForm">Update Employee</button>
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

        <div class="dialog-footer">
          <a class="btn-secondary" href="employees.php">Cancel</a>
          <button class="btn-danger" type="button" id="confirmDelete">Delete</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script>
    (function () {
      var addForm = document.getElementById("dialogAddForm");
      if (addForm) {
        addForm.addEventListener("submit", function (event) {
          event.preventDefault();
          window.alert("Frontend demo only. Employee save is not connected yet.");
        });
      }

      var editForm = document.getElementById("dialogEditForm");
      if (editForm) {
        editForm.addEventListener("submit", function (event) {
          event.preventDefault();
          window.alert("Frontend demo only. Employee update is not connected yet.");
        });
      }

      var confirmDelete = document.getElementById("confirmDelete");
      if (confirmDelete) {
        confirmDelete.addEventListener("click", function () {
          window.alert("Frontend demo only. Delete backend is not connected yet.");
        });
      }
    })();
  </script>
</body>
</html>
