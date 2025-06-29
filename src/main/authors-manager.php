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

// Add last_modified column to authors if needed
try {
    $pdo->query("SELECT last_modified FROM authors LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE authors ADD COLUMN last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
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

// Handle author actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                case 'add_author':
                    $stmt = $pdo->prepare("INSERT INTO authors (name, bio) VALUES (?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['bio'] ?? ''
                    ]);
                    $success_msg = "Author added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'authors', 'Added author: '.$_POST['name']);
                    break;
                    
                case 'update_author':
                    $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ? WHERE author_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['bio'] ?? '',
                        $_POST['author_id']
                    ]);
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
    
    if ($id && $entity === 'author') {
        try {
            // Check if author is used in any items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM item_authors WHERE author_id = ?");
            $stmt->execute([$id]);
            $item_count = $stmt->fetchColumn();
            
            if ($item_count > 0) {
                $error_msg = "Cannot delete author because it is used in $item_count items!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Author deleted successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'authors', 'Deleted author ID: '.$id);
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting author: " . $e->getMessage();
        }
    }
}

// PAGINATION MUST COME BEFORE STATS CALCULATION
// Pagination Configuration
$perPage = 10; // Authors per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Get total authors count
$totalAuthors = $pdo->query("SELECT COUNT(*) FROM authors")->fetchColumn();
$totalPages = ceil($totalAuthors / $perPage);

