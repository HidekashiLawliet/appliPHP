<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Application configuration read from the environment (.env file).
 *
 * Credentials never live in the source code: they are loaded once from the
 * `.env` file at the project root (see `.env.example`).
 */
final class Config
{
    private static bool $loaded = false;

    /**
     * Loads the `.env` file of the given directory, once per process.
     *
     * Variables already defined in the real environment take precedence.
     */
    public static function load(string $rootDir): void
    {
        if (self::$loaded) {
            return;
        }

        Dotenv::createImmutable($rootDir)->safeLoad();
        self::$loaded = true;
    }

    /**
     * Returns a configuration value, or the default when it is not defined.
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Returns a required configuration value.
     *
     * @throws \RuntimeException When the key is missing from the environment.
     */
    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === '') {
            throw new \RuntimeException(sprintf('La variable de configuration "%s" est absente du fichier .env.', $key));
        }

        return $value;
    }

    /**
     * Tells whether the application runs in debug mode (APP_DEBUG=true).
     */
    public static function isDebug(): bool
    {
        return filter_var(self::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
    }
}
