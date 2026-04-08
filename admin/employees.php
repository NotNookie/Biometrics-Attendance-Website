<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$dialog = (string) ($_GET['dialog'] ?? '');
if ($dialog !== 'add' && $dialog !== 'delete') {
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
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">Attendance System</div>
      <nav class="nav-group">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="employees.php" class="nav-link active">Employees</a>
        <a href="attendance.php" class="nav-link">Attendance</a>
        <a href="dtr.php" class="nav-link">DTR</a>
        <a href="logout.php" class="nav-link">Logout</a>
      </nav>
    </aside>

    <main class="main-panel">
      <header class="topbar">
        <h1 class="page-title">Employee List</h1>
        <div class="admin-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: Cletus Igbe
        </div>
      </header>

      <section class="content">
        <div class="card-grid card-grid-3">
          <article class="stat-card">
            <p class="stat-title">Total Employees</p>
            <p class="stat-value">128</p>
          </article>
          <article class="stat-card">
            <p class="stat-title">Active</p>
            <p class="stat-value">121</p>
          </article>
          <article class="stat-card">
            <p class="stat-title">Inactive</p>
            <p class="stat-value">7</p>
          </article>
        </div>

        <article class="card">
          <div class="card-header-row">
            <h2 class="card-title">Manage Employees</h2>
            <div class="download-actions">
              <a class="btn-primary" href="employees.php?dialog=add">+ Add New Employee</a>
            </div>
          </div>

          <div class="table-toolbar" role="group" aria-label="Employee toolbar">
            <input class="input" type="search" placeholder="Search name, employee ID, department...">
            <select aria-label="Status filter">
              <option>All Status</option>
              <option>Active</option>
              <option>Inactive</option>
            </select>
            <button class="btn-secondary" type="button">Search</button>
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
                      <button class="btn-mini edit" type="button">Edit</button>
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
                      <button class="btn-mini edit" type="button">Edit</button>
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
                  <td><span class="badge warning">Inactive</span></td>
                  <td>
                    <div class="tool-actions">
                      <button class="btn-mini edit" type="button">Edit</button>
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
