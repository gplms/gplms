<?php

/*
===============================================================================
  GPLMS (General Purpose Library Management System)
===============================================================================
  Project Repository : https://github.com/PanagiotisKotsorgios/gplms
  License            : MIT Licence
  Copyright          : (c) 2025 Panagiotis Kotsorgios, Fotis Markantonatos & Contributors
  Website            : [+]

  Description:
    GPLMS is a free and open-source Library Management System for schools,
    universities, and public libraries. It is built using PHP, HTML, JavaScript,
    and MySQL, and is designed to be modular, extensible, and easy to deploy.

  Created At:
    - SAEK MESOLOGHIOY [MESOLOGHI] [GREECE]
    - WEBSITE: [https://www.saekmesol.gr/]
            
  This File:
    - [authors-manager.php]
    - Purpose: [The page that is respoonsible for the full management of the authors registered in the system]

  Documentation:
    - Setup Guide         : https://github.com/PanagiotisKotsorgios/gplms/blob/main/README.md
    - User Guide          : https://github.com/PanagiotisKotsorgios/gplms/blob/main/docs/README.md

  Contributing:
    - Please see the contributing guide at 
      https://github.com/PanagiotisKotsorgios/gplms/blob/main/CONTRIBUTION.md

  License Notice:

    This project was originally created by students and independent open-source developers,
    not by a professional company. It is made for the community, by the community, in the
    spirit of open source and collective learning. Contributions, use, and sharing are
    greatelly encouraged!

    This program is free software: you can use, copy, modify, merge, publish,
    distribute, sublicense, and/or sell copies of it under the terms of the MIT License.
    See https://opensource.org/licenses/MIT for details.

    WARNING: This software is provided as-is, without any warranty of any kind.
    That means there are no guarantees, either express or implied, including but not limited to
    merchantability, fitness for a particular purpose, or non-infringement.
    The authors and contributors are not responsible for any issues, damages, or losses
    that may arise from using, modifying, or distributing this software. 
    You use this project entirely at your own risk.

    Thank you for using our software 😁💖
===============================================================================
*/



// Starting the session here
session_start();

// Check if user is admin
if (!isset($_SESSION['role'])) {
    // Redirect to login page
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    // Redirect ot search item page
    header("Location: search.php");
    exit;
}

//  configuration file containing constants and environment settings
require_once '../conf/config.php';

// Library's name fetching module from the database
require_once '../functions/fetch-lib-name.php';

// Translator module
require_once '../conf/translation.php';


// Add last_modified column to authors if needed
try {
    $pdo->query("SELECT last_modified FROM authors LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE authors ADD COLUMN last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}


// Function that Inserts the data
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
                    $success_msg = $lang['author_added_successfully'];
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'authors', sprintf($lang['log_added_author'], $_POST['name']));
                    break;
                    
                case 'update_author':
                    $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ? WHERE author_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['bio'] ?? '',
                        $_POST['author_id']
                    ]);
                    $success_msg = $lang['author_updated_successfully'];
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'authors', sprintf($lang['log_updated_author'], $_POST['name']));
                    break;
            }
            
            $pdo->commit();
            
            // Redirect after form submission
            header("Location: authors-manager.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = $lang['error'] . $e->getMessage();
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
                $error_msg = sprintf($lang['cannot_delete_author'], $item_count);
            } else {
                $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
                $stmt->execute([$id]);
                $success_msg = $lang['author_deleted_successfully'];
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'authors', sprintf($lang['log_deleted_author'], $id));
            }
            
            // Redirect after delete to the same pg
            header("Location: authors-manager.php");
            exit;
        } catch (Exception $e) {
            $error_msg = $lang['error_deleting_author'] . $e->getMessage();
        }
    }
}

