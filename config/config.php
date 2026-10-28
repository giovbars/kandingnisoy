<?php
// ------------------------------------------------------------
// Session (secure cookie flags; "secure" turns on automatically on HTTPS)
// ------------------------------------------------------------
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $__https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../includes/env-loader.php';
loadEnv(__DIR__ . '/../.env');

date_default_timezone_set('Asia/Manila');

// ------------------------------------------------------------
// Error display: OFF in production. Set APP_DEBUG=true in .env
// only while troubleshooting, then set it back to false.
// ------------------------------------------------------------
$__debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
if ($__debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Email configuration (for verification) - set MAIL_FROM in .env
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@yourdomain.com');

// Your site URL, no trailing slash. Example: https://yourdomain.com
define('BASE_URL', rtrim(getenv('BASE_URL') ?: 'https://goatpro.ics-dev.io/', '/'));

// Database - set these in .env (Hostinger gives you the values in hPanel)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u442411629_goatpro_db');
define('DB_USER', getenv('DB_USER') ?: 'u442411629_dev_goatpro');
define('DB_PASS', getenv('DB_PASS') ?: 'V8i4?32IJoB&');

// ============================================================
// TEXTBEE SMS GATEWAY CONFIGURATION
// ============================================================
define('TEXTBEE_API_KEY', getenv('TEXTBEE_API_KEY') ?: '');
define('TEXTBEE_DEVICE_ID', getenv('TEXTBEE_DEVICE_ID') ?: '');
define('OPENWEATHER_API_KEY', getenv('OPENWEATHER_API_KEY') ?: '');
define('TEXTBEE_API_URL', 'https://api.textbee.dev/api/v1');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die($__debug ? 'Connection failed: ' . $e->getMessage()
                 : 'Database connection failed. Please contact the administrator.');
}

// Add any tables/columns the app needs that the imported SQL doesn't have
require_once __DIR__ . '/../includes/schema-sync.php';
gfSyncSchema($pdo);

?>