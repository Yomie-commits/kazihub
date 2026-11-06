<?php
/**
 * Authentication helpers.
 */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

/** Current user row (cached per request). Logs out deactivated accounts. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (!is_logged_in()) {
        return $user = null;
    }
    $user = db_one('SELECT * FROM users WHERE id = ?', [current_user_id()]);
    if (!$user || !$user['is_active']) {
        logout_user();
        return $user = null;
    }
    return $user;
}

function refresh_current_user(): array
{
    // Bypass the static cache after updates.
    return db_one('SELECT * FROM users WHERE id = ?', [current_user_id()]);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = $user['role'];
    db_exec('UPDATE users SET last_seen = ? WHERE id = ?', [now(), $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'])));
    }
    return $user;
}

/** Require one of the given roles. Admins pass every check. */
function require_role(array $roles): array
{
    $user = require_login();
    if ($user['role'] !== 'admin' && !in_array($user['role'], $roles, true)) {
        flash('danger', 'That page is not available for your account type.');
        redirect('dashboard.php');
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function can_post_jobs(?array $user): bool
{
    return $user && in_array($user['role'], ['client', 'admin'], true);
}

function can_apply(?array $user): bool
{
    return $user && in_array($user['role'], ['freelancer', 'job_seeker'], true);
}

/** Profile completeness as a percentage, based on account type. */
function profile_completion(array $u): int
{
    $fields = ['name', 'email', 'phone', 'location', 'bio', 'avatar'];
    $extra = [
        'freelancer' => ['headline', 'skills', 'experience', 'hourly_rate'],
        'job_seeker' => ['headline', 'skills', 'experience'],
        'client'     => ['company_name', 'industry'],
        'admin'      => [],
    ];
    $all = array_merge($fields, $extra[$u['role']] ?? []);
    $done = count(array_filter($all, fn($f) => !empty($u[$f])));
    return (int)round($done / count($all) * 100);
}
