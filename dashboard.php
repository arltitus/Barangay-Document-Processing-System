<?php
require 'config.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$conn = db_connect();
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
if($role === 'admin'){
    header('Location: admin_dashboard.php'); exit;
}
// load user and requests
$stmt = $conn->prepare('SELECT * FROM users WHERE id=?'); $stmt->bind_param('i',$uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$reqs = $conn->query('SELECT r.*, dt.title FROM requests r JOIN document_types dt ON r.document_type_id=dt.id WHERE r.user_id=' . intval($uid) . ' ORDER BY r.created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - BDPS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/pages/dashboard.css">
    <link rel="stylesheet" href="css/modern-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="dashboard.php" class="navbar-brand">BDPS</a>
            <div class="navbar-links">
                <span>Welcome, <?=htmlspecialchars($user['full_name'])?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-welcome">Hello, <?=htmlspecialchars($user['full_name'])?></h1>
            <p class="text-secondary">Manage your document requests and track their status</p>
        </div>
        <?php if(!$user['is_verified']): ?>
            <div class="alert alert-warning">
                <strong>Account Status:</strong> Pending Verification - You must be verified by admin before requests are approved. You can still submit requests.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>Account Status:</strong> Verified
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Request a Document</h3>
            </div>
            <form method="post" action="request_document.php" class="request-form">
                <div class="grid">
                    <div class="form-group">
                        <label class="form-label">Document Type</label>
                        <select name="document_type_id" class="form-control">
                            <?php $dts = $conn->query('SELECT * FROM document_types'); while($dt = $dts->fetch_assoc()): ?>
                            <option value="<?=$dt['id']?>"><?=htmlspecialchars($dt['title'])?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purpose/Notes</label>
                        <input name="purpose" class="form-control" required 
                               placeholder="State the purpose of your request">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Appointment Date</label>
                        <input name="appointment_date" type="date" class="form-control" 
                               required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Appointment Time</label>
                        <input name="appointment_time" type="time" class="form-control" 
                               required min="08:00" max="17:00">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-submit">
                        Submit Document Request
                    </button>
                </div>
            </form>
        </div>

        <div class="card requests-table">
            <div class="card-header">
                <h3 class="card-title">Your Document Requests</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Document Type</th>
                            <th>Appointment Schedule</th>
                            <th>Current Status</th>
                            <th>Document Copy</th>
                        </tr>
                    </thead>
                    <tbody>
<?php while($r = $reqs->fetch_assoc()): ?>
                        <tr>
                            <td>#<?=$r['id']?></td>
                            <td>
                                <strong><?=htmlspecialchars($r['title'])?></strong>
                            </td>
                            <td>
                                <div style="color: var(--text-primary);">
                                    <?=date('F j, Y', strtotime($r['appointment_date']))?>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                    <?=date('g:i A', strtotime($r['appointment_time']))?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo match($r['status']) {
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        default => ''
                                    }
                                ?>">
                                    <?=ucfirst($r['status'])?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['softcopy_filename']): ?>
                                    <a href="download.php?id=<?=$r['id']?>" 
                                       class="btn-download">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" color="black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Not yet available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
