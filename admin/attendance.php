<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['admin_name']) || trim((string) $_SESSION['admin_name']) === '') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = get_pdo_connection();

$adminName = trim((string) $_SESSION['admin_name']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function toMinutes(?string $time): ?int
{
    if ($time === null || trim($time) === '') {
        return null;
    }

    $formats = ['Y-m-d H:i:s', 'H:i:s', 'H:i'];

    foreach ($formats as $format) {
        $clock = DateTime::createFromFormat($format, $time);
        if ($clock instanceof DateTime) {
            return ((int) $clock->format('H') * 60) + (int) $clock->format('i');
        }
    }

    return null;
}

function durationMinutes(?string $start, ?string $end): ?int
{
    $startMinutes = toMinutes($start);
    $endMinutes = toMinutes($end);

    if ($startMinutes === null || $endMinutes === null) {
        return null;
    }

    if ($endMinutes < $startMinutes) {
        $endMinutes += 24 * 60;
    }

    return $endMinutes - $startMinutes;
}

function formatMinutes(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;
    return $hours . 'h ' . str_pad((string) $remainingMinutes, 2, '0', STR_PAD_LEFT) . 'm';
}

function formatClock(?string $time): string
{
    if ($time === null || trim($time) === '') {
        return '-';
    }

    $formats = ['Y-m-d H:i:s', 'H:i:s', 'H:i'];

    foreach ($formats as $format) {
        $clock = DateTime::createFromFormat($format, $time);
        if ($clock instanceof DateTime) {
            return $clock->format('h:i A');
        }
    }

    return '-';
}

function attendanceStatus(array $record): string
{
    $timeIn = trim((string) ($record['time_in'] ?? ''));
    $timeOut = trim((string) ($record['time_out'] ?? ''));
    $scheduleStart = trim((string) ($record['schedule_start'] ?? ''));

    if ($timeIn === '') {
        return 'Absent';
    }

    if ($timeOut === '') {
        return 'Incomplete';
    }

    $lateMinutes = durationMinutes($scheduleStart, $timeIn);

    if ($lateMinutes !== null && $lateMinutes > 10) {
        return 'Late';
    }

    return 'On Time';
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

$employeeTableColumns = getEmployeeTableColumns($pdo);
$hasDepartmentColumn = in_array('department', $employeeTableColumns, true);

/* ================= FILTERS ================= */
$today = date('Y-m-d');
$selectedDate = $_GET['date'] ?? $today;

if (!isValidDate($selectedDate)) {
    $selectedDate = $today;
}

/* ================= FETCH DEPARTMENTS ================= */
$departments = ['all' => 'All Departments'];

if ($hasDepartmentColumn) {
  $deptStmt = $pdo->query("
    SELECT DISTINCT department
    FROM employees
    WHERE department IS NOT NULL AND department != ''
    ORDER BY department ASC
  ");

  while ($dept = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
    $department = trim((string) $dept['department']);
    $key = strtolower(str_replace(' ', '_', $department));
    $departments[$key] = $department;
  }
}

$selectedDepartment = $_GET['department'] ?? 'all';
if (!array_key_exists($selectedDepartment, $departments)) {
    $selectedDepartment = 'all';
}

$statusOptions = [
    'all' => 'All Status',
    'on_time' => 'On Time',
    'late' => 'Late',
    'incomplete' => 'Incomplete',
    'absent' => 'Absent',
];

$selectedStatus = $_GET['status'] ?? 'all';
if (!array_key_exists($selectedStatus, $statusOptions)) {
    $selectedStatus = 'all';
}

/* ================= FETCH ATTENDANCE FROM DB ================= */
$sql = "
    SELECT 
        a.employee_key,
        a.date,
        a.time_in,
        a.time_out,
        a.schedule_start,
        a.schedule_end,
        e.name AS employee_name,
    " . ($hasDepartmentColumn ? 'e.department' : "'N/A' AS department") . "
    FROM attendance a
    INNER JOIN employees e ON a.employee_key = e.employee_key
    WHERE a.date = :selected_date
";

$params = [
    ':selected_date' => $selectedDate
];

if ($hasDepartmentColumn && $selectedDepartment !== 'all') {
    $sql .= " AND LOWER(REPLACE(e.department, ' ', '_')) = :department";
    $params[':department'] = $selectedDepartment;
}

$sql .= " ORDER BY e.name ASC, a.time_in ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= BUILD TABLE ROWS ================= */
$rows = [];

foreach ($records as $record) {
    $status = attendanceStatus($record);
    $statusKey = strtolower(str_replace(' ', '_', $status));

    if ($selectedStatus !== 'all' && $selectedStatus !== $statusKey) {
        continue;
    }

    $workMinutes = durationMinutes(
        $record['time_in'] ?? null,
        $record['time_out'] ?? null
    );

    $rows[] = [
        'employee_name' => (string) ($record['employee_name'] ?? 'Unknown'),
        'department' => (string) ($record['department'] ?? 'N/A'),
        'shift' => formatClock($record['schedule_start'] ?? null) . ' - ' . formatClock($record['schedule_end'] ?? null),
        'time_in' => formatClock($record['time_in'] ?? null),
        'time_out' => formatClock($record['time_out'] ?? null),
        'hours' => $workMinutes === null ? 'Incomplete' : formatMinutes($workMinutes),
        'status' => $status,
        'source' => 'Biometric Scanner',
        'minutes' => $workMinutes,
    ];
}

/* ================= STATS ================= */
$totalLogs = count($rows);
$onTimeCount = count(array_filter($rows, static fn(array $row): bool => $row['status'] === 'On Time'));
$lateCount = count(array_filter($rows, static fn(array $row): bool => $row['status'] === 'Late'));
$incompleteCount = count(array_filter($rows, static fn(array $row): bool => $row['status'] === 'Incomplete'));

$totalMinutes = 0;
foreach ($rows as $row) {
    if (is_int($row['minutes'])) {
        $totalMinutes += $row['minutes'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
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
          <a href="attendance.php" class="dashboard-nav-link active">
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
          <h1 class="dashboard-title">Attendance Logs</h1>
          <p class="dashboard-subtitle">View and filter daily attendance records</p>
        </div>

        <div class="dashboard-profile-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e($adminName) ?>
        </div>
      </header>

      <section class="dashboard-content">
        <div class="dashboard-stats">
          <article class="dashboard-stat-card accent-teal">
            <div class="stat-icon-wrap teal">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="3" width="10" height="18" rx="2"></rect><path d="M9 7h6"></path><path d="M9 11h6"></path><path d="M9 15h4"></path></svg>
            </div>
            <div>
              <p class="stat-label">Total Logs</p>
              <p class="stat-number"><?= e((string) $totalLogs) ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-green">
            <div class="stat-icon-wrap green">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="m17 11 2 2 4-4"></path></svg>
            </div>
            <div>
              <p class="stat-label">On Time</p>
              <p class="stat-number"><?= e((string) $onTimeCount) ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-amber">
            <div class="stat-icon-wrap amber">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
            </div>
            <div>
              <p class="stat-label">Late</p>
              <p class="stat-number"><?= e((string) $lateCount) ?></p>
            </div>
          </article>

          <article class="dashboard-stat-card accent-teal">
            <div class="stat-icon-wrap teal">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"></circle><path d="M12 13l3-3"></path><path d="M9 3h6"></path></svg>
            </div>
            <div>
              <p class="stat-label">Total Worked</p>
              <p class="stat-number"><?= e(formatMinutes($totalMinutes)) ?></p>
            </div>
          </article>
        </div>

        <article class="dashboard-panel employee-panel">
          <div class="employee-panel-head">
            <h2 class="panel-title">Filter Attendance</h2>
            <a class="qa-btn qa-secondary" href="../biometrics%20scanner/index.php">Open Biometric Scanner</a>
          </div>

          <form class="attendance-filter" method="get" action="attendance.php">
            <div>
              <label for="date">Date</label>
              <input class="input" id="date" name="date" type="date" value="<?= e($selectedDate) ?>">
            </div>

            <div>
              <label for="department">Department</label>
              <select id="department" name="department">
                <?php foreach ($departments as $key => $name): ?>
                  <option value="<?= e($key) ?>" <?= $selectedDepartment === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="status">Status</label>
              <select id="status" name="status">
                <?php foreach ($statusOptions as $key => $name): ?>
                  <option value="<?= e($key) ?>" <?= $selectedStatus === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label>&nbsp;</label>
              <button class="btn-primary" type="submit">View Attendance</button>
            </div>
          </form>
        </article>

        <article class="dashboard-panel employee-panel">
          <h2 class="panel-title">Daily Attendance Table</h2>
          <div class="table-wrap">
            <table class="timecard employee-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Shift</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Total Hours</th>
                  <th>Status</th>
                  <th>Source</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($rows === []): ?>
                  <tr>
                    <td colspan="9">No attendance records found for the selected filters.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows as $index => $row): ?>
                    <?php
                      $statusClass = 'warning';
                      if ($row['status'] === 'On Time') {
                          $statusClass = 'success';
                      } elseif ($row['status'] === 'Incomplete' || $row['status'] === 'Absent') {
                          $statusClass = 'danger';
                      }
                    ?>
                    <tr>
                      <td><?= e((string) ($index + 1)) ?></td>
                      <td><?= e($row['employee_name']) ?></td>
                      <td><?= e($row['department']) ?></td>
                      <td><?= e($row['shift']) ?></td>
                      <td><?= e($row['time_in']) ?></td>
                      <td><?= e($row['time_out']) ?></td>
                      <td><?= e($row['hours']) ?></td>
                      <td><span class="badge <?= e($statusClass) ?>"><?= e($row['status']) ?></span></td>
                      <td><?= e($row['source']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="table-footnote">Incomplete records indicate missing Time Out or no Time In data.</p>
        </article>
      </section>
    </main>
  </div>
</body>
</html>