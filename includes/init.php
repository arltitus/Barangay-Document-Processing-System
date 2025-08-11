<?php
// Prevent direct access
if (!defined('BDPS_SYSTEM')) {
    define('BDPS_SYSTEM', true);
}

// Error reporting in development
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Start the session
session_start();

// Load core files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';

// Initialize core services
$db = Database::getInstance();
$security = Security::getInstance();
$auth = Auth::getInstance();

// API initialization
if (defined('API_REQUEST')) {
    header('Content-Type: application/json');
    Api::getInstance()->handleRequest();
    exit;
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Simple error logging function
function logError($message) {
    $logFile = LOGS_DIR . 'error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    error_log($logMessage, 3, $logFile);
}
