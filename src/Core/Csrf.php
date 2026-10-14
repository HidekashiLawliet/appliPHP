<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Synchronizer token protecting every form against CSRF attacks.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public const FIELD = 'csrf_token';

    /**
     * Returns the token of the session, generating it on first use.
     */
    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Compares a submitted token with the session token in constant time.
     */
    public static function isValid(?string $submitted): bool
    {
        $token = Session::get(self::SESSION_KEY);

        return is_string($token) && $token !== '' && is_string($submitted) && hash_equals($token, $submitted);
    }
}
