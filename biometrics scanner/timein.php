<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set("Asia/Manila");

$conn = new mysqli("localhost", "root", "", "nookie");
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$fingerprint = trim($_POST['fingerprint'] ?? '');

if ($fingerprint === '') {
    die("❌ Please enter Employee Key");
}

// Check employee
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_key = ?");
$stmt->bind_param("s", $fingerprint);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Employee not found");
}

$emp = $result->fetch_assoc();
$emp_key = $emp['employee_key'];
$name = $emp['name'];

$today = date("Y-m-d");
$now   = date("H:i:s");

// Check if record exists today
$check = $conn->prepare("SELECT * FROM attendance WHERE employee_key = ? AND date = ?");
$check->bind_param("ss", $emp_key, $today);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();

    if (!empty($row['time_in']) && empty($row['time_out'])) {
        die("⚠ Already timed in today");
    }

    if (!empty($row['time_in']) && !empty($row['time_out'])) {
        die("⚠ Already completed attendance today");
    }
}

// Auto Late check
$absence = ($now > "08:00:00") ? "Late" : "None";

// Insert attendance
$sql = "INSERT INTO attendance 
(employee_key, date, schedule_start, schedule_end, pay_code, amount, time_in, time_out, absence, transfer)
VALUES (?, ?, '08:00:00', '17:00:00', 'Regular', 1.00, ?, NULL, ?, 'HQ')";

$insert = $conn->prepare($sql);
$insert->bind_param("ssss", $emp_key, $today, $now, $absence);

if ($insert->execute()) {
    echo "✅ Time In successful for <b>$name</b> at <b>$now</b>";
} else {
    echo "❌ Insert failed: " . $conn->error;
}
?>