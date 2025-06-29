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

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';

// Add status column to roles table if it doesn't exist
try {
    $pdo->query("SELECT status FROM roles LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE roles ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER role_name");
}

// Add description column to roles table if it doesn't exist
try {
    $pdo->query("SELECT description FROM roles LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE roles ADD COLUMN description TEXT AFTER role_name");
}

// Add created_at column to roles table if it doesn't exist
try {
    $pdo->query("SELECT created_at FROM roles LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE roles ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
}

// Function to log activity
function logActivity($pdo, $user_id, $action, $target_object = null, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $username = $_SESSION['username'] ?? 'System';
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, target_object, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $action, $target_object, $details, $ip_address]);
}

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Handle role actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            if ($action_type === 'add_role') {
                $stmt = $pdo->prepare("INSERT INTO roles (role_name, description, status) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['role_name'],
                    $_POST['description'],
                    $_POST['status']
                ]);
                $success_msg = "Role added successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'roles', 'Added new role: '.$_POST['role_name']);
            }
            elseif ($action_type === 'update_role') {
                $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, description = ?, status = ? 
                                      WHERE role_id = ?");
                $stmt->execute([
                    $_POST['role_name'],
                    $_POST['description'],
                    $_POST['status'],
                    $_POST['role_id']
                ]);
                $success_msg = "Role updated successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Updated role: '.$_POST['role_name']);
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle role status change
if (isset($_GET['status_change']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['status_change'] === 'activate' ? 'active' : 'inactive';
    
    try {
        $stmt = $pdo->prepare("UPDATE roles SET status = ? WHERE role_id = ?");
        $stmt->execute([$new_status, $id]);
        
        $success_msg = "Role status updated successfully!";
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Changed status for role ID: '.$id);
    } catch (Exception $e) {
        $error_msg = "Error updating status: " . $e->getMessage();
    }
}

// Handle delete role
if (isset($_GET['delete']) && $_GET['delete'] === 'role' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Check if any users are assigned to this role
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $checkStmt->execute([$id]);
        $userCount = $checkStmt->fetchColumn();
        
        if ($userCount > 0) {
            $error_msg = "Cannot delete role: $userCount user(s) are assigned to this role.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
            $stmt->execute([$id]);
            $success_msg = "Role deleted successfully!";
            logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'roles', 'Deleted role ID: '.$id);
        }
    } catch (Exception $e) {
        $error_msg = "Error deleting role: " . $e->getMessage();
    }
}

