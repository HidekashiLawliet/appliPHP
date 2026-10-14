<?php

/**
 * Front controller: every HTTP request enters here.
 *
 * Loads the Composer autoloader and the .env configuration, starts the
 * session, then lets the router call the matching controller.
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Session;
use App\Core\View;
use Buki\Router\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Config::load($root);
Session::start();

$errorHandler = new ErrorHandler(new View($root . '/src/Views'));

// The router only catches Exception: PHP Errors (TypeError...) end up here.
set_exception_handler(static function (Throwable $exception) use ($errorHandler): void {
    $errorHandler->handle($exception)->send();
});

$router = new Router([
    'paths' => ['controllers' => $root . '/src/Controllers'],
    'namespaces' => ['controllers' => 'App\Controllers'],
    // The application is served from the web root (public/ is the document root).
    'base_folder' => '',
    'cache' => $root . '/var/routes.cache.php',
]);

$router->notFound(static fn (Request $request, Response $response): Response => $errorHandler->notFound());
$router->error(static fn (Request $request, Response $response, Throwable $exception): Response => $errorHandler->handle($exception));

(require $root . '/config/routes.php')($router);

$router->run();
