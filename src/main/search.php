<?php
// Start session for user authentication
session_start();

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $password == $user['password']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = ($username == 'admin_user') ? 'admin' : 'librarian';
    } else {
        $login_error = "Invalid username or password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Pagination settings
$recordsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Fetch data for search page
$materials = [];
$totalRecords = 0;
$search_query = "";
$type_filter = "";
$publisher_filter = "";
$year_filter = "";
$author_filter = "";
$isbn_filter = "";
$issn_filter = "";
$category_filter = "";
$language_filter = "";
$status_filter = "";
$added_by_filter = "";
$sort_by = "title";

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $search_query = $_GET['search'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $publisher_filter = $_GET['publisher'] ?? '';
    $year_filter = $_GET['year'] ?? '';
    $author_filter = $_GET['author'] ?? '';
    $isbn_filter = $_GET['isbn'] ?? '';
    $issn_filter = $_GET['issn'] ?? '';
    $category_filter = $_GET['category'] ?? '';
    $language_filter = $_GET['language'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $added_by_filter = $_GET['added_by'] ?? '';
    $sort_by = $_GET['sort'] ?? 'title';
    
    // Build the query for count
    $countSql = "SELECT COUNT(DISTINCT li.item_id) AS total
                FROM library_items li
                LEFT JOIN material_types mt ON li.type_id = mt.type_id
                LEFT JOIN publishers p ON li.publisher_id = p.publisher_id
                LEFT JOIN categories c ON li.category_id = c.category_id
                LEFT JOIN users u ON li.added_by = u.user_id
                LEFT JOIN item_authors ia ON li.item_id = ia.item_id
                LEFT JOIN authors a ON ia.author_id = a.author_id
                WHERE 1=1";
    
    // Build the query for data
    $sql = "SELECT 
                li.item_id, li.title, li.language, li.publication_year, 
                li.edition, li.isbn, li.issn, li.added_by, li.description,
                li.status, li.added_date,
                mt.type_name, p.name AS publisher_name, c.name AS category_name,
                u.username AS added_by_username,
                GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
            FROM library_items li
            LEFT JOIN material_types mt ON li.type_id = mt.type_id
            LEFT JOIN publishers p ON li.publisher_id = p.publisher_id
            LEFT JOIN categories c ON li.category_id = c.category_id
            LEFT JOIN users u ON li.added_by = u.user_id
            LEFT JOIN item_authors ia ON li.item_id = ia.item_id
            LEFT JOIN authors a ON ia.author_id = a.author_id
            WHERE 1=1";
    
    $params = [];
    $countParams = [];
    
    // Apply filters
    if (!empty($search_query)) {
        $sql .= " AND (li.title LIKE ?)";
        $countSql .= " AND (li.title LIKE ?)";
        $params[] = "%$search_query%";
        $countParams[] = "%$search_query%";
    }
    
    if (!empty($type_filter)) {
        $sql .= " AND mt.type_name = ?";
        $countSql .= " AND mt.type_name = ?";
        $params[] = $type_filter;
        $countParams[] = $type_filter;
    }
    
    if (!empty($publisher_filter)) {
        $sql .= " AND p.name LIKE ?";
        $countSql .= " AND p.name LIKE ?";
        $params[] = "%$publisher_filter%";
        $countParams[] = "%$publisher_filter%";
    }
    
    if (!empty($year_filter)) {
        $sql .= " AND li.publication_year = ?";
        $countSql .= " AND li.publication_year = ?";
        $params[] = $year_filter;
        $countParams[] = $year_filter;
    }
    
    if (!empty($author_filter)) {
        $sql .= " AND a.name LIKE ?";
        $countSql .= " AND a.name LIKE ?";
        $params[] = "%$author_filter%";
        $countParams[] = "%$author_filter%";
    }
    
    if (!empty($isbn_filter)) {
        $sql .= " AND li.isbn LIKE ?";
        $countSql .= " AND li.isbn LIKE ?";
        $params[] = "%$isbn_filter%";
        $countParams[] = "%$isbn_filter%";
    }
    
    if (!empty($issn_filter)) {
        $sql .= " AND li.issn LIKE ?";
        $countSql .= " AND li.issn LIKE ?";
        $params[] = "%$issn_filter%";
        $countParams[] = "%$issn_filter%";
    }
    
    if (!empty($category_filter)) {
        $sql .= " AND c.name = ?";
        $countSql .= " AND c.name = ?";
        $params[] = $category_filter;
        $countParams[] = $category_filter;
    }
    
    if (!empty($language_filter)) {
        $sql .= " AND li.language = ?";
        $countSql .= " AND li.language = ?";
        $params[] = $language_filter;
        $countParams[] = $language_filter;
    }
    
    if (!empty($status_filter)) {
        $sql .= " AND li.status = ?";
        $countSql .= " AND li.status = ?";
        $params[] = $status_filter;
        $countParams[] = $status_filter;
    }
    
    if (!empty($added_by_filter)) {
        $sql .= " AND u.username = ?";
        $countSql .= " AND u.username = ?";
        $params[] = $added_by_filter;
        $countParams[] = $added_by_filter;
    }
    
    $sql .= " GROUP BY li.item_id";
    
    // Apply sorting
    $sort_options = [
        'title' => 'li.title ASC',
        'title_desc' => 'li.title DESC',
        'year' => 'li.publication_year ASC',
        'year_desc' => 'li.publication_year DESC',
        'added' => 'li.added_date ASC',
        'added_desc' => 'li.added_date DESC'
    ];
    
    $sql .= " ORDER BY " . ($sort_options[$sort_by] ?? 'li.title ASC');
    
    // Add pagination
    $sql .= " LIMIT $recordsPerPage OFFSET $offset";
    
    // Execute count query
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $countResult = $countStmt->fetch();
    $totalRecords = $countResult['total'];
    
    // Calculate total pages
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Execute data query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();
}

// Get filter options
$types = $pdo->query("SELECT type_name FROM material_types")->fetchAll(PDO::FETCH_COLUMN, 0);
$publishers = $pdo->query("SELECT name FROM publishers")->fetchAll(PDO::FETCH_COLUMN, 0);
$years = $pdo->query("SELECT DISTINCT publication_year FROM library_items ORDER BY publication_year DESC")->fetchAll(PDO::FETCH_COLUMN, 0);
$categories = $pdo->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN, 0);
$authors = $pdo->query("SELECT name FROM authors")->fetchAll(PDO::FETCH_COLUMN, 0);
$languages = $pdo->query("SELECT DISTINCT language FROM library_items")->fetchAll(PDO::FETCH_COLUMN, 0);
$statuses = ['available', 'archived'];
$added_by_users = $pdo->query("SELECT username FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Search </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../styles/search.css" rel="stylesheet">
    <link href="../styles/components/header.css" rel="stylesheet">
   
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
  
   <?php include '../components/header.php'; ?>
    
   <?php include '../components/search-main.php'; ?>
    
   <?php include '../components/search-detail.php'; ?>
    
    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login to Library System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger"><?= $login_error ?></div>
                        <?php endif; ?>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


      <?php include '../components/search-func.php'; ?>
</body>
</html>

<?php
// Function to build pagination URL with parameters
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'search.php?' . http_build_query($params);
}
?>