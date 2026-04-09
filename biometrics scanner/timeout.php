<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set("Asia/Manila");

require_once __DIR__ . '/db.php';

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

// Find today's attendance
$check = $conn->prepare("SELECT * FROM attendance WHERE employee_key = ? AND date = ?");
$check->bind_param("ss", $emp_key, $today);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    die("❌ No Time In found today");
}

$row = $res->fetch_assoc();

if (empty($row['time_in'])) {
    die("❌ No Time In found");
}

if (!empty($row['time_out'])) {
    die("⚠ Already timed out today");
}

// Update timeout
$update = $conn->prepare("UPDATE attendance SET time_out = ? WHERE id = ?");
$update->bind_param("si", $now, $row['id']);

if ($update->execute()) {
    echo "✅ Time Out successful for <b>$name</b> at <b>$now</b>";
} else {
    echo "❌ Update failed: " . $conn->error;
}
?>