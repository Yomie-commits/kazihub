<?php
/**
 * Loaded at the top of every page: config, session, database and helpers.
 */

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('KaziHub is not configured yet. Copy config/config.sample.php to config/config.php and add your database details.');
}
require $configFile;

date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Africa/Nairobi');

if (defined('APP_DEBUG') && APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('kazihub_session');
    session_start();
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/cards.php';

// Keep "last seen" fresh for logged-in users (at most once a minute).
if (is_logged_in() && (empty($_SESSION['last_seen_ping']) || time() - $_SESSION['last_seen_ping'] > 60)) {
    db()->prepare('UPDATE users SET last_seen = ? WHERE id = ?')->execute([now(), current_user_id()]);
    $_SESSION['last_seen_ping'] = time();
}
