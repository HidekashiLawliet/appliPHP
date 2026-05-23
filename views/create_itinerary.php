<?php
/**
 * Create and edit itinerary view.
 *
 * Validates user-owned trip data, creates new trips, and updates existing trips
 * for the authenticated author.
 */
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/auth_cookie.php';
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/trajets_schema.php';

ensureTrajetsTable($pdo);
auth_restore_session_from_cookie($pdo);

if (empty($_SESSION['user_email'])) {
    http_response_code(403);
    echo '<h1>Acces refuse</h1><p>Vous devez etre connecte pour creer un trajet.</p>';
    return;
}

$currentEmail = $_SESSION['user_email'];
$userStmt = $pdo->prepare('SELECT nom, prenom, tel, email FROM userdata WHERE email = :email');
$userStmt->execute([':email' => $currentEmail]);
$currentUser = $userStmt->fetch();
$agencesPath = __DIR__ . '/../data/jeu-d-essais/agences.txt';
$agences = file_exists($agencesPath)
    ? array_values(array_filter(array_map('trim', file($agencesPath, FILE_IGNORE_NEW_LINES))))
    : [];

if (!$currentUser) {
    echo '<div class="alert alert-danger">Utilisateur introuvable.</div>';
    return;
}

if (empty($agences)) {
    echo '<div class="alert alert-danger">La liste des agences est introuvable.</div>';
    return;
}

$trajetId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $trajetId > 0;
$errors = [];
$success = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$trajet = [
    'agence_depart' => '',
    'date_depart' => '',
    'agence_arrivee' => '',
    'date_arrivee' => '',
    'places_total' => 1,
    'places_disponibles' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM trajets WHERE id = :id AND user_email = :email');
    $stmt->execute([
        ':id' => $trajetId,
        ':email' => $currentEmail,
    ]);
    $existingTrajet = $stmt->fetch();

    if (!$existingTrajet) {
        http_response_code(403);
        echo '<h1>Acces refuse</h1><p>Vous ne pouvez modifier que vos propres trajets.</p>';
        return;
    }

    $trajet = $existingTrajet;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Requete invalide.';
    }

    $trajet['agence_depart'] = trim($_POST['agence_depart'] ?? '');
    $trajet['date_depart'] = trim($_POST['date_depart'] ?? '');
    $trajet['agence_arrivee'] = trim($_POST['agence_arrivee'] ?? '');
    $trajet['date_arrivee'] = trim($_POST['date_arrivee'] ?? '');
    $trajet['places_total'] = (int) ($_POST['places_total'] ?? 0);
    $trajet['places_disponibles'] = (int) ($_POST['places_disponibles'] ?? $trajet['places_total']);

    $departTs = strtotime($trajet['date_depart']);
    $arriveeTs = strtotime($trajet['date_arrivee']);

    if ($trajet['agence_depart'] === '' || $trajet['agence_arrivee'] === '') {
        $errors[] = 'Les agences de depart et arrivee sont obligatoires.';
    } elseif (!in_array($trajet['agence_depart'], $agences, true) || !in_array($trajet['agence_arrivee'], $agences, true)) {
        $errors[] = 'Les agences choisies doivent faire partie de la liste autorisee.';
    } elseif (strcasecmp($trajet['agence_depart'], $trajet['agence_arrivee']) === 0) {
        $errors[] = 'L agence de depart et l agence d arrivee doivent etre differentes.';
    }

    if ($departTs === false || $arriveeTs === false) {
        $errors[] = 'Les dates de depart et arrivee sont obligatoires.';
    } else {
        if ($departTs < time()) {
            $errors[] = 'La date de depart ne peut pas etre dans le passe.';
        }
        if ($arriveeTs <= $departTs) {
            $errors[] = 'La date d arrivee doit etre apres la date de depart.';
        }
    }

    if ($trajet['places_total'] < 1) {
        $errors[] = 'Le nombre total de places doit etre au moins egal a 1.';
    }
    if ($trajet['places_disponibles'] < 0 || $trajet['places_disponibles'] > $trajet['places_total']) {
        $errors[] = 'Les places disponibles doivent etre comprises entre 0 et le nombre total de places.';
    }

    if (empty($errors)) {
        $dateDepartSql = date('Y-m-d H:i:s', $departTs);
        $dateArriveeSql = date('Y-m-d H:i:s', $arriveeTs);

        if ($isEdit) {
            $stmt = $pdo->prepare('
                UPDATE trajets
                SET agence_depart = :agence_depart,
                    date_depart = :date_depart,
                    agence_arrivee = :agence_arrivee,
                    date_arrivee = :date_arrivee,
                    places_total = :places_total,
                    places_disponibles = :places_disponibles
                WHERE id = :id AND user_email = :email
            ');
            $stmt->execute([
                ':agence_depart' => $trajet['agence_depart'],
                ':date_depart' => $dateDepartSql,
                ':agence_arrivee' => $trajet['agence_arrivee'],
                ':date_arrivee' => $dateArriveeSql,
                ':places_total' => $trajet['places_total'],
                ':places_disponibles' => $trajet['places_disponibles'],
                ':id' => $trajetId,
                ':email' => $currentEmail,
            ]);
            $success = 'Trajet modifie.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO trajets (user_email, agence_depart, date_depart, agence_arrivee, date_arrivee, places_total, places_disponibles)
                VALUES (:email, :agence_depart, :date_depart, :agence_arrivee, :date_arrivee, :places_total, :places_disponibles)
            ');
            $stmt->execute([
                ':email' => $currentEmail,
                ':agence_depart' => $trajet['agence_depart'],
                ':date_depart' => $dateDepartSql,
                ':agence_arrivee' => $trajet['agence_arrivee'],
                ':date_arrivee' => $dateArriveeSql,
                ':places_total' => $trajet['places_total'],
                ':places_disponibles' => $trajet['places_disponibles'],
            ]);
            $success = 'Trajet cree.';
            $trajetId = (int) $pdo->lastInsertId();
            $isEdit = true;
        }

        $trajet['date_depart'] = $dateDepartSql;
        $trajet['date_arrivee'] = $dateArriveeSql;
    }
}

