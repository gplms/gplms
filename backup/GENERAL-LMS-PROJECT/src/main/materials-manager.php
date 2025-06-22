<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gplms_general');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Function to log activity
function logActivity($pdo, $user_id, $action, $target_object = null, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $username = $_SESSION['username'] ?? 'System';
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, target_object, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $action, $target_object, $details, $ip_address]);
}

// Handle all form submissions
$success_msg = '';
$error_msg = '';

// Handle material actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            // Material CRUD operations
            if ($action_type === 'add_material') {
                $stmt = $pdo->prepare("INSERT INTO library_items 
                    (title, type_id, category_id, publisher_id, language, publication_year, 
                     edition, isbn, issn, description, added_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
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
                    $_SESSION['user_id'],
                    $_POST['status'] ?: 'available'
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
            }
            elseif ($action_type === 'update_material') {
                $stmt = $pdo->prepare("UPDATE library_items SET 
                    title = ?, type_id = ?, category_id = ?, publisher_id = ?, language = ?,
                    publication_year = ?, edition = ?, isbn = ?, issn = ?, description = ?, status = ?
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
                    $_POST['status'] ?: 'available',
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
            }
            
            // Material type CRUD operations
            elseif ($action_type === 'add_material_type') {
                $stmt = $pdo->prepare("INSERT INTO material_types (type_name) VALUES (?)");
                $stmt->execute([$_POST['type_name']]);
                
                $success_msg = "Material type added successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'material_types', 'Added type: '.$_POST['type_name']);
            }
            elseif ($action_type === 'update_material_type') {
                $stmt = $pdo->prepare("UPDATE material_types SET type_name = ? WHERE type_id = ?");
                $stmt->execute([$_POST['type_name'], $_POST['type_id']]);
                
                $success_msg = "Material type updated successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'material_types', 'Updated type: '.$_POST['type_name']);
            }
            elseif ($action_type === 'delete_material_type') {
                // Check if any materials are using this type
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE type_id = ?");
                $checkStmt->execute([$_POST['type_id']]);
                $materialCount = $checkStmt->fetchColumn();
                
                if ($materialCount > 0) {
                    $error_msg = "Cannot delete type: $materialCount material(s) are using this type.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM material_types WHERE type_id = ?");
                    $stmt->execute([$_POST['type_id']]);
                    
                    $success_msg = "Material type deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'material_types', 'Deleted material type ID: '.$_POST['type_id']);
                }
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle status changes and deletions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        try {
            // Material status change
            if ($action === 'change_status') {
                $new_status = $_GET['status'];
                $stmt = $pdo->prepare("UPDATE library_items SET status = ? WHERE item_id = ?");
                $stmt->execute([$new_status, $id]);
                
                // Get title for logging
                $title_stmt = $pdo->prepare("SELECT title FROM library_items WHERE item_id = ?");
                $title_stmt->execute([$id]);
                $title = $title_stmt->fetchColumn();
                
                $success_msg = "Material status updated successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'library_items', "Changed status to $new_status for: $title");
            }
            // Material deletion
            elseif ($action === 'delete_material') {
                // Get title before deletion
                $title_stmt = $pdo->prepare("SELECT title FROM library_items WHERE item_id = ?");
                $title_stmt->execute([$id]);
                $title = $title_stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
                $stmt->execute([$id]);
                
                $success_msg = "Material deleted successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'library_items', 'Deleted material: '.$title);
            }
            // Material type deletion
            elseif ($action === 'delete_type') {
                // Check if any materials are using this type
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE type_id = ?");
                $checkStmt->execute([$id]);
                $materialCount = $checkStmt->fetchColumn();
                
                if ($materialCount > 0) {
                    $error_msg = "Cannot delete type: $materialCount material(s) are using this type.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM material_types WHERE type_id = ?");
                    $stmt->execute([$id]);
                    
                    $success_msg = "Material type deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'material_types', 'Deleted material type ID: '.$id);
                }
            }
        } catch (Exception $e) {
            $error_msg = "Error processing request: " . $e->getMessage();
        }
    }
}

// Pagination settings
$itemsPerPage = 10; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $itemsPerPage; // Offset for SQL query

// Get total number of materials
$totalItems = $pdo->query("SELECT COUNT(*) FROM library_items")->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Ensure page is within valid range
$page = max(1, min($page, $totalPages));

