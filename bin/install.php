<?php

/**
 * Database installer: `composer db:install` (or `php bin/install.php`).
 *
 * 1. connects to the MySQL/MariaDB server WITHOUT selecting a database,
 * 2. drops and recreates the DB_NAME database from .env,
 * 3. runs database/01_schema.sql, 02_seed.sql and 03_create_admin.sql.
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

/**
 * Splits a SQL script into statements (one statement ends with ";" at end of line).
 *
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $withoutComments = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $statements = preg_split('/;\s*$/m', $withoutComments) ?: [];

    return array_values(array_filter(array_map('trim', $statements), static fn (string $s): bool => $s !== ''));
}

try {
    Config::load($root);
    $databaseName = Config::require('DB_NAME');
    if (preg_match('/^\w+$/', $databaseName) !== 1) {
        throw new RuntimeException('DB_NAME ne doit contenir que des lettres, chiffres et "_".');
    }

    $pdo = Database::connectToServer();
    echo "Connexion au serveur : OK\n";

    $pdo->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    $pdo->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$databaseName}`");
    echo "Base \"{$databaseName}\" creee.\n";

    foreach (['01_schema.sql', '02_seed.sql', '03_create_admin.sql'] as $file) {
        $sql = file_get_contents($root . '/database/' . $file);
        if ($sql === false) {
            throw new RuntimeException("Fichier introuvable : database/{$file}");
        }
        foreach (splitSqlStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
        echo "database/{$file} : OK\n";
    }

    echo "\nInstallation terminee. Administrateur : alexandre.martin@email.fr / Password123!\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Echec de l\'installation : ' . $exception->getMessage() . "\n");
    exit(1);
}