// Get roles with user counts
$roles = $pdo->query("SELECT r.*, 
                     (SELECT COUNT(*) FROM users u WHERE u.role_id = r.role_id) AS user_count
                     FROM roles r ORDER BY role_id")->fetchAll();

// Get role for editing
$edit_role = null;
if (isset($_GET['edit_role'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->execute([$_GET['edit_role']]);
    $edit_role = $stmt->fetch();
}

// Get data for statistics and charts
// Role distribution
$roleDistribution = $pdo->query("SELECT r.role_name, COUNT(u.user_id) AS user_count 
                                FROM roles r 
                                LEFT JOIN users u ON r.role_id = u.role_id 
                                GROUP BY r.role_id")->fetchAll();

// Role activity (last 30 days)
$roleActivity = $pdo->query("SELECT r.role_name, COUNT(a.log_id) AS activity_count 
                            FROM roles r 
                            LEFT JOIN users u ON r.role_id = u.role_id 
                            LEFT JOIN activity_logs a ON u.user_id = a.user_id 
                            WHERE a.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                            GROUP BY r.role_id")->fetchAll();

// Role usage statistics
$roleStats = [
    'total_roles' => $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn(),
    'active_roles' => $pdo->query("SELECT COUNT(*) FROM roles WHERE status = 'active'")->fetchColumn(),
    'inactive_roles' => $pdo->query("SELECT COUNT(*) FROM roles WHERE status = 'inactive'")->fetchColumn(),
    'role_with_most_users' => $pdo->query("SELECT role_name FROM roles ORDER BY (SELECT COUNT(*) FROM users u WHERE u.role_id = roles.role_id) DESC LIMIT 1")->fetchColumn(),
    'role_with_most_activity' => $pdo->query("SELECT role_name FROM roles ORDER BY (SELECT COUNT(*) FROM activity_logs a JOIN users u ON a.user_id = u.user_id WHERE u.role_id = roles.role_id) DESC LIMIT 1")->fetchColumn()
];

// Role growth over time
$roleGrowth = $pdo->query("
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM roles 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - GPLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link href="../styles/roles-manager.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>GPLMS</h3>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="#">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="#" class="active">
                    <i class="fas fa-user-tag"></i>
                    <span>Role Management</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-book"></i>
                    <span>Library Catalog</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-history"></i>
                    <span>Activity Log</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

<?php include '../components/roles-manager-main.php'; ?>
<?php include '../components/role-modal.php'; ?>


    <!-- Bootstrap & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-show modal if editing role
        <?php if ($edit_role): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('roleModal'));
                modal.show();
            });
        <?php endif; ?>
        
        // Toggle sidebar on mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Role Distribution Chart
            const roleCtx = document.getElementById('roleDistributionChart')?.getContext('2d');
            if (roleCtx) {
                const roleData = {
                    labels: [<?php foreach ($roleDistribution as $role): ?>'<?= $role['role_name'] ?>', <?php endforeach; ?>],
                    datasets: [{
                        data: [<?php foreach ($roleDistribution as $role): ?><?= $role['user_count'] ?>, <?php endforeach; ?>],
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(149, 165, 166, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                };
                
                new Chart(roleCtx, {
                    type: 'doughnut',
                    data: roleData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right' },
                            title: { 
                                display: true,
                                text: 'User Distribution by Role'
                            }
                        }
                    }
                });
            }
            
            // Role Activity Chart
            const activityCtx = document.getElementById('roleActivityChart')?.getContext('2d');
            if (activityCtx) {
                const activityData = {
                    labels: [<?php foreach ($roleActivity as $role): ?>'<?= $role['role_name'] ?>', <?php endforeach; ?>],
                    datasets: [{
                        label: 'Monthly Activity',
                        data: [<?php foreach ($roleActivity as $role): ?><?= $role['activity_count'] ?>, <?php endforeach; ?>],
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderWidth: 1
                    }]
                };
                
                new Chart(activityCtx, {
                    type: 'bar',
                    data: activityData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { 
                                display: true,
                                text: 'Monthly Activity by Role'
                            }
                        }
                    }
                });
            }
            
            // Role Growth Chart
            const growthCtx = document.getElementById('roleGrowthChart')?.getContext('2d');
            if (growthCtx) {
                const growthData = {
                    labels: [<?php foreach ($roleGrowth as $growth): ?>'<?= date('M d', strtotime($growth['date'])) ?>', <?php endforeach; ?>],
                    datasets: [{
                        label: 'Roles Added',
                        data: [<?php foreach ($roleGrowth as $growth): ?><?= $growth['count'] ?>, <?php endforeach; ?>],
                        backgroundColor: 'rgba(155, 89, 182, 0.7)',
                        borderColor: 'rgba(155, 89, 182, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                };
                
                new Chart(growthCtx, {
                    type: 'line',
                    data: growthData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { 
                            y: { beginAtZero: true },
                            x: { 
                                title: { display: true, text: 'Date' }
                            }
                        },
                        plugins: {
                            title: { 
                                display: true,
                                text: 'Role Creation Over Time'
                            }
                        }
                    }
                });
            }
            
            // Role Status Chart
            const statusCtx = document.getElementById('roleStatusChart')?.getContext('2d');
            if (statusCtx) {
                const statusData = {
                    labels: ['Active Roles', 'Inactive Roles'],
                    datasets: [{
                        data: [<?= $roleStats['active_roles'] ?>, <?= $roleStats['inactive_roles'] ?>],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(231, 76, 60, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                };
                
                new Chart(statusCtx, {
                    type: 'pie',
                    data: statusData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right' },
                            title: { 
                                display: true,
                                text: 'Role Status Distribution'
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>