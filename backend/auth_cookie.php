<?php
/**
 * Persistent authentication cookie helpers.
 *
 * Creates, validates, and clears signed login cookies, then restores the PHP
 * session from a trusted cookie when possible.
 */

const AUTH_COOKIE_NAME = 'user_login';
const AUTH_COOKIE_TTL = 86400;
/* :warning: change the secret key to a secure random string */
/**
 * Fallback signing secret used only when config.php has not defined one.
 */
const AUTH_COOKIE_DEFAULT_SECRET = 'ToChange';

/**
 * Returns the configured signing secret for authentication cookies.
 */
function auth_cookie_secret(): string
{
    return defined('AUTH_COOKIE_SECRET') ? AUTH_COOKIE_SECRET : AUTH_COOKIE_DEFAULT_SECRET;
}

/**
 * Detects whether the current request is served over HTTPS.
 */
function auth_cookie_is_secure(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

/**
 * Builds the cookie options used when creating the persistent login cookie.
 *
 * @return array<string, mixed>
 */
function auth_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => auth_cookie_is_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/**
 * Builds the cookie options used when deleting session or auth cookies.
 *
 * @param array<string, mixed> $params Existing cookie parameters to preserve.
 * @return array<string, mixed>
 */
function auth_cookie_delete_options(array $params = []): array
{
    $options = [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'secure' => $params['secure'] ?? auth_cookie_is_secure(),
        'httponly' => $params['httponly'] ?? true,
        'samesite' => ($params['samesite'] ?? '') ?: 'Lax',
    ];

    if (!empty($params['domain'])) {
        $options['domain'] = $params['domain'];
    }

    return $options;
}

/**
 * Encodes binary-safe data for compact URL-safe cookie storage.
 */
function auth_cookie_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

/**
 * Decodes URL-safe base64 data from the cookie payload.
 */
function auth_cookie_base64url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

/**
 * Signs an encoded cookie payload with HMAC-SHA256.
 */
function auth_cookie_sign(string $payload): string
{
    return hash_hmac('sha256', $payload, auth_cookie_secret());
}

/**
 * Stores a signed persistent login cookie for the given user email.
 */
function auth_cookie_set(string $email): void
{
    $payload = json_encode([
        'email' => $email,
        'exp' => time() + AUTH_COOKIE_TTL,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        return;
    }

    $encodedPayload = auth_cookie_base64url_encode($payload);
    $token = $encodedPayload . '.' . auth_cookie_sign($encodedPayload);
    if (!headers_sent()) {
        setcookie(AUTH_COOKIE_NAME, $token, auth_cookie_options(time() + AUTH_COOKIE_TTL));
    }
    $_COOKIE[AUTH_COOKIE_NAME] = $token;
}

/**
 * Clears the persistent login cookie from the browser and current request.
 */
function auth_cookie_clear(): void
{
    if (!headers_sent()) {
        setcookie(AUTH_COOKIE_NAME, '', auth_cookie_delete_options([
            'path' => '/',
            'secure' => auth_cookie_is_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]));
    }
    unset($_COOKIE[AUTH_COOKIE_NAME]);
}

/**
 * Validates the persistent login cookie and returns the stored email.
 */
function auth_cookie_get_email(): ?string
{
    $token = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
    if ($token === '' || !str_contains($token, '.')) {
        if ($token !== '') {
            auth_cookie_clear();
        }
        return null;
    }

    [$encodedPayload, $signature] = explode('.', $token, 2);
    $expectedSignature = auth_cookie_sign($encodedPayload);

    if (!hash_equals($expectedSignature, $signature)) {
        auth_cookie_clear();
        return null;
    }

    $payloadJson = auth_cookie_base64url_decode($encodedPayload);
    if ($payloadJson === false) {
        auth_cookie_clear();
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)
        || empty($payload['email'])
        || !is_string($payload['email'])
        || empty($payload['exp'])
        || !is_int($payload['exp'])
        || $payload['exp'] < time()
    ) {
        auth_cookie_clear();
        return null;
    }

    return $payload['email'];
}

/**
 * Restores session user data from a valid persistent login cookie.
 */
function auth_restore_session_from_cookie(PDO $pdo): bool
{
    if (!empty($_SESSION['user_email'])) {
        return true;
    }

    $email = auth_cookie_get_email();
    if ($email === null) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT nom, prenom, admin FROM userdata WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        auth_cookie_clear();
        return false;
    }

    $_SESSION['user_email'] = $email;
    $_SESSION['user_nom'] = $user['nom'] ?? '';
    $_SESSION['user_prenom'] = $user['prenom'] ?? '';
    $_SESSION['user_is_admin'] = (bool) ($user['admin'] ?? false);
    $_SESSION['user'] = [
        'email' => $email,
        'nom' => $user['nom'] ?? '',
        'prenom' => $user['prenom'] ?? '',
        'is_admin' => $_SESSION['user_is_admin'],
    ];

    return true;
}
