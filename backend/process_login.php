<?php
/**
 * Login form processor.
 *
 * Validates submitted credentials and CSRF token, fills the PHP session, stores
 * the persistent login cookie, and redirects the user to the home page.
 */
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/auth_cookie.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

/**
 * Rejects empty login submissions before touching the database.
 */
if ($email === '' || $password === '') {
    $_SESSION['error'] = "Email et mot de passe requis.";
    header('Location: /login.php');
    exit;
}

/**
 * Verifies that the login form was submitted by the active session.
 */
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['error'] = "Requete invalide.";
    header('Location: /login.php');
    exit;
}

/**
 * Loads the user row and compares the submitted password with stored data.
 */
$stmt = $pdo->prepare('SELECT password, nom, prenom, admin FROM userdata WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || $password !== $user['password']) {
    $_SESSION['error'] = "Email ou mot de passe incorrect.";
    header('Location: /login.php');
    exit;
}

session_regenerate_id(true);

$isAdmin = (bool) $user['admin'];

/**
 * Stores the authenticated user in all session formats currently supported.
 */
$user['email'] = $email;
$user['is_admin'] = $isAdmin;

$_SESSION['user_email'] = $email;
$_SESSION['user_nom'] = $user['nom'] ?? '';
$_SESSION['user_prenom'] = $user['prenom'] ?? '';
$_SESSION['user_is_admin'] = $isAdmin;
$_SESSION['user'] = [
    'email' => $user['email'],
    'nom' => $user['nom'] ?? '',
    'prenom' => $user['prenom'] ?? '',
    'is_admin' => $user['is_admin'],
];

auth_cookie_set($email);

header('Location: /home.php');
exit;
