<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login and logout.
 */
final class AuthController extends Controller
{
    /**
     * GET /login : displays the login form.
     */
    public function showLogin(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }

        return $this->render('auth/login', ['email' => '']);
    }

    /**
     * POST /login : checks the credentials and opens the session.
     */
    public function login(): Response
    {
        $this->verifyCsrf();

        $email = $this->input('email');
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || $email === '' || $password === '') {
            return $this->loginFailed($email, 'Email et mot de passe obligatoires.');
        }

        $user = $this->auth->attempt($email, $password);
        if ($user === null) {
            return $this->loginFailed($email, 'Email ou mot de passe incorrect.');
        }

        $this->auth->login($user, $this->input('remember') === '1');
        $prenom = is_string($user['prenom'] ?? null) ? $user['prenom'] : '';

        return $this->redirectWithFlash('/', Flash::SUCCESS, sprintf('Bienvenue %s, vous êtes connecté.', $prenom));
    }

    /**
     * POST /logout : closes the session.
     */
    public function logout(): Response
    {
        $this->verifyCsrf();
        $this->auth->logout();

        // A new, empty session carries the flash message to the next page.
        Session::start();

        return $this->redirectWithFlash('/', Flash::SUCCESS, 'Vous êtes déconnecté.');
    }

    /**
     * Re-displays the form with an error, keeping the typed email.
     */
    private function loginFailed(string $email, string $message): Response
    {
        Flash::add(Flash::ERROR, $message);

        return $this->render('auth/login', ['email' => $email], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
