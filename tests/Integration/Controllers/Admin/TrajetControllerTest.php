<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers\Admin;

use App\Controllers\Admin\TrajetController;
use App\Core\Flash;
use App\Core\HttpException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;

/**
 * Deletion of any trip by an administrator.
 */
#[CoversClass(TrajetController::class)]
final class TrajetControllerTest extends DatabaseTestCase
{
    public function testAdministratorCanDeleteAnyTrip(): void
    {
        $id = $this->createTrajet($this->createUser(), $this->createAgence('Paris'), $this->createAgence('Lyon'));
        $this->actingAs($this->createUser('admin@example.com', true), true);
        $this->post([]);

        $response = (new TrajetController($this->pdo))->delete($id);

        self::assertSame('/admin/trajets', $response->headers->get('Location'));
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
        self::assertSame(0, $this->countRows('trajets'));
    }

    public function testDeletingAnUnknownTripShowsAnError(): void
    {
        $this->actingAs($this->createUser('admin@example.com', true), true);
        $this->post([]);

        (new TrajetController($this->pdo))->delete(999);

        self::assertSame('Trajet introuvable.', Flash::peek()[0]['message'] ?? null);
    }

    public function testNonAdministratorsCannotDelete(): void
    {
        $id = $this->createTrajet($this->createUser(), $this->createAgence('Paris'), $this->createAgence('Lyon'));
        $this->actingAs($this->createUser('user@example.com'));
        $this->post([]);

        try {
            (new TrajetController($this->pdo))->delete($id);
            self::fail('Une HttpException 403 etait attendue.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
        self::assertSame(1, $this->countRows('trajets'));
    }
}
