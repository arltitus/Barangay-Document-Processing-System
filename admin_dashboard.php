<?php
// Define system access
define('BDPS_SYSTEM', true);

// Include configuration
require 'config.php';

// Strict session validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header('Location: login.php?error=unauthorized');
    exit;
}

// Initialize database connection
try {
    $conn = db_connect();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please try again later.");
}
// lists
$users = $conn->query('SELECT * FROM users ORDER BY created_at DESC');
$requests = $conn->query('SELECT r.*, u.full_name, dt.title FROM requests r JOIN users u ON r.user_id=u.id JOIN document_types dt ON r.document_type_id=dt.id ORDER BY r.created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BDPS Admin Dashboard - Manage users and document requests">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin Dashboard - BDPS</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/components/charts.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/brgy-logo.png">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="admin_dashboard.php" class="navbar-brand">BDPS Admin</a>
            <div class="navbar-links">
                <a href="dashboard.php" class="btn btn-primary">Home</a>
                <a href="admin_users.php" class="btn btn-primary">User Management</a>
                <a href="admin_requests.php" class="btn btn-primary">Document Requests</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <?php include 'includes/analytics.php' ?>

    <div class="container">
        <div class="admin-header">
            <h1>Administration Dashboard</h1>
            <p>Manage users and document requests</p>
        </div>

        <div class="admin-stats">
            <?php
            try {
                // Prepare and execute stats queries
                $statsQueries = [
                    'users' => "SELECT COUNT(*) as count FROM users",
                    'pending' => "SELECT COUNT(*) as count FROM requests WHERE status='pending'",
                    'completed' => "SELECT COUNT(*) as count FROM requests WHERE status='approved'"
                ];
                
                $stats = [];
                foreach ($statsQueries as $key => $query) {
                    $result = $conn->query($query);
                    if ($result === false) {
                        throw new Exception("Query failed: " . $conn->error);
                    }
                    $stats[$key] = $result->fetch_assoc()['count'];
                }
            } catch (Exception $e) {
                error_log("Stats query error: " . $e->getMessage());
                $stats = ['users' => 0, 'pending' => 0, 'completed' => 0];
            }
            ?>
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Requests</div>
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Completed Requests</div>
                <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>Request Status Distribution</h3>
                <canvas id="requestChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>User Registration Trend</h3>
                <canvas id="userTrendChart"></canvas>
            </div>
        </div>

        <?php
        // Get request status distribution
        $requestStats = $conn->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status");
        $requestLabels = [];
        $requestData = [];
        while ($row = $requestStats->fetch_assoc()) {
            $requestLabels[] = ucfirst($row['status']);
            $requestData[] = $row['count'];
        }

        // Get user registration trend (last 6 months)
        $userTrend = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        $trendLabels = [];
        $trendData = [];
        while ($row = $userTrend->fetch_assoc()) {
            $trendLabels[] = date('M Y', strtotime($row['month'] . '-01'));
            $trendData[] = $row['count'];
        }
        ?>

        <script>
        // Chart color scheme
        const chartColors = {
            primary: '#007bff',
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8',
            light: '#f8f9fa'
        };

        // Store the chart data in variables with error handling
        const requestChartData = {
            labels: <?= json_encode($requestLabels ?: [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [{
                data: <?= json_encode($requestData ?: [], JSON_NUMERIC_CHECK) ?>,
                backgroundColor: [chartColors.warning, chartColors.success, chartColors.danger],
                borderWidth: 1,
                borderColor: chartColors.light
            }]
        };

        const userTrendChartData = {
            labels: <?= json_encode($trendLabels ?: [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [{
                label: 'New Users',
                data: <?= json_encode($trendData ?: [], JSON_NUMERIC_CHECK) ?>,
                borderColor: chartColors.primary,
                backgroundColor: `${chartColors.primary}33`,
                tension: 0.1,
                fill: true
            }]
        };

        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Request Status Chart
            const requestCtx = document.getElementById('requestChart');
            if (requestCtx) {
                new Chart(requestCtx, {
                    type: 'pie',
                    data: requestChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            title: {
                                display: false
                            }
                        }
                    }
                });
            }

            // Initialize User Registration Trend Chart
            const userTrendCtx = document.getElementById('userTrendChart');
            if (userTrendCtx) {
                new Chart(userTrendCtx, {
                    type: 'line',
                    data: userTrendChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            title: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>

</body></html>
