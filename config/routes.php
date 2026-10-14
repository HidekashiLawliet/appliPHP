<?php

/**
 * Application routes (izniburak/router).
 *
 * Read pages use GET; every write operation uses POST with a CSRF token and
 * ends with a redirection carrying a flash message.
 */

declare(strict_types=1);

use Buki\Router\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;

return static function (Router $router): void {
    $router->get('/', 'HomeController@index');

    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->post('/logout', 'AuthController@logout');

    $router->get('/trajets/create', 'TrajetController@create');
    $router->post('/trajets', 'TrajetController@store');
    $router->get('/trajets/:id/edit', 'TrajetController@edit');
    $router->post('/trajets/:id/update', 'TrajetController@update');
    $router->post('/trajets/:id/delete', 'TrajetController@delete');

    $router->get('/admin', static fn (): RedirectResponse => new RedirectResponse('/admin/utilisateurs'));
    $router->get('/admin/utilisateurs', 'Admin\UtilisateurController@index');
    $router->get('/admin/agences', 'Admin\AgenceController@index');
    $router->post('/admin/agences', 'Admin\AgenceController@store');
    $router->post('/admin/agences/:id/update', 'Admin\AgenceController@update');
    $router->post('/admin/agences/:id/delete', 'Admin\AgenceController@delete');
    $router->get('/admin/trajets', 'Admin\TrajetController@index');
    $router->post('/admin/trajets/:id/delete', 'Admin\TrajetController@delete');
};
