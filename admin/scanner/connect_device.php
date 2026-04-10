<?php
declare(strict_types=1);

$id = (int) ($_GET['id'] ?? 0);
$query = 'bio_connect.php?dialog=connect';

if ($id > 0) {
    $query .= '&id=' . $id;
}

header('Location: ' . $query);
exit;