<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Exception carrying an HTTP status code (401, 403, 404...).
 *
 * Controllers throw it; the router error handler turns it into an error page
 * or, for 401, into a redirection to the login form.
 */
final class HttpException extends \RuntimeException
{
    /**
     * @param int    $statusCode HTTP status code to send.
     * @param string $message    Message displayed to the user.
     */
    public function __construct(private readonly int $statusCode, string $message = '')
    {
        parent::__construct($message, $statusCode);
    }

    /**
     * Returns the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
