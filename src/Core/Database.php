<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Single shared PDO connection to the application database (Singleton).
 *
 * The constructor is private: the only way to obtain the connection is
 * `Database::getInstance()->getConnection()`, which guarantees that a request
 * opens at most one connection.
 */
final class Database
{
    private static ?Database $instance = null;

    private PDO $connection;

    /**
     * Opens the connection to the database named in DB_NAME.
     */
    private function __construct()
    {
        $this->connection = self::connect(Config::require('DB_NAME'));
    }

    /**
     * Returns the unique instance, creating it on first call.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns the PDO connection held by the singleton.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Opens a connection to the MySQL/MariaDB server without selecting a
     * database, so that the installer can create the database first.
     */
    public static function connectToServer(): PDO
    {
        return self::connect(null);
    }

    /**
     * Builds a PDO connection configured for exceptions and associative fetches.
     *
     * @param string|null $databaseName Database to select, or null for none.
     */
    private static function connect(?string $databaseName): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306')
        );
        if ($databaseName !== null) {
            $dsn .= ';dbname=' . $databaseName;
        }

        return new PDO($dsn, Config::require('DB_USER'), Config::get('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Cloning would create a second instance: forbidden.
     */
    private function __clone()
    {
    }

    /**
     * Unserializing would create a second instance: forbidden.
     *
     * @throws \LogicException Always.
     */
    public function __wakeup(): void
    {
        throw new \LogicException('Database est un singleton et ne peut pas etre deserialise.');
    }
}
