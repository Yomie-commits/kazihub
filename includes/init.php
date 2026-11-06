<?php
/**
 * Loaded at the top of every page.
 */
require_once __DIR__ . '/../config/config.php';

if (APP_DEBUG) {
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

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/partials.php';

// Basic security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Verify CSRF token on every POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('SKIP_CSRF')) {
    verify_csrf();
}

// Log out suspended users and keep "last seen" fresh
if (!empty($_SESSION['user_id'])) {
    $me = current_user();
    if (!$me || $me['status'] === 'suspended') {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('danger', 'Your account is not active. Please contact support.');
    } elseif (empty($_SESSION['last_seen_ping']) || time() - $_SESSION['last_seen_ping'] > 120) {
        db_exec('UPDATE users SET last_seen = ? WHERE id = ?', [now(), $me['id']]);
        $_SESSION['last_seen_ping'] = time();
    }
}