// Validate page number
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Fetch authors with pagination
$stmt = $pdo->prepare("
    SELECT a.author_id, a.name, a.bio, a.last_modified, 
           COUNT(ia.item_id) as item_count 
    FROM authors a 
    LEFT JOIN item_authors ia ON a.author_id = ia.author_id 
    GROUP BY a.author_id 
    ORDER BY a.name 
    LIMIT :offset, :perPage
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$paginatedAuthors = $stmt->fetchAll();

// Get statistics for dashboard - MUST COME AFTER PAGINATION QUERY
$stats = [
    'total_authors' => $pdo->query("SELECT COUNT(*) FROM authors")->fetchColumn(),
    'authors_with_items' => $pdo->query("SELECT COUNT(DISTINCT author_id) FROM item_authors")->fetchColumn(),
    'items_by_authors' => $pdo->query("SELECT COUNT(*) FROM item_authors")->fetchColumn(),
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM authors WHERE last_modified >= CURDATE() - INTERVAL 7 DAY")->fetchColumn()
];

// Get items for editing
$edit_author = null;
if (isset($_GET['edit_author'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->execute([$_GET['edit_author']]);
    $edit_author = $stmt->fetch();
}

// Get chart data
$author_distribution = $pdo->query("
    SELECT a.name, COUNT(ia.item_id) AS count 
    FROM authors a
    LEFT JOIN item_authors ia ON a.author_id = ia.author_id
    GROUP BY a.author_id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recently updated authors
$recently_updated = $pdo->query("
    SELECT a.*, COUNT(ia.item_id) AS item_count 
    FROM authors a
    LEFT JOIN item_authors ia ON a.author_id = ia.author_id
    WHERE a.last_modified >= CURDATE() - INTERVAL 7 DAY
    GROUP BY a.author_id
    ORDER BY a.last_modified DESC
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Authors Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/component/components/sidebar.php">
            <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
    
        
     .topbar {
    background: white;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1600px;
    margin-left: auto; /* Pushes the element to the right */
    margin-right: 20px; /* Add space from the right edge */
}

        
        .topbar h4 {
            margin: 0;
            color: var(--dark-color);
            
        }
        
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
                max-width: 800px;
    margin-left: auto; /* Pushes the element to the right */
    margin-right: 20px; /* Add space from the right edge */
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--dark-color);
            
        }
        
        .card-body {
            padding: 20px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
                max-width: 1600px;
    margin-left: auto; /* Pushes the element to the right */
    margin-right: 20px; /* Add space from the right edge */
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 15px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .stat-icon.authors { background: rgba(78, 115, 223, 0.2); color: var(--primary-color); }
        .stat-icon.active { background: rgba(28, 200, 138, 0.2); color: var(--success-color); }
        .stat-icon.items { background: rgba(54, 185, 204, 0.2); color: var(--info-color); }
        .stat-icon.updated { background: rgba(246, 194, 62, 0.2); color: var(--warning-color); }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .stat-label {
            color: var(--secondary-color);
            font-size: 0.85rem;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th, 
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .admin-table th {
            background-color: #f8f9fc;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .admin-table tr {
            transition: background-color 0.2s;
        }
        
        .admin-table tr:hover {
            background-color: #f8f9fc;
            cursor: pointer;
        }
        
        .bio-preview {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            transition: all 0.2s;
        }
        
        .btn-edit {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .btn-edit:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-delete {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .btn-delete:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            padding: 10px 0;
        }
        
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 5px;
        }
        
        .page-item {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-link {
            display: block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #4e73df;
            background-color: white;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .page-link:hover {
            background-color: #f8f9fc;
            border-color: #d1d3e2;
        }
        
        .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
            color: white;
        }
        
        .page-item.disabled .page-link {
            color: #b7b9cc;
            pointer-events: none;
        }
        
        .page-ellipsis {
            padding: 8px 12px;
            color: #6e707e;
        }
        
        /* Author Detail Modal */
        .author-detail-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }
        
        .author-detail-card {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
        }
        
        .author-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .author-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
        }
        
        .author-name {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .author-id {
            text-align: center;
            opacity: 0.8;
            font-size: 14px;
        }
        
        .author-body {
            padding: 25px;
        }
        
        .author-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
        }
        
        .author-bio {
            line-height: 1.6;
            color: #555;
        }
        
        .author-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 13px;
            color: #6e707e;
        }
        
        .author-footer {
            padding: 15px 25px;
            border-top: 1px solid #e3e6f0;
            display: flex;
            justify-content: flex-end;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close-modal:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }

        
    </style>
    <link rel="stylesheet" href="../styles/components/sidebar.css">

</head>
<body>

<?php include '../components/sidebar.php';?>

    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Authors Manager</h4>
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
                <div class="stat-icon authors">
                    <i class="fas fa-feather"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_authors'] ?></div>
                    <div class="stat-label">Total Authors</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['authors_with_items'] ?></div>
                    <div class="stat-label">Authors with Items</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_by_authors'] ?></div>
                    <div class="stat-label">Items by Authors</div>
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
                        <span>Manage Authors</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal">
                            <i class="fas fa-plus me-1"></i> Add Author
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Author Name</th>
                                        <th>Bio</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paginatedAuthors as $author): ?>
                                        <tr class="author-row" 
                                            data-id="<?= $author['author_id'] ?>" 
                                            data-name="<?= htmlspecialchars($author['name']) ?>" 
                                            data-bio="<?= htmlspecialchars($author['bio']) ?>" 
                                            data-items="<?= $author['item_count'] ?>" 
                                            data-modified="<?= date('M d, Y', strtotime($author['last_modified'])) ?>">
                                            <td><?= $author['author_id'] ?></td>
                                            <td><?= $author['name'] ?></td>
                                            <td class="bio-preview" title="<?= htmlspecialchars($author['bio']) ?>">
                                                <?= $author['bio'] ? substr($author['bio'], 0, 50) . '...' : 'N/A' ?>
                                            </td>
                                            <td><?= $author['item_count'] ?></td>
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
                                    
                                    <?php if (count($paginatedAuthors) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                                <p>No authors found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-container">
                            <ul class="pagination">
                                <!-- Previous Button -->
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- First Page -->
                                <?php if ($page > 3): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                    </li>
                                    <?php if ($page > 4): ?>
                                        <li class="page-item"><span class="page-ellipsis">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Page Numbers -->
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" 
                                           href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Last Page -->
                                <?php if ($page < $totalPages - 2): ?>
                                    <?php if ($page < $totalPages - 3): ?>
                                        <li class="page-item"><span class="page-ellipsis">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                                            <?= $totalPages ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next Button -->
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Top Authors</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="authorChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Authors</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recently_updated as $author): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-user text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= $author['name'] ?></div>
                                        <div class="text-muted small">
                                            Updated: <?= date('M d, Y', strtotime($author['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $author['item_count'] ?> items
                                            </span>
                                        </div>
                                        <div class="mt-1 small text-truncate" title="<?= htmlspecialchars($author['bio']) ?>">
                                            <?= $author['bio'] ? substr($author['bio'], 0, 80) . '...' : 'No bio available' ?>
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
    
    <!-- Author Detail Modal -->
    <div class="author-detail-modal" id="authorDetailModal">
        <div class="author-detail-card">
            <div class="author-header">
                <div class="close-modal" id="closeDetailModal">
                    <i class="fas fa-times"></i>
                </div>
                <div class="author-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="author-name" id="detailAuthorName">Author Name</h2>
                <div class="author-id" id="detailAuthorId">ID: 123</div>
            </div>
            <div class="author-body">
                <div class="author-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> Biography
                    </div>
                    <div class="author-bio" id="detailAuthorBio">
                        This is where the author's biography will appear. 
                        If the author has no biography, a placeholder will be shown.
                    </div>
                </div>
                
                <div class="author-stats">
                    <div class="stat-box">
                        <div class="stat-value" id="detailItemsCount">0</div>
                        <div class="stat-label">Items</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="detailLastModified">Today</div>
                        <div class="stat-label">Last Updated</div>
                    </div>
                </div>
            </div>
            <div class="author-footer">
                <button class="btn btn-sm btn-primary" id="editAuthorBtn">
                    <i class="fas fa-edit me-1"></i> Edit Author
                </button>
            </div>
        </div>
    </div>
    
    <!-- Author Modal -->
    <div class="modal fade" id="authorModal" tabindex="-1" aria-labelledby="authorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="authorModalLabel">
                        <?= $edit_author ? 'Edit Author' : 'Add New Author' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="<?= $edit_author ? 'update_author' : 'add_author' ?>">
                        <?php if ($edit_author): ?>
                            <input type="hidden" name="author_id" value="<?= $edit_author['author_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="authorName" class="form-label">Author Name</label>
                            <input type="text" class="form-control" id="authorName" name="name" 
                                   value="<?= $edit_author ? $edit_author['name'] : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="authorBio" class="form-label">Biography</label>
                            <textarea class="form-control" id="authorBio" name="bio" rows="5"><?= $edit_author ? $edit_author['bio'] : '' ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <?= $edit_author ? 'Update Author' : 'Add Author' ?>
                        </button>
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
            // Author Distribution Chart
            const authorCtx = document.getElementById('authorChart').getContext('2d');
            const authorLabels = <?= json_encode(array_keys($author_distribution)) ?>;
            const authorData = <?= json_encode(array_values($author_distribution)) ?>;
            
            const authorChart = new Chart(authorCtx, {
                type: 'bar',
                data: {
                    labels: authorLabels,
                    datasets: [{
                        label: 'Items by Author',
                        data: authorData,
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
            <?php if ($edit_author): ?>
                const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
                authorModal.show();
            <?php endif; ?>
        });
        
        // Author Detail Modal Functionality
        const detailModal = document.getElementById('authorDetailModal');
        const closeDetailModal = document.getElementById('closeDetailModal');
        const authorRows = document.querySelectorAll('.author-row');
        
        // Show author detail modal
        authorRows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Prevent opening if user clicked on action buttons
                if (e.target.closest('.action-btns')) return;
                
                const authorId = this.getAttribute('data-id');
                const authorName = this.getAttribute('data-name');
                const authorBio = this.getAttribute('data-bio') || 'No biography available';
                const itemsCount = this.getAttribute('data-items');
                const lastModified = this.getAttribute('data-modified');
                
                document.getElementById('detailAuthorName').textContent = authorName;
                document.getElementById('detailAuthorId').textContent = `ID: ${authorId}`;
                document.getElementById('detailAuthorBio').textContent = authorBio;
                document.getElementById('detailItemsCount').textContent = itemsCount;
                document.getElementById('detailLastModified').textContent = lastModified;
                
                // Set edit button link
                document.getElementById('editAuthorBtn').onclick = function() {
                    window.location.href = `?edit_author=${authorId}`;
                };
                
                detailModal.style.display = 'flex';
            });
        });
        
        // Close modal
        closeDetailModal.addEventListener('click', function() {
            detailModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        detailModal.addEventListener('click', function(e) {
            if (e.target === detailModal) {
                detailModal.style.display = 'none';
            }
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this author?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Show full bio on hover
        const bioPreviews = document.querySelectorAll('.bio-preview');
        bioPreviews.forEach(preview => {
            preview.addEventListener('mouseover', function() {
                this.style.whiteSpace = 'normal';
                this.style.overflow = 'visible';
                this.style.textOverflow = 'clip';
            });
            
            preview.addEventListener('mouseout', function() {
                this.style.whiteSpace = 'nowrap';
                this.style.overflow = 'hidden';
                this.style.textOverflow = 'ellipsis';
            });
        });
    </script>
</body>
</html>