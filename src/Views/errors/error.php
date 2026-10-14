<?php
/**
 * Error page (403, 404, 500...).
 *
 * @var \App\Core\View $this
 * @var int $status
 * @var string $message
 */
?>
<div class="text-center py-5">
    <h1 class="display-4"><?= $status ?></h1>
    <p class="lead"><?= $this->e($message) ?></p>
    <a class="btn btn-primary" href="/">Retour à l'accueil</a>
</div>
