<?php
/**
 * Administration: agencies (create, rename, delete).
 *
 * @var \App\Core\View $this
 * @var list<array<string, mixed>> $agences
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h3 mb-0">Agences</h1>
    <form method="post" action="/admin/agences" class="d-flex gap-2">
        <?= $this->csrfField() ?>
        <label class="visually-hidden" for="nouvelle-agence">Nom de la nouvelle agence</label>
        <input class="form-control" id="nouvelle-agence" name="nom" placeholder="Nouvelle agence" maxlength="100" required>
        <button class="btn btn-primary text-nowrap" type="submit">Créer</button>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle text-center app-table">
        <thead class="table-dark">
            <tr>
                <th>Agence</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agences as $agence): ?>
                <?php $id = (int) $agence['id']; ?>
                <tr>
                    <td>
                        <form method="post" action="/admin/agences/<?= $id ?>/update" class="d-flex gap-2">
                            <?= $this->csrfField() ?>
                            <label class="visually-hidden" for="agence-<?= $id ?>">Nom</label>
                            <input class="form-control form-control-sm" id="agence-<?= $id ?>" name="nom" value="<?= $this->e($agence['nom']) ?>" maxlength="100" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Renommer</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="/admin/agences/<?= $id ?>/delete" class="d-inline"
                              onsubmit="return confirm('Supprimer cette agence ?');">
                            <?= $this->csrfField() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
