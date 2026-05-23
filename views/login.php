<?php
/**
 * Login view.
 *
 * Renders the authentication form, displays one-time login errors, and creates
 * a CSRF token for credential submission.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body>
    <h1>Login</h1>
    <?php if (!empty($_SESSION['error'])): ?>
        <p style="color:red;"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <form action="/backend/process_login.php" method="post">
        <label>E‑mail :</label>
        <input type="email" name="email" required>
        <label>Mot de passe :</label>
        <input type="password" name="password" required>

        <?php /** Generates the CSRF token used by the login processor. */ ?>
        <?php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
