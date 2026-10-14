<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers\Admin;

use App\Controllers\Admin\AgenceController;
use App\Core\Flash;
use App\Core\HttpException;
use App\Models\Agence;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;

/**
 * Write operations on agencies (administrators only).
 */
#[CoversClass(AgenceController::class)]
final class AgenceControllerTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser('admin@example.com', true), true);
    }

    private function controller(): AgenceController
    {
        return new AgenceController($this->pdo);
    }

    public function testStoreCreatesTheAgencyAndRedirectsToTheList(): void
    {
        $this->post(['nom' => 'Grenoble']);

        $response = $this->controller()->store();

        self::assertSame('/admin/agences', $response->headers->get('Location'));
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
        self::assertNotNull((new Agence($this->pdo))->findByNom('Grenoble'));
    }

    public function testStoreRefusesADuplicateName(): void
    {
        $this->createAgence('Paris');
        $this->post(['nom' => 'paris']);

        $this->controller()->store();

        self::assertSame('Une agence porte déjà ce nom.', Flash::peek()[0]['message'] ?? null);
        self::assertSame(1, $this->countRows('agences'));
    }

    public function testStoreRefusesAnInvalidName(): void
    {
        $this->post(['nom' => '']);

        $this->controller()->store();

        self::assertSame(Flash::ERROR, Flash::peek()[0]['type'] ?? null);
        self::assertSame(0, $this->countRows('agences'));
    }

    public function testUpdateRenamesTheAgency(): void
    {
        $id = $this->createAgence('Pari');
        $this->post(['nom' => 'Paris']);

        $this->controller()->update($id);

        self::assertSame('Paris', (new Agence($this->pdo))->find($id)['nom'] ?? null);
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
    }

    public function testUpdateOfAnUnknownAgency(): void
    {
        $this->post(['nom' => 'Paris']);

        $this->controller()->update(999);

        self::assertSame('Agence introuvable.', Flash::peek()[0]['message'] ?? null);
    }

    public function testDeleteRemovesAnUnusedAgency(): void
    {
        $id = $this->createAgence('Nice');
        $this->post([]);

        $this->controller()->delete($id);

        self::assertSame(0, $this->countRows('agences'));
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
    }

    public function testDeleteRefusesAnAgencyUsedByATrip(): void
    {
        $paris = $this->createAgence('Paris');
        $this->createTrajet($this->createUser(), $paris, $this->createAgence('Lyon'));
        $this->post([]);

        $this->controller()->delete($paris);

        self::assertSame(Flash::ERROR, Flash::peek()[0]['type'] ?? null);
        self::assertSame(2, $this->countRows('agences'));
    }

    public function testNonAdministratorsAreRejected(): void
    {
        $this->actingAs($this->createUser('user@example.com'));
        $this->post(['nom' => 'Grenoble']);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->controller()->store();
    }
}
