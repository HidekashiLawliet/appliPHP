<?php

declare(strict_types=1);

namespace Tests\Integration\Core;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Model;
use App\Core\Session;
use App\Models\Utilisateur;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Support\DatabaseTestCase;

#[CoversClass(Auth::class)]
#[UsesClass(Config::class)]
#[UsesClass(Model::class)]
#[UsesClass(Session::class)]
#[UsesClass(Utilisateur::class)]
final class AuthTest extends DatabaseTestCase
{
    private Auth $auth;
    private Utilisateur $utilisateurs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilisateurs = new Utilisateur($this->pdo);
        $this->auth = new Auth($this->utilisateurs);
        $_ENV['APP_SECRET'] = 'test-secret';
    }

    protected function tearDown(): void
    {
        unset($_ENV['APP_SECRET']);
    }

    public function testAttemptAcceptsTheRightPassword(): void
    {
        $id = $this->createUser('ok@example.com');

        self::assertSame($id, (int) ($this->auth->attempt('ok@example.com', 'secret')['id'] ?? 0));
    }

    public function testAttemptRejectsAWrongPasswordOrAnUnknownEmail(): void
    {
        $this->createUser('ok@example.com');

        self::assertNull($this->auth->attempt('ok@example.com', 'wrong'));
        self::assertNull($this->auth->attempt('nobody@example.com', 'secret'));
    }

    public function testAttemptUpgradesAnOutdatedHash(): void
    {
        $id = $this->createUser('old@example.com');
        $weakHash = password_hash('secret', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->utilisateurs->updatePasswordHash($id, $weakHash);

        self::assertNotNull($this->auth->attempt('old@example.com', 'secret'));

        $newHash = (string) ($this->utilisateurs->find($id)['mot_de_passe'] ?? '');
        self::assertNotSame($weakHash, $newHash);
        self::assertFalse(password_needs_rehash($newHash, PASSWORD_DEFAULT));
    }

    public function testLoginStoresOnlyPublicDataInTheSession(): void
    {
        $id = $this->createUser('admin@example.com', true);
        $user = $this->utilisateurs->find($id) ?? [];

        $this->auth->login($user);

        self::assertSame(
            ['id' => $id, 'nom' => 'Dupont', 'prenom' => 'Jean', 'email' => 'admin@example.com', 'est_admin' => true],
            $this->auth->user()
        );
        self::assertArrayNotHasKey('mot_de_passe', $_SESSION['_user']);
    }

    public function testLogoutClearsTheSession(): void
    {
        $this->auth->login($this->utilisateurs->find($this->createUser()) ?? []);

        $this->auth->logout();

        self::assertFalse($this->auth->check());
    }

    public function testRememberMeCookieRestoresTheSession(): void
    {
        $id = $this->createUser();
        $this->auth->login($this->utilisateurs->find($id) ?? [], true);
        self::assertArrayHasKey('remember_me', $_COOKIE);

        $_SESSION = []; // the browser comes back after the session expired

        self::assertSame($id, $this->auth->user()['id'] ?? null);
    }

    public function testTamperedRememberMeCookieIsRejected(): void
    {
        $id = $this->createUser();
        $other = $this->createUser('victime@example.com');
        $this->auth->login($this->utilisateurs->find($id) ?? [], true);
        $_SESSION = [];

        $_COOKIE['remember_me'] = preg_replace('/^\d+/', (string) $other, (string) $_COOKIE['remember_me']);

        self::assertNull($this->auth->user());
    }
}
