<?php
// Start session for user authentication
session_start();

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
    <title>Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #9b59b6;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--secondary), #1e5799);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .logo-text {
            font-weight: 700;
            color: white;
            font-size: 1.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            transition: all 0.3s;
            position: relative;
            padding: 10px 15px;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 0;
            height: 3px;
            background-color: var(--secondary);
            transition: width 0.3s;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }
        
        .search-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        
        .filters-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary);
        }
        
        .collapse-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .clear-filters {
            position: absolute;
            top: 15px;
            right: 120px;
        }
        
        .collapse-icon {
            transition: transform 0.3s;
        }
        
        .collapsed .collapse-icon {
            transform: rotate(180deg);
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-top: 20px;
            overflow: hidden;
        }
        
        .results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .results-table th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 12px 15px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .results-table tr {
            transition: background-color 0.2s;
        }
        
        .results-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .results-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
            cursor: pointer;
        }
        
        .results-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .title-cell {
            font-weight: 500;
            color: var(--primary);
        }
        
        .badge-type {
            background-color: var(--secondary);
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-available {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-archived {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        
        .action-cell {
            text-align: center;
            white-space: nowrap;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 5px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }
        
        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .btn-request {
            background-color: rgba(241, 196, 15, 0.1);
            color: #f39c12;
        }
        
        .material-icon {
            font-size: 1.2rem;
            margin-right: 5px;
        }
        
        .material-book { color: #3498db; }
        .material-magazine { color: #e74c3c; }
        .material-newspaper { color: #27ae60; }
        .material-journal { color: #9b59b6; }
        .material-manuscript { color: #f39c12; }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 20px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .detail-item {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-start;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--primary);
            min-width: 120px;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .authors-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .author-badge {
            background-color: #e0f7fa;
            color: #006064;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.85rem;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .page-link {
            color: var(--primary);
        }
        
        .results-count {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .pagination-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .book-cover {
            width: 100px;
            height: 150px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 15px;
        }
        
        .footer {
            background-color: var(--primary);
            color: white;
            padding: 30px 0;
            margin-top: 40px;
        }
        
        .footer a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .footer-logo i {
            margin-right: 10px;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-icons a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        
        .social-icons a:hover {
            background-color: var(--secondary);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="logo">
                    <i class="fas fa-book"></i>
                </div>
                <span class="logo-text">LibrarySystem</span>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="search.php"><i class="fas fa-search me-1"></i> Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="insert.php"><i class="fas fa-plus-circle me-1"></i> Insert Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-headset me-1"></i> Contact Developers</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="?logout=true">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout (<?= $_SESSION['username'] ?>)
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container py-4">
        <div class="search-container">
            <h2 class="mb-4 text-center"><i class="fas fa-search me-2"></i>Search Library Catalog</h2>
            
            <!-- Search Form -->
            <form id="searchForm" method="GET" action="search.php">
                <div class="input-group mb-4">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search by title, author, or ISBN..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
                
                <!-- Advanced Filters Section -->
                <div class="filters-section">
                    <h5 class="mb-3 d-flex align-items-center">
                        <i class="fas fa-filter me-2"></i>Advanced Filters
                        <button type="button" class="collapse-btn" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                            <i class="fas fa-chevron-up collapse-icon"></i> Toggle
                        </button>
                        <a href="search.php" class="btn btn-sm btn-outline-secondary clear-filters">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </a>
                    </h5>
                    
                    <div class="collapse show" id="advancedFilters">
                        <div class="row">
                            <!-- Column 1 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">Material Type</div>
                                    <select name="type" class="form-select">
                                        <option value="">All Types</option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?= $type ?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                                                <?= $type ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Publisher</div>
                                    <select name="publisher" class="form-select">
                                        <option value="">All Publishers</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?= $publisher ?>" <?= $publisher_filter === $publisher ? 'selected' : '' ?>>
                                                <?= $publisher ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Author</div>
                                    <select name="author" class="form-select">
                                        <option value="">All Authors</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?= $author ?>" <?= $author_filter === $author ? 'selected' : '' ?>>
                                                <?= $author ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 2 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">ISBN</div>
                                    <input type="text" name="isbn" class="form-control" 
                                           placeholder="Enter ISBN" 
                                           value="<?= htmlspecialchars($isbn_filter) ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">ISSN</div>
                                    <input type="text" name="issn" class="form-control" 
                                           placeholder="Enter ISSN" 
                                           value="<?= htmlspecialchars($issn_filter) ?>">
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Category</div>
                                    <select name="category" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category ?>" <?= $category_filter === $category ? 'selected' : '' ?>>
                                                <?= $category ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 3 -->
                            <div class="col-md-4">
                                <div class="filter-group">
                                    <div class="filter-label">Publication Year</div>
                                    <select name="year" class="form-select">
                                        <option value="">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>>
                                                <?= $year ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Language</div>
                                    <select name="language" class="form-select">
                                        <option value="">All Languages</option>
                                        <?php foreach ($languages as $language): ?>
                                            <option value="<?= $language ?>" <?= $language_filter === $language ? 'selected' : '' ?>>
                                                <?= $language ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <div class="filter-label">Added By</div>
                                    <select name="added_by" class="form-select">
                                        <option value="">All Users</option>
                                        <?php foreach ($added_by_users as $user): ?>
                                            <option value="<?= $user ?>" <?= $added_by_filter === $user ? 'selected' : '' ?>>
                                                <?= $user ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Column 4 -->
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <div class="filter-label">Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <div class="filter-label">Sort By</div>
                                    <select name="sort" class="form-select">
                                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Title (A-Z)</option>
                                        <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)</option>
                                        <option value="year" <?= $sort_by === 'year' ? 'selected' : '' ?>>Year (Oldest)</option>
                                        <option value="year_desc" <?= $sort_by === 'year_desc' ? 'selected' : '' ?>>Year (Newest)</option>
                                        <option value="added" <?= $sort_by === 'added' ? 'selected' : '' ?>>Added Date (Oldest)</option>
                                        <option value="added_desc" <?= $sort_by === 'added_desc' ? 'selected' : '' ?>>Added Date (Newest)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Results Section -->
            <div class="mt-4">
                <div class="pagination-info">
                    <div>
                        <i class="fas fa-books me-2"></i>
                        <?= $totalRecords ?> Material<?= $totalRecords !== 1 ? 's' : '' ?> Found
                        <small class="text-muted ms-2"><?= $search_query ? "Search: \"$search_query\"" : "All materials" ?></small>
                    </div>
                    <div>
                        Showing <?= min($recordsPerPage, count($materials)) ?> of <?= $totalRecords ?> items
                    </div>
                </div>
                
                <?php if (empty($materials)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No materials found</h4>
                        <p class="mb-0">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Authors</th>
                                        <th>Type</th>
                                        <th>Publisher</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $item): 
                                        $icon_class = '';
                                        if (strpos($item['type_name'], 'Book') !== false) $icon_class = 'material-book';
                                        if (strpos($item['type_name'], 'Magazine') !== false) $icon_class = 'material-magazine';
                                        if (strpos($item['type_name'], 'Newspaper') !== false) $icon_class = 'material-newspaper';
                                        if (strpos($item['type_name'], 'Journal') !== false) $icon_class = 'material-journal';
                                        if (strpos($item['type_name'], 'Manuscript') !== false) $icon_class = 'material-manuscript';
                                    ?>
                                        <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#detailModal" data-item-id="<?= $item['item_id'] ?>">
                                            <td><?= $item['item_id'] ?></td>
                                            <td class="title-cell"><?= htmlspecialchars($item['title']) ?></td>
                                            <td><?= $item['authors'] ? htmlspecialchars(substr($item['authors'], 0, 30)) . (strlen($item['authors']) > 30 ? '...' : '') : 'N/A' ?></td>
                                            <td>
                                                <span class="badge badge-type">
                                                    <i class="fas fa-book material-icon <?= $icon_class ?>"></i>
                                                    <?= htmlspecialchars($item['type_name']) ?>
                                                </span>
                                            </td>
                                            <td><?= $item['publisher_name'] ? htmlspecialchars(substr($item['publisher_name'], 0, 15)) . (strlen($item['publisher_name']) > 15 ? '...' : '') : 'N/A' ?></td>
                                            <td><?= $item['publication_year'] ? htmlspecialchars($item['publication_year']) : 'N/A' ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $item['status'] ?>">
                                                    <?= ucfirst($item['status']) ?>
                                                </span>
                                            </td>
                                            <td class="action-cell">
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <!-- Admin can edit/delete all items -->
                                                        <a href="edit.php?id=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit" onclick="event.stopPropagation()">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure?'); event.stopPropagation()">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- For non-admin users (Librarians) -->
                                                        <?php if ($item['added_by'] == $_SESSION['user_id']): ?>
                                                            <!-- User can edit/delete their own items -->
                                                            <a href="edit.php?id=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit" onclick="event.stopPropagation()">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="delete.php?id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure?'); event.stopPropagation()">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <!-- Request buttons for others' items -->
                                                            <button class="action-btn btn-request" title="Request Edit" 
                                                                    onclick="requestEdit(<?= $item['item_id'] ?>, event)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="action-btn btn-request" title="Request Delete" 
                                                                    onclick="requestDelete(<?= $item['item_id'] ?>, event)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination controls -->
                        <div class="pagination-controls">
                            <nav>
                                <ul class="pagination">
                                    <!-- Previous button -->
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Page numbers -->
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next button -->
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Material Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="book-cover">
                                <i class="fas fa-book"></i>
                            </div>
                            <h4 id="detailTitle" class="mb-3">Material Title</h4>
                            <div class="mb-4">
                                <span class="status-badge" id="detailStatus"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="detail-item">
                                <span class="detail-label">Authors:</span>
                                <div class="detail-content" id="detailAuthors"></div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Publisher:</span>
                                <span class="detail-content" id="detailPublisher"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Publication Year:</span>
                                <span class="detail-content" id="detailYear"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Language:</span>
                                <span class="detail-content" id="detailLanguage"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Edition:</span>
                                <span class="detail-content" id="detailEdition"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">ISBN:</span>
                                <span class="detail-content" id="detailISBN"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">ISSN:</span>
                                <span class="detail-content" id="detailISSN"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Category:</span>
                                <span class="detail-content" id="detailCategory"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Material Type:</span>
                                <span class="detail-content" id="detailType"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Added By:</span>
                                <span class="detail-content" id="detailAddedBy"></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Added Date:</span>
                                <span class="detail-content" id="detailAddedDate"></span>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Description:</h5>
                                <p id="detailDescription" class="card-text">No description available</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="modalActions">
                        <!-- Actions will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-logo">
                        <i class="fas fa-book"></i>
                        LibrarySystem
                    </div>
                    <p>Your premier digital library management solution. Access thousands of books, journals, and other resources from anywhere.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Home</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Search Books</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Insert Books</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Contact Us</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i> Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Contact Information</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Library Street, Bookville</li>
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope me-2"></i> info@librarysystem.com</li>
                        <li><i class="fas fa-clock me-2"></i> Mon-Fri: 9AM - 6PM</li>
                    </ul>
                </div>
            </div>
            
            <hr class="mt-4 mb-4" style="background-color: rgba(255,255,255,0.1);">
            
            <div class="text-center">
                <p class="mb-0">&copy; 2025 LibrarySystem. All rights reserved.</p>
                <p class="mt-2">Developed with <i class="fas fa-heart text-danger"></i> by Library Team</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Create materials map for quick lookup
        const materialsMap = {};
        <?php foreach ($materials as $item): ?>
            materialsMap[<?= $item['item_id'] ?>] = <?= json_encode($item) ?>;
        <?php endforeach; ?>
        
        // Auto-submit form when filters change
        document.querySelectorAll('select[name], input[name="search"]').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('searchForm').submit();
            });
        });
        
        // Request functions
        function requestEdit(itemId) {
            alert(`Edit request sent to admin for item ID: ${itemId}`);
            // In a real app, you would send this request to the server
        }
        
        function requestDelete(itemId) {
            alert(`Delete request sent to admin for item ID: ${itemId}`);
            // In a real app, you would send this request to the server
        }
        
        // Detail Modal Handling
        const detailModal = document.getElementById('detailModal');
        if (detailModal) {
            detailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const itemId = button.getAttribute('data-item-id');
                const item = materialsMap[itemId];
                
                if (item) {
                    // Update modal content
                    document.getElementById('detailModalLabel').textContent = `Details for ${item.title}`;
                    document.getElementById('detailTitle').textContent = item.title;
                    
                    // Authors
                    const authorsElement = document.getElementById('detailAuthors');
                    authorsElement.innerHTML = '';
                    if (item.authors) {
                        const authors = item.authors.split(', ');
                        const authorsList = document.createElement('div');
                        authorsList.className = 'authors-list';
                        
                        authors.forEach(author => {
                            const badge = document.createElement('span');
                            badge.className = 'author-badge';
                            badge.textContent = author;
                            authorsList.appendChild(badge);
                        });
                        
                        authorsElement.appendChild(authorsList);
                    } else {
                        authorsElement.textContent = 'N/A';
                    }
                    
                    // Set other details
                    document.getElementById('detailPublisher').textContent = item.publisher_name || 'N/A';
                    document.getElementById('detailYear').textContent = item.publication_year || 'N/A';
                    document.getElementById('detailLanguage').textContent = item.language || 'N/A';
                    document.getElementById('detailEdition').textContent = item.edition || 'N/A';
                    document.getElementById('detailISBN').textContent = item.isbn || 'N/A';
                    document.getElementById('detailISSN').textContent = item.issn || 'N/A';
                    document.getElementById('detailCategory').textContent = item.category_name || 'N/A';
                    
                    // Material Type with icon
                    let typeIconClass = '';
                    if (item.type_name.includes('Book')) typeIconClass = 'material-book';
                    if (item.type_name.includes('Magazine')) typeIconClass = 'material-magazine';
                    if (item.type_name.includes('Newspaper')) typeIconClass = 'material-newspaper';
                    if (item.type_name.includes('Journal')) typeIconClass = 'material-journal';
                    if (item.type_name.includes('Manuscript')) typeIconClass = 'material-manuscript';
                    
                    const typeElement = document.getElementById('detailType');
                    typeElement.innerHTML = `<i class="fas fa-book material-icon ${typeIconClass}"></i> ${item.type_name}`;
                    
                    // Status
                    const statusElement = document.getElementById('detailStatus');
                    statusElement.textContent = item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'N/A';
                    statusElement.className = 'status-badge';
                    statusElement.classList.add(`status-${item.status}`);
                    
                    document.getElementById('detailAddedBy').textContent = item.added_by_username || 'N/A';
                    document.getElementById('detailAddedDate').textContent = item.added_date ? new Date(item.added_date).toLocaleDateString() : 'N/A';
                    
                    // Description
                    document.getElementById('detailDescription').textContent = item.description || 'No description available';
                    
                    // Add action buttons to modal footer
                    const modalActions = document.getElementById('modalActions');
                    if (modalActions) {
                        modalActions.innerHTML = '';
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                modalActions.innerHTML = `
                                    <a href="edit.php?id=${item.item_id}" class="btn btn-primary me-2">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <a href="delete.php?id=${item.item_id}" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                `;
                            <?php else: ?>
                                <?php if ($item['added_by'] == $_SESSION['user_id']): ?>
                                    modalActions.innerHTML = `
                                        <a href="edit.php?id=${item.item_id}" class="btn btn-primary me-2">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <a href="delete.php?id=${item.item_id}" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                    `;
                                <?php else: ?>
                                    modalActions.innerHTML = `
                                        <button class="btn btn-warning me-2" onclick="requestEdit(${item.item_id})">
                                            <i class="fas fa-edit me-1"></i> Request Edit
                                        </button>
                                        <button class="btn btn-warning" onclick="requestDelete(${item.item_id})">
                                            <i class="fas fa-trash me-1"></i> Request Delete
                                        </button>
                                    `;
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    }
                }
            });
        }
        
        // Highlight row on hover
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(52, 152, 219, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
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