<?php
// Prevent direct access
defined('BDPS_SYSTEM') or die('Direct access not permitted');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_db');

// Session configuration
define('SESSION_NAME', 'bdps_session');
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');

// Basic security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// Storage and logs configuration
define('STORAGE_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR);
define('LOGS_DIR', STORAGE_ROOT . 'logs' . DIRECTORY_SEPARATOR);
define('UPLOADS_DIR', STORAGE_ROOT . 'uploads' . DIRECTORY_SEPARATOR);

// Create required directories
$directories = [STORAGE_ROOT, LOGS_DIR, UPLOADS_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
