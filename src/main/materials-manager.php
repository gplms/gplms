<?php
session_start();
require_once '../conf/config.php';
require_once '../functions/fetch-lib-name.php';
// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}


require_once '../conf/translation.php';

// Get default language setting from database
$default_language = 'EN'; // Default value
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'default_language'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $default_language = $result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Default language setting error: " . $e->getMessage());
}

// Get items per page setting
$itemsPerPage = 10;
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'items_per_page'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && is_numeric($result['setting_value'])) {
        $itemsPerPage = (int)$result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Items per page setting error: " . $e->getMessage());
}

// Handle status changes and deletions
$success_msg = '';
$error_msg = '';

// ========================================================================
// TRANSLATION SYSTEM
// ========================================================================

$lang = $translations[$default_language] ?? $translations['EN'];

// Status translations
$status_translations = [
    'EN' => [
        'available' => 'Available',
        'archived' => 'Archived'
    ],
    'GR' => [
        'available' => 'Διαθέσιμο',
        'archived' => 'Αρχειοθετημένο'
    ]
];

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
                $success_msg = $lang['status_updated'];
            }
            // Material deletion
            elseif ($action === 'delete_material') {
                $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
                $stmt->execute([$id]);
                $success_msg = $lang['material_deleted'];
            }
            // Material type deletion
            elseif ($action === 'delete_type') {
                // Check if any materials are using this type
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE type_id = ?");
                $checkStmt->execute([$id]);
                $materialCount = $checkStmt->fetchColumn();
                
                if ($materialCount > 0) {
                    $error_msg = sprintf($lang['type_delete_error'], $materialCount);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM material_types WHERE type_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = $lang['type_deleted'];
                }
            }
        } catch (Exception $e) {
            $error_msg = $lang['processing_error'] . $e->getMessage();
        }
    }
}

// Pagination
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
    <title><?= $lang['page_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/components/sidebar1.css">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
       
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



    <!-- Include Sidebar -->
    <?php include '../components/sidebar1.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <button class="sidebar-toggle btn btn-light">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="d-inline ms-3"><?= $lang['library_materials_management'] ?></h4>
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
                    <span><?= $lang['manage_library_materials'] ?></span>
                    <div class="material-actions">
                        <a href="insert.php" class="btn btn-primary material-action-btn">
                            <i class="fas fa-plus"></i> <?= $lang['add_material'] ?>
                        </a>
                        <a href="manage-types.php" class="btn btn-secondary material-action-btn">
                            <i class="fas fa-tag"></i> <?= $lang['manage_material_types'] ?>
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
                        <div class="stat-label"><?= $lang['total_materials'] ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-available text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['available'] ?></div>
                        <div class="stat-label"><?= $lang['available'] ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-archived text-secondary">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['archived'] ?></div>
                        <div class="stat-label"><?= $lang['archived'] ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-recent text-info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?= $material_stats['recent'] ?></div>
                        <div class="stat-label"><?= $lang['added_this_week'] ?></div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs px-3" id="materialsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">
                            <i class="fas fa-book me-1"></i> <?= $lang['materials_tab'] ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                            <i class="fas fa-chart-bar me-1"></i> <?= $lang['statistics_tab'] ?>
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
                                            <th><?= $lang['id'] ?></th>
                                            <th><?= $lang['title'] ?></th>
                                            <th><?= $lang['type'] ?></th>
                                            <th><?= $lang['authors'] ?></th>
                                            <th><?= $lang['publisher'] ?></th>
                                            <th><?= $lang['year'] ?></th>
                                            <th><?= $lang['language'] ?></th>
                                            <th><?= $lang['status'] ?></th>
                                            <th><?= $lang['actions'] ?></th>
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
                                                    <?php 
                                                    $status_trans = $status_translations[$default_language][$material['status']] ?? ucfirst($material['status']);
                                                    ?>
                                                    <span class="status-badge status-<?= $material['status'] ?>">
                                                        <?= $status_trans ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <a href="edit.php?id=<?= $material['item_id'] ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($material['status'] === 'available'): ?>
                                                            <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=archived&page=<?= $page ?>" class="action-btn btn-archive" title="<?= $lang['archive'] ?>">
                                                                <i class="fas fa-archive"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=change_status&id=<?= $material['item_id'] ?>&status=available&page=<?= $page ?>" class="action-btn btn-success" title="<?= $lang['restore'] ?>">
                                                                <i class="fas fa-box-open"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?action=delete_material&id=<?= $material['item_id'] ?>&page=<?= $page ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" onclick="return confirm('<?= $default_language === 'GR' ? 'Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το υλικό;' : 'Are you sure you want to delete this material?' ?>');">
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
                                    <?= sprintf(
                                        '%s %d %s %d %s %d %s',
                                        $lang['showing'],
                                        $offset + 1,
                                        $lang['to'],
                                        min($offset + $itemsPerPage, $totalItems),
                                        $lang['of'],
                                        $totalItems,
                                        $lang['entries']
                                    ) ?>
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
                                            <span><?= $lang['material_types_distribution'] ?></span>
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
                                            <span><?= $lang['language_distribution'] ?></span>
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
                                            <span><?= $lang['publication_years'] ?></span>
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
                                            <span><?= $lang['top_authors'] ?></span>
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
        top_authors: <?= json_encode($material_stats['top_authors']) ?>,
        lang: {
            material_types_distribution: "<?= $lang['material_types_distribution'] ?>",
            language_distribution: "<?= $lang['language_distribution'] ?>",
            publication_years: "<?= $lang['publication_years'] ?>",
            top_authors: "<?= $lang['top_authors'] ?>",
            publications: "<?= $default_language === 'GR' ? 'Δημοσιεύσεις' : 'Publications' ?>",
            material_count: "<?= $default_language === 'GR' ? 'Αριθμός Υλικών' : 'Number of Materials' ?>"
        }
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
                            text: statsData.lang.material_types_distribution
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
                            text: statsData.lang.language_distribution
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
                    label: statsData.lang.publications,
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
                            title: { 
                                display: true, 
                                text: "<?= $default_language === 'GR' ? 'Έτος Δημοσίευσης' : 'Publication Year' ?>" 
                            }
                        }
                    },
                    plugins: {
                        title: { 
                            display: true,
                            text: statsData.lang.publication_years
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
                    label: statsData.lang.material_count,
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
                            text: statsData.lang.top_authors
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






 <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mainMenu').classList.toggle('expanded');
        });
        
        // Add active class to clicked menu items
        const menuItems = document.querySelectorAll('.main-menu li');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mainMenu');
            const toggleBtn = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768 && 
                !menu.contains(event.target) && 
                event.target !== toggleBtn &&
                menu.classList.contains('expanded')) {
                menu.classList.remove('expanded');
            }
        });
    </script>
</body>
</html>