<?php
declare(strict_types=1);
session_start();

/* ================= DB CONNECTION ================= */
require_once __DIR__ . '/../config/database.php';
$pdo = get_pdo_connection();

/* ================= FUNCTIONS ================= */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isValidDate(string $value): bool {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function toMinutes(?string $time): ?int {
    if (!$time) return null;

    $formats = ['Y-m-d H:i:s', 'H:i:s', 'H:i'];
    foreach ($formats as $format) {
        $clock = DateTime::createFromFormat($format, $time);
        if ($clock) {
            return ((int)$clock->format('H') * 60) + (int)$clock->format('i');
        }
    }

    return null;
}

function durationMinutes(?string $start, ?string $end): ?int {
    $start = toMinutes($start);
    $end = toMinutes($end);
    if ($start === null || $end === null) return null;
    if ($end < $start) $end += 1440;
    return $end - $start;
}

function formatMinutes(int $m): string {
    return intdiv($m, 60) . ':' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT);
}

function formatClock(?string $time): string {
    if (!$time) return '-';

    $formats = ['Y-m-d H:i:s', 'H:i:s', 'H:i'];
    foreach ($formats as $format) {
        $clock = DateTime::createFromFormat($format, $time);
        if ($clock) {
            return $clock->format('h:i A');
        }
    }

    return '-';
}

/* ================= FILTERS ================= */
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-t');

$selectedEmployee = $_GET['employee'] ?? 'all';
$fromDate = $_GET['from'] ?? $defaultFrom;
$toDate = $_GET['to'] ?? $defaultTo;

if (!isValidDate($fromDate)) $fromDate = $defaultFrom;
if (!isValidDate($toDate)) $toDate = $defaultTo;

/* ================= FETCH EMPLOYEES ================= */
$employees = ['all' => 'All Employees'];

$stmt = $pdo->query("SELECT employee_key, name FROM employees ORDER BY name ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $employees[$row['employee_key']] = $row['name'];
}

/* ================= FETCH ATTENDANCE ================= */
$sql = "
SELECT a.*, e.name AS employee_name
FROM attendance a
JOIN employees e ON a.employee_key = e.employee_key
WHERE a.date BETWEEN :from AND :to
";

$params = [
    ':from' => $fromDate,
    ':to' => $toDate
];

if ($selectedEmployee !== 'all') {
    $sql .= " AND a.employee_key = :emp";
    $params[':emp'] = $selectedEmployee;
}

$sql .= " ORDER BY e.name, a.date";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= PROCESS DATA ================= */
$runningTotals = [];
$summaryTotals = [];
$rows = [];

foreach ($records as $r) {
    $key = $r['employee_key'];

    $shift = durationMinutes($r['schedule_start'], $r['schedule_end']);
    $daily = durationMinutes($r['time_in'], $r['time_out']);

    if (!isset($runningTotals[$key])) $runningTotals[$key] = 0;
    if (!isset($summaryTotals[$key])) {
        $summaryTotals[$key] = ['name' => $r['employee_name'], 'minutes' => 0];
    }

    if ($daily !== null) {
        $runningTotals[$key] += $daily;
        $summaryTotals[$key]['minutes'] += $daily;
    }

    $absence = $r['absence'] ?? 'None';

    $rows[] = [
        'date' => $r['date'],
        'employee' => $r['employee_name'],
        'schedule' => formatClock($r['schedule_start']) . ' - ' . formatClock($r['schedule_end']),
        'pay' => $r['pay_code'] ?? 'Regular',
        'amount' => $r['amount'] ?? '1.00',
        'in' => formatClock($r['time_in']),
        'out' => $r['time_out'] ? formatClock($r['time_out']) : 'Missing',
        'shift' => $shift !== null ? formatMinutes($shift) : '-',
        'daily' => $daily !== null ? formatMinutes($daily) : 'Incomplete',
        'period' => formatMinutes($runningTotals[$key]),
        'absence' => $absence,
        'transfer' => $r['transfer'] ?? 'HQ'
    ];
}

/* ================= EXPORT ================= */
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $delimiter = $type === 'xls' ? "\t" : ',';

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=dtr_export.csv");

    $out = fopen("php://output", "w");

    fputcsv($out, ['Date','Employee','Schedule','Pay Code','Amount','Time In','Time Out','Shift','Daily Total','Period Total','Absence','Transfer'], $delimiter);

    foreach ($rows as $row) {
        fputcsv($out, [
            $row['date'],
            $row['employee'],
            $row['schedule'],
            $row['pay'],
            $row['amount'],
            $row['in'],
            $row['out'],
            $row['shift'],
            $row['daily'],
            $row['period'],
            $row['absence'],
            $row['transfer']
        ], $delimiter);
    }

    fclose($out);
    exit;
}

/* ================= ADMIN NAME ================= */
$adminName = $_SESSION['admin_name'] ?? 'Admin';
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

        <!-- FILTER CARD -->
        <article class="card">
          <h2 class="card-title">Filter Time Card</h2>

          <form method="GET" class="filter-bar">
            <div>
              <label for="employee">Employee</label>
              <select id="employee" name="employee">
                <?php foreach ($employees as $k => $v): ?>
                  <option value="<?= e((string)$k) ?>" <?= $k == $selectedEmployee ? 'selected' : '' ?>>
                    <?= e($v) ?>
                  </option>
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

            <div>
              <label>&nbsp;</label>
              <a class="btn-primary" style="text-decoration:none;display:inline-block;text-align:center;"
                 href="?employee=<?= urlencode((string)$selectedEmployee) ?>&from=<?= urlencode($fromDate) ?>&to=<?= urlencode($toDate) ?>&download=csv">
                 Download CSV
              </a>
            </div>
          </form>
        </article>

        <!-- DTR TABLE -->
        <article class="card">
          <h2 class="card-title">DTR / Time Card Table</h2>
          <div class="table-wrap">
            <table class="timecard">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Employee</th>
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
                <?php if (count($rows) > 0): ?>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td><?= e($r['date']) ?></td>
                      <td><?= e($r['employee']) ?></td>
                      <td><?= e($r['schedule']) ?></td>
                      <td><?= e($r['pay']) ?></td>
                      <td><?= e((string)$r['amount']) ?></td>
                      <td><?= e($r['in']) ?></td>
                      <td><?= e($r['out']) ?></td>
                      <td><?= e($r['shift']) ?></td>
                      <td><?= e($r['daily']) ?></td>
                      <td><?= e($r['period']) ?></td>
                      <td>
                        <?php
                          $absenceClass = 'success';
                          if (strtolower($r['absence']) === 'late') $absenceClass = 'warning';
                          elseif (strtolower($r['absence']) === 'absent') $absenceClass = 'danger';
                        ?>
                        <span class="badge <?= $absenceClass ?>"><?= e($r['absence']) ?></span>
                      </td>
                      <td><?= e($r['transfer']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="12">No attendance records found.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <!-- SUMMARY -->
        <article class="card">
          <h2 class="card-title">Summary Total Hours</h2>
          <div class="table-wrap">
            <table class="timecard">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Total Hours</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($summaryTotals) > 0): ?>
                  <?php foreach ($summaryTotals as $s): ?>
                    <tr>
                      <td><?= e($s['name']) ?></td>
                      <td><?= e(formatMinutes($s['minutes'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="2">No summary data available.</td>
                  </tr>
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