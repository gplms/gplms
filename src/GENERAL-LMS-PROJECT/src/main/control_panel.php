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

// Add last_login column if it doesn't exist
try {
    $pdo->query("SELECT last_login FROM users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER created_at");
}

// Add username column to activity_logs if missing
try {
    $pdo->query("SELECT username FROM activity_logs LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN username VARCHAR(255) AFTER user_id");
}

// Add status to library_items if missing
try {
    $pdo->query("SELECT status FROM library_items LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE library_items ADD COLUMN status ENUM('available', 'archived') NOT NULL DEFAULT 'available'");
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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $success_msg = "Settings updated successfully!";
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'system_settings', 'Updated system settings');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error updating settings: " . $e->getMessage();
    }
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                // User actions
                case 'add_user':
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role_id) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['username'],
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        $_POST['full_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['role_id']
                    ]);
                    $success_msg = "User added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'users', 'Added new user: '.$_POST['username']);
                    break;
                    
                case 'update_user':
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role_id = ? 
                                          WHERE user_id = ?");
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['full_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['role_id'],
                        $_POST['user_id']
                    ]);
                    $success_msg = "User updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'users', 'Updated user: '.$_POST['username']);
                    break;
                    
                // Role actions
                case 'add_role':
                    $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
                    $stmt->execute([$_POST['role_name']]);
                    $success_msg = "Role added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'roles', 'Added new role: '.$_POST['role_name']);
                    break;
                    
                case 'update_role':
                    $stmt = $pdo->prepare("UPDATE roles SET role_name = ? WHERE role_id = ?");
                    $stmt->execute([$_POST['role_name'], $_POST['role_id']]);
                    $success_msg = "Role updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Updated role: '.$_POST['role_name']);
                    break;
                    
                // Material actions
                case 'add_material':
                    $stmt = $pdo->prepare("INSERT INTO library_items (title, type_id, category_id, publisher_id, language, 
                                         publication_year, edition, isbn, issn, description, added_by) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['type_id'],
                        $_POST['category_id'] ?: null,
                        $_POST['publisher_id'] ?: null,
                        $_POST['language'],
                        $_POST['publication_year'] ?: null,
                        $_POST['edition'] ?: null,
                        $_POST['isbn'] ?: null,
                        $_POST['issn'] ?: null,
                        $_POST['description'] ?: null,
                        $_SESSION['user_id']
                    ]);
                    $item_id = $pdo->lastInsertId();
                    
                    // Handle authors
                    if (!empty($_POST['authors'])) {
                        foreach ($_POST['authors'] as $author_id) {
                            $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                            $stmt->execute([$item_id, $author_id]);
                        }
                    }
                    
                    $success_msg = "Material added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'library_items', 'Added material: '.$_POST['title']);
                    break;
                    
                case 'update_material':
                    $stmt = $pdo->prepare("UPDATE library_items SET title = ?, type_id = ?, category_id = ?, publisher_id = ?, 
                                          language = ?, publication_year = ?, edition = ?, isbn = ?, issn = ?, description = ?
                                          WHERE item_id = ?");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['type_id'],
                        $_POST['category_id'] ?: null,
                        $_POST['publisher_id'] ?: null,
                        $_POST['language'],
                        $_POST['publication_year'] ?: null,
                        $_POST['edition'] ?: null,
                        $_POST['isbn'] ?: null,
                        $_POST['issn'] ?: null,
                        $_POST['description'] ?: null,
                        $_POST['item_id']
                    ]);
                    
                    // Update authors
                    $pdo->prepare("DELETE FROM item_authors WHERE item_id = ?")->execute([$_POST['item_id']]);
                    if (!empty($_POST['authors'])) {
                        foreach ($_POST['authors'] as $author_id) {
                            $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                            $stmt->execute([$_POST['item_id'], $author_id]);
                        }
                    }
                    
                    $success_msg = "Material updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'library_items', 'Updated material: '.$_POST['title']);
                    break;
                    
                // Category actions
                case 'add_category':
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$_POST['name']]);
                    $success_msg = "Category added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'categories', 'Added category: '.$_POST['name']);
                    break;
                    
                case 'update_category':
                    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['category_id']]);
                    $success_msg = "Category updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'categories', 'Updated category: '.$_POST['name']);
                    break;
                    
                // Publisher actions
                case 'add_publisher':
                    $stmt = $pdo->prepare("INSERT INTO publishers (name, contact_info) VALUES (?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['contact_info']]);
                    $success_msg = "Publisher added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'publishers', 'Added publisher: '.$_POST['name']);
                    break;
                    
                case 'update_publisher':
                    $stmt = $pdo->prepare("UPDATE publishers SET name = ?, contact_info = ? WHERE publisher_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['contact_info'], $_POST['publisher_id']]);
                    $success_msg = "Publisher updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'publishers', 'Updated publisher: '.$_POST['name']);
                    break;
                    
                // Author actions
                case 'add_author':
                    $stmt = $pdo->prepare("INSERT INTO authors (name, bio) VALUES (?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['bio']]);
                    $success_msg = "Author added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'authors', 'Added author: '.$_POST['name']);
                    break;
                    
                case 'update_author':
                    $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ? WHERE author_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['bio'], $_POST['author_id']]);
                    $success_msg = "Author updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'authors', 'Updated author: '.$_POST['name']);
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
    
    if ($id) {
        try {
            switch ($entity) {
                case 'user':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "User deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'users', 'Deleted user ID: '.$id);
                    break;
                    
                case 'role':
                    $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Role deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'roles', 'Deleted role ID: '.$id);
                    break;
                    
                case 'material':
                    $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Material deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'library_items', 'Deleted material ID: '.$id);
                    break;
                    
                case 'category':
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Category deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'categories', 'Deleted category ID: '.$id);
                    break;
                    
                case 'publisher':
                    $stmt = $pdo->prepare("DELETE FROM publishers WHERE publisher_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Publisher deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'publishers', 'Deleted publisher ID: '.$id);
                    break;
                    
                case 'author':
                    $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Author deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'authors', 'Deleted author ID: '.$id);
                    break;
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting $entity: " . $e->getMessage();
        }
    }
}

