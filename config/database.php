<?php

define('DEFAULT_DB_HOST', 'localhost');
define('DEFAULT_DB_PORT', 3306);
define('DEFAULT_DB_NAME', 'nookie');
define('DEFAULT_DB_USER', 'root');
define('DEFAULT_DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('LOCAL_DB_CONFIG_FILE', __DIR__ . '/local.database.php');

function read_db_env(string $key): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return null;
    }

    return $value;
}

function load_database_settings(): array
{
    $settings = [
        'host' => DEFAULT_DB_HOST,
        'port' => DEFAULT_DB_PORT,
        'name' => DEFAULT_DB_NAME,
        'user' => DEFAULT_DB_USER,
        'pass' => DEFAULT_DB_PASS,
    ];

    if (is_file(LOCAL_DB_CONFIG_FILE)) {
        $local = require LOCAL_DB_CONFIG_FILE;
        if (is_array($local)) {
            foreach (['host', 'port', 'name', 'user', 'pass'] as $key) {
                if (array_key_exists($key, $local)) {
                    $settings[$key] = $local[$key];
                }
            }
        }
    }

    $envMap = [
        'DB_HOST' => 'host',
        'DB_PORT' => 'port',
        'DB_NAME' => 'name',
        'DB_USER' => 'user',
        'DB_PASS' => 'pass',
    ];

    foreach ($envMap as $envKey => $settingKey) {
        $envValue = read_db_env($envKey);
        if ($envValue !== null) {
            $settings[$settingKey] = $envValue;
        }
    }

    $settings['port'] = (int) $settings['port'];
    if ($settings['port'] <= 0) {
        $settings['port'] = DEFAULT_DB_PORT;
    }

    return $settings;
}

function fail_database_connection(string $message): never
{
    $setupHint = 'Run setup.php once to save DB credentials for this machine.';
    if (PHP_SAPI === 'cli') {
        die("Database Connection Failed. {$message} {$setupHint}");
    }

    die("Database Connection Failed. {$message} {$setupHint}");
}

function get_mysqli_connection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = load_database_settings();

    $conn = @new mysqli($db['host'], $db['user'], (string) $db['pass'], $db['name'], (int) $db['port']);
    if ($conn->connect_error) {
        fail_database_connection($conn->connect_error);
    }

    $conn->set_charset(DB_CHARSET);
    return $conn;
}

function get_pdo_connection(): PDO
{
    $db = load_database_settings();

    $dsn = 'mysql:host=' . $db['host']
        . ';port=' . (int) $db['port']
        . ';dbname=' . $db['name']
        . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, $db['user'], (string) $db['pass']);
    } catch (PDOException $e) {
        fail_database_connection($e->getMessage());
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}
