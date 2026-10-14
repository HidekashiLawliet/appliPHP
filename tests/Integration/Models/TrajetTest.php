<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Core\Model;
use App\Models\Agence;
use App\Models\Trajet;
use App\Models\Utilisateur;
use DateTimeImmutable;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Support\DatabaseTestCase;

#[CoversClass(Trajet::class)]
#[CoversClass(Model::class)]
#[UsesClass(Agence::class)]
#[UsesClass(Utilisateur::class)]
final class TrajetTest extends DatabaseTestCase
{
    private int $userId;
    private int $paris;
    private int $lyon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createUser();
        $this->paris = $this->createAgence('Paris');
        $this->lyon = $this->createAgence('Lyon');
    }

    public function testCreate(): void
    {
        $id = (new Trajet($this->pdo))->create($this->userId, [
            'agence_depart_id' => $this->paris,
            'agence_arrivee_id' => $this->lyon,
            'date_depart' => '2030-01-02 08:00:00',
            'date_arrivee' => '2030-01-02 12:00:00',
            'places_total' => 4,
            'places_disponibles' => 3,
        ]);

        $trajet = (new Trajet($this->pdo))->find($id);
        self::assertNotNull($trajet);
        self::assertSame($this->userId, (int) $trajet['utilisateur_id']);
        self::assertSame('2030-01-02 08:00:00', $trajet['date_depart']);
        self::assertSame(3, (int) $trajet['places_disponibles']);
    }

    public function testDatabaseRejectsATripBetweenTheSameAgency(): void
    {
        $this->expectException(PDOException::class);
        $this->createTrajet($this->userId, $this->paris, $this->paris);
    }

    public function testUpdate(): void
    {
        $id = $this->createTrajet($this->userId, $this->paris, $this->lyon);

        (new Trajet($this->pdo))->update($id, [
            'agence_depart_id' => $this->lyon,
            'agence_arrivee_id' => $this->paris,
            'date_depart' => '2031-05-01 09:00:00',
            'date_arrivee' => '2031-05-01 13:00:00',
            'places_total' => 2,
            'places_disponibles' => 0,
        ]);

        $trajet = (new Trajet($this->pdo))->find($id) ?? [];
        self::assertSame($this->lyon, (int) $trajet['agence_depart_id']);
        self::assertSame('2031-05-01 09:00:00', $trajet['date_depart']);
        self::assertSame(0, (int) $trajet['places_disponibles']);
    }

    public function testDeleteOwnedByOnlyDeletesTheTripsOfTheAuthor(): void
    {
        $id = $this->createTrajet($this->userId, $this->paris, $this->lyon);
        $otherUser = $this->createUser('autre@example.com');
        $model = new Trajet($this->pdo);

        self::assertFalse($model->deleteOwnedBy($id, $otherUser));
        self::assertTrue($model->exists($id));

        self::assertTrue($model->deleteOwnedBy($id, $this->userId));
        self::assertFalse($model->exists($id));
    }

    public function testFindOwnedBy(): void
    {
        $id = $this->createTrajet($this->userId, $this->paris, $this->lyon);
        $model = new Trajet($this->pdo);

        self::assertNotNull($model->findOwnedBy($id, $this->userId));
        self::assertNull($model->findOwnedBy($id, $this->userId + 1));
    }

    public function testUpcomingAvailableExcludesPastAndFullTrips(): void
    {
        $later = $this->createTrajet($this->userId, $this->paris, $this->lyon, '+3 days');
        $sooner = $this->createTrajet($this->userId, $this->lyon, $this->paris, '+1 day');
        $this->createTrajet($this->userId, $this->paris, $this->lyon, '-1 day');
        $this->createTrajet($this->userId, $this->paris, $this->lyon, '+2 days', 0);

        $trajets = (new Trajet($this->pdo))->findUpcomingAvailable(new DateTimeImmutable());

        self::assertSame([$sooner, $later], array_map(static fn (array $t): int => (int) $t['id'], $trajets));
        self::assertSame('Lyon', $trajets[0]['agence_depart']);
        self::assertSame('Paris', $trajets[0]['agence_arrivee']);
        self::assertSame('jean.dupont@example.com', $trajets[0]['auteur_email']);
    }

    public function testDeletingAUserDeletesItsTrips(): void
    {
        $this->createTrajet($this->userId, $this->paris, $this->lyon);

        (new Utilisateur($this->pdo))->delete($this->userId);

        self::assertSame(0, $this->countRows('trajets'));
    }
}