// Get statistics for dashboard
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'library_items' => $pdo->query("SELECT COUNT(*) FROM library_items")->fetchColumn(),
    'authors' => $pdo->query("SELECT COUNT(*) FROM authors")->fetchColumn(),
    'activity_logs' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(),
    'recent_activity' => $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE timestamp >= CURDATE() - INTERVAL 7 DAY")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login >= NOW() - INTERVAL 15 MINUTE")->fetchColumn()
];

// Get chart data
$material_distribution = $pdo->query("
    SELECT mt.type_name, COUNT(li.item_id) AS count 
    FROM material_types mt
    LEFT JOIN library_items li ON mt.type_id = li.type_id
    GROUP BY mt.type_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$language_distribution = $pdo->query("
    SELECT language, COUNT(*) as count 
    FROM library_items 
    GROUP BY language
")->fetchAll(PDO::FETCH_KEY_PAIR);

$yearly_publications = $pdo->query("
    SELECT publication_year, COUNT(*) as count 
    FROM library_items 
    WHERE publication_year IS NOT NULL
    GROUP BY publication_year
    ORDER BY publication_year DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get all data for dashboard
$users = $pdo->query("SELECT * FROM users")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$material_types = $pdo->query("SELECT * FROM material_types")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();
$authors = $pdo->query("SELECT * FROM authors")->fetchAll();
$library_items = $pdo->query("SELECT * FROM library_items")->fetchAll();
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll();

// Activity logs pagination
$logs_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Count total activity logs
$total_logs = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$total_pages = ceil($total_logs / $logs_per_page);

// Fetch activity logs for current page
$offset = ($current_page - 1) * $logs_per_page;
$activity_logs = $pdo->prepare("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
$activity_logs->bindValue(':limit', $logs_per_page, PDO::PARAM_INT);
$activity_logs->bindValue(':offset', $offset, PDO::PARAM_INT);
$activity_logs->execute();
$activity_logs = $activity_logs->fetchAll();

// Recent activity for dashboard
$recent_activity_logs = $pdo->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 5")->fetchAll();

// Prepare settings array
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

// Get items for editing
$edit_user = null;
$edit_role = null;
$edit_material = null;
$edit_category = null;
$edit_publisher = null;
$edit_author = null;

if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_GET['edit_user']]);
    $edit_user = $stmt->fetch();
}

if (isset($_GET['edit_role'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->execute([$_GET['edit_role']]);
    $edit_role = $stmt->fetch();
}

if (isset($_GET['edit_material'])) {
    $stmt = $pdo->prepare("SELECT * FROM library_items WHERE item_id = ?");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material = $stmt->fetch();
    
    // Get authors for this material
    $stmt = $pdo->prepare("SELECT author_id FROM item_authors WHERE item_id = ?");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material['authors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (isset($_GET['edit_category'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$_GET['edit_category']]);
    $edit_category = $stmt->fetch();
}

if (isset($_GET['edit_publisher'])) {
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE publisher_id = ?");
    $stmt->execute([$_GET['edit_publisher']]);
    $edit_publisher = $stmt->fetch();
}

if (isset($_GET['edit_author'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->execute([$_GET['edit_author']]);
    $edit_author = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/control_panel-styles.css">
    <style>
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
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
            <h4>Admin Control Panel</h4>
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
        
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $stats['users'] ?></div>
                        <div class="stat-label">Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon books">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $stats['library_items'] ?></div>
                        <div class="stat-label">Library Items</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon authors">
                            <i class="fas fa-feather"></i>
                        </div>
                        <div class="stat-number"><?= $stats['authors'] ?></div>
                        <div class="stat-label">Authors</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon activity">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-number"><?= $stats['recent_activity'] ?></div>
                        <div class="stat-label">Recent Activities</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Recent Activity</span>
                                <a href="#activity" class="text-white" data-bs-toggle="tab">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="activity-list">
                                    <?php foreach ($recent_activity_logs as $log): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-history"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div>
                                                    <strong><?= $log['username'] ?? 'System' ?></strong>
                                                    <?= $log['action'] ?>
                                                    <?php if ($log['target_object']): ?>
                                                        <span class="text-muted">(<?= $log['target_object'] ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="activity-time">
                                                    <?= date('M d, Y h:i A', strtotime($log['timestamp'])) ?> | 
                                                    <?= $log['ip_address'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Material Types Distribution</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="materialsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Language Distribution</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="languageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Publication Years</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="yearChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>System Overview</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Database Info</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Database Name:</span>
                                        <span><?= $db ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Tables Count:</span>
                                        <span>10</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Last Backup:</span>
                                        <span>Today at <?= date('H:i') ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Server Info</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>PHP Version:</span>
                                        <span><?= phpversion() ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Server Software:</span>
                                        <span><?= $_SERVER['SERVER_SOFTWARE'] ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Server OS:</span>
                                        <span><?= php_uname('s') ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>System Status</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>System Status:</span>
                                        <span class="status-badge status-active">Operational</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Maintenance Mode:</span>
                                        <span class="status-badge status-inactive">Off</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Active Users:</span>
                                        <span><?= $stats['active_users'] ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Tab -->
            <div class="tab-pane fade" id="users">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Users</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?></td>
                                            <td><?= $user['username'] ?></td>
                                            <td><?= $user['full_name'] ?></td>
                                            <td><?= $user['email'] ?></td>
                                            <td>
                                                <?php 
                                                    $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                                                    $role_stmt->execute([$user['role_id']]);
                                                    $role_name = $role_stmt->fetchColumn();
                                                    echo $role_name;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-active">Active</span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_user=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=user&id=<?= $user['user_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
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
            
            <!-- Roles Tab -->
            <div class="tab-pane fade" id="roles">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Roles</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
                            <i class="fas fa-plus"></i> Add Role
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): 
                                        $user_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
                                        $user_count->execute([$role['role_id']]);
                                        $count = $user_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $role['role_id'] ?></td>
                                            <td><?= $role['role_name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_role=<?= $role['role_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=role&id=<?= $role['role_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this role?')">
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
            
            <!-- Materials Tab -->
            <div class="tab-pane fade" id="materials">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Library Materials</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#materialModal">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Authors</th>
                                        <th>Publisher</th>
                                        <th>Added By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($library_items as $item): 
                                        $type_stmt = $pdo->prepare("SELECT type_name FROM material_types WHERE type_id = ?");
                                        $type_stmt->execute([$item['type_id']]);
                                        $type_name = $type_stmt->fetchColumn();
                                        
                                        $publisher_stmt = $pdo->prepare("SELECT name FROM publishers WHERE publisher_id = ?");
                                        $publisher_stmt->execute([$item['publisher_id']]);
                                        $publisher_name = $publisher_stmt->fetchColumn();
                                        
                                        $user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                                        $user_stmt->execute([$item['added_by']]);
                                        $username = $user_stmt->fetchColumn();
                                        
                                        $author_stmt = $pdo->prepare("
                                            SELECT a.name 
                                            FROM item_authors ia
                                            JOIN authors a ON ia.author_id = a.author_id
                                            WHERE ia.item_id = ?
                                        ");
                                        $author_stmt->execute([$item['item_id']]);
                                        $authors = $author_stmt->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                        <tr>
                                            <td><?= $item['item_id'] ?></td>
                                            <td><?= $item['title'] ?></td>
                                            <td><?= $type_name ?></td>
                                            <td><?= implode(', ', $authors) ?></td>
                                            <td><?= $publisher_name ?></td>
                                            <td><?= $username ?></td>
                                            <td>
                                                <span class="status-badge status-active">Available</span>
                                            </td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_material=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=material&id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this material?')">
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
            
            <!-- Categories Tab -->
            <div class="tab-pane fade" id="categories">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Categories</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE category_id = ?");
                                        $item_count->execute([$category['category_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $category['category_id'] ?></td>
                                            <td><?= $category['name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_category=<?= $category['category_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=category&id=<?= $category['category_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
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
            
            <!-- Publishers Tab -->
            <div class="tab-pane fade" id="publishers">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Publishers</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#publisherModal">
                            <i class="fas fa-plus"></i> Add Publisher
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
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE publisher_id = ?");
                                        $item_count->execute([$publisher['publisher_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $publisher['publisher_id'] ?></td>
                                            <td><?= $publisher['name'] ?></td>
                                            <td><?= $publisher['contact_info'] ? substr($publisher['contact_info'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td><?= $count ?></td>
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
            
            <!-- Authors Tab -->
            <div class="tab-pane fade" id="authors">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Authors</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal">
                            <i class="fas fa-plus"></i> Add Author
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Author Name</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authors as $author): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM item_authors WHERE author_id = ?");
                                        $item_count->execute([$author['author_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $author['author_id'] ?></td>
                                            <td><?= $author['name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_author=<?= $author['author_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=author&id=<?= $author['author_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this author?')">
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
            
            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings">
                <div class="admin-card">
                    <div class="card-header">
                        <span>System Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="settings-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Library Name</label>
                                        <input type="text" name="settings[library_name]" 
                                               class="form-control" 
                                               value="<?= $settings_array['library_name'] ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Max Items Per Page</label>
                                        <input type="number" name="settings[max_items_per_page]" 
                                               class="form-control" 
                                               value="<?= $settings_array['max_items_per_page'] ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Default Language</label>
                                        <select name="settings[default_language]" class="form-select">
                                            <option value="EN" <?= ($settings_array['default_language'] ?? 'EN') === 'EN' ? 'selected' : '' ?>>English</option>
                                            <option value="GR" <?= ($settings_array['default_language'] ?? 'EN') === 'GR' ? 'selected' : '' ?>>Greek</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">User Registration</label>
                                        <select name="settings[enable_user_registration]" class="form-select">
                                            <option value="1" <?= ($settings_array['enable_user_registration'] ?? 1) == '1' ? 'selected' : '' ?>>Enabled</option>
                                            <option value="0" <?= ($settings_array['enable_user_registration'] ?? 1) == '0' ? 'selected' : '' ?>>Disabled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Default Theme</label>
                                        <select name="settings[default_theme]" class="form-select">
                                            <option value="light" selected>Light</option>
                                            <option value="dark">Dark</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Maintenance Mode</label>
                                        <select name="settings[maintenance_mode]" class="form-select">
                                            <option value="0" selected>Disabled</option>
                                            <option value="1">Enabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Email Settings</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" name="settings[smtp_host]" 
                                               class="form-control" 
                                               value="smtp.example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" name="settings[smtp_port]" 
                                               class="form-control" 
                                               value="587">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" name="settings[smtp_username]" 
                                               class="form-control" 
                                               value="user@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" name="settings[smtp_password]" 
                                               class="form-control" 
                                               value="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Activity Log Tab -->
            <div class="tab-pane fade" id="activity">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Activity Logs</span>
                        <div class="d-flex">
                            <input type="text" class="form-control form-control-sm me-2" placeholder="Search logs..." style="width: 200px;">
                            <button class="btn btn-light btn-sm">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Action</th>
                                        <th>Target</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <tr>
                                            <td><?= $log['log_id'] ?></td>
                                            <td><?= $log['username'] ?></td>
                                            <td><?= $log['action'] ?></td>
                                            <td><?= $log['target_object'] ?></td>
                                            <td><?= $log['details'] ? substr($log['details'], 0, 30) . '...' : '-' ?></td>
                                            <td><?= $log['ip_address'] ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page - 1 ?>#activity" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>#activity"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page + 1 ?>#activity" aria-label="Next">
                                            <span aria-hidden="true">Next &raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals (User, Role, Material, Category, Publisher, Author) -->
    <!-- ... (All modal code remains unchanged from your original) ... -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Material Types Chart
            const materialsCtx = document.getElementById('materialsChart').getContext('2d');
            const materialLabels = <?= json_encode(array_keys($material_distribution)) ?>;
            const materialData = <?= json_encode(array_values($material_distribution)) ?>;
            
            const materialsChart = new Chart(materialsCtx, {
                type: 'doughnut',
                data: {
                    labels: materialLabels,
                    datasets: [{
                        data: materialData,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c',
                            '#1abc9c', '#34495e', '#d35400', '#8e44ad', '#16a085'
                        ]
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
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Language Distribution Chart
            const languageCtx = document.getElementById('languageChart').getContext('2d');
            const languageLabels = <?= json_encode(array_keys($language_distribution)) ?>;
            const languageData = <?= json_encode(array_values($language_distribution)) ?>;
            
            const languageChart = new Chart(languageCtx, {
                type: 'pie',
                data: {
                    labels: languageLabels,
                    datasets: [{
                        data: languageData,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Publication Year Chart
            const yearCtx = document.getElementById('yearChart').getContext('2d');
            const yearLabels = <?= json_encode(array_keys($yearly_publications)) ?>;
            const yearData = <?= json_encode(array_values($yearly_publications)) ?>;
            
            const yearChart = new Chart(yearCtx, {
                type: 'bar',
                data: {
                    labels: yearLabels,
                    datasets: [{
                        label: 'Publications',
                        data: yearData,
                        backgroundColor: '#3498db'
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
            
            // Tab persistence
            const tabLinks = document.querySelectorAll('#sidebar .nav-link');
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    localStorage.setItem('lastTab', target);
                });
            });
            
            // Load last active tab
            const lastTab = localStorage.getItem('lastTab');
            if (lastTab) {
                const tab = new bootstrap.Tab(document.querySelector(`a[data-bs-target="${lastTab}"]`));
                tab.show();
            }
            
            // Open modals if needed
            <?php if ($edit_user): ?>
                const userModal = new bootstrap.Modal(document.getElementById('userModal'));
                userModal.show();
            <?php endif; ?>
            
            <?php if ($edit_role): ?>
                const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
                roleModal.show();
            <?php endif; ?>
            
            <?php if ($edit_material): ?>
                const materialModal = new bootstrap.Modal(document.getElementById('materialModal'));
                materialModal.show();
            <?php endif; ?>
            
            <?php if ($edit_category): ?>
                const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
                categoryModal.show();
            <?php endif; ?>
            
            <?php if ($edit_publisher): ?>
                const publisherModal = new bootstrap.Modal(document.getElementById('publisherModal'));
                publisherModal.show();
            <?php endif; ?>
            
            <?php if ($edit_author): ?>
                const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
                authorModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>