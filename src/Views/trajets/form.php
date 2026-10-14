<?php
/**
 * Trip creation / edition form.
 *
 * @var \App\Core\View $this
 * @var array<string, string> $values Field values.
 * @var int|null $trajetId Edited trip, null for a creation.
 * @var list<string> $errors
 * @var list<array<string, mixed>> $agences
 * @var array<string, mixed> $auteur Logged-in user (read-only fields).
 * @var int $maxPlaces
 */
$isEdit = $trajetId !== null;
$action = $isEdit ? '/trajets/' . $trajetId . '/update' : '/trajets';
$agenceFields = ['agence_depart_id' => 'Agence de départ', 'agence_arrivee_id' => "Agence d'arrivée"];
?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Modifier le trajet' : 'Créer un trajet' ?></h1>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= $this->e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" class="row g-3">
    <?= $this->csrfField() ?>

    <div class="col-md-6">
        <label class="form-label" for="auteur_nom">Nom</label>
        <input class="form-control" id="auteur_nom" value="<?= $this->e($auteur['nom'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="auteur_prenom">Prénom</label>
        <input class="form-control" id="auteur_prenom" value="<?= $this->e($auteur['prenom'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="auteur_email">Email</label>
        <input class="form-control" id="auteur_email" value="<?= $this->e($auteur['email'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="auteur_telephone">Téléphone</label>
        <input class="form-control" id="auteur_telephone" value="<?= $this->e($auteur['telephone'] ?? '') ?>" readonly>
    </div>

    <?php foreach ($agenceFields as $field => $label): ?>
        <div class="col-md-6">
            <label class="form-label" for="<?= $field ?>"><?= $this->e($label) ?></label>
            <select class="form-select" id="<?= $field ?>" name="<?= $field ?>" required>
                <option value="">Choisir une agence</option>
                <?php foreach ($agences as $agence): ?>
                    <option value="<?= (int) $agence['id'] ?>" <?= (string) $agence['id'] === $values[$field] ? 'selected' : '' ?>>
                        <?= $this->e($agence['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>

    <div class="col-md-6">
        <label class="form-label" for="date_depart">Date et heure de départ</label>
        <input class="form-control" type="datetime-local" id="date_depart" name="date_depart" value="<?= $this->e($values['date_depart']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="date_arrivee">Date et heure d'arrivée</label>
        <input class="form-control" type="datetime-local" id="date_arrivee" name="date_arrivee" value="<?= $this->e($values['date_arrivee']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="places_total">Nombre total de places</label>
        <input class="form-control" type="number" id="places_total" name="places_total" min="1" max="<?= $maxPlaces ?>" value="<?= $this->e($values['places_total']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="places_disponibles">Places disponibles</label>
        <input class="form-control" type="number" id="places_disponibles" name="places_disponibles" min="0" max="<?= $maxPlaces ?>" value="<?= $this->e($values['places_disponibles']) ?>" required>
    </div>

    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
        <a class="btn btn-outline-secondary" href="/">Annuler</a>
    </div>
</form>
