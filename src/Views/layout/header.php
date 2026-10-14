<?php
/**
 * Navigation bar. Content depends on the role of the visitor.
 *
 * @var \App\Core\View $this
 * @var array{id: int, nom: string, prenom: string, email: string, est_admin: bool}|null $currentUser
 * @var string $currentPath
 */
$adminLinks = [
    '/admin/utilisateurs' => 'Utilisateurs',
    '/admin/agences' => 'Agences',
    '/admin/trajets' => 'Trajets',
];
?>
<header class="container">
    <nav class="navbar app-navbar">
        <a class="navbar-brand" href="/">Touche pas au klaxon</a>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($currentUser === null): ?>
                <a class="btn btn-dark" href="/login">Connexion</a>
            <?php else: ?>
                <?php if ($currentUser['est_admin']): ?>
                    <?php foreach ($adminLinks as $url => $label): ?>
                        <a class="btn btn-secondary<?= $currentPath === $url ? ' active' : '' ?>" href="<?= $url ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a class="btn btn-dark" href="/trajets/create">Créer un trajet</a>
                <?php endif; ?>

                <span class="navbar-text mx-2">
                    Bonjour <?= $this->e($currentUser['prenom'] . ' ' . $currentUser['nom']) ?>
                </span>

                <form method="post" action="/logout" class="m-0">
                    <?= $this->csrfField() ?>
                    <button class="btn btn-dark" type="submit">Déconnexion</button>
                </form>
            <?php endif; ?>
        </div>
    </nav>
</header>
