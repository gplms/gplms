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

// Add website and last_modified columns to publishers if needed
try {
    $pdo->query("SELECT website FROM publishers LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE publishers ADD COLUMN website VARCHAR(255) AFTER contact_info");
}

try {
    $pdo->query("SELECT last_modified FROM publishers LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE publishers ADD COLUMN last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

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

// Handle publisher actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                case 'add_publisher':
                    $stmt = $pdo->prepare("INSERT INTO publishers (name, contact_info, website) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact_info'] ?? '',
                        $_POST['website'] ?? ''
                    ]);
                    $success_msg = "Publisher added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'publishers', 'Added publisher: '.$_POST['name']);
                    break;
                    
                case 'update_publisher':
                    $stmt = $pdo->prepare("UPDATE publishers SET name = ?, contact_info = ?, website = ? WHERE publisher_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact_info'] ?? '',
                        $_POST['website'] ?? '',
                        $_POST['publisher_id']
                    ]);
                    $success_msg = "Publisher updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'publishers', 'Updated publisher: '.$_POST['name']);
                    break;
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete actions
if (isset($_GET['delete'])) {
    $entity = $_GET['delete'];
    $id = $_GET['id'] ?? null;
    
    if ($id && $entity === 'publisher') {
        try {
            // Check if publisher is used in any items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE publisher_id = ?");
            $stmt->execute([$id]);
            $item_count = $stmt->fetchColumn();
            
            if ($item_count > 0) {
                $error_msg = "Cannot delete publisher because it is used in $item_count items!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM publishers WHERE publisher_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Publisher deleted successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'publishers', 'Deleted publisher ID: '.$id);
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting publisher: " . $e->getMessage();
        }
    }
}

// Get publishers with item counts
$publishers = $pdo->query("
    SELECT p.*, COUNT(li.item_id) AS item_count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    GROUP BY p.publisher_id
")->fetchAll();

// Get items for editing
$edit_publisher = null;
if (isset($_GET['edit_publisher'])) {
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE publisher_id = ?");
    $stmt->execute([$_GET['edit_publisher']]);
    $edit_publisher = $stmt->fetch();
}

// Get statistics for dashboard
$stats = [
    'total_publishers' => $pdo->query("SELECT COUNT(*) FROM publishers")->fetchColumn(),
    'publishers_with_items' => $pdo->query("SELECT COUNT(DISTINCT publisher_id) FROM library_items WHERE publisher_id IS NOT NULL")->fetchColumn(),
    'items_in_publishers' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE publisher_id IS NOT NULL")->fetchColumn(),
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM publishers WHERE last_modified >= CURDATE() - INTERVAL 7 DAY")->fetchColumn()
];

// Get chart data
$publisher_distribution = $pdo->query("
    SELECT p.name, COUNT(li.item_id) AS count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    GROUP BY p.publisher_id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recently updated publishers
$recently_updated = $pdo->query("
    SELECT p.*, COUNT(li.item_id) AS item_count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    WHERE p.last_modified >= CURDATE() - INTERVAL 7 DAY
    GROUP BY p.publisher_id
    ORDER BY p.last_modified DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers Manager - Library System</title>
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
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .stat-icon.publishers {
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
        }
        
        .stat-icon.items {
            background: linear-gradient(180deg, var(--success-color) 0%, #13855c 100%);
        }
        
        .stat-icon.active {
            background: linear-gradient(180deg, var(--info-color) 0%, #258391 100%);
        }
        
        .stat-icon.updated {
            background: linear-gradient(180deg, var(--warning-color) 0%, #dda20a 100%);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--dark-text);
            opacity: 0.8;
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
        
        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .admin-table th {
            background-color: #f8f9fc;
            color: var(--dark-text);
            font-weight: 700;
            padding: 12px 15px;
            border-bottom: 1px solid #e3e6f0;
            text-align: left;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e3e6f0;
            vertical-align: middle;
        }
        
        .admin-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .website-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .website-link:hover {
            text-decoration: underline;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: var(--info-color);
        }
        
        .btn-edit:hover {
            background: #258391;
        }
        
        .btn-delete {
            background: var(--danger-color);
        }
        
        .btn-delete:hover {
            background: #c03529;
        }
        
        /* Charts */
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }
        
        /* Modal Styles */
        .modal-header {
            background: var(--primary-color);
            color: #fff;
        }
        
        .modal-title {
            font-weight: 700;
        }
        
        .modal-footer {
            background: #f8f9fc;
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
            
            .stats-container {
                grid-template-columns: 1fr;
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
                <a href="publishers-manager.php" class="nav-link active">
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
                <a href="activity-log.php" class="nav-link">
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
            <h4>Publishers Manager</h4>
            <div>
                <span class="me-3">Welcome, <?= $_SESSION['username'] ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon publishers">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_publishers'] ?></div>
                    <div class="stat-label">Total Publishers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['publishers_with_items'] ?></div>
                    <div class="stat-label">Publishers with Items</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_in_publishers'] ?></div>
                    <div class="stat-label">Items in Publishers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon updated">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['recently_updated'] ?></div>
                    <div class="stat-label">Recently Updated</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-7">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Publishers</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#publisherModal">
                            <i class="fas fa-plus me-1"></i> Add Publisher
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Publisher Name</th>
                                        <th>Contact Info</th>
                                        <th>Website</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                        <tr>
                                            <td><?= $publisher['publisher_id'] ?></td>
                                            <td><?= $publisher['name'] ?></td>
                                            <td><?= $publisher['contact_info'] ? substr($publisher['contact_info'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td>
                                                <?php if (!empty($publisher['website'])): ?>
                                                    <a href="<?= $publisher['website'] ?>" class="website-link" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> Visit
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $publisher['item_count'] ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_publisher=<?= $publisher['publisher_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=publisher&id=<?= $publisher['publisher_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this publisher?')">
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
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Publisher Distribution</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="publisherChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Publishers</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recently_updated as $publisher): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-building text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= $publisher['name'] ?></div>
                                        <div class="text-muted small">
                                            Updated: <?= date('M d, Y', strtotime($publisher['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $publisher['item_count'] ?> items
                                            </span>
                                            <?php if (!empty($publisher['website'])): ?>
                                                <a href="<?= $publisher['website'] ?>" class="badge bg-info text-white ms-1" target="_blank">
                                                    <i class="fas fa-external-link-alt me-1"></i> Website
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Publisher Modal -->
    <div class="modal fade" id="publisherModal" tabindex="-1" aria-labelledby="publisherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="publisherModalLabel">
                            <?= $edit_publisher ? 'Edit Publisher' : 'Add New Publisher' ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($edit_publisher): ?>
                            <input type="hidden" name="publisher_id" value="<?= $edit_publisher['publisher_id'] ?>">
                            <input type="hidden" name="action_type" value="update_publisher">
                        <?php else: ?>
                            <input type="hidden" name="action_type" value="add_publisher">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Publisher Name</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= $edit_publisher['name'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Information</label>
                            <textarea name="contact_info" class="form-control" rows="3"><?= $edit_publisher['contact_info'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website" class="form-control" 
                                   placeholder="https://example.com"
                                   value="<?= $edit_publisher['website'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Publisher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Publisher Distribution Chart
            const publisherCtx = document.getElementById('publisherChart').getContext('2d');
            const publisherLabels = <?= json_encode(array_keys($publisher_distribution)) ?>;
            const publisherData = <?= json_encode(array_values($publisher_distribution)) ?>;
            
            const publisherChart = new Chart(publisherCtx, {
                type: 'bar',
                data: {
                    labels: publisherLabels,
                    datasets: [{
                        label: 'Items by Publisher',
                        data: publisherData,
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
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y} items`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Open modal if editing
            <?php if ($edit_publisher): ?>
                const publisherModal = new bootstrap.Modal(document.getElementById('publisherModal'));
                publisherModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this publisher?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>