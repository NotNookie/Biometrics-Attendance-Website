<?php
include "db.php";

$fingerprint = $_POST['fingerprint'];

$sql = "SELECT * FROM employees WHERE fingerprint_id='$fingerprint'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $emp = $result->fetch_assoc();
    $emp_id = $emp['id'];

    // Find today's record without timeout
    $check = $conn->query("SELECT * FROM attendance 
        WHERE employee_id='$emp_id' 
        AND DATE(time_in)=CURDATE()
        AND time_out IS NULL");

    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();

        $conn->query("UPDATE attendance 
                      SET time_out = NOW() 
                      WHERE id = '".$row['id']."'");

        echo "✅ Time Out successful for " . $emp['name'];
    } else {
        echo "⚠️ No Time In record found!";
    }

} else {
    echo "❌ Fingerprint not recognized!";
}
?>