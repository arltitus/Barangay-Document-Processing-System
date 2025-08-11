<?php
require 'config.php';
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){ header('Location: login.php'); exit; }
$conn = db_connect();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    if($action === 'verify_user'){
        $uid = intval($_POST['user_id']);
        $conn->query('UPDATE users SET is_verified=1 WHERE id=' . $uid);
        header('Location: admin_dashboard.php'); exit;
    }
    // request actions
    $rid = intval($_POST['request_id'] ?? 0);
    if($rid){
        if(isset($_FILES['softcopy']) && $_FILES['softcopy']['error'] === 0){
            $allowed = ['application/pdf','image/png','image/jpeg'];
            if(in_array($_FILES['softcopy']['type'],$allowed)){
                $target = UPLOAD_DIR . time() . '_soft_' . basename($_FILES['softcopy']['name']);
                move_uploaded_file($_FILES['softcopy']['tmp_name'], $target);
                $softfn = basename($target);
                $conn->query("UPDATE requests SET softcopy_filename='" . $conn->real_escape_string($softfn) . "' WHERE id=".$rid);
            }
        }
        if($action === 'approve'){
            $conn->query("UPDATE requests SET status='approved' WHERE id=".$rid);
        } elseif($action === 'cancel'){
            $conn->query("UPDATE requests SET status='cancelled' WHERE id=".$rid);
        } elseif($action === 'complete'){
            $conn->query("UPDATE requests SET status='completed' WHERE id=".$rid);
        }
    }
}
header('Location: admin_dashboard.php');
