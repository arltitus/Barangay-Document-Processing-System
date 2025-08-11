<?php
// Edit these settings to match your environment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_db');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
if(!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

function db_connect(){
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);
    $conn->set_charset('utf8mb4');
    return $conn;
}
session_start();
?>