// Fetch materials with related data (with pagination)
$materials = $pdo->query("
    SELECT li.*, 
           mt.type_name, 
           c.name AS category_name,
           p.name AS publisher_name,
           u.username AS added_by_name
    FROM library_items li
    LEFT JOIN material_types mt ON li.type_id = mt.type_id
    LEFT JOIN categories c ON li.category_id = c.category_id
    LEFT JOIN publishers p ON li.publisher_id = p.publisher_id
    LEFT JOIN users u ON li.added_by = u.user_id
    ORDER BY li.added_date DESC
    LIMIT $itemsPerPage OFFSET $offset
")->fetchAll();

// For each material, fetch authors
foreach ($materials as &$material) {
    $author_stmt = $pdo->prepare("
        SELECT a.name 
        FROM item_authors ia 
        JOIN authors a ON ia.author_id = a.author_id 
        WHERE ia.item_id = ?
    ");
    $author_stmt->execute([$material['item_id']]);
    $material['authors'] = $author_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
unset($material); // Break the reference

// Get material for editing
$edit_material = null;
if (isset($_GET['edit_material'])) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM library_items 
        WHERE item_id = ?
    ");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material = $stmt->fetch();
    
    // Get authors for this material
    $stmt = $pdo->prepare("
        SELECT author_id 
        FROM item_authors 
        WHERE item_id = ?
    ");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material['authors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch data for dropdowns
$material_types = $pdo->query("SELECT * FROM material_types ORDER BY type_name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers ORDER BY name")->fetchAll();
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();

// Get material type for editing
$edit_type = null;
if (isset($_GET['edit_type'])) {
    $stmt = $pdo->prepare("SELECT * FROM material_types WHERE type_id = ?");
    $stmt->execute([$_GET['edit_type']]);
    $edit_type = $stmt->fetch();
}

// Language options
$languages = ['EN' => 'English', 'GR' => 'Greek', 'Other' => 'Other'];

// Status options
$status_options = ['available' => 'Available', 'archived' => 'Archived'];

// Get statistics
$material_stats = [
    'total' => $totalItems,
    'available' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE status = 'available'")->fetchColumn(),
    'archived' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE status = 'archived'")->fetchColumn(),
    'recent' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE added_date >= CURDATE() - INTERVAL 7 DAY")->fetchColumn(),
    'by_type' => $pdo->query("
        SELECT mt.type_name, COUNT(li.item_id) AS count 
        FROM material_types mt 
        LEFT JOIN library_items li ON mt.type_id = li.type_id 
        GROUP BY mt.type_id
    ")->fetchAll(PDO::FETCH_KEY_PAIR),
    'by_language' => $pdo->query("
        SELECT language, COUNT(*) AS count 
        FROM library_items 
        GROUP BY language
    ")->fetchAll(PDO::FETCH_KEY_PAIR),
    'by_year' => $pdo->query("
        SELECT publication_year, COUNT(*) AS count 
        FROM library_items 
        WHERE publication_year IS NOT NULL
        GROUP BY publication_year
        ORDER BY publication_year DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_KEY_PAIR),
    'top_authors' => $pdo->query("
        SELECT a.name, COUNT(ia.item_id) AS count 
        FROM item_authors ia 
        JOIN authors a ON ia.author_id = a.author_id 
        GROUP BY a.author_id 
        ORDER BY count DESC 
        LIMIT 5
    ")->fetchAll()
];

// Recent additions
$recent_materials = $pdo->query("
    SELECT li.title, li.added_date, mt.type_name, u.username 
    FROM library_items li
    JOIN material_types mt ON li.type_id = mt.type_id
    JOIN users u ON li.added_by = u.user_id
    ORDER BY li.added_date DESC
    LIMIT 5
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Materials Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
          <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            background: rgba(0,0,0,0.2);
            color: white;
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        .topbar {
            background: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .content-container {
            padding: 25px;
        }
        
        /* Admin Card Styles */
        .admin-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header span {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-total { background: var(--primary-color); }
        .icon-available { background: var(--secondary-color); }
        .icon-archived { background: var(--dark-color); }
        .icon-recent { background: var(--warning-color); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .admin-table th {
            background: #f1f2f6;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dfe4ea;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dfe4ea;
            vertical-align: top;
        }
        
        .admin-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .type-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            background: #e0f7fa;
            color: #00838f;
            display: inline-block;
        }
        
        .author-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .author-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-archived {
            background: #f5f5f5;
            color: #616161;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-edit { background: #3498db; }
        .btn-archive { background: #7f8c8d; }
        .btn-success { background: #2ecc71; }
        .btn-delete { background: #e74c3c; }
        
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        
        .material-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .material-action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-tabs .nav-link {
            padding: 12px 20px;
            color: #7f8c8d;
            font-weight: 600;
            border: none;
            border-radius: 0;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .tab-content {
            margin-top: 20px;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            padding: 15px 0;
            border-top: 1px solid #eee;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
        }
        
        .page-item {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .close-sidebar {
            display: none;
            position: absolute;
            right: 15px;
            top: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
        }
        
        @media (max-width: 992px) {
            .close-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <button class="close-sidebar">
            <i class="fas fa-times"></i>
        </button>
        <div class="sidebar-header">
            <h3><i class="fas fa-book"></i> GPLMS</h3>
            <p class="mb-0">Library Management</p>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-book"></i> Materials</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="d-inline ms-3">Library Materials Management</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="text-end">
                        <strong><?= $_SESSION['username'] ?? 'Admin User' ?></strong>
                        <div class="text-muted small"><?= $_SESSION['role'] ?? 'Administrator' ?></div>
                    </div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin') ?>&background=random" class="rounded-circle" width="40" height="40">
            </div>
        </div>
        
        <!-- Content Container -->
        <div class="content-container">
            <!-- Materials Management Content -->
            <div class="admin-card fade-in">
                <div class="card-header">
                    <span>Manage Library Materials</span>
                    <div class="material-actions">
                        <button class="btn btn-primary material-action-btn" data-bs-toggle="modal" data-bs-target="#materialModal">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                        <button class="btn btn-secondary material-action-btn" data-bs-toggle="modal" data-bs-target="#materialTypeModal">
                            <i class="fas fa-tag"></i> Manage Material Types
                        </button>
                    </div>
                </div>
                
                <!-- Status Messages -->
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Material Statistics Cards -->
                <div class="stats-container px-3">
                    <div class="stat-card">
                        <div class="stat-icon icon-total">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['total'] ?></div>
                        <div class="stat-label">Total Materials</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-available">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['available'] ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-archived">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['archived'] ?></div>
                        <div class="stat-label">Archived</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-recent">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['recent'] ?></div>
                        <div class="stat-label">Added This Week</div>
                    </div>
                </div>
                
                <!-- Tabs for Materials and Types -->
                <ul class="nav nav-tabs px-3" id="materialsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">
                            <i class="fas fa-book me-1"></i> Materials
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button" role="tab">
                            <i class="fas fa-tags me-1"></i> Material Types
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                            <i class="fas fa-chart-bar me-1"></i> Statistics
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content p-3" id="materialsTabContent">
                    <!-- Materials Tab -->
                    <div class="tab-pane fade show active" id="materials" role="tabpanel">
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
                                            <th>Year</th>
                                            <th>Language</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                            <tr>
                                                <td><?= $material['item_id'] ?></td>
                                                <td><?= htmlspecialchars($material['title']) ?></td>
                                                <td><span class="type-badge"><?= htmlspecialchars($material['type_name']) ?></span></td>
                                                <td>
                                                    <div class="author-list">
                                                        <?php foreach ($material['authors'] as $author): ?>
                                                            <span class="author-badge"><?= htmlspecialchars($author) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($material['publisher_name'] ?? 'N/A') ?></td>
                                                <td><?= $material['publication_year'] ?? '' ?></td>
                                                <td><?= $languages[$material['language']] ?? $material['language'] ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= $material['status'] ?>">
                                                        <?= ucfirst($material['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <a href="?edit_material=<?= $material['item_id'] ?>&page=<?= $page ?>" class="action-btn btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($material['status'] === 'available'): ?>
                                                            <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=archived&page=<?= $page ?>" class="action-btn btn-archive" title="Archive">
                                                                <i class="fas fa-archive"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=available&page=<?= $page ?>" class="action-btn btn-success" title="Restore">
                                                                <i class="fas fa-box-open"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?action=delete_material&id=<?= $material['item_id'] ?>&page=<?= $page ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this material?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="pagination-container">
                                <div class="pagination-info">
                                    Showing <?= $offset + 1 ?> to <?= min($offset + $itemsPerPage, $totalItems) ?> of <?= $totalItems ?> entries
                                </div>
                                
                                <nav>
                                    <ul class="pagination">
                                        <!-- Previous Button -->
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Page Numbers -->
                                        <?php 
                                        // Calculate start and end page numbers
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        // Always show first page
                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif;
                                        endif;
                                        
                                        // Show page numbers
                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor;
                                        
                                        // Always show last page
                                        if ($endPage < $totalPages): 
                                            if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Next Button -->
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Material Types Tab -->
                    <div class="tab-pane fade" id="types" role="tabpanel">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type Name</th>
                                            <th>Materials Count</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($material_types as $type): 
                                            $count = $material_stats['by_type'][$type['type_name']] ?? 0;
                                        ?>
                                            <tr>
                                                <td><?= $type['type_id'] ?></td>
                                                <td><?= htmlspecialchars($type['type_name']) ?></td>
                                                <td><?= $count ?></td>
                                                <td>
                                                    <div class="action-btns">
                                                        <a href="?edit_type=<?= $type['type_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?action=delete_type&id=<?= $type['type_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this type?');">
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
                    
                    <!-- Statistics Tab -->
                    <div class="tab-pane fade" id="stats" role="tabpanel">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="admin-card">
                                        <div class="card-header">
                                            <span>Material Types Distribution</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="typeDistributionChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
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
                            </div>
                            
                            <div class="row mt-4">
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
                                
                                <div class="col-md-6">
                                    <div class="admin-card">
                                        <div class="card-header">
                                            <span>Top Authors</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="authorsChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Material Modal -->
    <div class="modal fade" id="materialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= isset($edit_material) ? 'Edit Material' : 'Add New Material' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_type" value="<?= isset($edit_material) ? 'update_material' : 'add_material' ?>">
                    <?php if (isset($edit_material)): ?>
                        <input type="hidden" name="item_id" value="<?= $edit_material['item_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?= isset($edit_material) ? htmlspecialchars($edit_material['title']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="available" <?= (isset($edit_material) && $edit_material['status'] === 'available') ? 'selected' : '' ?>>Available</option>
                                        <option value="archived" <?= (isset($edit_material) && $edit_material['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type_id" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($material_types as $type): ?>
                                            <option value="<?= $type['type_id'] ?>" 
                                                <?= (isset($edit_material) && $edit_material['type_id'] == $type['type_id'] ? 'selected' : '' )?>>
                                                <?= htmlspecialchars($type['type_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['category_id'] ?>" 
                                                <?= (isset($edit_material) && $edit_material['category_id'] == $category['category_id'] ? 'selected' : '' )?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Publisher</label>
                                    <select name="publisher_id" class="form-select">
                                        <option value="">Select Publisher</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?= $publisher['publisher_id'] ?>" 
                                                <?= (isset($edit_material) && $edit_material['publisher_id'] == $publisher['publisher_id'] ? 'selected' : '' )?>>
                                                <?= htmlspecialchars($publisher['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Language</label>
                                    <select name="language" class="form-select">
                                        <?php foreach ($languages as $code => $name): ?>
                                            <option value="<?= $code ?>" 
                                                <?= (isset($edit_material) && $edit_material['language'] === $code ? 'selected' : '' )?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Publication Year</label>
                                    <input type="number" name="publication_year" class="form-control" min="1800" max="<?= date('Y') ?>"
                                           value="<?= $edit_material['publication_year'] ?? '' ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Edition</label>
                                    <input type="number" name="edition" class="form-control" min="1"
                                           value="<?= $edit_material['edition'] ?? '' ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" name="isbn" class="form-control" placeholder="e.g., 978-3-16-148410-0"
                                           value="<?= $edit_material['isbn'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Authors</label>
                            <select name="authors[]" class="form-select" multiple size="5">
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['author_id'] ?>" 
                                        <?= (isset($edit_material) && in_array($author['author_id'], $edit_material['authors'] ?? []) ? 'selected' : '' )?>>
                                        <?= htmlspecialchars($author['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple authors</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4"><?= $edit_material['description'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?= isset($edit_material) ? 'Update Material' : 'Add Material' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Material Type Modal -->
    <div class="modal fade" id="materialTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= isset($edit_type) ? 'Edit Material Type' : 'Add New Material Type' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_type" value="<?= isset($edit_type) ? 'update_material_type' : 'add_material_type' ?>">
                    <?php if (isset($edit_type)): ?>
                        <input type="hidden" name="type_id" value="<?= $edit_type['type_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type Name <span class="text-danger">*</span></label>
                            <input type="text" name="type_name" class="form-control" required
                                   value="<?= $edit_type['type_name'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?= isset($edit_type) ? 'Update Type' : 'Add Type' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap & Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    // Toggle sidebar on mobile
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    
    document.querySelector('.close-sidebar').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.remove('active');
    });
    
    // Pass PHP stats to JavaScript
    const statsData = {
        by_type: <?= json_encode($material_stats['by_type']) ?>,
        by_language: <?= json_encode($material_stats['by_language']) ?>,
        by_year: <?= json_encode($material_stats['by_year']) ?>,
        top_authors: <?= json_encode($material_stats['top_authors']) ?>
    };
    
    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Type Distribution Chart
        const typeCtx = document.getElementById('typeDistributionChart')?.getContext('2d');
        if (typeCtx) {
            const typeData = {
                labels: Object.keys(statsData.by_type),
                datasets: [{
                    data: Object.values(statsData.by_type),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c',
                        '#1abc9c', '#34495e', '#d35400', '#8e44ad', '#27ae60'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(typeCtx, {
                type: 'doughnut',
                data: typeData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                        title: { 
                            display: true,
                            text: 'Material Types Distribution'
                        }
                    }
                }
            });
        }
        
        // Language Distribution Chart
        const langCtx = document.getElementById('languageChart')?.getContext('2d');
        if (langCtx) {
            const langData = {
                labels: Object.keys(statsData.by_language),
                datasets: [{
                    data: Object.values(statsData.by_language),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(langCtx, {
                type: 'pie',
                data: langData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                        title: { 
                            display: true,
                            text: 'Language Distribution'
                        }
                    }
                }
            });
        }
        
        // Year Chart
        const yearCtx = document.getElementById('yearChart')?.getContext('2d');
        if (yearCtx) {
            const yearData = {
                labels: Object.keys(statsData.by_year),
                datasets: [{
                    label: 'Publications',
                    data: Object.values(statsData.by_year),
                    backgroundColor: '#3498db'
                }]
            };
            
            new Chart(yearCtx, {
                type: 'bar',
                data: yearData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        y: { beginAtZero: true },
                        x: { 
                            title: { display: true, text: 'Publication Year' }
                        }
                    },
                    plugins: {
                        title: { 
                            display: true,
                            text: 'Publications by Year'
                        }
                    }
                }
            });
        }
        
        // Authors Chart
        const authorsCtx = document.getElementById('authorsChart')?.getContext('2d');
        if (authorsCtx) {
            const authorNames = statsData.top_authors.map(a => a.name);
            const authorCounts = statsData.top_authors.map(a => a.count);
            
            const authorsData = {
                labels: authorNames,
                datasets: [{
                    label: 'Number of Materials',
                    data: authorCounts,
                    backgroundColor: '#9b59b6'
                }]
            };
            
            new Chart(authorsCtx, {
                type: 'bar',
                data: authorsData,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        x: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false },
                        title: { 
                            display: true,
                            text: 'Top Authors by Material Count'
                        }
                    }
                }
            });
        }
    });
    
    // Auto-show modal if in edit mode
    <?php if (isset($edit_material) || isset($edit_type)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($edit_material)): ?>
                const materialModal = new bootstrap.Modal(document.getElementById('materialModal'));
                materialModal.show();
            <?php endif; ?>
            
            <?php if (isset($edit_type)): ?>
                const typeModal = new bootstrap.Modal(document.getElementById('materialTypeModal'));
                typeModal.show();
            <?php endif; ?>
        });
    <?php endif; ?>
    </script>
</body>
</html>
<?php
// Clear edit session after showing modal
if (isset($edit_material)) unset($_GET['edit_material']);
if (isset($edit_type)) unset($_GET['edit_type']);
?>