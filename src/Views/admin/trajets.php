<?php
/**
 * Administration: every trip, with deletion.
 *
 * @var \App\Core\View $this
 * @var list<array<string, mixed>> $trajets
 */
?>
<h1 class="h3 mb-4">Trajets</h1>

<?php if ($trajets === []): ?>
    <p class="text-muted">Aucun trajet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle text-center app-table">
            <thead class="table-dark">
                <tr>
                    <th>Départ</th>
                    <th>Arrivée</th>
                    <th>Auteur</th>
                    <th>Places</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trajets as $trajet): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= $this->e($trajet['agence_depart']) ?></div>
                            <div class="small text-muted"><?= $this->date($trajet['date_depart']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= $this->e($trajet['agence_arrivee']) ?></div>
                            <div class="small text-muted"><?= $this->date($trajet['date_arrivee']) ?></div>
                        </td>
                        <td>
                            <div><?= $this->e($trajet['auteur_prenom'] . ' ' . $trajet['auteur_nom']) ?></div>
                            <div class="small text-muted"><?= $this->e($trajet['auteur_email']) ?></div>
                        </td>
                        <td><?= $this->e($trajet['places_disponibles']) ?> / <?= $this->e($trajet['places_total']) ?></td>
                        <td>
                            <form method="post" action="/admin/trajets/<?= (int) $trajet['id'] ?>/delete" class="d-inline"
                                  onsubmit="return confirm('Supprimer ce trajet ?');">
                                <?= $this->csrfField() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
