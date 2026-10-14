<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Csrf;
use App\Models\Agence;
use App\Models\Trajet;
use App\Models\Utilisateur;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that need a database.
 *
 * Each test gets a fresh in-memory SQLite database built from
 * tests/Support/schema.sqlite.sql, and clean $_SESSION / $_POST / $_COOKIE.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec((string) file_get_contents(__DIR__ . '/schema.sqlite.sql'));

        $_SESSION = [];
        $_POST = [];
        $_COOKIE = [];
    }

    /**
     * Creates a user with the password "secret" and returns its id.
     */
    protected function createUser(string $email = 'jean.dupont@example.com', bool $admin = false): int
    {
        return (new Utilisateur($this->pdo))->create('Dupont', 'Jean', '0600000000', $email, 'secret', $admin);
    }

    /**
     * Creates an agency and returns its id.
     */
    protected function createAgence(string $nom): int
    {
        return (new Agence($this->pdo))->create($nom);
    }

    /**
     * Creates a trip in the future and returns its id.
     */
    protected function createTrajet(int $userId, int $departId, int $arriveeId, string $start = '+1 day', int $free = 2): int
    {
        $depart = new DateTimeImmutable($start);

        return (new Trajet($this->pdo))->create($userId, [
            'agence_depart_id' => $departId,
            'agence_arrivee_id' => $arriveeId,
            'date_depart' => $depart->format('Y-m-d H:i:s'),
            'date_arrivee' => $depart->modify('+2 hours')->format('Y-m-d H:i:s'),
            'places_total' => 3,
            'places_disponibles' => $free,
        ]);
    }

    /**
     * Simulates a logged-in user in the session.
     */
    protected function actingAs(int $userId, bool $admin = false): void
    {
        $_SESSION['_user'] = [
            'id' => $userId,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'est_admin' => $admin,
        ];
    }

    /**
     * Simulates a form submission with a valid CSRF token.
     *
     * @param array<string, string> $fields
     */
    protected function post(array $fields): void
    {
        $_POST = $fields + [Csrf::FIELD => Csrf::token()];
    }

    /**
     * Returns the number of rows of a table.
     */
    protected function countRows(string $table): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM ' . $table);
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }
}
