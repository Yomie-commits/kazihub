<?php
/**
 * KaziHub configuration
 * ---------------------------------------------------------------
 * Fill in your MySQL details below. On InfinityFree you will find
 * them under: Control Panel -> MySQL Databases.
 */

// --- Database ---------------------------------------------------
// Real passwords never go in this file (it is public on GitHub).
// They live in config/config.local.php, which is created on the server
// by the automatic deploy (or by hand) and is ignored by git.
$local = is_file(__DIR__ . '/config.local.php') ? (array) require __DIR__ . '/config.local.php' : [];
$cfg = fn(string $key, string $default) => $local[$key] ?? (getenv('KAZI_' . $key) ?: $default);

define('DB_DRIVER', $cfg('DB_DRIVER', 'mysql'));
define('DB_HOST',   $cfg('DB_HOST', 'localhost'));   // e.g. sql123.infinityfree.com
define('DB_NAME',   $cfg('DB_NAME', 'kazihub'));     // e.g. if0_12345678_kazihub
define('DB_USER',   $cfg('DB_USER', 'root'));        // e.g. if0_12345678
define('DB_PASS',   $cfg('DB_PASS', ''));            // your vPanel password
define('DB_SQLITE_PATH', $cfg('SQLITE', ''));        // only used for local testing

// --- App --------------------------------------------------------
define('APP_NAME', 'KaziHub');
define('APP_TAGLINE', 'Find work. Find workers. Find success.');

// Leave empty to auto-detect. Set to '/kazihub' if the site lives in a sub-folder,
// or '' if it is at the root of your domain.
define('BASE_URL_OVERRIDE', null);

// Show PHP errors on screen? Keep false on a live site.
define('APP_DEBUG', (bool) $cfg('DEBUG', ''));

// Max upload size for images (bytes)
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);

date_default_timezone_set('Africa/Nairobi');
