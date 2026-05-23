<?php
/**
 * Front controller for the application.
 *
 * Starts the session, restores persistent authentication, resolves the current
 * URL path, and renders the matching view inside the shared layout.
 */
session_start();
require __DIR__ . '/backend/config.php';
require __DIR__ . '/backend/auth_cookie.php';

auth_restore_session_from_cookie($pdo);

/**
 * Checks that the database connection is available for the request.
 */
try {
    $pdo->query("SELECT 1");
    $db_status = "Base de donnees connectee.";
} catch (PDOException $e) {
    $db_status = "Erreur de connexion a la base de donnees : " . htmlspecialchars($e->getMessage());
}

$url = $_SERVER['REQUEST_URI'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

if (preg_match('#^/logout(?:\\.php)?$#', $path)) {
    require('./backend/logout.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Touche pas a mon klaxon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <?php include('./views/header.php'); ?>

    <main class="container" style="margin-top: 4rem;">
        <?php
        /**
         * Maps public URLs to the PHP view files rendered by this front controller.
         *
         * @var array<string, string> $routes
         */
        $routes = [
            '' => './views/home.php',
            '/home' => './views/home.php',
            '/home.php' => './views/home.php',
            '/login' => './views/login.php',
            '/login.php' => './views/login.php',
            '/dashboard' => './views/dashboard.php',
            '/dashboard.php' => './views/dashboard.php',
            '/create_itinerary' => './views/create_itinerary.php',
            '/create_itinerary.php' => './views/create_itinerary.php',
            '/admin/users' => './views/admin/users.php',
            '/admin/users.php' => './views/admin/users.php',
            '/admin/trajets' => './views/admin/trajets.php',
            '/admin/trajets.php' => './views/admin/trajets.php',
            '/admin/statistiques' => './views/admin/statistiques.php',
            '/admin/statistiques.php' => './views/admin/statistiques.php',
            '/admin/parametres' => './views/admin/parametres.php',
            '/admin/parametres.php' => './views/admin/parametres.php',
        ];

        /**
         * Routes that require an authenticated administrator.
         *
         * @var string[] $adminRoutes
         */
        $adminRoutes = [
            '/dashboard',
            '/dashboard.php',
            '/admin/users',
            '/admin/users.php',
            '/admin/trajets',
            '/admin/trajets.php',
            '/admin/statistiques',
            '/admin/statistiques.php',
            '/admin/parametres',
            '/admin/parametres.php',
        ];

        /**
         * Routes available without a logged-in session.
         *
         * @var string[] $publicRoutes
         */
        $publicRoutes = [
            '',
            '/home',
            '/home.php',
            '/login',
            '/login.php',
        ];

        if (isset($routes[$path])) {
            if (!isLoggedIn() && !in_array($path, $publicRoutes, true)) {
                http_response_code(403);
                echo '<h1>Acces refuse</h1><p>Vous devez etre connecte pour acceder a cette page.</p>';
            } elseif (in_array($path, $adminRoutes, true) && !isAdmin()) {
                http_response_code(403);
                echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs.</p>';
            } else {
                require($routes[$path]);
            }
        } else {
            require('./views/404.php');
        }
        ?>
    </main>
    <?php include('./views/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
