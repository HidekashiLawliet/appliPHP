<?php
/**
 * Shared navigation header.
 *
 * Provides session-aware navigation links and helper functions used by the
 * layout and protected views.
 */
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/auth_cookie.php';

// Nom de l'application
$appName = "TOUCHE PAS AU KLAXON";

// Fonction pour vérifier si l'utilisateur est connecté
/**
 * Checks whether the current visitor has an active authenticated session.
 */
function isLoggedIn() {
    // Supporter plusieurs conventions : session user array, email en session ou cookie persistent
    if (!empty($_SESSION['user'])) return true;
    if (!empty($_SESSION['user_email'])) return true;
    if (!empty($_SESSION['user_is_admin'])) return true;
    global $pdo;
    if (!isset($pdo)) {
        $configPath = __DIR__ . '/../backend/config.php';
        if (file_exists($configPath)) {
            require $configPath;
        }
    }

    if (isset($pdo)) {
        return auth_restore_session_from_cookie($pdo);
    }

    return false;
}

// Fonction pour vérifier si l'utilisateur est admin
/**
 * Checks whether the current authenticated user has administrator rights.
 */
function isAdmin() {
    if (!isLoggedIn()) return false;
    if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true) return true;
    if (isset($_SESSION['user_is_admin']) && $_SESSION['user_is_admin'] === true) return true;
    return false;
}

// Fonction pour obtenir le nom complet de l'utilisateur
/**
 * Returns the current user's display name from the session or database.
 */
function getUserName() {
    if (!isLoggedIn()) return '';

    // Prefer session-stored name
    $prenom = $_SESSION['user_prenom'] ?? null;
    $nom = $_SESSION['user_nom'] ?? null;
    if ($prenom || $nom) {
        return trim(($prenom ?? '') . ' ' . ($nom ?? ''));
    }

    // Fallback: if a signed cookie is valid, fetch name from DB once
    $email = $_SESSION['user_email'] ?? auth_cookie_get_email();
    if ($email) {
        global $pdo;
        if (!isset($pdo)) {
            $configPath = __DIR__ . '/../backend/config.php';
            if (file_exists($configPath)) {
                require $configPath;
            }
        }

        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare('SELECT nom, prenom FROM userdata WHERE email = :email');
                $stmt->execute(['email' => $email]);
                $row = $stmt->fetch();
                if ($row) {
                    $_SESSION['user_nom'] = $row['nom'];
                    $_SESSION['user_prenom'] = $row['prenom'];
                    return trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
                }
            } catch (Exception $e) {
                // ignore DB errors and fall through
            }
        }
    }

    return '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #384050;position: fixed; width: 100%; top: 0; margin-bottom: 15px;">
    <div class="container-fluid">

        <a class="navbar-brand" href="<?php echo isAdmin() ? '/dashboard' : '/home'; ?>"><?php echo $appName; ?></a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"></span>
            <?php if (!isLoggedIn()): ?>
                <!-- Utilisateur non connecté -->
                <a class="nav-link btn btn-success text-white ms-2" href="/login.php">Connexion</a>
            <?php elseif (isAdmin()): ?>
                <span class="navbar-text ms-2">Admin: <?php echo getUserName(); ?> </span>
                <!-- Administrateur -->
                <button class="btn btn-primary mb-2 mx-2 shadow" type="button"
                    data-bs-toggle="collapse" data-bs-target="#myList"
                    aria-expanded="false" aria-controls="myList">
                    Admin Pannel
                </button>
                    <ul id="myList" class="list-group collapse">
                    <li class="list-group-item">
                        <a class="text-decoration-none btn btn-outline-light  text-dark" href="/dashboard#users-panel">users</a>
                    </li>
                    <li class="list-group-item">
                        <a class="text-decoration-none btn btn-outline-dark text-dark" href="/dashboard#agences-panel">agences</a>
                    </li>
                    <li class="list-group-item">
                        <a class="text-decoration-none btn btn-outline-dark text-dark" href="/dashboard#trajets-panel">trajets</a>
                    </li>
                </ul>
                <a class="nav-link btn btn-success  text-white ms-2" href="/backend/logout.php">Déconnexion</a>
            <?php else: ?>
                <!-- Utilisateur connecté -->
                <a class="nav-link btn btn-success text-white ms-2" href="/create_itinerary.php">Créer un trajet</a>
                <span class="navbar-text ms-2"><?php echo getUserName(); ?> </span>
                <a class="nav-link btn btn-success text-white ms-2" href="/backend/logout.php">Déconnexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
