<?php
/**
 * Login form.
 *
 * @var \App\Core\View $this
 * @var string $email Email typed during the previous attempt.
 */
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <h1 class="h3 mb-4">Connexion</h1>
        <form method="post" action="/login" novalidate>
            <?= $this->csrfField() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= $this->e($email) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mot de passe</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                <label class="form-check-label" for="remember">Se souvenir de moi</label>
            </div>
            <button class="btn btn-primary w-100" type="submit">Se connecter</button>
        </form>
    </div>
</div>
