<?php
require 'config.php';
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){ header('Location: login.php'); exit; }
$conn = db_connect();

// Get requests list with user details
$requests = $conn->query('SELECT r.*, u.full_name, dt.title FROM requests r 
                         JOIN users u ON r.user_id=u.id 
                         JOIN document_types dt ON r.document_type_id=dt.id 
                         ORDER BY r.created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Requests - BDPS Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="admin_dashboard.php" class="navbar-brand">BDPS Admin</a>
            <div class="navbar-links">
                <a href="admin_dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="admin_users.php" class="btn btn-primary">Users</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <h1>Document Requests</h1>
            <p>Manage and process document requests</p>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="header-content">
                    <h3 class="card-title">All Requests</h3>
                    <button class="btn btn-primary" onclick="exportRequests()">Export Requests</button>
                </div>
            </div>

            <div class="user-filters">
                <div class="filter-group">
                    <input type="text" class="filter-input" id="requestSearch" placeholder="Search by name or document...">
                </div>
                <div class="filter-group">
                    <select class="filter-input" id="requestStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <input type="date" class="filter-input" id="dateFilter" placeholder="Filter by date">
                </div>
            </div>

            <div class="table-container">
                <table class="table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Document</th>
                            <th>Appointment</th>
                            <th>Status</th>
                            <th>Soft Copy</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = $requests->fetch_assoc()): ?>
                        <tr>
                            <td>#<?=$r['id']?></td>
                            <td>
                                <div style="font-weight: 500;"><?=htmlspecialchars($r['full_name'])?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?=htmlspecialchars($r['title'])?></div>
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
                                    <a href="uploads/<?=htmlspecialchars($r['softcopy_filename'])?>" 
                                       class="btn btn-primary" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                        Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Not uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="admin_actions.php" enctype="multipart/form-data" class="request-action-form">
                                    <input type="hidden" name="request_id" value="<?=$r['id']?>">
                                    <select name="action" class="form-control">
                                        <option value="">Select Action...</option>
                                        <option value="approve">Approve Request</option>
                                        <option value="reject">Reject Request</option>
                                        <option value="complete">Mark as Completed</option>
                                    </select>
                                    <div class="upload-section">
                                        <input type="file" name="softcopy" class="file-input" accept=".pdf,.doc,.docx">
                                        <small class="text-secondary">Upload document (PDF, DOC, DOCX)</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Request search functionality
    document.getElementById('requestSearch').addEventListener('input', function(e) {
        filterRequests();
    });

    // Status filter functionality
    document.getElementById('requestStatusFilter').addEventListener('change', function(e) {
        filterRequests();
    });

    // Date filter functionality
    document.getElementById('dateFilter').addEventListener('change', function(e) {
        filterRequests();
    });

    function filterRequests() {
        const searchTerm = document.getElementById('requestSearch').value.toLowerCase();
        const statusFilter = document.getElementById('requestStatusFilter').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilter').value;
        const rows = document.querySelectorAll('#requestsTable tbody tr');
        
        rows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const document = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const status = row.querySelector('.badge').textContent.toLowerCase();
            const date = row.querySelector('td:nth-child(4)').textContent;
            
            const matchesSearch = name.includes(searchTerm) || document.includes(searchTerm);
            const matchesStatus = !statusFilter || status.includes(statusFilter);
            const matchesDate = !dateFilter || date.includes(dateFilter);
            
            if (matchesSearch && matchesStatus && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Export requests
    function exportRequests() {
        const table = document.getElementById('requestsTable');
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');
        
        let csv = 'ID,User,Document,Appointment Date,Appointment Time,Status\n';
        rows.forEach(row => {
            const id = row.cells[0].textContent;
            const user = row.cells[1].textContent.trim();
            const document = row.cells[2].textContent.trim();
            const date = row.cells[3].querySelector('div:first-child').textContent.trim();
            const time = row.cells[3].querySelector('div:last-child').textContent.trim();
            const status = row.querySelector('.badge').textContent;
            
            csv += `"${id}","${user}","${document}","${date}","${time}","${status}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'document_requests.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    </script>
</body>
</html>