/**
 * Converts a database datetime into the value expected by datetime-local inputs.
 */
function inputDateValue(?string $date): string
{
    if (!$date) {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($date));
}
?>

<section class="py-4">
    <h1><?php echo $isEdit ? 'Modifier un trajet' : 'Creer un trajet'; ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo (int) $trajetId; ?>">
        <?php endif; ?>

        <div class="col-12 col-md-6">
            <label class="form-label">Nom</label>
            <input class="form-control" value="<?php echo htmlspecialchars($currentUser['nom']); ?>" readonly>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Prenom</label>
            <input class="form-control" value="<?php echo htmlspecialchars($currentUser['prenom']); ?>" readonly>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Telephone</label>
            <input class="form-control" value="<?php echo htmlspecialchars($currentUser['tel']); ?>" readonly>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="agence_depart">Agence de depart</label>
            <select class="form-select" id="agence_depart" name="agence_depart" required>
                <option value="">Choisir une agence</option>
                <?php foreach ($agences as $agence): ?>
                    <option value="<?php echo htmlspecialchars($agence); ?>" <?php echo $trajet['agence_depart'] === $agence ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($agence); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="agence_arrivee">Agence d'arrivee</label>
            <select class="form-select" id="agence_arrivee" name="agence_arrivee" required>
                <option value="">Choisir une agence</option>
                <?php foreach ($agences as $agence): ?>
                    <option value="<?php echo htmlspecialchars($agence); ?>" <?php echo $trajet['agence_arrivee'] === $agence ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($agence); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="date_depart">Date de depart</label>
            <input class="form-control" id="date_depart" name="date_depart" type="datetime-local" value="<?php echo htmlspecialchars(inputDateValue($trajet['date_depart'])); ?>" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="date_arrivee">Date d'arrivee</label>
            <input class="form-control" id="date_arrivee" name="date_arrivee" type="datetime-local" value="<?php echo htmlspecialchars(inputDateValue($trajet['date_arrivee'])); ?>" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="places_total">Nombre total de places</label>
            <input class="form-control" id="places_total" name="places_total" type="number" min="1" value="<?php echo (int) $trajet['places_total']; ?>" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="places_disponibles">Places disponibles</label>
            <input class="form-control" id="places_disponibles" name="places_disponibles" type="number" min="0" value="<?php echo (int) $trajet['places_disponibles']; ?>" required>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-success" type="submit"><?php echo $isEdit ? 'Enregistrer' : 'Creer'; ?></button>
            <a class="btn btn-outline-secondary" href="/home">Retour</a>
        </div>
    </form>
</section>
