<?php
/**
 * Database and authentication configuration.
 *
 * Defines connection constants and creates the shared PDO instance used by the
 * rest of the application.
 *
 * @var PDO $pdo
 */
// Load environment variables from .env if present.
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if ($name === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv("{$name}={$value}");
        }
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Returns an environment variable or a default value.
 */
function env(string $name, $default = null)
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// Database connection parameters
if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', 'tpak'));
/* CHANGE PASSWORD AND USER TO NOT DISPLAY THEM IN CLEAR */
if (!defined('DB_USER')) define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS', 'AdminRoot13'));
if (!defined('AUTH_COOKIE_SECRET')) {
    /**
     * Secret key used to sign persistent login cookies.
     *
     * Change this value before deploying to production.
     */
    /* :warning: TO CHANGE BEFORE PROD */
    define('AUTH_COOKIE_SECRET', env('AUTH_COOKIE_SECRET', 'ToChangeBeforeProduction'));
}

/**
 * Opens the PDO connection and configures exception-based error handling.
 */
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // erreurs levées
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la BD : '.htmlspecialchars($e->getMessage()));
}

?>
