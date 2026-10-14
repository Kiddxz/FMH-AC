<?php
date_default_timezone_set('Asia/Manila');

/**
 * Database connection settings. XAMPP defaults are used unless config/database.local.php
 * (not committed, see database.local.php.example) or SUPREMEDENT_DB_* environment variables say otherwise.
 * For a real deployment create a dedicated MySQL user with a strong password instead of root.
 */
function supremeDentDbConfig(): array
{
    $config = ['host' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'supremedent_db'];
    $file = __DIR__ . '/database.local.php';
    if (is_file($file)) {
        $local = require $file;
        if (is_array($local)) $config = array_replace($config, array_intersect_key($local, $config));
    }
    foreach (['host' => 'SUPREMEDENT_DB_HOST', 'username' => 'SUPREMEDENT_DB_USER', 'password' => 'SUPREMEDENT_DB_PASSWORD', 'database' => 'SUPREMEDENT_DB_NAME'] as $key => $env) {
        $value = getenv($env);
        if ($value !== false && $value !== '') $config[$key] = $value;
    }
    return $config;
}

$dbConfig = supremeDentDbConfig();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
} catch (Throwable $e) {
    // Never show the database user or host to visitors; the detail goes to the PHP error log.
    error_log('[SupremeDent DB] Connection failed: ' . $e->getMessage());
    throw new RuntimeException("Database connection failed. Please start MySQL in XAMPP.");
}
if ($conn->connect_error) {
    error_log('[SupremeDent DB] Connection failed: ' . $conn->connect_error);
    throw new RuntimeException("Database connection failed. Please start MySQL in XAMPP.");
}
unset($dbConfig);
$conn->set_charset("utf8mb4");
// Match OTP timestamps to the PHP Asia/Manila clock.
$conn->query("SET time_zone = '+08:00'");
require_once __DIR__ . '/schema.php';
ensure_schema($conn);
