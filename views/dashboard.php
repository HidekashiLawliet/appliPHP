<?php
/**
 * Administrator dashboard view.
 *
 * Manages users, agencies, and shared itineraries from a single tabbed admin
 * screen.
 */
require_once __DIR__ . '/../backend/trajets_schema.php';

if (!isAdmin()) {
    http_response_code(403);
    echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs.</p>';
    return;
}

ensureTrajetsTable($pdo);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$agencesPath = __DIR__ . '/../data/jeu-d-essais/agences.txt';
$errors = [];
$success = '';

/**
 * Reads the agency list from a plain-text data file.
 *
 * @return string[]
 */
function readAgences(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('trim', file($path, FILE_IGNORE_NEW_LINES)))));
}

/**
 * Persists the agency list in sorted order.
 *
 * @param string[] $agences
 */
function writeAgences(string $path, array $agences): void
{
    sort($agences, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents($path, implode(PHP_EOL, $agences) . PHP_EOL, LOCK_EX);
}

$agences = readAgences($agencesPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Requete invalide.';
    } elseif ($action === 'create_agence') {
        $newAgence = trim($_POST['agence_name'] ?? '');
        if ($newAgence === '') {
            $errors[] = 'Le nom de l agence est obligatoire.';
        } elseif (in_array($newAgence, $agences, true)) {
            $errors[] = 'Cette agence existe deja.';
        } else {
            $agences[] = $newAgence;
            writeAgences($agencesPath, $agences);
            $success = 'Agence creee.';
        }
    } elseif ($action === 'update_agence') {
        $oldAgence = trim($_POST['old_agence_name'] ?? '');
        $newAgence = trim($_POST['agence_name'] ?? '');

        if ($oldAgence === '' || !in_array($oldAgence, $agences, true)) {
            $errors[] = 'Agence introuvable.';
        } elseif ($newAgence === '') {
            $errors[] = 'Le nouveau nom est obligatoire.';
        } elseif ($newAgence !== $oldAgence && in_array($newAgence, $agences, true)) {
            $errors[] = 'Une agence porte deja ce nom.';
        } else {
            $agences = array_map(fn($agence) => $agence === $oldAgence ? $newAgence : $agence, $agences);
            writeAgences($agencesPath, $agences);

            $stmt = $pdo->prepare('UPDATE trajets SET agence_depart = :new WHERE agence_depart = :old');
            $stmt->execute([':new' => $newAgence, ':old' => $oldAgence]);
            $stmt = $pdo->prepare('UPDATE trajets SET agence_arrivee = :new WHERE agence_arrivee = :old');
            $stmt->execute([':new' => $newAgence, ':old' => $oldAgence]);

            $success = 'Agence modifiee.';
        }
    } elseif ($action === 'delete_agence') {
        $agenceName = trim($_POST['agence_name'] ?? '');

        if ($agenceName === '' || !in_array($agenceName, $agences, true)) {
            $errors[] = 'Agence introuvable.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM trajets WHERE agence_depart = :agence OR agence_arrivee = :agence');
            $stmt->execute([':agence' => $agenceName]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'Cette agence est utilisee par un trajet et ne peut pas etre supprimee.';
            } else {
                $agences = array_values(array_filter($agences, fn($agence) => $agence !== $agenceName));
                writeAgences($agencesPath, $agences);
                $success = 'Agence supprimee.';
            }
        }
    } elseif ($action === 'delete_trajet') {
        $trajetId = (int) ($_POST['trajet_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM trajets WHERE id = :id');
        $stmt->execute([':id' => $trajetId]);
        $success = $stmt->rowCount() > 0 ? 'Trajet supprime.' : 'Trajet introuvable.';
    }

    $agences = readAgences($agencesPath);
}

$usersStmt = $pdo->query('SELECT nom, prenom, tel, email, admin FROM userdata ORDER BY nom, prenom');
$users = $usersStmt->fetchAll();

$trajetsStmt = $pdo->query('
    SELECT
        t.*,
        u.nom,
        u.prenom,
        u.tel,
        u.email
    FROM trajets t
    LEFT JOIN userdata u ON u.email COLLATE utf8mb4_general_ci = t.user_email COLLATE utf8mb4_general_ci
    ORDER BY t.date_depart DESC
');
$trajets = $trajetsStmt->fetchAll();

/**
 * Formats a database datetime for the admin dashboard.
 */
function dashboardDateFr(?string $date): string
{
    if (!$date) {
        return '';
    }

    return date('d/m/Y H:i', strtotime($date));
}
?>

<section class="py-4">
    <button type="button" class="btn btn-primary mb-3" onclick="window.location.href='/home.php'">Home</button>
    <h1 class="mb-4">Tableau de bord administrateur</h1>

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

    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-panel" type="button" role="tab">Utilisateurs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="agences-tab" data-bs-toggle="tab" data-bs-target="#agences-panel" type="button" role="tab">Agences</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trajets-tab" data-bs-toggle="tab" data-bs-target="#trajets-panel" type="button" role="tab">Trajets</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="users-panel" role="tabpanel" tabindex="0">
            <h2 class="h4 mb-3">Utilisateurs</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prenom</th>
                            <th>Telephone</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($user['tel']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo !empty($user['admin']) ? 'Admin' : 'Utilisateur'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="agences-panel" role="tabpanel" tabindex="0">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h4 mb-0">Agences</h2>
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="create_agence">
                    <input class="form-control" name="agence_name" placeholder="Nouvelle agence" required>
                    <button class="btn btn-success" type="submit">Creer</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Agence</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agences as $agence): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agence); ?></td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_agence">
                                            <input type="hidden" name="old_agence_name" value="<?php echo htmlspecialchars($agence); ?>">
                                            <input class="form-control form-control-sm" name="agence_name" value="<?php echo htmlspecialchars($agence); ?>" required>
                                            <button class="btn btn-outline-primary btn-sm" type="submit">Modifier</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Supprimer cette agence ?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="delete_agence">
                                            <input type="hidden" name="agence_name" value="<?php echo htmlspecialchars($agence); ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="trajets-panel" role="tabpanel" tabindex="0">
            <h2 class="h4 mb-3">Trajets</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Depart</th>
                            <th>Arrivee</th>
                            <th>Auteur</th>
                            <th>Places</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trajets as $trajet): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($trajet['agence_depart']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars(dashboardDateFr($trajet['date_depart'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($trajet['agence_arrivee']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars(dashboardDateFr($trajet['date_arrivee'])); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars(trim(($trajet['prenom'] ?? '') . ' ' . ($trajet['nom'] ?? ''))); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($trajet['user_email']); ?></div>
                                </td>
                                <td><?php echo (int) $trajet['places_disponibles']; ?> / <?php echo (int) $trajet['places_total']; ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce trajet ?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="delete_trajet">
                                        <input type="hidden" name="trajet_id" value="<?php echo (int) $trajet['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($trajets)): ?>
                            <tr>
                                <td colspan="5">Aucun trajet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabTrigger = document.querySelector('[data-bs-target="' + window.location.hash + '"]');

    if (tabTrigger) {
        bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
    }

    document.querySelectorAll('#adminTabs [data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            history.replaceState(null, '', event.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
