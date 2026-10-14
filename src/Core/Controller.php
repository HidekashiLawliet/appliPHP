<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Utilisateur;
use PDO;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class of every controller: rendering, redirections, flash messages,
 * access control, CSRF check and input reading.
 *
 * Controllers return a Response instead of echoing, so they can be tested
 * without a web server.
 *
 * @phpstan-import-type AuthUser from Auth
 */
abstract class Controller
{
    protected PDO $db;
    protected View $view;
    protected Auth $auth;

    /**
     * The router builds controllers without arguments; tests inject a PDO.
     */
    public function __construct(?PDO $db = null, ?View $view = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->view = $view ?? new View(dirname(__DIR__) . '/Views');
        $this->auth = new Auth(new Utilisateur($this->db));
    }

    /**
     * Renders a template inside the layout.
     *
     * The layout always receives the current user, the flash messages and the
     * current path.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = Response::HTTP_OK): Response
    {
        $html = $this->view->render($template, $data + [
            'currentUser' => $this->auth->user(),
            'flashes' => Flash::pull(),
            'currentPath' => parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
        ]);

        return new Response($html, $status);
    }

    /**
     * Redirects to a path of the application.
     */
    protected function redirect(string $path): RedirectResponse
    {
        return new RedirectResponse($path);
    }

    /**
     * Queues a flash message, then redirects (Post/Redirect/Get).
     */
    protected function redirectWithFlash(string $path, string $type, string $message): RedirectResponse
    {
        Flash::add($type, $message);

        return $this->redirect($path);
    }

    /**
     * Returns the logged-in user, or stops with HTTP 401.
     *
     * @return AuthUser
     * @throws HttpException 401 when nobody is logged in.
     */
    protected function requireLogin(): array
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Vous devez être connecté pour accéder à cette page.');
        }

        return $user;
    }

    /**
     * Returns the logged-in administrator, or stops with HTTP 401/403.
     *
     * @return AuthUser
     * @throws HttpException 401 when nobody is logged in, 403 for non-admins.
     */
    protected function requireAdmin(): array
    {
        $user = $this->requireLogin();
        if (!$user['est_admin']) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Cette page est réservée aux administrateurs.');
        }

        return $user;
    }

    /**
     * Checks the CSRF token of the submitted form.
     *
     * @throws HttpException 403 when the token is missing or wrong.
     */
    protected function verifyCsrf(): void
    {
        if (!Csrf::isValid($this->input(Csrf::FIELD))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Requête invalide (jeton CSRF). Rechargez la page et recommencez.');
        }
    }

    /**
     * Returns a trimmed POST value ('' when absent or not a string).
     */
    protected function input(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Returns several trimmed POST values at once.
     *
     * @param list<string> $keys
     * @return array<string, string>
     */
    protected function inputs(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->input($key);
        }

        return $values;
    }
}
