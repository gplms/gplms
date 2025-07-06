<?php
session_start();

require_once '../conf/check-session.php';
require_once '../conf/config.php';
require_once 'maintenance_check.php';
require_once '../conf/translation.php'; // Include the translation component

$library_name = 'GPLMS'; // Default value
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'library_name'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['setting_value'])) {
        $library_name = $result['setting_value'];
    }
} catch (Exception $e) {
    error_log("Error fetching library name: " . $e->getMessage());
}

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
                $success_msg = $lang['role_added'];
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
                $success_msg = $lang['role_updated'];
                logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Updated role: '.$_POST['role_name']);
            }
            
            $pdo->commit();
            
            // Redirect after form submission
            header("Location: roles-manager.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = $lang['error'] . $e->getMessage();
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
        
        $success_msg = $lang['status_updated'];
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Changed status for role ID: '.$id);
        
        // Redirect after status change
        header("Location: roles-manager.php");
        exit;
    } catch (Exception $e) {
        $error_msg = $lang['error'] . $e->getMessage();
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
            $error_msg = sprintf($lang['cannot_delete_role'], $userCount);
        } else {
            $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
            $stmt->execute([$id]);
            $success_msg = $lang['role_deleted'];
            logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'roles', 'Deleted role ID: '.$id);
            
            // Redirect after delete
            header("Location: roles-manager.php");
            exit;
        }
    } catch (Exception $e) {
        $error_msg = $lang['error'] . $e->getMessage();
    }
}

// Handle edit role request
if (isset($_GET['edit_role'])) {
    // Store role ID in session for modal handling
    $_SESSION['edit_role_id'] = (int)$_GET['edit_role'];
    
    // Redirect to clear URL parameters
    header("Location: roles-manager.php");
    exit;
}

// Get roles with user counts
$roles = $pdo->query("SELECT r.*, 
                     (SELECT COUNT(*) FROM users u WHERE u.role_id = r.role_id) AS user_count
                     FROM roles r ORDER BY role_id")->fetchAll();

// Check if we have a stored edit role ID
$edit_role = null;
if (isset($_SESSION['edit_role_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->execute([$_SESSION['edit_role_id']]);
    $edit_role = $stmt->fetch();
    
    // Clear the session variable after use
    unset($_SESSION['edit_role_id']);
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
    <title><?= $lang['page_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link href="../styles/roles-manager.css" rel="stylesheet">
   <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
    
<?php include '../components/sidebar1.php'; ?>


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
                                text: '<?= $lang['user_distribution'] ?>'
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
                        label: '<?= $lang['monthly_activity'] ?>',
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
                                text: '<?= $lang['monthly_activity'] ?>'
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
                        label: '<?= $lang['role_creation'] ?>',
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
                                text: '<?= $lang['role_creation'] ?>'
                            }
                        }
                    }
                });
            }
            
            // Role Status Chart
            const statusCtx = document.getElementById('roleStatusChart')?.getContext('2d');
            if (statusCtx) {
                const statusData = {
                    labels: ['<?= $lang['active_roles'] ?>', '<?= $lang['inactive_roles'] ?>'],
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
                                text: '<?= $lang['role_status'] ?>'
                            }
                        }
                    }
                });
            }
        });




document.addEventListener('DOMContentLoaded', function () {
    var roleModal = document.getElementById('roleModal');
    if (roleModal) {
        roleModal.addEventListener('hidden.bs.modal', function () {
            // Always reload the page after the modal is closed to reset state
            window.location.href = 'roles-manager.php';
        });
    }
});

    </script>
</body>
</html>