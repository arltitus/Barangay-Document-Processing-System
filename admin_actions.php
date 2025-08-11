<?php
require 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_softcopy') {
        $rid = intval($_POST['request_id'] ?? 0);
        if ($rid) {
            $res = $conn->query("SELECT softcopy_filename FROM requests WHERE id=$rid");
            if ($res) {
                $row = $res->fetch_assoc();
                if ($row && !empty($row['softcopy_filename'])) {
                    $file = UPLOAD_DIR . $row['softcopy_filename'];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    $conn->query("UPDATE requests SET softcopy_filename='' WHERE id=$rid");
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
        exit;
    }

    if ($action === 'verify_user') {
        $uid = intval($_POST['user_id']);
        $conn->query('UPDATE users SET is_verified=1 WHERE id=' . $uid);
        header('Location: admin_dashboard.php');
        exit;
    }

    // request actions
    $rid = intval($_POST['request_id'] ?? 0);
    if ($rid) {
        // Sanitize and prepare admin notes
        $admin_notes = $conn->real_escape_string(trim($_POST['admin_notes'] ?? ''));

        // Handle softcopy upload
        if (isset($_FILES['softcopy']) && $_FILES['softcopy']['error'] === 0) {
            $allowed = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            if (in_array($_FILES['softcopy']['type'], $allowed)) {
                $target = UPLOAD_DIR . time() . '_soft_' . basename($_FILES['softcopy']['name']);
                if (move_uploaded_file($_FILES['softcopy']['tmp_name'], $target)) {
                    $softfn = basename($target);
                    $conn->query("UPDATE requests SET softcopy_filename='" . $conn->real_escape_string($softfn) . "' WHERE id=" . $rid);
                }
            }
        }

        // Update status and admin notes together
        $status_map = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'cancel' => 'cancelled',
            'complete' => 'completed'
        ];
        if (array_key_exists($action, $status_map)) {
            $new_status = $status_map[$action];
            $sql = "UPDATE requests SET status='$new_status', admin_notes='$admin_notes' WHERE id=$rid";
            $conn->query($sql);
        }
    }

    header('Location: admin_dashboard.php');
    exit;
}

header('Location: admin_dashboard.php');
exit;
