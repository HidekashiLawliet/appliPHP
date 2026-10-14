<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Utilisateur;

/**
 * Authentication: password check, session login/logout and an optional
 * signed "remember me" cookie.
 *
 * @phpstan-type AuthUser array{id: int, nom: string, prenom: string, email: string, est_admin: bool}
 */
final class Auth
{
    private const SESSION_KEY = '_user';
    private const COOKIE_NAME = 'remember_me';
    private const COOKIE_TTL = 2592000; // 30 days

    /**
     * Hash compared when the email is unknown, so that the response time does
     * not reveal whether an account exists.
     */
    private const DUMMY_HASH = '$2y$12$dqEUvh3BYzXbz3BtChw6aOuPhPOF3EaE8iu5F0FJBcyl.HISiufhS';

    public function __construct(private readonly Utilisateur $utilisateurs)
    {
    }

    /**
     * Checks credentials and returns the matching user row, or null.
     *
     * Upgrades the stored hash when PHP's default algorithm has changed.
     *
     * @return array<string, mixed>|null
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->utilisateurs->findByEmail($email);
        $hash = is_string($user['mot_de_passe'] ?? null) ? $user['mot_de_passe'] : self::DUMMY_HASH;

        if (!password_verify($password, $hash) || $user === null) {
            return null;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->utilisateurs->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        return $user;
    }

    /**
     * Opens an authenticated session for this user.
     *
     * @param array<string, mixed> $user     Row from the `utilisateurs` table.
     * @param bool                 $remember Also set the persistent cookie.
     */
    public function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, self::toAuthUser($user));

        if ($remember) {
            $this->setRememberCookie($user);
        }
    }

    /**
     * Closes the session and deletes the persistent cookie.
     */
    public function logout(): void
    {
        $this->sendCookie('', time() - 3600);
        unset($_COOKIE[self::COOKIE_NAME]);
        Session::destroy();
    }

    /**
     * Returns the logged-in user, restoring it from the cookie if needed.
     *
     * @return AuthUser|null
     */
    public function user(): ?array
    {
        return self::sessionUser() ?? $this->restoreFromCookie();
    }

    /**
     * Returns the user stored in the session, without touching the database.
     *
     * @return AuthUser|null
     */
    public static function sessionUser(): ?array
    {
        $user = Session::get(self::SESSION_KEY);
        if (!is_array($user) || !isset($user['id'], $user['nom'], $user['prenom'], $user['email'], $user['est_admin'])) {
            return null;
        }

        return self::toAuthUser($user);
    }

    /**
     * Tells whether a user is logged in.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Converts a database row into the minimal data kept in the session.
     *
     * @param array<string, mixed> $row
     * @return AuthUser
     */
    private static function toAuthUser(array $row): array
    {
        return [
            'id' => is_numeric($row['id'] ?? null) ? (int) $row['id'] : 0,
            'nom' => is_string($row['nom'] ?? null) ? $row['nom'] : '',
            'prenom' => is_string($row['prenom'] ?? null) ? $row['prenom'] : '',
            'email' => is_string($row['email'] ?? null) ? $row['email'] : '',
            'est_admin' => (bool) ($row['est_admin'] ?? false),
        ];
    }

    /**
     * Signs "id|expiry" with APP_SECRET and the current password hash, so that
     * changing the password invalidates existing cookies.
     *
     * @param array<string, mixed> $user
     */
    private function setRememberCookie(array $user): void
    {
        $secret = Config::get('APP_SECRET');
        if ($secret === '') {
            return;
        }

        $expires = time() + self::COOKIE_TTL;
        $payload = $user['id'] . '|' . $expires;
        $hash = is_string($user['mot_de_passe'] ?? null) ? $user['mot_de_passe'] : '';
        $token = $payload . '|' . hash_hmac('sha256', $payload . '|' . $hash, $secret);

        $this->sendCookie($token, $expires);
        $_COOKIE[self::COOKIE_NAME] = $token;
    }

    /**
     * Logs the user in again from a valid remember-me cookie.
     *
     * @return AuthUser|null
     */
    private function restoreFromCookie(): ?array
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        $secret = Config::get('APP_SECRET');
        if (!is_string($token) || $secret === '' || substr_count($token, '|') !== 2) {
            return null;
        }

        [$id, $expires, $signature] = explode('|', $token);
        if (!ctype_digit($id) || !ctype_digit($expires) || (int) $expires < time()) {
            return null;
        }

        $user = $this->utilisateurs->find((int) $id);
        $hash = is_string($user['mot_de_passe'] ?? null) ? $user['mot_de_passe'] : '';
        $expected = hash_hmac('sha256', $id . '|' . $expires . '|' . $hash, $secret);
        if ($user === null || !hash_equals($expected, $signature)) {
            return null;
        }

        Session::regenerate();
        Session::set(self::SESSION_KEY, self::toAuthUser($user));

        return self::sessionUser();
    }

    /**
     * Writes (or deletes, with a past expiry) the remember-me cookie.
     */
    private function sendCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
