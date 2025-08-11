<?php
require 'config.php';
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){ header('Location: login.php'); exit; }
$conn = db_connect();

// Get users list
$users = $conn->query('SELECT * FROM users ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BDPS Admin</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="admin_dashboard.php" class="navbar-brand">BDPS Admin</a>
            <div class="navbar-links">
                <a href="admin_dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="admin_requests.php" class="btn btn-primary">Document Requests</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <h1>User Management</h1>
            <p>Manage and verify user accounts</p>
        </div>

        <div class="card user-management">
            <div class="card-header">
                <div class="header-content">
                    <h3 class="card-title">Users List</h3>
                    <button class="btn btn-primary" onclick="exportUsersList()">Export Users</button>
                </div>
            </div>
            
            <div class="user-filters">
                <div class="filter-group">
                    <input type="text" class="filter-input" id="userSearch" placeholder="Search users...">
                </div>
                <div class="filter-group">
                    <select class="filter-input" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="verified">Verified</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>ID File</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?=$u['id']?></td>
                            <td><?=htmlspecialchars($u['full_name'])?></td>
                            <td><?=htmlspecialchars($u['email'])?></td>
                            <td><?=htmlspecialchars($u['address'])?></td>
                            <td>
                                <?php if($u['id_filename']): ?>
                                    <a href="uploads/<?=htmlspecialchars($u['id_filename'])?>" class="btn btn-primary" target="_blank">View ID</a>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?=$u['is_verified'] ? 'badge-success' : 'badge-warning'?>">
                                    <?=$u['is_verified'] ? 'Verified' : 'Pending'?>
                                </span>
                            </td>
                            <td>
                                <?php if(!$u['is_verified']): ?>
                                    <form method="post" action="admin_actions.php" style="display:inline">
                                        <input type="hidden" name="action" value="verify_user">
                                        <input type="hidden" name="user_id" value="<?=$u['id']?>">
                                        <button type="submit" class="btn btn-success">Verify User</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // User search functionality
    document.getElementById('userSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        
        rows.forEach(row => {
            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Status filter functionality
    document.getElementById('statusFilter').addEventListener('change', function(e) {
        const filterValue = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        
        rows.forEach(row => {
            const status = row.querySelector('.badge').textContent.toLowerCase();
            
            if (!filterValue || status.includes(filterValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Export users list
    function exportUsersList() {
        const table = document.getElementById('usersTable');
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');
        
        let csv = 'ID,Name,Email,Address,Status\n';
        rows.forEach(row => {
            const id = row.cells[0].textContent;
            const name = row.cells[1].textContent;
            const email = row.cells[2].textContent;
            const address = row.cells[3].textContent;
            const status = row.querySelector('.badge').textContent;
            
            csv += `"${id}","${name}","${email}","${address}","${status}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'users_list.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    </script>
</body>
</html>
