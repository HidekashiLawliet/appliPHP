<?php
/**
 * Logout controller.
 *
 * Clears session data, deletes session and persistent auth cookies, destroys
 * the session, and redirects back to the home page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_cookie.php';

$_SESSION = [];
/**
 * Deletes the PHP session cookie before destroying the server-side session.
 */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    if (!headers_sent()) {
        setcookie(session_name(), '', auth_cookie_delete_options($params));
    }
    unset($_COOKIE[session_name()]);
}

auth_cookie_clear();
session_destroy();

header('Location: /home.php');
exit;
