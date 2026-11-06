<?php
/**
 * General helper functions.
 */

// ---------- Output & URLs ----------------------------------------

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (BASE_URL_OVERRIDE !== null) {
        return $base = rtrim(BASE_URL_OVERRIDE, '/');
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(__DIR__ . '/..') ?: '';
    $rel = ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '';
    return $base = rtrim(str_replace('\\', '/', $rel), '/');
}

function url(string $path = '', array $query = []): string
{
    $u = base_url() . '/' . ltrim($path, '/');
    if ($query) {
        $u .= '?' . http_build_query($query);
    }
    return $u;
}

function redirect(string $path, array $query = []): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path, $query)));
    exit;
}

/** Redirect back to the page the request came from (same site only). */
function redirect_back(string $fallback = 'index.php'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

// ---------- Flash messages & old input ---------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

// ---------- CSRF -------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        flash('danger', 'Your session expired. Please try again.');
        redirect_back();
    }
}

// ---------- Auth ---------------------------------------------------

function current_user(bool $refresh = false): ?array
{
    static $user = false;
    if ($refresh) {
        $user = false;
    }
    if ($user === false) {
        $user = empty($_SESSION['user_id'])
            ? null
            : db_one('SELECT * FROM users WHERE id = ?', [(int) $_SESSION['user_id']]);
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/** Stop unless logged in (optionally with one of the given roles). Returns the user. */
function require_login(array $roles = []): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        flash('danger', 'You do not have access to that page.');
        redirect('dashboard.php');
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['login_attempts']);
    db_exec('UPDATE users SET last_seen = ? WHERE id = ?', [now(), $user['id']]);
}

// ---------- Formatting --------------------------------------------

function money($amount, string $currency = 'KES'): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    return $currency . ' ' . number_format((float) $amount, 0);
}

function pay_range(array $job): string
{
    $cur = $job['currency'] ?: 'KES';
    $min = $job['budget_min'];
    $max = $job['budget_max'];
    $period = ['daily' => ' / day', 'monthly' => ' / month', 'fixed' => ''][$job['pay_period']] ?? '';
    if ($min && $max && (float) $min !== (float) $max) {
        return $cur . ' ' . number_format((float) $min) . ' – ' . number_format((float) $max) . $period;
    }
    if ($min || $max) {
        return money($min ?: $max, $cur) . $period;
    }
    return 'Negotiable';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return date('j M Y', strtotime($datetime));
}

function is_online(?string $lastSeen): bool
{
    return $lastSeen && (time() - strtotime($lastSeen)) < 600;
}

function excerpt(?string $text, int $len = 140): string
{
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $i = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
    return mb_strtoupper($i ?: '?');
}

/** Avatar <img> or initials bubble. */
function avatar(array $user, int $size = 40): string
{
    $name = $user['name'] ?? '';
    if (!empty($user['avatar'])) {
        return '<img src="' . e(url($user['avatar'])) . '" alt="' . e($name) . '" class="rounded-circle object-fit-cover flex-shrink-0" width="' . $size . '" height="' . $size . '">';
    }
    $hue = crc32($name) % 360;
    return '<span class="avatar-initials flex-shrink-0" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size * 0.4) . 'px;background:hsl(' . $hue . ',55%,45%)">' . e(initials($name)) . '</span>';
}

function stars(float $rating, bool $showNumber = true): string
{
    $html = '<span class="stars" title="' . number_format($rating, 1) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) $html .= '<i class="bi bi-star-fill"></i>';
        elseif ($rating >= $i - 0.5) $html .= '<i class="bi bi-star-half"></i>';
        else $html .= '<i class="bi bi-star"></i>';
    }
    if ($showNumber) {
        $html .= ' <span class="text-body-secondary small">' . number_format($rating, 1) . '</span>';
    }
    return $html . '</span>';
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning', 'open' => 'success', 'closed' => 'secondary', 'rejected' => 'danger',
        'in_progress' => 'primary', 'delivered' => 'info', 'completed' => 'success', 'cancelled' => 'secondary',
        'shortlisted' => 'info', 'accepted' => 'success', 'withdrawn' => 'secondary',
        'active' => 'success', 'suspended' => 'danger', 'new' => 'warning', 'reviewed' => 'secondary',
    ];
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e($label) . '</span>';
}

function skills_list(?string $skills): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $skills))));
}

// ---------- Ratings, distance, counts -------------------------------

/** Returns ['avg' => float, 'count' => int] */
function user_rating(int $userId): array
{
    $r = db_one('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE reviewee_id = ?', [$userId]);
    return ['avg' => round((float) ($r['avg_rating'] ?? 0), 1), 'count' => (int) ($r['cnt'] ?? 0)];
}

/** Great-circle distance in km between two points. */
function distance_km($lat1, $lng1, $lat2, $lng2): ?float
{
    if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null
        || $lat1 === '' || $lng1 === '' || $lat2 === '' || $lng2 === '') {
        return null;
    }
    $r = 6371;
    $dLat = deg2rad((float) $lat2 - (float) $lat1);
    $dLng = deg2rad((float) $lng2 - (float) $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function format_km(?float $km): string
{
    if ($km === null) return '';
    return $km < 1 ? round($km * 1000) . ' m away' : number_format($km, $km < 10 ? 1 : 0) . ' km away';
}

function unread_messages(): int
{
    $u = current_user();
    return $u ? (int) db_value('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$u['id']]) : 0;
}

/** Profile completeness 0-100 */
function profile_completion(array $u): int
{
    $fields = ['name', 'phone', 'county', 'town', 'bio', 'avatar'];
    if ($u['role'] === 'worker') {
        $fields = array_merge($fields, ['skills', 'experience_years', 'hourly_rate']);
    } elseif ($u['role'] === 'employer') {
        $fields[] = 'company_name';
    }
    $done = count(array_filter($fields, fn($f) => !empty($u[$f])));
    return (int) round($done / count($fields) * 100);
}

// ---------- Uploads -------------------------------------------------

/**
 * Validate and store an uploaded image. Returns the relative path, null if no
 * file was sent, or throws RuntimeException with a friendly message.
 */
function upload_image(array $file, string $folder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded. Try a smaller file.');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Images must be smaller than ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $info = @getimagesize($file['tmp_name']);
    $mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : ($info['mime'] ?? '');
    if (!isset($allowed[$mime]) || $info === false) {
        throw new RuntimeException('Please upload a JPG, PNG, WEBP or GIF image.');
    }
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the image on the server.');
    }
    return 'uploads/' . $folder . '/' . $name;
}

function delete_upload(?string $path): void
{
    if ($path && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}

// ---------- Pagination ---------------------------------------------

function current_page(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

function pagination(int $total, int $perPage): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) {
        return '';
    }
    $page = current_page();
    $q = $_GET;
    $html = '<nav aria-label="Pages"><ul class="pagination justify-content-center mt-4">';
    for ($p = 1; $p <= $pages; $p++) {
        if ($p !== 1 && $p !== $pages && abs($p - $page) > 2) {
            if (abs($p - $page) === 3) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            continue;
        }
        $q['page'] = $p;
        $html .= '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="?' . e(http_build_query($q)) . '">' . $p . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/** <option> list helper */
function options(array $items, $selected = '', bool $assoc = false): string
{
    $html = '';
    foreach ($items as $k => $v) {
        $value = $assoc ? $k : $v;
        $html .= '<option value="' . e($value) . '"' . ((string) $value === (string) $selected ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return $html;
}

function in_list(string $value, array $list): bool
{
    return in_array($value, $list, true);
}
