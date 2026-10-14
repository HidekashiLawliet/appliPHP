<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Core\Model;
use App\Models\Utilisateur;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;

#[CoversClass(Utilisateur::class)]
#[CoversClass(Model::class)]
final class UtilisateurTest extends DatabaseTestCase
{
    public function testCreateStoresAHashNeverTheClearPassword(): void
    {
        $model = new Utilisateur($this->pdo);
        $id = $model->create('Martin', 'Alexandre', '0612345678', 'alexandre.martin@email.fr', 'Password123!', true);

        $user = $model->find($id);
        self::assertNotNull($user);
        self::assertNotSame('Password123!', $user['mot_de_passe']);
        self::assertTrue(password_verify('Password123!', (string) $user['mot_de_passe']));
        self::assertSame(1, (int) $user['est_admin']);
    }

    public function testFindByEmail(): void
    {
        $id = $this->createUser('lucie@example.com');

        self::assertSame($id, (int) ((new Utilisateur($this->pdo))->findByEmail('lucie@example.com')['id'] ?? 0));
        self::assertNull((new Utilisateur($this->pdo))->findByEmail('inconnu@example.com'));
    }

    public function testEmailMustBeUnique(): void
    {
        $this->createUser('doublon@example.com');

        $this->expectException(PDOException::class);
        $this->createUser('doublon@example.com');
    }

    public function testUpdatePasswordHash(): void
    {
        $id = $this->createUser();
        $model = new Utilisateur($this->pdo);

        $model->updatePasswordHash($id, password_hash('nouveau', PASSWORD_DEFAULT));

        self::assertTrue(password_verify('nouveau', (string) ($model->find($id)['mot_de_passe'] ?? '')));
    }

    public function testAllIsSortedByName(): void
    {
        $model = new Utilisateur($this->pdo);
        $model->create('Zola', 'Emile', '01', 'z@example.com', 'x');
        $model->create('Albert', 'Anne', '02', 'a@example.com', 'x');

        self::assertSame(['Albert', 'Zola'], array_column($model->all(), 'nom'));
    }
}