// Handle edit author request
if (isset($_GET['edit_author'])) {
    // Store author ID in session for modal handling
    $_SESSION['edit_author_id'] = (int)$_GET['edit_author'];
    
    // Redirect to clear URL parameters
    header("Location: authors-manager.php");
    exit;
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

// Check if we have a stored edit author ID
$edit_author = null;
if (isset($_SESSION['edit_author_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->execute([$_SESSION['edit_author_id']]);
    $edit_author = $stmt->fetch();
    
    // Clear the session variable after use
    unset($_SESSION['edit_author_id']);
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




<!-- HTML STARTING POINT -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['page_title_authors_manager'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <!-- STYLE INCLUDES -->
    <link rel="stylesheet" href="../styles/components/sidebar1.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <link rel="stylesheet" href="../styles/authors.css">
    <link rel="stylesheet" href="../styles/components/sidebar.css">
</head>
<body>

<!-- Sidebar Component Include-->
<?php include '../components/sidebar1.php';?>

    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4><?= $lang['authors_manager'] ?></h4>
            <div>
                <span class="me-3"><?= $lang['welcome'] ?>, <?= $_SESSION['username'] ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> <?= $lang['logout'] ?>
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
                    <div class="stat-label"><?= $lang['total_authors'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['authors_with_items'] ?></div>
                    <div class="stat-label"><?= $lang['authors_with_items'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_by_authors'] ?></div>
                    <div class="stat-label"><?= $lang['items_by_authors'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon updated">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['recently_updated'] ?></div>
                    <div class="stat-label"><?= $lang['recently_updated'] ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-7">
                <div class="admin-card">
                    <div class="card-header">
                        <span><?= $lang['manage_authors'] ?></span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal">
                            <i class="fas fa-plus me-1"></i> <?= $lang['add_author'] ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th><?= $lang['id'] ?></th>
                                        <th><?= $lang['author_name'] ?></th>
                                        <th><?= $lang['bio'] ?></th>
                                        <th><?= $lang['items'] ?></th>
                                        <th><?= $lang['actions'] ?></th>
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
                                                <?= $author['bio'] ? substr($author['bio'], 0, 50) . '...' : $lang['na'] ?>
                                            </td>
                                            <td><?= $author['item_count'] ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_author=<?= $author['author_id'] ?>&page=<?= $page ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=author&id=<?= $author['author_id'] ?>&page=<?= $page ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" onclick="return confirm('<?= $lang['confirm_delete_author'] ?>')">
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
                                                <p><?= $lang['no_authors_found'] ?></p>
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
                        <span><?= $lang['top_authors'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="authorChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span><?= $lang['recently_updated_authors'] ?></span>
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
                                            <?= $lang['updated'] ?>: <?= date('M d, Y', strtotime($author['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $author['item_count'] ?> <?= $lang['items_plural'] ?>
                                            </span>
                                        </div>
                                        <div class="mt-1 small text-truncate" title="<?= htmlspecialchars($author['bio']) ?>">
                                            <?= $author['bio'] ? substr($author['bio'], 0, 80) . '...' : $lang['no_bio_available'] ?>
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
                <h2 class="author-name" id="detailAuthorName"><?= $lang['author_name'] ?></h2>
                <div class="author-id" id="detailAuthorId"><?= $lang['id'] ?>: 123</div>
            </div>
            <div class="author-body">
                <div class="author-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> <?= $lang['biography'] ?>
                    </div>
                    <div class="author-bio" id="detailAuthorBio">
                        <?= $lang['author_bio_placeholder'] ?>
                    </div>
                </div>
                
                <div class="author-stats">
                    <div class="stat-box">
                        <div class="stat-value" id="detailItemsCount">0</div>
                        <div class="stat-label"><?= $lang['items'] ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="detailLastModified"><?= $lang['today'] ?></div>
                        <div class="stat-label"><?= $lang['last_updated'] ?></div>
                    </div>
                </div>
            </div>
            <div class="author-footer">
                <button class="btn btn-sm btn-primary" id="editAuthorBtn">
                    <i class="fas fa-edit me-1"></i> <?= $lang['edit_author'] ?>
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
                        <?= $edit_author ? $lang['edit_author'] : $lang['add_new_author'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="<?= $edit_author ? 'update_author' : 'add_author' ?>">
                        <?php if ($edit_author): ?>
                            <input type="hidden" name="author_id" value="<?= $edit_author['author_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="authorName" class="form-label"><?= $lang['author_name'] ?></label>
                            <input type="text" class="form-control" id="authorName" name="name" 
                                   value="<?= $edit_author ? $edit_author['name'] : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="authorBio" class="form-label"><?= $lang['biography'] ?></label>
                            <textarea class="form-control" id="authorBio" name="bio" rows="5"><?= $edit_author ? $edit_author['bio'] : '' ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
                        <button type="submit" class="btn btn-primary">
                            <?= $edit_author ? $lang['update_author'] : $lang['add_author'] ?>
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
                        label: '<?= $lang['items_by_author'] ?>',
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
                                    return `${context.parsed.y} <?= $lang['items_plural'] ?>`;
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
                const authorBio = this.getAttribute('data-bio') || '<?= $lang['no_bio_available'] ?>';
                const itemsCount = this.getAttribute('data-items');
                const lastModified = this.getAttribute('data-modified');
                
                document.getElementById('detailAuthorName').textContent = authorName;
                document.getElementById('detailAuthorId').textContent = `<?= $lang['id'] ?>: ${authorId}`;
                document.getElementById('detailAuthorBio').textContent = authorBio;
                document.getElementById('detailItemsCount').textContent = itemsCount;
                document.getElementById('detailLastModified').textContent = lastModified;
                
                // Set edit button link
                document.getElementById('editAuthorBtn').onclick = function() {
                    window.location.href = `?edit_author=${authorId}&page=<?= $page ?>`;
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
                if (!confirm('<?= $lang['confirm_delete_author'] ?>')) {
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

     
    document.addEventListener('DOMContentLoaded', function () {
        var authorModal = document.getElementById('authorModal');
        if (authorModal) {
            authorModal.addEventListener('hidden.bs.modal', function () {
                // Always reload the page after the modal is closed to reset state
                window.location.href = 'authors-manager.php<?= $page > 1 ? '?page=' . $page : '' ?>';
            });
        }
    });

    </script>
</body>
</html>
