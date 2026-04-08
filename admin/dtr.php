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

function isValidDate(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function toMinutes(?string $time): ?int
{
    if ($time === null || $time === '') {
        return null;
    }

    $clock = DateTime::createFromFormat('H:i', $time);
    if (!$clock instanceof DateTime) {
        return null;
    }

    return ((int) $clock->format('H') * 60) + (int) $clock->format('i');
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

function formatDailyTotal(?int $minutes): string
{
    if ($minutes === null) {
        return 'Incomplete';
    }

    return formatMinutes($minutes);
}

function formatClock(?string $time): string
{
    if ($time === null || $time === '') {
        return '-';
    }

    $clock = DateTime::createFromFormat('H:i', $time);
    if (!$clock instanceof DateTime) {
        return '-';
    }

    return $clock->format('h:i A');
}

$employees = [
    'all' => 'All Employees',
    'john_doe' => 'John Doe',
    'ilele_ray' => 'Ilele Ray',
];

$records = [
    [
        'employee_key' => 'john_doe',
        'employee_name' => 'John Doe',
        'date' => '2026-04-01',
        'schedule_start' => '08:00',
        'schedule_end' => '17:00',
        'pay_code' => 'Regular',
        'amount' => '1.00',
        'time_in' => '08:03',
        'time_out' => '17:05',
        'absence' => 'None',
        'transfer' => 'HQ',
    ],
    [
        'employee_key' => 'john_doe',
        'employee_name' => 'John Doe',
        'date' => '2026-04-02',
        'schedule_start' => '08:00',
        'schedule_end' => '17:00',
        'pay_code' => 'Regular',
        'amount' => '1.00',
        'time_in' => '08:10',
        'time_out' => '',
        'absence' => 'None',
        'transfer' => 'HQ',
    ],
    [
        'employee_key' => 'ilele_ray',
        'employee_name' => 'Ilele Ray',
        'date' => '2026-04-01',
        'schedule_start' => '07:00',
        'schedule_end' => '16:00',
        'pay_code' => 'Regular',
        'amount' => '1.00',
        'time_in' => '07:01',
        'time_out' => '16:02',
        'absence' => 'None',
        'transfer' => 'Production',
    ],
    [
        'employee_key' => 'ilele_ray',
        'employee_name' => 'Ilele Ray',
        'date' => '2026-04-03',
        'schedule_start' => '07:00',
        'schedule_end' => '16:00',
        'pay_code' => 'Regular',
        'amount' => '1.00',
        'time_in' => '07:12',
        'time_out' => '16:05',
        'absence' => 'Late',
        'transfer' => 'Production',
    ],
];

$defaultFrom = '2026-04-01';
$defaultTo = '2026-04-30';

$selectedEmployee = $_GET['employee'] ?? 'all';
if (!array_key_exists($selectedEmployee, $employees)) {
    $selectedEmployee = 'all';
}

$fromDate = $_GET['from'] ?? $defaultFrom;
if (!isValidDate($fromDate)) {
    $fromDate = $defaultFrom;
}

$toDate = $_GET['to'] ?? $defaultTo;
if (!isValidDate($toDate)) {
    $toDate = $defaultTo;
}

$filteredRecords = array_values(array_filter($records, function (array $record) use ($selectedEmployee, $fromDate, $toDate): bool {
    if ($selectedEmployee !== 'all' && $record['employee_key'] !== $selectedEmployee) {
        return false;
    }

    if ($record['date'] < $fromDate || $record['date'] > $toDate) {
        return false;
    }

    return true;
}));

usort($filteredRecords, static function (array $first, array $second): int {
    if ($first['employee_name'] === $second['employee_name']) {
        return strcmp($first['date'], $second['date']);
    }
    return strcmp($first['employee_name'], $second['employee_name']);
});

$runningTotals = [];
$summaryTotals = [];
$processedRows = [];

foreach ($filteredRecords as $record) {
    $employeeKey = $record['employee_key'];
    $shiftMinutes = durationMinutes($record['schedule_start'], $record['schedule_end']);
    $dailyMinutes = durationMinutes($record['time_in'], $record['time_out']);

    if (!isset($runningTotals[$employeeKey])) {
        $runningTotals[$employeeKey] = 0;
    }

    if (!isset($summaryTotals[$employeeKey])) {
        $summaryTotals[$employeeKey] = [
            'name' => $record['employee_name'],
            'minutes' => 0,
        ];
    }

    if ($dailyMinutes !== null) {
        $runningTotals[$employeeKey] += $dailyMinutes;
        $summaryTotals[$employeeKey]['minutes'] += $dailyMinutes;
    }

    $processedRows[] = [
        'employee_name' => $record['employee_name'],
        'date' => $record['date'],
        'schedule' => formatClock($record['schedule_start']) . ' - ' . formatClock($record['schedule_end']),
        'pay_code' => $record['pay_code'],
        'amount' => $record['amount'],
        'time_in' => formatClock($record['time_in']),
        'time_out' => $record['time_out'] === '' ? 'Missing' : formatClock($record['time_out']),
        'shift' => $shiftMinutes === null ? '-' : formatMinutes($shiftMinutes),
        'daily_total' => formatDailyTotal($dailyMinutes),
        'period_total' => formatMinutes($runningTotals[$employeeKey]),
        'absence' => $record['absence'],
        'transfer' => $record['transfer'],
    ];
}

$downloadType = $_GET['download'] ?? '';
if (in_array($downloadType, ['csv', 'xls'], true)) {
    $delimiter = $downloadType === 'xls' ? "\t" : ',';
    $extension = $downloadType === 'xls' ? 'xls' : 'csv';
    $contentType = $downloadType === 'xls' ? 'application/vnd.ms-excel' : 'text/csv';

    header('Content-Type: ' . $contentType . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="dtr_export_' . date('Ymd_His') . '.' . $extension . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        exit;
    }

    fputcsv($output, ['Date', 'Employee', 'Schedule', 'Pay Code', 'Amount', 'Time In', 'Time Out', 'Shift', 'Daily Total', 'Period Total', 'Absence', 'Transfer'], $delimiter);

    foreach ($processedRows as $row) {
        fputcsv($output, [
            $row['date'],
            $row['employee_name'],
            $row['schedule'],
            $row['pay_code'],
            $row['amount'],
            $row['time_in'],
            $row['time_out'],
            $row['shift'],
            $row['daily_total'],
            $row['period_total'],
            $row['absence'],
            $row['transfer'],
        ], $delimiter);
    }

    if ($summaryTotals !== []) {
        fputcsv($output, [], $delimiter);
        fputcsv($output, ['Summary: Total Hours per Employee'], $delimiter);
        fputcsv($output, ['Employee', 'Total Hours'], $delimiter);

        foreach ($summaryTotals as $summary) {
            fputcsv($output, [$summary['name'], formatMinutes($summary['minutes'])], $delimiter);
        }
    }

    fclose($output);
    exit;
}

$csvQuery = http_build_query([
    'employee' => $selectedEmployee,
    'from' => $fromDate,
    'to' => $toDate,
    'download' => 'csv',
]);

$excelQuery = http_build_query([
    'employee' => $selectedEmployee,
    'from' => $fromDate,
    'to' => $toDate,
    'download' => 'xls',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DTR / Time Card | Biometric Attendance</title>
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
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="employees.php" class="nav-link">Employees</a>
        <a href="attendance.php" class="nav-link">Attendance</a>
        <a href="dtr.php" class="nav-link active">DTR</a>
        <a href="logout.php" class="nav-link">Logout</a>
      </nav>
    </aside>

    <main class="main-panel">
      <header class="topbar">
        <h1 class="page-title">Attendance Time Card</h1>
        <div class="admin-pill">
          <span class="dot" aria-hidden="true"></span>
          Admin: <?= e($adminName) ?>
        </div>
      </header>

      <section class="content">
        <article class="card">
          <h2 class="card-title">Filter Time Card</h2>
          <form class="filter-bar" method="get" action="dtr.php">
            <div>
              <label for="employee">Employee</label>
              <select id="employee" name="employee">
                <?php foreach ($employees as $key => $name): ?>
                  <option value="<?= e($key) ?>" <?= $selectedEmployee === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="from">From</label>
              <input class="input" id="from" name="from" type="date" value="<?= e($fromDate) ?>">
            </div>

            <div>
              <label for="to">To</label>
              <input class="input" id="to" name="to" type="date" value="<?= e($toDate) ?>">
            </div>

            <div>
              <label>&nbsp;</label>
              <button class="btn-primary" type="submit">View Reports</button>
            </div>
          </form>
        </article>

        <article class="card">
          <div class="card-header-row">
            <h2 class="card-title">DTR / Time Card Table</h2>
            <div class="download-actions">
              <a class="btn-secondary" href="dtr.php?<?= e($csvQuery) ?>">Download CSV</a>
              <a class="btn-secondary" href="dtr.php?<?= e($excelQuery) ?>">Download Excel</a>
            </div>
          </div>

          <div class="table-wrap">
            <table class="timecard">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Schedule</th>
                  <th>Pay Code</th>
                  <th>Amount</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Shift</th>
                  <th>Daily Total</th>
                  <th>Period Total</th>
                  <th>Absence</th>
                  <th>Transfer</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($processedRows === []): ?>
                  <tr>
                    <td colspan="11">No records found for the selected filters.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($processedRows as $row): ?>
                    <tr>
                      <td><?= e($row['date']) ?></td>
                      <td><?= e($row['schedule']) ?></td>
                      <td><?= e($row['pay_code']) ?></td>
                      <td><?= e($row['amount']) ?></td>
                      <td><?= e($row['time_in']) ?></td>
                      <td><?= e($row['time_out']) ?></td>
                      <td><?= e($row['shift']) ?></td>
                      <td><?= e($row['daily_total']) ?></td>
                      <td><?= e($row['period_total']) ?></td>
                      <td>
                        <span class="badge <?= $row['absence'] === 'None' ? 'success' : 'warning' ?>"><?= e($row['absence']) ?></span>
                      </td>
                      <td><?= e($row['transfer']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="summary-box">
            <h3 class="summary-title">Total Hours per Employee</h3>
            <table class="summary-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Total Hours</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($summaryTotals === []): ?>
                  <tr>
                    <td colspan="2">No summary available.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($summaryTotals as $summary): ?>
                    <tr>
                      <td><?= e($summary['name']) ?></td>
                      <td><?= e(formatMinutes($summary['minutes'])) ?></td>
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
</body>
</html>
