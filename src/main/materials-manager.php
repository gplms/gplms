<?php
session_start();
require_once '../conf/config.php';

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Handle status changes and deletions
$success_msg = '';
$error_msg = '';

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
                $success_msg = "Material status updated successfully!";
            }
            // Material deletion
            elseif ($action === 'delete_material') {
                $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Material deleted successfully!";
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
                }
            }
        } catch (Exception $e) {
            $error_msg = "Error processing request: " . $e->getMessage();
        }
    }
}

// Pagination
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total number of materials
$totalItems = $pdo->query("SELECT COUNT(*) FROM library_items")->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch materials
$materials = $pdo->query("
    SELECT li.*, mt.type_name, c.name AS category_name,
           p.name AS publisher_name, u.username AS added_by_name
    FROM library_items li
    LEFT JOIN material_types mt ON li.type_id = mt.type_id
    LEFT JOIN categories c ON li.category_id = c.category_id
    LEFT JOIN publishers p ON li.publisher_id = p.publisher_id
    LEFT JOIN users u ON li.added_by = u.user_id
    ORDER BY li.added_date DESC
    LIMIT $itemsPerPage OFFSET $offset
")->fetchAll();

// Fetch authors for each material
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
unset($material);

// Fetch data for dropdowns
$material_types = $pdo->query("SELECT * FROM material_types ORDER BY type_name")->fetchAll();

// Language options
$languages = ['EN' => 'English', 'GR' => 'Greek', 'Other' => 'Other'];

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Materials Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
        .sidebar { width: 250px; height: 100%; position: fixed; top: 0; left: 0; background: #343a40; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; padding: 10px 15px; text-decoration: none; display: block; }
        .sidebar a:hover { color: white; background: #495057; }
        .sidebar .active { color: white; background: #495057; }
        .topbar { background: #fff; padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .admin-card { background: #fff; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .stats-container { display: flex; padding: 20px; gap: 20px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 200px; background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; }
        .stat-icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-number { font-size: 2rem; font-weight: bold; }
        .stat-label { color: #6c757d; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .admin-table th { background: #f8f9fa; text-align: left; }
        .action-btns { display: flex; gap: 10px; }
        .action-btn { padding: 5px 10px; border-radius: 4px; }
        .btn-edit { background: #0d6efd; color: white; }
        .btn-archive { background: #6c757d; color: white; }
        .btn-success { background: #198754; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .pagination-container { display: flex; justify-content: space-between; align-items: center; padding: 20px; }
        .chart-container { position: relative; height: 300px; }
        .type-badge, .author-badge, .status-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; }
        .type-badge { background: #e8f4ff; color: #0a58ca; }
        .author-badge { background: #f0f7ff; color: #0a58ca; margin-right: 5px; }
        .status-available { background: #d1e7dd; color: #0f5132; }
        .status-archived { background: #f8d7da; color: #842029; }
        .author-list { display: flex; flex-wrap: wrap; gap: 5px; }
        .sidebar-toggle { display: none; }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-toggle { display: block; }
        }
    </style>
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
 <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-book me-2"></i> GPLMS</h3>
            <hr>
       
            <hr>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="../main/control_panel.php" class="">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../main/users-manager.php">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="../main/roles-manager.php">
                    <i class="fas fa-user-tag"></i>
                    <span>Roles</span>
                </a>
            </li>
            <li>
                <a href="../main/library-catalog.php">
                    <i class="fas fa-book"></i>
                    <span>Library Catalog</span>
                </a>
            </li>
            <li>
                <a href="../main/materials-manager.php">
                    <i class="fas fa-book-open"></i>
                    <span>Materials</span>
                </a>
            </li>
            <li>
                <a href="../main/categories-manager.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="../main/publishers-manager.php">
                    <i class="fas fa-building"></i>
                    <span>Publishers</span>
                </a>
            </li>
            <li>
                <a href="../main/authors-manager.php">
                    <i class="fas fa-feather"></i>
                    <span>Authors</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </a>
            </li>
            <div class="divider"></div>
            <li>
                <a href="../main/settings-manager.php">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
        
            <li>
                <a href="../main/activity-log.php">
                    <i class="fas fa-history"></i>
                    <span>Activity Log</span>
                </a>
            </li>
    
            <br>
            
            <div class="divider"></div>
            <li>
                <a href="../main/search.php">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Library</span>
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
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <button class="sidebar-toggle btn btn-light">
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
            <div class="admin-card">
                <div class="card-header">
                    <span>Manage Library Materials</span>
                    <div class="material-actions">
                        <a href="insert.php" class="btn btn-primary material-action-btn">
                            <i class="fas fa-plus"></i> Add Material
                        </a>
                        <a href="manage-types.php" class="btn btn-secondary material-action-btn">
                            <i class="fas fa-tag"></i> Manage Material Types
                        </a>
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
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon icon-total text-primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['total'] ?></div>
                        <div class="stat-label">Total Materials</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-available text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['available'] ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-archived text-secondary">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['archived'] ?></div>
                        <div class="stat-label">Archived</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-recent text-info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['recent'] ?></div>
                        <div class="stat-label">Added This Week</div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs px-3" id="materialsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">
                            <i class="fas fa-book me-1"></i> Materials
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
                                                        <a href="edit.php?id=<?= $material['item_id'] ?>" class="action-btn btn-edit" title="Edit">
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
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
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

    <!-- Bootstrap & Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    // Toggle sidebar on mobile
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    
    // Pass PHP stats to JavaScript
    const statsData = {
        by_type: <?= json_encode($material_stats['by_type']) ?>,
        by_language: <?= json_encode($material_stats['by_language']) ?>,
        by_year: <?= json_encode($material_stats['by_year']) ?>,
        top_authors: <?= json_encode($material_stats['top_authors']) ?>
    };
    
    // Function to initialize charts
    function initCharts() {
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
    }

    // Initialize charts when stats tab is shown
    document.getElementById('stats-tab').addEventListener('shown.bs.tab', initCharts);
    
    // Initialize charts immediately if stats tab is active on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('stats').classList.contains('active')) {
            initCharts();
        }
    });
    </script>
</body>
</html>