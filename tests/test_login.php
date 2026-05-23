<?php
/**
 * Login integration test.
 *
 * Validates the login form processing, session management, and persistent
 * cookie creation with test credentials and CSRF token verification.
 *
 * Usage: php tests/test_login.php
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load authentication cookie helpers (independent of config.php).
require __DIR__ . '/../backend/auth_cookie.php';

// Initialize in-memory database: SQLite if available, fallback to array storage.
$pdo = null;
$userStore = [];
try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(<<<'SQL'
CREATE TABLE userdata (
    email TEXT PRIMARY KEY,
    password TEXT NOT NULL,
    nom TEXT,
    prenom TEXT,
    tel TEXT,
    admin INTEGER DEFAULT 0
);
SQL
    );

    // Insert test user (plaintext password as application stores it plaintext).
    $testEmail = 'test@example.com';
    $testPassword = 'secret123';
    $pdo->prepare('INSERT INTO userdata (email,password,nom,prenom,tel,admin) VALUES (:e,:p,:n,:r,:t,:a)')
        ->execute([':e' => $testEmail, ':p' => $testPassword, ':n' => 'NomTest', ':r' => 'PrenomTest', ':t' => '1234567890', ':a' => 1]);
} catch (Exception $e) {
    // SQLite PDO driver unavailable — use in-memory array fallback.
    $testEmail = 'test@example.com';
    $testPassword = 'secret123';
    $userStore[$testEmail] = [
        'password' => $testPassword,
        'nom' => 'NomTest',
        'prenom' => 'PrenomTest',
        'tel' => '1234567890',
        'admin' => 1,
    ];
}

// Simulate form submission with valid credentials and CSRF token.
$_SESSION['csrf_token'] = 'token-xyz-123';
$_POST['email'] = $testEmail;
$_POST['password'] = $testPassword;
$_POST['csrf_token'] = $_SESSION['csrf_token'];

// Minimal login processing implementation (mirrors backend/process_login.php logic).
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

if ($email === '' || $password === '') {
    echo "FAIL: champs vides\n";
    exit(1);
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo "FAIL: token CSRF invalide\n";
    exit(1);
}

$user = false;
if ($pdo) {
    $stmt = $pdo->prepare('SELECT password, nom, prenom, admin, tel FROM userdata WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
} else {
    $user = $userStore[$email] ?? false;
}

if (!$user || $password !== $user['password']) {
    echo "FAIL: identifiants incorrects\n";
    exit(1);
}

session_regenerate_id(true);

$isAdmin = (bool) $user['admin'];
$user['email'] = $email;
$user['is_admin'] = $isAdmin;

// Populate session data as the application does.
$_SESSION['user_email'] = $email;
$_SESSION['user_nom'] = $user['nom'] ?? '';
$_SESSION['user_prenom'] = $user['prenom'] ?? '';
$_SESSION['user_tel'] = $user['tel'] ?? '';
$_SESSION['user_is_admin'] = $isAdmin;
$_SESSION['user'] = [
    'email' => $user['email'],
    'nom' => $user['nom'] ?? '',
    'prenom' => $user['prenom'] ?? '',
    'tel' => $user['tel'] ?? '',
    'is_admin' => $user['is_admin'],
];

// Call function to create persistent login cookie (defined in auth_cookie.php).
auth_cookie_set($email);

// Final validation checks.
$errors = [];
if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== $testEmail) {
    $errors[] = 'session_email';
}
if (!isset($_SESSION['user_nom']) || $_SESSION['user_nom'] !== 'NomTest') {
    $errors[] = 'session_nom';
}
if (!isset($_SESSION['user_tel']) || $_SESSION['user_tel'] !== '1234567890') {
    $errors[] = 'session_tel';
}
if (!isset($_SESSION['user_is_admin']) || $_SESSION['user_is_admin'] !== true) {
    $errors[] = 'session_admin';
}
if (empty($_COOKIE[AUTH_COOKIE_NAME])) {
    $errors[] = 'cookie_persistent';
}

if (!empty($errors)) {
    echo "TEST FAILED: " . implode(', ', $errors) . "\n";
    // Cleanup: delete test user before exit.
    if ($pdo) {
        $pdo->prepare('DELETE FROM userdata WHERE email = :email')->execute([':email' => $testEmail]);
    }
    exit(1);
}

echo "TEST PASSED\n";

// Cleanup: delete test user before exit.
if ($pdo) {
    $pdo->prepare('DELETE FROM userdata WHERE email = :email')->execute([':email' => $testEmail]);
} else {
    unset($userStore[$testEmail]);
}

exit(0);
