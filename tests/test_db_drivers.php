<?php
/**
 * Database driver availability test.
 *
 * Lists available PDO drivers and attempts connection to MariaDB using
 * backend/config.php credentials, then SQLite as fallback.
 *
 * Usage: php tests/test_db_drivers.php
 */
declare(strict_types=1);
// Display available PDO drivers.
$drivers = PDO::getAvailableDrivers();
echo "Drivers disponibles: " . implode(', ', $drivers) . "\n";

/**
 * Parse database configuration constants from backend/config.php using regex.
 *
 * @param string $path Path to config.php file.
 * @return array{host?: string, name?: string, user?: string, pass?: string} Database credentials.
 */
function parse_db_constants(string $path): array
{
    if (!is_readable($path)) return [];
    $content = file_get_contents($path);
    $res = [];
    if (preg_match("/define\('\s*DB_HOST\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['host'] = $m[1];
    if (preg_match("/define\('\s*DB_NAME\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['name'] = $m[1];
    if (preg_match("/define\('\s*DB_USER\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['user'] = $m[1];
    if (preg_match("/define\('\s*DB_PASS\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['pass'] = $m[1];
    return $res;
}

$config = parse_db_constants(__DIR__ . '/../backend/config.php');

// Priority: test MariaDB/MySQL if driver available and config loaded.
if (in_array('mysql', $drivers, true) && !empty($config)) {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['name']);
    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "MariaDB/MySQL connexion: OK\n";
        exit(0);
    } catch (Exception $e) {
        echo "MariaDB/MySQL connexion: FAIL - " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Fallback: test SQLite in-memory if available.
if (in_array('sqlite', $drivers, true)) {
    try {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "SQLite in-memory: OK\n";
        exit(0);
    } catch (Exception $e) {
        echo "SQLite in-memory: FAIL - " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "No suitable database driver found (mysql/mariadb or sqlite).\n";
exit(2);
