<?php
include "db.php";

$fingerprint = $_POST['fingerprint'];

$sql = "SELECT * FROM employees WHERE fingerprint_id='$fingerprint'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $emp = $result->fetch_assoc();
    $emp_id = $emp['id'];

    // Check if already timed in today
    $check = $conn->query("SELECT * FROM attendance 
        WHERE employee_id='$emp_id' 
        AND DATE(time_in)=CURDATE()");

    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO attendance (employee_id, time_in)
                      VALUES ('$emp_id', NOW())");

        echo "✅ Time In successful for " . $emp['name'];
    } else {
        echo "⚠️ Already timed in today!";
    }

} else {
    echo "❌ Fingerprint not recognized!";
}
?>