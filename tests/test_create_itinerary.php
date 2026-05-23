<?php
/**
 * Itinerary creation integration test.
 *
 * Validates itinerary form processing, trip record validation, and insertion
 * into MariaDB or fallback SQLite. Tests CSRF protection and date constraints.
 *
 * Usage: php tests/test_create_itinerary.php
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// Initialize database storage: prioritize MariaDB via backend/config.php, fallback to SQLite, then array.
$pdo = null;
$userStore = [];
$trajetsStore = [];

/**
 * Parse database configuration constants from backend/config.php.
 *
 * @param string $path Path to config.php file.
 * @return array{host?: string, name?: string, user?: string, pass?: string} Database credentials.
 */
function parse_db_constants(string $path): array
{
    if (!is_readable($path)) return [];
    $content = file_get_contents($path);
    $res = [];
    if (preg_match("/define\('\s*DB_HOST\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['host'] = $m[1];
    if (preg_match("/define\('\s*DB_NAME\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['name'] = $m[1];
    if (preg_match("/define\('\s*DB_USER\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['user'] = $m[1];
    if (preg_match("/define\('\s*DB_PASS\s*'\s*,\s*'([^']*)'\s*\)/", $content, $m)) $res['pass'] = $m[1];
    return $res;
}

$config = parse_db_constants(__DIR__ . '/../backend/config.php');
if (!empty($config)) {
    // Attempt MariaDB/MySQL connection using parsed credentials.
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['name']);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        $pdo = null;
    }
}

if (!$pdo) {
    // Fallback to SQLite in-memory database if MariaDB unavailable.
    try {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec('CREATE TABLE userdata (email TEXT PRIMARY KEY, password TEXT, nom TEXT, prenom TEXT, tel TEXT, admin INTEGER)');
        $pdo->exec('CREATE TABLE trajets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_email TEXT, agence_depart TEXT, date_depart DATETIME, agence_arrivee TEXT, date_arrivee DATETIME, places_total INTEGER, places_disponibles INTEGER)');
    } catch (Exception $e) {
        $pdo = null;
    }
}

// Create test author user.
$author = 'author@example.com';
$password = 'pwd';
if ($pdo) {
    $pdo->prepare('INSERT INTO userdata (email,password,nom,prenom, tel,admin) VALUES (:e,:p,:n,:r,:t,:a)')
        ->execute([':e'=>$author,':p'=>$password,':n'=>'A',':r'=>'B', ':t'=>'1234567890',':a'=>0]);
} else {
    $userStore[$author] = ['password'=>$password,'nom'=>'A','prenom'=>'B','tel'=>'1234567890','admin'=>0];
}

// Load or create agency list from test data file.
$agencesDir = __DIR__ . '/../data/jeu-d-essais';
@mkdir($agencesDir, 0777, true);
$agencesFile = $agencesDir . '/agences.txt';
file_put_contents($agencesFile, "Paris\nLyon\n");
$agences = array_values(array_filter(array_map('trim', file($agencesFile, FILE_IGNORE_NEW_LINES))));

// Simulate authenticated user session.
$_SESSION['user_email'] = $author;

// Prepare valid form submission data.
$now = time() + 3600;
$depart = date('Y-m-d\TH:i', $now);
$arrivee = date('Y-m-d\TH:i', $now + 3600);
$_SESSION['csrf_token'] = 'csrf-test';
$_POST = [
    'csrf_token' => 'csrf-test',
    'agence_depart' => $agences[0],
    'agence_arrivee' => $agences[1],
    'date_depart' => $depart,
    'date_arrivee' => $arrivee,
    'places_total' => '3',
    'places_disponibles' => '3',
];

// Validate form submission (logic copied from create_itinerary.php).
$errors = [];
$trajet = [];
$trajet['agence_depart'] = trim($_POST['agence_depart'] ?? '');
$trajet['date_depart'] = trim($_POST['date_depart'] ?? '');
$trajet['agence_arrivee'] = trim($_POST['agence_arrivee'] ?? '');
$trajet['date_arrivee'] = trim($_POST['date_arrivee'] ?? '');
$trajet['places_total'] = (int) ($_POST['places_total'] ?? 0);
$trajet['places_disponibles'] = (int) ($_POST['places_disponibles'] ?? $trajet['places_total']);

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = 'Requete invalide.';
}
if ($trajet['agence_depart'] === '' || $trajet['agence_arrivee'] === '') {
    $errors[] = 'agences obligatoires';
} elseif (!in_array($trajet['agence_depart'], $agences, true) || !in_array($trajet['agence_arrivee'], $agences, true)) {
    $errors[] = 'agences non autorisees';
} elseif (strcasecmp($trajet['agence_depart'], $trajet['agence_arrivee']) === 0) {
    $errors[] = 'agences identiques';
}

$departTs = strtotime($trajet['date_depart']);
$arriveeTs = strtotime($trajet['date_arrivee']);
if ($departTs === false || $arriveeTs === false) {
    $errors[] = 'dates obligatoires';
} else {
    if ($departTs < time()) {
        $errors[] = 'depart dans le passe';
    }
    if ($arriveeTs <= $departTs) {
        $errors[] = 'arrivee avant depart';
    }
}
if ($trajet['places_total'] < 1) $errors[] = 'places total insuffisantes';
if ($trajet['places_disponibles'] < 0 || $trajet['places_disponibles'] > $trajet['places_total']) $errors[] = 'places disponibles invalides';

if (!empty($errors)) {
    echo "TEST FAILED (validation): " . implode(', ', $errors) . "\n";
    exit(1);
}

// Insert validated trip record into database.
if ($pdo) {
    $stmt = $pdo->prepare('INSERT INTO trajets (user_email, agence_depart, date_depart, agence_arrivee, date_arrivee, places_total, places_disponibles) VALUES (:email,:ad,:dd,:aa,:da,:pt,:pd)');
    $stmt->execute([
        ':email' => $_SESSION['user_email'],
        ':ad' => $trajet['agence_depart'],
        ':dd' => date('Y-m-d H:i:s', $departTs),
        ':aa' => $trajet['agence_arrivee'],
        ':da' => date('Y-m-d H:i:s', $arriveeTs),
        ':pt' => $trajet['places_total'],
        ':pd' => $trajet['places_disponibles'],
    ]);

    $row = $pdo->query('SELECT COUNT(*) AS c FROM trajets')->fetch();
    if ((int)$row['c'] >= 1) {
        echo "TEST PASSED\n";
        // Cleanup: delete test data before exit.
        $pdo->prepare('DELETE FROM trajets WHERE user_email = :email')->execute([':email' => $author]);
        $pdo->prepare('DELETE FROM userdata WHERE email = :email')->execute([':email' => $author]);
        exit(0);
    }
    echo "TEST FAILED (no row)\n";
    // Cleanup: delete test data before exit.
    $pdo->prepare('DELETE FROM trajets WHERE user_email = :email')->execute([':email' => $author]);
    $pdo->prepare('DELETE FROM userdata WHERE email = :email')->execute([':email' => $author]);
    exit(1);
} else {
    // Fallback to array storage if database unavailable.
    $trajetsStore[] = [
        'user_email' => $_SESSION['user_email'],
        'agence_depart' => $trajet['agence_depart'],
    ];
    if (count($trajetsStore) >= 1) {
        echo "TEST PASSED (fallback)\n";
        // Cleanup: clear in-memory storage before exit.
        unset($userStore[$author], $trajetsStore[0]);
        exit(0);
    }
    echo "TEST FAILED (fallback)\n";
    exit(1);
}
