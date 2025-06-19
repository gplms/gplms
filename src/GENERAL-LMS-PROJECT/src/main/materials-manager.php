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

// Fetch all materials with related data
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
    'total' => $pdo->query("SELECT COUNT(*) FROM library_items")->fetchColumn(),
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

// Include header template
include '../compoonents/header.php';
?>

<!-- Include sidebar template -->
<?php include '../compoonents/sidebar.php'; ?>

<div id="content">
    <!-- Include topbar template -->
    <?php include '../compoonents/topbar.php'; ?>
    
    <!-- Materials Management Content -->
    <div class="admin-card">
        <style>
            /* ... (previous styles remain the same) ... */
            
            .tab-content {
                margin-top: 20px;
            }
            
            .nav-tabs .nav-link.active {
                background-color: #3498db;
                color: white;
                border-color: #3498db;
            }
            
            .type-badge {
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 0.8rem;
                background: #e0e0e0;
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
        </style>
        
        <div class="card-header">
            <span>Manage Library Materials</span>
            <div class="material-actions">
                <button class="btn btn-primary material-action-btn" data-bs-toggle="modal" data-bs-target="#materialModal">
                    <i class="fas fa-plus"></i> Add Material
                </button>
                <button class="btn btn-secondary material-action-btn" data-bs-toggle="modal" data-bs-target="#materialTypeModal">
                    <i class="fas fa-tag"></i> Manage Material Types
                </button>
                <a href="authors_manager.php" class="btn btn-info material-action-btn">
                    <i class="fas fa-feather"></i> Manage Authors
                </a>
                <a href="publishers_manager.php" class="btn btn-warning material-action-btn">
                    <i class="fas fa-building"></i> Manage Publishers
                </a>
            </div>
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
        
        <!-- Material Statistics Cards -->
        <div class="stats-container">
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
        <ul class="nav nav-tabs" id="materialsTab" role="tablist">
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
        
        <div class="tab-content" id="materialsTabContent">
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
                                    <th>Added By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?= $material['item_id'] ?></td>
                                    <td><?= htmlspecialchars($material['title']) ?></td>
                                    <td>
                                        <span class="type-badge"><?= $material['type_name'] ?></span>
                                    </td>
                                    <td>
                                        <div class="author-list">
                                            <?php if (!empty($material['authors'])): ?>
                                                <?php foreach ($material['authors'] as $author): ?>
                                                    <span class="author-badge"><?= htmlspecialchars($author) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= $material['publisher_name'] ?? 'N/A' ?></td>
                                    <td><?= $material['publication_year'] ?? 'N/A' ?></td>
                                    <td><?= $languages[$material['language']] ?? $material['language'] ?></td>
                                    <td><?= $material['added_by_name'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $material['status'] ?>">
                                            <?= ucfirst($material['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit_material=<?= $material['item_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($material['status'] === 'available'): ?>
                                                <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=archived" class="action-btn btn-archive" title="Archive">
                                                    <i class="fas fa-archive"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=available" class="action-btn btn-success" title="Restore">
                                                    <i class="fas fa-box-open"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=delete_material&id=<?= $material['item_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this material?')">
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
                                    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE type_id = ?");
                                    $count_stmt->execute([$type['type_id']]);
                                    $material_count = $count_stmt->fetchColumn();
                                ?>
                                    <tr>
                                        <td><?= $type['type_id'] ?></td>
                                        <td><?= htmlspecialchars($type['type_name']) ?></td>
                                        <td><?= $material_count ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?edit_type=<?= $type['type_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete_type&id=<?= $type['type_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this material type?')">
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
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="admin-card">
                                <div class="card-header">
                                    <span>Recent Additions</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Type</th>
                                                    <th>Added By</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_materials as $material): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($material['title']) ?></td>
                                                        <td><?= $material['type_name'] ?></td>
                                                        <td><?= $material['username'] ?></td>
                                                        <td><?= date('M d, Y', strtotime($material['added_date'])) ?></td>
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
                                    <span>Status Distribution</span>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
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
                <h5 class="modal-title"><?= $edit_material ? 'Edit Material' : 'Add New Material' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_material ? 'update_material' : 'add_material' ?>">
                <?php if ($edit_material): ?>
                    <input type="hidden" name="item_id" value="<?= $edit_material['item_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?= $edit_material ? htmlspecialchars($edit_material['title']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($status_options as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($edit_material && $edit_material['status'] === $value) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                            <?= ($edit_material && $edit_material['type_id'] == $type['type_id']) ? 'selected' : '' ?>>
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
                                            <?= ($edit_material && $edit_material['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
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
                                            <?= ($edit_material && $edit_material['publisher_id'] == $publisher['publisher_id']) ? 'selected' : '' ?>>
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
                                            <?= ($edit_material && $edit_material['language'] === $code) ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Publication Year</label>
                                <input type="number" name="publication_year" class="form-control" min="1800" max="<?= date('Y') + 5 ?>"
                                       value="<?= $edit_material ? $edit_material['publication_year'] : '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Edition</label>
                                <input type="number" name="edition" class="form-control" min="1"
                                       value="<?= $edit_material ? $edit_material['edition'] : '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-control" placeholder="e.g., 978-3-16-148410-0"
                                       value="<?= $edit_material ? $edit_material['isbn'] : '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Authors</label>
                        <select name="authors[]" class="form-select" multiple size="5">
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $author['author_id'] ?>" 
                                    <?= ($edit_material && in_array($author['author_id'], $edit_material['authors'] ?? [])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($author['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple authors</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= $edit_material ? htmlspecialchars($edit_material['description']) : '' ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?= $edit_material ? 'Update' : 'Add' ?> Material</button>
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
                <h5 class="modal-title"><?= $edit_type ? 'Edit Material Type' : 'Add New Material Type' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_type ? 'update_material_type' : 'add_material_type' ?>">
                <?php if ($edit_type): ?>
                    <input type="hidden" name="type_id" value="<?= $edit_type['type_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" class="form-control" required
                               value="<?= $edit_type ? htmlspecialchars($edit_type['type_name']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?= $edit_type ? 'Update' : 'Add' ?> Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer template -->
<?php include '../compoonents/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Auto-show modals if needed
<?php if ($edit_material): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('materialModal'));
        modal.show();
    });
<?php endif; ?>

<?php if ($edit_type): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('materialTypeModal'));
        modal.show();
    });
<?php endif; ?>

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Type Distribution Chart
    const typeCtx = document.getElementById('typeDistributionChart')?.getContext('2d');
    if (typeCtx) {
        const typeData = {
            labels: [<?php foreach ($material_stats['by_type'] as $type => $count): ?>'<?= $type ?>', <?php endforeach; ?>],
            datasets: [{
                data: [<?php foreach ($material_stats['by_type'] as $count): ?><?= $count ?>, <?php endforeach; ?>],
                backgroundColor: [
                    '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c',
                    '#1abc9c', '#34495e', '#d35400', '#8e44ad', '#16a085'
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
            labels: [<?php foreach ($material_stats['by_language'] as $lang => $count): ?>'<?= $languages[$lang] ?? $lang ?>', <?php endforeach; ?>],
            datasets: [{
                data: [<?php foreach ($material_stats['by_language'] as $count): ?><?= $count ?>, <?php endforeach; ?>],
                backgroundColor: [
                    '#3498db', '#2ecc71', '#9b59b6', '#f1c40f'
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
            labels: [<?php foreach ($material_stats['by_year'] as $year => $count): ?>'<?= $year ?>', <?php endforeach; ?>],
            datasets: [{
                label: 'Publications',
                data: [<?php foreach ($material_stats['by_year'] as $count): ?><?= $count ?>, <?php endforeach; ?>],
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
        const authorsData = {
            labels: [<?php foreach ($material_stats['top_authors'] as $author): ?>'<?= $author['name'] ?>', <?php endforeach; ?>],
            datasets: [{
                label: 'Number of Materials',
                data: [<?php foreach ($material_stats['top_authors'] as $author): ?><?= $author['count'] ?>, <?php endforeach; ?>],
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
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        const statusData = {
            labels: ['Available', 'Archived'],
            datasets: [{
                data: [<?= $material_stats['available'] ?>, <?= $material_stats['archived'] ?>],
                backgroundColor: [
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(149, 165, 166, 0.7)'
                ],
                borderWidth: 1
            }]
        };
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    title: { 
                        display: true,
                        text: 'Status Distribution'
                    }
                }
            }
        });
    }
});
</script>