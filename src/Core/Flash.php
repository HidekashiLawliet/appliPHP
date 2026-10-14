<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-shot messages kept in the session across a redirection.
 *
 * A controller calls `Flash::add()` before redirecting; the next page reads
 * them with `Flash::pull()`, which also deletes them.
 */
final class Flash
{
    private const SESSION_KEY = '_flash';

    public const SUCCESS = 'success';
    public const ERROR = 'danger';
    public const INFO = 'info';

    /**
     * Queues a message for the next displayed page.
     *
     * @param string $type    One of the class constants (Bootstrap alert type).
     * @param string $message Text shown to the user.
     */
    public static function add(string $type, string $message): void
    {
        $messages = self::peek();
        $messages[] = ['type' => $type, 'message' => $message];
        Session::set(self::SESSION_KEY, $messages);
    }

    /**
     * Returns the queued messages without removing them.
     *
     * @return list<array{type: string, message: string}>
     */
    public static function peek(): array
    {
        $stored = Session::get(self::SESSION_KEY, []);
        if (!is_array($stored)) {
            return [];
        }

        $messages = [];
        foreach ($stored as $item) {
            if (is_array($item) && is_string($item['type'] ?? null) && is_string($item['message'] ?? null)) {
                $messages[] = ['type' => $item['type'], 'message' => $item['message']];
            }
        }

        return $messages;
    }

    /**
     * Returns the queued messages and removes them from the session.
     *
     * @return list<array{type: string, message: string}>
     */
    public static function pull(): array
    {
        $messages = self::peek();
        Session::remove(self::SESSION_KEY);

        return $messages;
    }
}
