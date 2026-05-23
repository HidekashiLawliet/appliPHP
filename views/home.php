<?php
/**
 * Home view.
 *
 * Lists available future itineraries and lets authenticated authors manage
 * their own trips.
 */
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../backend/auth_cookie.php';
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/trajets_schema.php';

ensureTrajetsTable($pdo);
auth_restore_session_from_cookie($pdo);

$currentEmail = $_SESSION['user_email'] ?? null;
$isLogged = !empty($currentEmail);
$error = '';
$success = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_trajet') {
    if (!$isLogged) {
        $error = 'Vous devez etre connecte pour supprimer un trajet.';
    } elseif (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Requete invalide.';
    } else {
        $trajetId = (int) ($_POST['trajet_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM trajets WHERE id = :id AND user_email = :email');
        $stmt->execute([
            ':id' => $trajetId,
            ':email' => $currentEmail,
        ]);
        $success = $stmt->rowCount() > 0 ? 'Trajet supprime.' : 'Suppression impossible.';
    }
}

$stmt = $pdo->prepare('
    SELECT
        t.*,
        u.nom,
        u.prenom,
        u.tel,
        u.email
    FROM trajets t
    INNER JOIN userdata u ON u.email COLLATE utf8mb4_general_ci = t.user_email COLLATE utf8mb4_general_ci
    WHERE t.places_disponibles > 0
    AND t.date_depart >= NOW()
    ORDER BY t.date_depart ASC
');
$stmt->execute();
$trajets = $stmt->fetchAll();

/**
 * Formats a database datetime for public trip listings.
 */
function formatDateFr(string $date): string
{
    return date('d/m/Y H:i', strtotime($date));
}
?>

<section class="py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="mb-0">Trajets disponibles</h1>
        <?php if ($isLogged): ?>
            <a class="btn btn-success" href="/create_itinerary">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Creer un trajet
            </a>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($trajets)): ?>
        <p>Aucun trajet disponible pour le moment.</p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($trajets as $trajet): ?>
                <?php
                $modalId = 'trajetDetails' . (int) $trajet['id'];
                $isAuthor = $isLogged && $currentEmail === $trajet['user_email'];
                ?>
                <div class="list-group-item">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-9">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="text-muted small">Agence de depart</div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($trajet['agence_depart']); ?></div>
                                    <div><?php echo htmlspecialchars(formatDateFr($trajet['date_depart'])); ?></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="text-muted small">Agence d'arrivee</div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($trajet['agence_arrivee']); ?></div>
                                    <div><?php echo htmlspecialchars(formatDateFr($trajet['date_arrivee'])); ?></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge text-bg-success">
                                    <?php echo (int) $trajet['places_disponibles']; ?> place(s) disponible(s)
                                </span>
                            </div>
                        </div>

                        <?php if ($isLogged): ?>
                            <div class="col-12 col-lg-3 d-flex flex-wrap justify-content-lg-end gap-2">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
                                    Details
                                </button>
                                <?php if ($isAuthor): ?>
                                    <a class="btn btn-outline-secondary btn-sm" href="/create_itinerary?id=<?php echo (int) $trajet['id']; ?>">
                                        Modifier
                                    </a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce trajet ?');">
                                        <input type="hidden" name="action" value="delete_trajet">
                                        <input type="hidden" name="trajet_id" value="<?php echo (int) $trajet['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isLogged): ?>
                    <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5" id="<?php echo $modalId; ?>Label">Informations du trajet</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Propose par :</strong> <?php echo htmlspecialchars(trim($trajet['prenom'] . ' ' . $trajet['nom'])); ?></p>
                                    <p><strong>Telephone :</strong> <?php echo htmlspecialchars($trajet['tel']); ?></p>
                                    <p><strong>Email :</strong> <?php echo htmlspecialchars($trajet['email']); ?></p>
                                    <p><strong>Nombre total de places :</strong> <?php echo (int) $trajet['places_total']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
