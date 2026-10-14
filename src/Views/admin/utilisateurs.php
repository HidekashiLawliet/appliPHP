<?php
/**
 * Administration: list of the employees.
 *
 * @var \App\Core\View $this
 * @var list<array<string, mixed>> $utilisateurs
 */
?>
<h1 class="h3 mb-4">Utilisateurs</h1>

<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle text-center app-table">
        <thead class="table-dark">
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Rôle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utilisateurs as $utilisateur): ?>
                <tr>
                    <td><?= $this->e($utilisateur['nom']) ?></td>
                    <td><?= $this->e($utilisateur['prenom']) ?></td>
                    <td><?= $this->e($utilisateur['telephone']) ?></td>
                    <td><?= $this->e($utilisateur['email']) ?></td>
                    <td><?= (int) $utilisateur['est_admin'] === 1 ? 'Administrateur' : 'Utilisateur' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
