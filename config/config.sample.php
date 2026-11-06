<?php
/**
 * KaziHub configuration.
 * Copy this file to config.php and fill in your database details.
 *
 * InfinityFree: find these under Control Panel > MySQL Databases.
 *   DB_HOST looks like  sql123.infinityfree.com
 *   DB_NAME looks like  if0_12345678_kazihub
 *   DB_USER looks like  if0_12345678
 *   DB_PASS is your vPanel / hosting account password
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'kazihub');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'KaziHub');
define('APP_TIMEZONE', 'Africa/Nairobi');

// Set to false on the live site so visitors never see error details.
define('APP_DEBUG', true);
