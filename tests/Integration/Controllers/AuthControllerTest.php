<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\AuthController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\HttpException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;

#[CoversClass(AuthController::class)]
final class AuthControllerTest extends DatabaseTestCase
{
    public function testLoginWithGoodCredentialsOpensTheSession(): void
    {
        $id = $this->createUser('jean@example.com');
        $this->post(['email' => 'jean@example.com', 'password' => 'secret']);

        $response = (new AuthController($this->pdo))->login();

        self::assertSame(302, $response->getStatusCode());
        self::assertSame($id, Auth::sessionUser()['id'] ?? null);
        self::assertSame(Flash::SUCCESS, Flash::peek()[0]['type'] ?? null);
    }

    public function testLoginWithAWrongPasswordIsRefused(): void
    {
        $this->createUser('jean@example.com');
        $this->post(['email' => 'jean@example.com', 'password' => 'mauvais']);

        $response = (new AuthController($this->pdo))->login();

        self::assertSame(422, $response->getStatusCode());
        self::assertNull(Auth::sessionUser());
        self::assertStringContainsString('Email ou mot de passe incorrect', (string) $response->getContent());
    }

    public function testLoginRequiresTheCsrfToken(): void
    {
        $this->createUser('jean@example.com');
        $_POST = ['email' => 'jean@example.com', 'password' => 'secret'];

        $this->expectException(HttpException::class);
        (new AuthController($this->pdo))->login();
    }

    public function testLogoutClosesTheSession(): void
    {
        $this->actingAs($this->createUser());
        $this->post([]);

        $response = (new AuthController($this->pdo))->logout();

        self::assertSame(302, $response->getStatusCode());
        self::assertNull(Auth::sessionUser());
    }
}
