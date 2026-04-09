<?php

declare(strict_types=1);

$params = [
    'dialog' => 'edit',
    'emp_id' => (string) ($_GET['emp_id'] ?? ''),
    'name' => (string) ($_GET['name'] ?? ''),
    'department' => (string) ($_GET['department'] ?? ''),
    'position' => (string) ($_GET['position'] ?? ''),
    'email' => (string) ($_GET['email'] ?? ''),
    'mobile' => (string) ($_GET['mobile'] ?? ''),
    'shift_in' => (string) ($_GET['shift_in'] ?? ''),
    'shift_out' => (string) ($_GET['shift_out'] ?? ''),
    'status' => (string) ($_GET['status'] ?? ''),
];

header('Location: employees.php?' . http_build_query($params));
exit;
