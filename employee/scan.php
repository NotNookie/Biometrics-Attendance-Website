<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Scan | Biometric Attendance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <section class="scan-page">
    <article class="scan-card">
      <h1 class="scan-title">Employee Scan</h1>
      <p class="scan-sub">Press the button to simulate biometric or QR attendance scan.</p>

      <button class="scan-button" id="scanBtn" type="button">SCAN NOW</button>

      <p class="last-scan" id="lastScan">Last Scan: --:-- --</p>
      <span class="status-ready">Scanner status: Ready</span>
    </article>
  </section>

  <script>
    (function () {
      var scanBtn = document.getElementById("scanBtn");
      var lastScan = document.getElementById("lastScan");

      function formatNow(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, "0");
        var d = String(date.getDate()).padStart(2, "0");
        var h = String(date.getHours()).padStart(2, "0");
        var min = String(date.getMinutes()).padStart(2, "0");
        var s = String(date.getSeconds()).padStart(2, "0");
        return y + "-" + m + "-" + d + " " + h + ":" + min + ":" + s;
      }

      scanBtn.addEventListener("click", function () {
        lastScan.textContent = "Last Scan: " + formatNow(new Date());
      });
    })();
  </script>
</body>
</html>
