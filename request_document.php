<?php
require 'config.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$conn = db_connect();
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $uid = $_SESSION['user_id'];
    $doc = intval($_POST['document_type_id']);
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');
    $adate = $_POST['appointment_date'] ?: null;
    $atime = $_POST['appointment_time'] ?: null;
    $stmt = $conn->prepare('INSERT INTO requests (user_id,document_type_id,purpose,appointment_date,appointment_time) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iisss',$uid,$doc,$purpose,$adate,$atime);
    if($stmt->execute()){
        header('Location: dashboard.php'); exit;
    } else {
        die('Error: ' . $conn->error);
    }
}
header('Location: dashboard.php');
