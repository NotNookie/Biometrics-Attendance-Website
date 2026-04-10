<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$current = load_database_settings();
$error = '';
$success = '';
$next = trim((string) ($_GET['next'] ?? $_POST['next'] ?? ''));

function resolve_post_setup_redirect(string $next): string
{
    if ($next !== '') {
        $decodedNext = rawurldecode($next);

        if (strpos($decodedNext, '://') === false && str_starts_with($decodedNext, '/')) {
            if (!str_contains(strtolower($decodedNext), '/setup.php')) {
                return $decodedNext;
            }
        }
    }

    if (isset($_SESSION['admin_name']) && trim((string) $_SESSION['admin_name']) !== '') {
        return 'admin/dashboard.php';
    }

    return 'admin/login.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string) ($_POST['host'] ?? DEFAULT_DB_HOST));
    $port = (int) ($_POST['port'] ?? DEFAULT_DB_PORT);
    $name = trim((string) ($_POST['name'] ?? DEFAULT_DB_NAME));
    $user = trim((string) ($_POST['user'] ?? DEFAULT_DB_USER));
    $pass = (string) ($_POST['pass'] ?? '');
    $import = isset($_POST['import_sql']) && $_POST['import_sql'] === '1';

    if ($host === '' || $name === '' || $user === '' || $port <= 0) {
        $error = 'Please fill in host, port, database name, and user.';
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli($host, $user, $pass, '', $port);

        if ($conn->connect_error) {
            $error = 'Connection failed: ' . $conn->connect_error;
        } else {
            $dbNameEscaped = str_replace('`', '``', $name);
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $error = 'Unable to create/access database: ' . $conn->error;
            } else {
                if ($import) {
                    $sqlFile = __DIR__ . '/nookie.sql';
                    if (!is_file($sqlFile)) {
                        $error = 'nookie.sql not found. Uncheck import or add the SQL file.';
                    } else {
                        $sql = (string) file_get_contents($sqlFile);
                        if (!$conn->select_db($name)) {
                            $error = 'Cannot select database: ' . $conn->error;
                        } elseif (!$conn->multi_query($sql)) {
                            $error = 'SQL import failed: ' . $conn->error;
                        } else {
                            do {
                                if ($result = $conn->store_result()) {
                                    $result->free();
                                }
                            } while ($conn->more_results() && $conn->next_result());
                        }
                    }
                }

                if ($error === '') {
                    $localConfig = [
                        'host' => $host,
                        'port' => $port,
                        'name' => $name,
                        'user' => $user,
                        'pass' => $pass,
                    ];

                    $content = "<?php\n\nreturn " . var_export($localConfig, true) . ";\n";
                    $saveOk = file_put_contents(__DIR__ . '/config/local.database.php', $content, LOCK_EX);

                    if ($saveOk === false) {
                        $error = 'Could not write config/local.database.php. Check folder write permission.';
                    } else {
                        $success = 'Setup complete. Database settings saved for this machine.';
                        $current = $localConfig;

                        $redirectTarget = resolve_post_setup_redirect($next);
                        header('Location: ' . $redirectTarget);
                        exit;
                    }
                }
            }

            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        h1 { margin-top: 0; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { margin-bottom: 12px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #cfd6e4; border-radius: 8px; box-sizing: border-box; }
        .actions { margin-top: 8px; }
        button { background: #0f62fe; color: #fff; border: 0; border-radius: 8px; padding: 10px 14px; cursor: pointer; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .ok { background: #e8f7ee; color: #116329; border: 1px solid #b8e3c4; }
        .err { background: #ffecec; color: #7d1f1f; border: 1px solid #f2b4b4; }
        .hint { color: #444; font-size: 13px; margin-top: 8px; }
        @media (max-width: 640px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="card">
        <h1>Database Setup</h1>
        <p>Run this once on each computer. It saves credentials to config/local.database.php and the app will use them automatically.</p>

        <?php if ($success !== ''): ?>
            <div class="msg ok"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="msg err"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="row">
                <div class="field">
                    <label for="host">Host</label>
                    <input id="host" name="host" value="<?= e((string) ($current['host'] ?? DEFAULT_DB_HOST)) ?>" required>
                </div>
                <div class="field">
                    <label for="port">Port</label>
                    <input id="port" name="port" type="number" value="<?= e((string) ($current['port'] ?? DEFAULT_DB_PORT)) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="field">
                    <label for="name">Database Name</label>
                    <input id="name" name="name" value="<?= e((string) ($current['name'] ?? DEFAULT_DB_NAME)) ?>" required>
                </div>
                <div class="field">
                    <label for="user">Database User</label>
                    <input id="user" name="user" value="<?= e((string) ($current['user'] ?? DEFAULT_DB_USER)) ?>" required>
                </div>
            </div>

            <div class="field">
                <label for="pass">Database Password</label>
                <input id="pass" name="pass" type="password" value="<?= e((string) ($current['pass'] ?? '')) ?>">
            </div>

            <div class="field">
                <label>
                    <input type="checkbox" name="import_sql" value="1" checked>
                    Import nookie.sql after creating database
                </label>
            </div>

            <div class="actions">
                <button type="submit">Save and Test Connection</button>
            </div>

            <p class="hint">After success, open the app normally. No code edits needed for database password changes per machine.</p>
        </form>
    </div>
</body>
</html>
