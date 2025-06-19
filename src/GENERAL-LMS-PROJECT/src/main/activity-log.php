<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Database connection
$host = 'localhost';
$db   = 'gplms_general';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$targetFilter = isset($_GET['target']) ? $_GET['target'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build base query
$baseQuery = "SELECT * FROM activity_logs";
$whereClauses = [];
$params = [];

// Add search filter
if (!empty($search)) {
    $whereClauses[] = "(username LIKE :search OR action LIKE :search OR target_object LIKE :search OR details LIKE :search OR ip_address LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add action filter
if (!empty($actionFilter)) {
    $whereClauses[] = "action = :action";
    $params[':action'] = $actionFilter;
}

// Add target filter
if (!empty($targetFilter)) {
    $whereClauses[] = "target_object = :target";
    $params[':target'] = $targetFilter;
}

// Add date range filter
if (!empty($dateFrom)) {
    $whereClauses[] = "timestamp >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if (!empty($dateTo)) {
    $whereClauses[] = "timestamp <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59'; // Include entire day
}

// Build WHERE clause
$where = '';
if (!empty($whereClauses)) {
    $where = " WHERE " . implode(" AND ", $whereClauses);
}

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Get total number of logs
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $where");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Get logs for current page
$query = $pdo->prepare("SELECT * FROM activity_logs $where ORDER BY timestamp DESC LIMIT :offset, :perPage");

// Bind parameters for WHERE clause
foreach ($params as $key => $value) {
    $query->bindValue($key, $value);
}

// Bind pagination parameters separately
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$query->execute();
$logs = $query->fetchAll();

// Get distinct actions for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Get distinct targets for filter dropdown
$targets = $pdo->query("SELECT DISTINCT target_object FROM activity_logs WHERE target_object IS NOT NULL AND target_object != '' ORDER BY target_object")->fetchAll(PDO::FETCH_COLUMN);

// Get data for charts
// Action distribution
$actionData = $pdo->query("SELECT action, COUNT(*) as count FROM activity_logs GROUP BY action ORDER BY count DESC")->fetchAll();

// Last 7 days activity
$dailyActivity = $pdo->query("SELECT DATE(timestamp) as date, COUNT(*) as count 
                              FROM activity_logs 
                              WHERE timestamp >= CURDATE() - INTERVAL 7 DAY
                              GROUP BY DATE(timestamp) 
                              ORDER BY date")->fetchAll();

// Top users by activity
$topUsers = $pdo->query("SELECT username, COUNT(*) as count 
                         FROM activity_logs 
                         GROUP BY username 
                         ORDER BY count DESC 
                         LIMIT 5")->fetchAll();

// Function to format action for display
function formatAction($action) {
    $icons = [
        'CREATE' => 'fas fa-plus-circle text-success',
        'UPDATE' => 'fas fa-edit text-primary',
        'DELETE' => 'fas fa-trash-alt text-danger',
        'LOGIN' => 'fas fa-sign-in-alt text-info',
        'LOGOUT' => 'fas fa-sign-out-alt text-warning',
        'ACCESS' => 'fas fa-unlock text-secondary',
        'ERROR' => 'fas fa-exclamation-circle text-danger',
        'REGISTER' => 'fas fa-user-plus text-info',
        'SETTINGS' => 'fas fa-cog text-primary',
        'BACKUP' => 'fas fa-save text-warning'
    ];
    
    $icon = $icons[$action] ?? 'fas fa-circle';
    return "<i class='$icon'></i> $action";
}

// Function to format target object
function formatTarget($target) {
    if (!$target) return '';
    
    $icons = [
        'user' => 'fas fa-user',
        'system_settings' => 'fas fa-cog',
        'library_item' => 'fas fa-book',
        'role' => 'fas fa-user-tag',
        'category' => 'fas fa-tag',
        'publisher' => 'fas fa-building',
        'author' => 'fas fa-feather',
        'material' => 'fas fa-book-open',
        'activity_log' => 'fas fa-history'
    ];
    
    $parts = explode('_', $target);
    $base = $parts[0] ?? '';
    $icon = $icons[$base] ?? $icons[$target] ?? 'fas fa-file';
    
    return "<i class='$icon'></i> " . ucwords(str_replace('_', ' ', $target));
}

// Function to format IP address
function formatIP($ip) {
    return "<span class='font-monospace'>$ip</span>";
}

// Function to format timestamp
function formatTimestamp($timestamp) {
    $date = date('M d, Y', strtotime($timestamp));
    $time = date('H:i', strtotime($timestamp));
    return "<div>$date</div><div class='small text-muted'>$time</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 0;
        }
        
        #sidebar ul.components {
            padding: 20px 0;
        }
        
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 0.9rem;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        #sidebar ul li a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #fff;
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Topbar Styles */
        .topbar {
            height: 70px;
            background: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .btn-toggle {
            background: transparent;
            border: none;
            color: var(--dark-text);
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .topbar h4 {
            margin: 0;
            font-weight: 700;
            color: var(--dark-text);
            flex-grow: 1;
        }
        
        /* Main Content */
        #content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 70px);
            padding-top: 90px;
        }
        
        /* Admin Cards */
        .admin-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header span {
            font-weight: 700;
            color: var(--dark-text);
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Activity Log Table */
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .log-table th {
            background-color: #f8f9fc;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-text);
            border-bottom: 2px solid #e3e6f0;
        }
        
        .log-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e3e6f0;
            vertical-align: top;
        }
        
        .log-table tr:hover {
            background-color: #f8f9fc;
        }
        
        .log-id {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .log-action {
            font-weight: 500;
        }
        
        .log-details {
            max-width: 300px;
            word-wrap: break-word;
            font-size: 0.9rem;
        }
        
        .log-timestamp {
            font-size: 0.9rem;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .page-item {
            margin: 0 5px;
        }
        
        .page-link {
            color: var(--primary-color);
            border: 1px solid #d1d3e2;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
        }
        
        /* Status indicators */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .status-active {
            background: #e0f7e9;
            color: #13855c;
        }
        
        .status-inactive {
            background: #fdecea;
            color: #e74a3b;
        }
        
        /* Filters */
        .filter-container {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-text);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                margin-left: 0;
            }
            
            .log-table {
                display: block;
                overflow-x: auto;
            }
            
            .filter-container .row > div {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-book me-2"></i> LibrarySystem</h3>
            <p class="mb-0">Admin Control Panel</p>
        </div>
        
        <ul class="list-unstyled components">
            <li>
                <a href="control_panel.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users-manager.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="roles-manager.php" class="nav-link">
                    <i class="fas fa-user-tag"></i> Roles
                </a>
            </li>
            <li>
                <a href="materials-manager.php" class="nav-link">
                    <i class="fas fa-book"></i> Materials
                </a>
            </li>
            <li>
                <a href="categories-manager.php" class="nav-link">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="publishers-manager.php" class="nav-link">
                    <i class="fas fa-building"></i> Publishers
                </a>
            </li>
            <li>
                <a href="authors-manager.php" class="nav-link">
                    <i class="fas fa-feather"></i> Authors
                </a>
            </li>
            <li>
                <a href="settings-manager.php" class="nav-link">
                    <i class="fas fa-cog"></i> System Settings
                </a>
            </li>
            <li>
                <a href="activity-log.php" class="nav-link active">
                    <i class="fas fa-history"></i> Activity Log
                </a>
            </li>
            <li>
                <a href="search.php" class="nav-link">
                    <i class="fas fa-arrow-left"></i> Back to Library
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Activity Log</h4>
            <div>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <span>System Activity Log</span>
                <div>
                    <span class="status-badge status-active">Total Records: <?= $totalLogs ?></span>
                </div>
            </div>
            
            <div class="filter-container">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="page" value="1">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?= $action ?>" <?= $actionFilter === $action ? 'selected' : '' ?>>
                                        <?= $action ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="target" class="form-select">
                                <option value="">All Targets</option>
                                <?php foreach ($targets as $target): ?>
                                    <option value="<?= $target ?>" <?= $targetFilter === $target ? 'selected' : '' ?>>
                                        <?= ucwords(str_replace('_', ' ', $target)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Target Object</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="log-id">#<?= $log['log_id'] ?></td>
                                        <td class="log-timestamp"><?= formatTimestamp($log['timestamp']) ?></td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($log['username']) ?></div>
                                            <div class="small text-muted">ID: <?= $log['user_id'] ?></div>
                                        </td>
                                        <td class="log-action"><?= formatAction($log['action']) ?></td>
                                        <td><?= formatTarget($log['target_object']) ?></td>
                                        <td class="log-details"><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= formatIP($log['ip_address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <h5>No activity logs found</h5>
                                        <p class="text-muted">Try adjusting your filters or search term</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-container">
                        <ul class="pagination">
                            <!-- Previous button -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page - 1]
                                    )) 
                                ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => 1])) . 
                                        '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $active = $i == $page ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => $i])) . 
                                        '">' . $i . '</a></li>';
                                }
                                
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => $totalPages])) . 
                                        '">' . $totalPages . '</a></li>';
                                }
                            ?>
                            
                            <!-- Next button -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page + 1]
                                    )) 
                                ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Activity Analysis</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Action Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="actionChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Last 7 Days Activity</h5>
                                <div class="chart-container">
                                    <canvas id="dailyActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Top Active Users</span>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if (count($topUsers) > 0): ?>
                                <?php foreach ($topUsers as $index => $user): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($user['username']) ?></div>
                                            <div class="small text-muted"><?= $user['count'] ?> actions</div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">#<?= $index + 1 ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">No user activity data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Add hover effect to table rows
        const rows = document.querySelectorAll('.log-table tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = '#f8f9fc';
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
            });
        });
        
        // Action Distribution Chart
        const actionCtx = document.getElementById('actionChart').getContext('2d');
        const actionChart = new Chart(actionCtx, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', array_map(function($a) { return "'" . $a['action'] . "'"; }, $actionData)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_map(function($a) { return $a['count']; }, $actionData)) ?>],
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#5a5c69', '#858796', '#3a3b45', '#f8f9fc', '#e3e6f0'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' actions';
                            }
                        }
                    }
                }
            }
        });
        
        // Daily Activity Chart
        const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($d) { 
                    $date = new DateTime($d['date']);
                    return "'" . $date->format('M d') . "'"; 
                }, $dailyActivity)) ?>],
                datasets: [{
                    label: 'Actions per day',
                    data: [<?= implode(',', array_map(function($d) { return $d['count']; }, $dailyActivity)) ?>],
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Reset filters
        document.querySelector('.filter-form').addEventListener('reset', function() {
            // Reset page to 1
            document.querySelector('input[name="page"]').value = 1;
        });
    </script>
</body>
</html>