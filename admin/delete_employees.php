<?php

declare(strict_types=1);

$params = [
    'dialog' => 'delete',
    'emp_id' => (string) ($_GET['emp_id'] ?? ''),
    'name' => (string) ($_GET['name'] ?? ''),
];

header('Location: employees.php?' . http_build_query($params));
exit;
