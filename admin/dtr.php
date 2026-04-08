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
          Admin: Cletus Igbe
        </div>
      </header>

      <section class="content">
        <article class="card">
          <h2 class="card-title">Filter Time Card</h2>
          <div class="filter-bar">
            <div>
              <label for="employee">Employee</label>
              <select id="employee">
                <option>John Doe</option>
                <option>Ilele Ray</option>
              </select>
            </div>

            <div>
              <label for="from">From</label>
              <input class="input" id="from" type="date" value="2026-04-01">
            </div>

            <div>
              <label for="to">To</label>
              <input class="input" id="to" type="date" value="2026-04-08">
            </div>

            <div>
              <label>&nbsp;</label>
              <button class="btn-primary" type="button">View Reports</button>
            </div>
          </div>
        </article>

        <article class="card">
          <h2 class="card-title">DTR / Time Card Table</h2>
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
                <tr>
                  <td>2026-04-03</td>
                  <td>8:00 AM - 5:00 PM</td>
                  <td>Regular</td>
                  <td>1.00</td>
                  <td>08:01 AM</td>
                  <td>05:03 PM</td>
                  <td>9:02</td>
                  <td>8:00</td>
                  <td>40:00</td>
                  <td><span class="badge success">None</span></td>
                  <td>HQ</td>
                </tr>
                <tr>
                  <td>2026-04-04</td>
                  <td>8:00 AM - 5:00 PM</td>
                  <td>Regular</td>
                  <td>1.00</td>
                  <td>08:12 AM</td>
                  <td>05:01 PM</td>
                  <td>8:49</td>
                  <td>7:50</td>
                  <td>47:50</td>
                  <td><span class="badge warning">Late</span></td>
                  <td>HQ</td>
                </tr>
                <tr>
                  <td>2026-04-05</td>
                  <td>8:00 AM - 5:00 PM</td>
                  <td>Regular</td>
                  <td>1.00</td>
                  <td>08:00 AM</td>
                  <td>05:00 PM</td>
                  <td>9:00</td>
                  <td>8:00</td>
                  <td>55:50</td>
                  <td><span class="badge success">None</span></td>
                  <td>HQ</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>
