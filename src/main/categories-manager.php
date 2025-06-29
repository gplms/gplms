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

// Add missing columns to categories table if needed
try {
    $pdo->query("SELECT status FROM categories LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE categories 
                ADD COLUMN description TEXT AFTER name,
                ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                ADD COLUMN last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
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

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                case 'add_category':
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?? '',
                        $_POST['status'] ?? 'active'
                    ]);
                    $success_msg = "Category added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'categories', 'Added category: '.$_POST['name']);
                    break;
                    
                case 'update_category':
                    // Force timestamp update
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ?, last_modified = CURRENT_TIMESTAMP WHERE category_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'] ?? '',
                        $_POST['status'] ?? 'active',
                        $_POST['category_id']
                    ]);
                    $success_msg = "Category updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'categories', 'Updated category: '.$_POST['name']);
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
    
    if ($id && $entity === 'category') {
        try {
            // Check if category is used in any items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE category_id = ?");
            $stmt->execute([$id]);
            $item_count = $stmt->fetchColumn();
            
            if ($item_count > 0) {
                $error_msg = "Cannot delete category because it is used in $item_count items!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Category deleted successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'categories', 'Deleted category ID: '.$id);
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get categories with item counts
$categories = $pdo->query("
    SELECT c.*, COUNT(li.item_id) AS item_count 
    FROM categories c
    LEFT JOIN library_items li ON c.category_id = li.category_id
    GROUP BY c.category_id
")->fetchAll();

// Get items for editing
$edit_category = null;
if (isset($_GET['edit_category'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$_GET['edit_category']]);
    $edit_category = $stmt->fetch();
}

// Get statistics for dashboard
$stats = [
    'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'active_categories' => $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn(),
    'items_in_categories' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE category_id IS NOT NULL")->fetchColumn(),
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM categories WHERE last_modified >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
];

// Get chart data
$category_distribution = $pdo->query("
    SELECT c.name, COUNT(li.item_id) AS count 
    FROM categories c
    LEFT JOIN library_items li ON c.category_id = li.category_id
    GROUP BY c.category_id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

$status_distribution = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM categories 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recently updated categories - FIXED QUERY
$recently_updated = $pdo->query("
    SELECT c.*, COUNT(li.item_id) AS item_count 
    FROM categories c
    LEFT JOIN library_items li ON c.category_id = li.category_id
    WHERE c.last_modified >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY c.category_id
    ORDER BY c.last_modified DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Manager - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/categories-manager.css">
    <link rel="stylesheet" href="../styles/general/general-main-styles.css">
    <link rel="stylesheet" href="../styles/components/topbar.css">
    <link rel="stylesheet" href="../styles/components/sidebar.css">
    <link rel="stylesheet" href="../styles/responsive/responsive.css">
</head>
<body>

    <!-- Sidebar Component -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Categories Manager</h4>
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
                <div class="stat-icon categories">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_categories'] ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['active_categories'] ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_in_categories'] ?></div>
                    <div class="stat-label">Items in Categories</div>
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
                        <span>Manage Categories</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-1"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= $category['category_id'] ?></td>
                                            <td><?= $category['name'] ?></td>
                                            <td><?= $category['description'] ? substr($category['description'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td><?= $category['item_count'] ?></td>
                                            <td>
                                                <span class="status-badge <?= $category['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                                    <?= ucfirst($category['status']) ?>
                                                </span>
                                            </td>
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
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Category Distribution</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Categories</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (!empty($recently_updated)): ?>
                                <?php foreach ($recently_updated as $category): ?>
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <div class="bg-light rounded p-2 me-3">
                                            <i class="fas fa-tag text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= $category['name'] ?></div>
                                            <div class="text-muted small">
                                                Updated: <?= date('M d, Y H:i', strtotime($category['last_modified'])) ?>
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-book me-1"></i> <?= $category['item_count'] ?> items
                                                </span>
                                                <span class="badge <?= $category['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> ms-1">
                                                    <?= ucfirst($category['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No categories updated in the last 7 days</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="categoryModalLabel"><?= isset($edit_category) ? 'Edit Category' : 'Add New Category' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="<?= isset($edit_category) ? 'update_category' : 'add_category' ?>">
                        <?php if (isset($edit_category)): ?>
                            <input type="hidden" name="category_id" value="<?= $edit_category['category_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= $edit_category['name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= $edit_category['description'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= (isset($edit_category) && $edit_category['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (isset($edit_category) && $edit_category['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><?= isset($edit_category) ? 'Update Category' : 'Add Category' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle modal show events
        const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        // Show modal when edit button is clicked
        <?php if (isset($edit_category)): ?>
            categoryModal.show();
        <?php endif; ?>
        
        // Reset modal when closed
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            window.location.href = 'categories-manager.php';
        });
        
        // Initialize charts
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($category_distribution)) ?>,
                datasets: [{
                    label: 'Number of Items',
                    data: <?= json_encode(array_values($category_distribution)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
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
    });
    </script>
</body>
</html>