<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Core\Model;
use App\Models\Agence;
use App\Models\Trajet;
use App\Models\Utilisateur;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Support\DatabaseTestCase;

#[CoversClass(Agence::class)]
#[CoversClass(Model::class)]
#[UsesClass(Trajet::class)]
#[UsesClass(Utilisateur::class)]
final class AgenceTest extends DatabaseTestCase
{
    public function testCreate(): void
    {
        $id = (new Agence($this->pdo))->create('Paris');

        self::assertSame('Paris', (new Agence($this->pdo))->find($id)['nom'] ?? null);
    }

    public function testRename(): void
    {
        $id = $this->createAgence('Pari');

        (new Agence($this->pdo))->rename($id, 'Paris');

        self::assertSame('Paris', (new Agence($this->pdo))->find($id)['nom'] ?? null);
    }

    public function testDelete(): void
    {
        $id = $this->createAgence('Nice');

        self::assertTrue((new Agence($this->pdo))->delete($id));
        self::assertFalse((new Agence($this->pdo))->exists($id));
        self::assertFalse((new Agence($this->pdo))->delete($id));
    }

    public function testFindByNomIsCaseInsensitiveAndCanIgnoreOneAgency(): void
    {
        $id = $this->createAgence('Lyon');
        $model = new Agence($this->pdo);

        self::assertNotNull($model->findByNom('LYON'));
        self::assertNull($model->findByNom('Lyon', $id));
    }

    public function testIsUsedAndDatabaseRefusesToDeleteAUsedAgency(): void
    {
        $paris = $this->createAgence('Paris');
        $lyon = $this->createAgence('Lyon');
        $nice = $this->createAgence('Nice');
        $this->createTrajet($this->createUser(), $paris, $lyon);
        $model = new Agence($this->pdo);

        self::assertTrue($model->isUsed($paris));
        self::assertTrue($model->isUsed($lyon));
        self::assertFalse($model->isUsed($nice));

        $this->expectException(PDOException::class);
        $model->delete($paris);
    }

    public function testIdsAndAllAreSortedByName(): void
    {
        $b = $this->createAgence('Bordeaux');
        $a = $this->createAgence('Amiens');

        self::assertSame([$a, $b], (new Agence($this->pdo))->ids());
    }
}
