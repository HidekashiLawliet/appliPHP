<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\TrajetController;
use App\Core\Flash;
use App\Core\HttpException;
use App\Models\Trajet;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;

/**
 * Write operations of a trip author: create, update, delete.
 */
#[CoversClass(TrajetController::class)]
final class TrajetControllerTest extends DatabaseTestCase
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
        $this->actingAs($this->userId);
    }

    /**
     * @return array<string, string>
     */
    private function validForm(): array
    {
        $depart = new DateTimeImmutable('+2 days 08:00');

        return [
            'agence_depart_id' => (string) $this->paris,
            'agence_arrivee_id' => (string) $this->lyon,
            'date_depart' => $depart->format('Y-m-d\TH:i'),
            'date_arrivee' => $depart->modify('+3 hours')->format('Y-m-d\TH:i'),
            'places_total' => '4',
            'places_disponibles' => '4',
        ];
    }

    private function controller(): TrajetController
    {
        return new TrajetController($this->pdo);
    }

    public function testStoreCreatesTheTripAndRedirectsWithAFlashMessage(): void
    {
        $this->post($this->validForm());

        $response = $this->controller()->store();

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/', $response->headers->get('Location'));
        self::assertSame([['type' => Flash::SUCCESS, 'message' => 'Le trajet a été créé.']], Flash::peek());
        self::assertSame(1, $this->countRows('trajets'));
        $trajet = (new Trajet($this->pdo))->all()[0];
        self::assertSame($this->userId, (int) $trajet['utilisateur_id']);
    }

    public function testStoreWithInvalidDataShowsTheFormAgainAndSavesNothing(): void
    {
        $this->post(['agence_arrivee_id' => (string) $this->paris] + $this->validForm());

        $response = $this->controller()->store();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('doit être différente', (string) $response->getContent());
        self::assertSame(0, $this->countRows('trajets'));
    }

    public function testStoreRequiresAValidCsrfToken(): void
    {
        $_POST = $this->validForm() + ['csrf_token' => 'forged'];

        try {
            $this->controller()->store();
            self::fail('Une HttpException 403 etait attendue.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
        self::assertSame(0, $this->countRows('trajets'));
    }

    public function testStoreRequiresALoggedInUser(): void
    {
        $_SESSION = [];
        $this->post($this->validForm());

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->controller()->store();
    }

    public function testUpdateChangesTheTripOfTheAuthor(): void
    {
        $id = $this->createTrajet($this->userId, $this->paris, $this->lyon);
        $this->post(['places_total' => '2', 'places_disponibles' => '1'] + $this->validForm());

        $response = $this->controller()->update($id);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('Le trajet a été modifié.', Flash::peek()[0]['message'] ?? null);
        self::assertSame(1, (int) ((new Trajet($this->pdo))->find($id)['places_disponibles'] ?? -1));
    }

    public function testUpdateOfSomeoneElsesTripIsForbidden(): void
    {
        $id = $this->createTrajet($this->createUser('autre@example.com'), $this->paris, $this->lyon);
        $this->post(['places_disponibles' => '0'] + $this->validForm());

        try {
            $this->controller()->update($id);
            self::fail('Une HttpException 403 etait attendue.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
        self::assertSame(2, (int) ((new Trajet($this->pdo))->find($id)['places_disponibles'] ?? -1));
    }

    public function testDeleteRemovesTheTripOfTheAuthor(): void
    {
        $id = $this->createTrajet($this->userId, $this->paris, $this->lyon);
        $this->post([]);

        $response = $this->controller()->delete($id);

        self::assertSame('/', $response->headers->get('Location'));
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
        self::assertSame(0, $this->countRows('trajets'));
    }

    public function testDeleteOfSomeoneElsesTripDoesNothing(): void
    {
        $id = $this->createTrajet($this->createUser('autre@example.com'), $this->paris, $this->lyon);
        $this->post([]);

        $this->controller()->delete($id);

        self::assertSame(Flash::ERROR, Flash::peek()[0]['type'] ?? null);
        self::assertSame(1, $this->countRows('trajets'));
    }
}
