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

// Include header template
include '../compoonents/header.php';
?>

<!-- Include sidebar template -->
<?php include '../compoonents/sidebar.php'; ?>

<div id="content">
    <!-- Include topbar template -->
    <?php include '../compoonents/topbar.php'; ?>
    
    <!-- Roles Management Content -->
    <div class="admin-card">
        <style>
            :root {
                --primary-color: #3498db;
                --secondary-color: #2ecc71;
                --dark-color: #2c3e50;
                --light-color: #ecf0f1;
                --danger-color: #e74c3c;
                --warning-color: #f39c12;
            }
            
            .role-badge {
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .role-admin {
                background-color: rgba(231, 76, 60, 0.1);
                color: var(--danger-color);
            }
            
            .role-librarian {
                background-color: rgba(46, 204, 113, 0.1);
                color: var(--secondary-color);
            }
            
            .role-user {
                background-color: rgba(52, 152, 219, 0.1);
                color: var(--primary-color);
            }
            
            .role-staff {
                background-color: rgba(155, 89, 182, 0.1);
                color: #9b59b6;
            }
            
            .status-badge {
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .status-active {
                background-color: rgba(46, 204, 113, 0.1);
                color: var(--secondary-color);
            }
            
            .status-inactive {
                background-color: rgba(231, 76, 60, 0.1);
                color: var(--danger-color);
            }
            
            .chart-container {
                position: relative;
                height: 300px;
                width: 100%;
            }
            
            .action-btns {
                display: flex;
                gap: 10px;
            }
            
            .action-btn {
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 5px;
                color: white;
                text-decoration: none;
            }
            
            .btn-edit {
                background-color: var(--warning-color);
            }
            
            .btn-delete {
                background-color: var(--danger-color);
            }
            
            .btn-add {
                background-color: var(--secondary-color);
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 5px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .stats-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }
            
            .stat-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
                transition: transform 0.3s ease;
            }
            
            .stat-card:hover {
                transform: translateY(-5px);
            }
            
            .stat-icon {
                font-size: 2.5rem;
                margin-bottom: 15px;
            }
            
            .stat-number {
                font-size: 2rem;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .stat-label {
                color: #666;
                font-size: 1rem;
            }
            
            .icon-users { color: #3498db; }
            .icon-active { color: #2ecc71; }
            .icon-inactive { color: #e74c3c; }
            .icon-growth { color: #9b59b6; }
        </style>
        
        <div class="card-header">
            <span>Manage Roles</span>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#roleModal">
                <i class="fas fa-plus"></i> Add Role
            </button>
        </div>
        
        <!-- Status Messages -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Role Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon icon-users">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div class="stat-number"><?= $roleStats['total_roles'] ?></div>
                <div class="stat-label">Total Roles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $roleStats['active_roles'] ?></div>
                <div class="stat-label">Active Roles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-inactive">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-number"><?= $roleStats['inactive_roles'] ?></div>
                <div class="stat-label">Inactive Roles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-growth">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?= $roleStats['role_with_most_users'] ?></div>
                <div class="stat-label">Most Popular Role</div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?= $role['role_id'] ?></td>
                            <td>
                                <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $role['role_name'])) ?>">
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                            <td><?= $role['user_count'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $role['status'] ?>">
                                    <?= ucfirst($role['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="?edit_role=<?= $role['role_id'] ?>" class="action-btn btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($role['status'] === 'active'): ?>
                                        <a href="?status_change=deactivate&id=<?= $role['role_id'] ?>" class="action-btn btn-warning" title="Deactivate">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?status_change=activate&id=<?= $role['role_id'] ?>" class="action-btn btn-success" title="Activate">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?delete=role&id=<?= $role['role_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                       onclick="return confirm('Are you sure you want to delete this role?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Role Statistics -->
        <div class="card-header mt-4">
            <span>Role Statistics & Charts</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span>User Distribution by Role</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="roleDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span>Monthly Activity by Role</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="roleActivityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="admin-card">
                        <div class="card-header">
                            <span>Role Growth Over Time</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="roleGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span>Role Performance Metrics</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Users</th>
                                            <th>Activity (30d)</th>
                                            <th>Avg. Activity/User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roleActivity as $role): 
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = ?)");
                                            $stmt->execute([$role['role_name']]);
                                            $user_count = $stmt->fetchColumn();
                                            $avg_activity = $user_count > 0 ? round($role['activity_count'] / $user_count, 1) : 0;
                                        ?>
                                            <tr>
                                                <td><?= $role['role_name'] ?></td>
                                                <td><?= $user_count ?></td>
                                                <td><?= $role['activity_count'] ?></td>
                                                <td><?= $avg_activity ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <span>Role Status Distribution</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="roleStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit_role ? 'Edit Role' : 'Add New Role' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_role ? 'update_role' : 'add_role' ?>">
                <?php if ($edit_role): ?>
                    <input type="hidden" name="role_id" value="<?= $edit_role['role_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" class="form-control" required
                               value="<?= $edit_role ? htmlspecialchars($edit_role['role_name']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?= $edit_role ? htmlspecialchars($edit_role['description']) : '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?= ($edit_role && $edit_role['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($edit_role && $edit_role['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?= $edit_role ? 'Update' : 'Add' ?> Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer template -->
<?php include '../compoonents/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Auto-show modal if editing role
<?php if ($edit_role): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('roleModal'));
        modal.show();
    });
<?php endif; ?>

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