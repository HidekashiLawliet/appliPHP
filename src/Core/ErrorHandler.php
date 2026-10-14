<?php

declare(strict_types=1);

namespace App\Core;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Turns exceptions and unknown routes into user-friendly responses.
 *
 * Technical details are logged, and only shown when APP_DEBUG=true.
 */
final class ErrorHandler
{
    public function __construct(private readonly View $view)
    {
    }

    /**
     * Response for a URL that matches no route.
     */
    public function notFound(): Response
    {
        return $this->page(Response::HTTP_NOT_FOUND, "La page demandée n'existe pas.");
    }

    /**
     * Response for an exception thrown while handling the request.
     */
    public function handle(Throwable $exception): Response
    {
        if ($exception instanceof HttpException) {
            if ($exception->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                Flash::add(Flash::INFO, $exception->getMessage());

                return new RedirectResponse('/login');
            }

            return $this->page($exception->getStatusCode(), $exception->getMessage());
        }

        error_log((string) $exception);
        $message = Config::isDebug()
            ? $exception::class . ' : ' . $exception->getMessage()
            : 'Une erreur interne est survenue. Réessayez plus tard.';

        return $this->page(Response::HTTP_INTERNAL_SERVER_ERROR, $message);
    }

    /**
     * Renders the error template. Uses only the session (no database access),
     * so it still works when the database is down.
     */
    private function page(int $status, string $message): Response
    {
        $html = $this->view->render('errors/error', [
            'status' => $status,
            'message' => $message,
            'currentUser' => Auth::sessionUser(),
            'flashes' => Flash::pull(),
            'currentPath' => '',
        ]);

        return new Response($html, $status);
    }
}
