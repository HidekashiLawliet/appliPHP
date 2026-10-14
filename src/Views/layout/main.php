<?php
/**
 * Main layout: HTML skeleton, navigation, flash messages and footer.
 *
 * @var \App\Core\View $this
 * @var string $content Rendered page.
 * @var array{id: int, nom: string, prenom: string, email: string, est_admin: bool}|null $currentUser
 * @var list<array{type: string, message: string}> $flashes
 * @var string $currentPath
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Touche pas au klaxon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
</head>
<body class="app">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="container app-main">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= $this->e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= $this->e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

    <footer class="app-footer">
        &copy; <?= date('Y') ?> - CENEF - MVC PHP
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
