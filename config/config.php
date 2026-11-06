<?php
/**
 * KaziHub configuration
 * ---------------------------------------------------------------
 * Fill in your MySQL details below. On InfinityFree you will find
 * them under: Control Panel -> MySQL Databases.
 */

// --- Database ---------------------------------------------------
define('DB_DRIVER', getenv('KAZI_DB_DRIVER') ?: 'mysql');   // 'mysql' (leave as is)
define('DB_HOST',   getenv('KAZI_DB_HOST') ?: 'localhost');  // e.g. sql123.infinityfree.com
define('DB_NAME',   getenv('KAZI_DB_NAME') ?: 'kazihub');    // e.g. if0_12345678_kazihub
define('DB_USER',   getenv('KAZI_DB_USER') ?: 'root');       // e.g. if0_12345678
define('DB_PASS',   getenv('KAZI_DB_PASS') ?: '');           // your vPanel password
define('DB_SQLITE_PATH', getenv('KAZI_SQLITE') ?: '');       // only used for local testing

// --- App --------------------------------------------------------
define('APP_NAME', 'KaziHub');
define('APP_TAGLINE', 'Find work. Find workers. Find success.');

// Leave empty to auto-detect. Set to '/kazihub' if the site lives in a sub-folder,
// or '' if it is at the root of your domain.
define('BASE_URL_OVERRIDE', null);

// Show PHP errors on screen? Keep false on a live site.
define('APP_DEBUG', (bool) getenv('KAZI_DEBUG'));

// Max upload size for images (bytes)
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);

date_default_timezone_set('Africa/Nairobi');
