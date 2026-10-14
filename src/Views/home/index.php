<?php
/**
 * Home page: available trips.
 *
 * @var \App\Core\View $this
 * @var list<array<string, mixed>> $trajets
 * @var array{id: int, nom: string, prenom: string, email: string, est_admin: bool}|null $currentUser
 */
?>
<h1 class="h3 mb-4">
    <?= $currentUser === null ? 'Pour obtenir plus d\'informations sur un trajet, veuillez vous connecter' : 'Trajets proposés' ?>
</h1>

<?php if ($trajets === []): ?>
    <p class="text-muted">Aucun trajet disponible pour le moment.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle text-center app-table">
            <thead class="table-dark">
                <tr>
                    <th>Départ</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Destination</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Places</th>
                    <?php if ($currentUser !== null): ?>
                        <th><span class="visually-hidden">Actions</span></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trajets as $trajet): ?>
                    <?php
                    $id = (int) $trajet['id'];
                    $isAuthor = $currentUser !== null && $currentUser['id'] === (int) $trajet['utilisateur_id'];
                    ?>
                    <tr>
                        <td><?= $this->e($trajet['agence_depart']) ?></td>
                        <td><?= $this->date($trajet['date_depart'], 'd/m/y') ?></td>
                        <td><?= $this->date($trajet['date_depart'], 'H:i') ?></td>
                        <td><?= $this->e($trajet['agence_arrivee']) ?></td>
                        <td><?= $this->date($trajet['date_arrivee'], 'd/m/y') ?></td>
                        <td><?= $this->date($trajet['date_arrivee'], 'H:i') ?></td>
                        <td><?= $this->e($trajet['places_disponibles']) ?></td>
                        <?php if ($currentUser !== null): ?>
                            <td class="text-nowrap app-actions">
                                <button class="btn btn-link p-0 me-2" type="button" title="Détails"
                                        data-bs-toggle="modal" data-bs-target="#trajet-<?= $id ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Détails</span>
                                </button>
                                <?php if ($isAuthor): ?>
                                    <a class="btn btn-link p-0 me-2" href="/trajets/<?= $id ?>/edit" title="Modifier">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i><span class="visually-hidden">Modifier</span>
                                    </a>
                                    <form method="post" action="/trajets/<?= $id ?>/delete" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce trajet ?');">
                                        <?= $this->csrfField() ?>
                                        <button class="btn btn-link p-0 text-danger" type="submit" title="Supprimer">
                                            <i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($currentUser !== null): ?>
        <?php foreach ($trajets as $trajet): ?>
            <div class="modal fade" id="trajet-<?= (int) $trajet['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-6 text-muted">
                                <?= $this->e($trajet['agence_depart']) ?> → <?= $this->e($trajet['agence_arrivee']) ?>
                            </h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p>Auteur : <strong><?= $this->e($trajet['auteur_prenom'] . ' ' . $trajet['auteur_nom']) ?></strong></p>
                            <p>Téléphone : <strong><?= $this->e($trajet['auteur_telephone']) ?></strong></p>
                            <p>Email : <strong><?= $this->e($trajet['auteur_email']) ?></strong></p>
                            <p class="mb-0">Nombre total de places : <?= $this->e($trajet['places_total']) ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
