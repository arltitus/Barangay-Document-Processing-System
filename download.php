<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
// Validate request ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$request_id = intval($_GET['id']);

$conn = db_connect();

// Fetch the softcopy filename and user_id from the request to verify ownership
$stmt = $conn->prepare('SELECT softcopy_filename, user_id FROM requests WHERE id = ?');
$stmt->bind_param('i', $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Request not found');
}

$request = $result->fetch_assoc();

// Check if current user owns the request
if ($request['user_id'] != $uid) {
    http_response_code(403);
    exit('Access denied');
}

// Check if file exists
$file = __DIR__ . '/uploads/' . $request['softcopy_filename'];
if (!file_exists($file)) {
    http_response_code(404);
    exit('File not found');
}

// Send headers to force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
flush();
readfile($file);
exit;
