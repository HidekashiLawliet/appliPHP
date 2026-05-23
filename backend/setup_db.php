<?php
/**
 * Database initialization script.
 *
 * Creates the userdata and trajets tables when needed, then imports sample
 * users from data/jeu-d-essais/users.txt.
 */
/**
 * Script d'initialisation de la base de données
 * Crée la table userdata si elle n'existe pas et importe les utilisateurs.
 */

require __DIR__ . '/config.php';
require_once __DIR__ . '/trajets_schema.php';

$file_path = __DIR__ . '/../data/jeu-d-essais/users.txt';

/**
 * Converts a CSV admin field into a database-ready boolean integer.
 */
function normalizeAdminValue(string $value): int
{
    $value = strtolower(trim($value));
    return in_array($value, ['1', 1], true) ? 1 : 0;
}

/**
 * Creates database objects and imports users from the sample data file.
 */
try {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $pdo->exec('USE ' . DB_NAME);

    $pdo->exec('CREATE TABLE IF NOT EXISTS userdata (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL COLLATE utf8mb4_general_ci,
        prenom VARCHAR(50) NOT NULL COLLATE utf8mb4_general_ci,
        tel VARCHAR(20) NOT NULL COLLATE utf8mb4_general_ci,
        email VARCHAR(150) NOT NULL COLLATE utf8mb4_general_ci UNIQUE,
        password VARCHAR(255) NOT NULL,
        admin TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

    $adminColumnStmt = $pdo->query("SHOW COLUMNS FROM userdata LIKE 'admin'");
    if (!$adminColumnStmt->fetch()) {
        $pdo->exec('ALTER TABLE userdata ADD admin TINYINT(1) NOT NULL DEFAULT 0');
    }

    ensureTrajetsTable($pdo);

    if (!file_exists($file_path)) {
        throw new Exception('Le fichier des utilisateurs est introuvable : ' . $file_path);
    }

    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new Exception('Impossible de lire le fichier des utilisateurs.');
    }

    $checkStmt = $pdo->prepare('SELECT id, admin FROM userdata WHERE email = :email');
    $insertStmt = $pdo->prepare('INSERT INTO userdata (nom, prenom, tel, email, password, admin) VALUES (:nom, :prenom, :tel, :email, :password, :admin)');
    /* No password where given so the temporary password is that, if you want each user to create their own you can remove this line
    ** and just get the password from the database, if needed i can add a "reset password" feature in the future.
    */
    $defaultPassword = 'Password123!';

    $imported = 0;
    $skipped = 0;

    foreach ($lines as $line) {
        $parts = str_getcsv($line);
        if (count($parts) < 4) {
            continue;
        }

        [$nom, $prenom, $tel, $email] = array_map('trim', array_slice($parts, 0, 4));
        $admin = normalizeAdminValue($parts[4] ?? '0');
        if ($email === '') {
            continue;
        }

        $checkStmt->execute(['email' => $email]);
        $existingUser = $checkStmt->fetch();
        if ($existingUser) {
            $admin = (int) $existingUser['admin'];
            $skipped++;
            continue;
        }

        $insertStmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':tel' => $tel,
            ':email' => $email,
            ':password' => $defaultPassword,
            ':admin' => $admin,
        ]);
        $imported++;
    }

} catch (PDOException $e) {
    die("<h2> Erreur lors de l'initialisation</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>");
} catch (Exception $e) {
    die("<h2> Erreur</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
