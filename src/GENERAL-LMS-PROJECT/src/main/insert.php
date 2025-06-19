<?php
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

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: search.php");
    exit;
}

// Get data for dropdowns
$types = $pdo->query("SELECT * FROM material_types")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();
$authors = $pdo->query("SELECT * FROM authors")->fetchAll();

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['insert_type'])) {
        // Handle manual form submission
        try {
            $pdo->beginTransaction();
            
            // Insert new category if needed
            $category_id = $_POST['category_id'];
            if (!empty($_POST['new_category'])) {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$_POST['new_category']]);
                $category_id = $pdo->lastInsertId();
            }
            
            // Insert new publisher if needed
            $publisher_id = $_POST['publisher_id'];
            if (!empty($_POST['new_publisher'])) {
                $stmt = $pdo->prepare("INSERT INTO publishers (name) VALUES (?)");
                $stmt->execute([$_POST['new_publisher']]);
                $publisher_id = $pdo->lastInsertId();
            }
            
            // Insert new authors if needed
            $author_ids = [];
            if (!empty($_POST['new_author'])) {
                foreach ($_POST['new_author'] as $new_author) {
                    if (!empty($new_author)) {
                        $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                        $stmt->execute([$new_author]);
                        $author_ids[] = $pdo->lastInsertId();
                    }
                }
            }
            
            // Add selected authors
            if (!empty($_POST['author_ids'])) {
                foreach ($_POST['author_ids'] as $author_id) {
                    $author_ids[] = $author_id;
                }
            }
            
            // Insert library item
            $stmt = $pdo->prepare("
                INSERT INTO library_items (
                    title, type_id, category_id, publisher_id, language, 
                    publication_year, edition, isbn, issn, description, added_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['title'],
                $_POST['type_id'],
                $category_id ?: null, // Use new or existing category
                $publisher_id,
                $_POST['language'],
                $_POST['publication_year'] ?: null,
                $_POST['edition'] ?: null,
                $_POST['isbn'] ?: null,
                $_POST['issn'] ?: null,
                $_POST['description'] ?: null,
                $_SESSION['user_id']
            ]);
            
            $item_id = $pdo->lastInsertId();
            
            // Insert author relationships
            foreach ($author_ids as $author_id) {
                $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                $stmt->execute([$item_id, $author_id]);
            }
            
            $pdo->commit();
            
            // Refresh categories after adding new one
            $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
            
            $success_message = "Material added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error adding material: " . $e->getMessage();
        }
    } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        // Handle CSV upload
        $csv_file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($csv_file, "r");
        
        // Skip header
        fgetcsv($handle);
        
        try {
            $pdo->beginTransaction();
            $success_count = 0;
            $error_count = 0;
            $error_details = [];
            
            while (($data = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (count($data) == 0) continue;
                
                // Pad data array to ensure 11 elements
                $data = array_pad($data, 11, '');
                
                list($title, $type_name, $category_name, $publisher_name, $language, 
                     $publication_year, $edition, $isbn, $issn, $description, $authors) = $data;
                
                // Process material type (required)
                $type_id = null;
                $stmt = $pdo->prepare("SELECT type_id FROM material_types WHERE type_name = ?");
                $stmt->execute([trim($type_name)]);
                $type = $stmt->fetch();
                
                if ($type) {
                    $type_id = $type['type_id'];
                } else {
                    $error_details[] = "Invalid type: '$type_name' for '$title'";
                    $error_count++;
                    continue;
                }
                
                // Process category (optional)
                $category_id = null;
                $category_name = trim($category_name);
                if (!empty($category_name)) {
                    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ?");
                    $stmt->execute([$category_name]);
                    $category = $stmt->fetch();
                    
                    if ($category) {
                        $category_id = $category['category_id'];
                    } else {
                        // Create new category
                        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                        $stmt->execute([$category_name]);
                        $category_id = $pdo->lastInsertId();
                    }
                }
                
                // Process publisher (optional)
                $publisher_id = null;
                $publisher_name = trim($publisher_name);
                if (!empty($publisher_name)) {
                    $stmt = $pdo->prepare("SELECT publisher_id FROM publishers WHERE name = ?");
                    $stmt->execute([$publisher_name]);
                    $publisher = $stmt->fetch();
                    
                    if ($publisher) {
                        $publisher_id = $publisher['publisher_id'];
                    } else {
                        // Create new publisher
                        $stmt = $pdo->prepare("INSERT INTO publishers (name) VALUES (?)");
                        $stmt->execute([$publisher_name]);
                        $publisher_id = $pdo->lastInsertId();
                    }
                }
                
                // Insert library item
                $stmt = $pdo->prepare("
                    INSERT INTO library_items (
                        title, type_id, category_id, publisher_id, language, 
                        publication_year, edition, isbn, issn, description, added_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    trim($title),
                    $type_id,
                    $category_id ?: null,
                    $publisher_id ?: null,
                    trim($language),
                    is_numeric(trim($publication_year)) ? trim($publication_year) : null,
                    trim($edition) !== '' ? trim($edition) : null,
                    trim($isbn) !== '' ? trim($isbn) : null,
                    trim($issn) !== '' ? trim($issn) : null,
                    trim($description) !== '' ? trim($description) : null,
                    $_SESSION['user_id']
                ]);
                
                $item_id = $pdo->lastInsertId();
                
                // Process authors (optional)
                $author_ids = [];
                $author_names = !empty(trim($authors)) ? explode(';', trim($authors)) : [];
                
                foreach ($author_names as $author_name) {
                    $author_name = trim($author_name);
                    if (!empty($author_name)) {
                        // Check if author exists
                        $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
                        $stmt->execute([$author_name]);
                        $author = $stmt->fetch();
                        
                        if ($author) {
                            $author_ids[] = $author['author_id'];
                        } else {
                            // Insert new author
                            $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                            $stmt->execute([$author_name]);
                            $author_ids[] = $pdo->lastInsertId();
                        }
                    }
                }
                
                // Insert author relationships
                foreach ($author_ids as $author_id) {
                    $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                    $stmt->execute([$item_id, $author_id]);
                }
                
                $success_count++;
            }
            
            $pdo->commit();
            
            // Generate result message
            if ($error_count > 0) {
                $error_message = "CSV import completed with $success_count records added, but $error_count records failed.<br>";
                $error_message .= implode("<br>", $error_details);
            } else {
                $success_message = "CSV import successful! $success_count records added.";
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error during CSV import: " . $e->getMessage();
        }
        
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Materials - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
              :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .main-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        
        .section-title {
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: var(--primary);
        }
        
        .form-card {
            background-color: var(--light);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .toggle-section {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-section:hover {
            background-color: #1e2a38;
        }
        
        .toggle-section i {
            transition: transform 0.3s;
        }
        
        .toggle-section.collapsed i {
            transform: rotate(180deg);
        }
        
        .hidden-section {
            display: none;
        }
        
        .identifier-badge {
            background-color: var(--secondary);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 5px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-primary-bg {
            background-color: var(--secondary);
            color: white;
            border: none;
        }
        
        .btn-primary-bg:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-outline-bg {
            background-color: transparent;
            color: var(--secondary);
            border: 2px solid var(--secondary);
        }
        
        .btn-outline-bg:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .author-chip {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--dark);
            border-radius: 20px;
            padding: 5px 15px;
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .author-chip .remove {
            margin-left: 10px;
            cursor: pointer;
            color: var(--accent);
            font-weight: bold;
        }
        
        .new-field {
            background-color: rgba(46, 204, 113, 0.15);
            border-left: 3px solid #2ecc71;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        
        .csv-instructions {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary);
        }
        
        .csv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .csv-table th {
            background-color: var(--primary);
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        .csv-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .csv-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .success-alert {
            background-color: rgba(46, 204, 113, 0.15);
            border-left: 4px solid #2ecc71;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        
        .error-alert {
            background-color: rgba(231, 76, 60, 0.15);
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
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
                        <a class="nav-link" href="search.php"><i class="fas fa-search me-1"></i> Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="insert.php"><i class="fas fa-plus-circle me-1"></i> Insert Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="fas fa-headset me-1"></i> Contact Developers</a>
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
        <div class="main-container">
            <h1 class="text-center mb-4"><i class="fas fa-plus-circle me-2"></i>Add New Materials</h1>
            
            <?php if ($success_message): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- Toggle Section -->
            <div class="text-center mb-4">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary-bg active" id="formToggle">
                        <i class="fas fa-keyboard me-2"></i>Form Entry
                    </button>
                    <button type="button" class="btn btn-outline-bg" id="csvToggle">
                        <i class="fas fa-file-csv me-2"></i>CSV Upload
                    </button>
                </div>
            </div>
            
            <!-- Form Entry Section -->
            <div id="formSection">
                <form method="POST">
                    <input type="hidden" name="insert_type" value="manual">
                    
                    <div class="form-card">
                        <h3 class="section-title">Material Information</h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Material Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= $type['type_id'] ?>"><?= $type['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['category_id'] ?>"><?= $category['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Language <span class="text-danger">*</span></label>
                                <select name="language" class="form-select" required>
                                    <option value="EN">English</option>
                                    <option value="GR">Greek</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Publication Year</label>
                                <input type="number" name="publication_year" class="form-control" min="1000" max="<?= date('Y') ?>">
                            </div>
                        </div>
                        
                        <!-- NEW CATEGORY SECTION -->
                        <div class="toggle-section" onclick="toggleSection('newCategorySection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Category
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newCategorySection">
                            <div class="new-field">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="new_category" class="form-control">
                            </div>
                        </div>
                        <!-- END NEW CATEGORY SECTION -->
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Edition</label>
                                <input type="number" name="edition" class="form-control" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Identifiers</h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ISBN <span class="identifier-badge">For Books</span></label>
                                <input type="text" name="isbn" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">ISSN <span class="identifier-badge">For Periodicals</span></label>
                                <input type="text" name="issn" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Publisher</h3>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Publisher</label>
                            <select name="publisher_id" class="form-select">
                                <option value="">Select Publisher</option>
                                <?php foreach ($publishers as $publisher): ?>
                                    <option value="<?= $publisher['publisher_id'] ?>"><?= $publisher['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="toggle-section" onclick="toggleSection('newPublisherSection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Publisher
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newPublisherSection">
                            <div class="new-field">
                                <label class="form-label">Publisher Name</label>
                                <input type="text" name="new_publisher" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="section-title">Authors</h3>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Authors</label>
                            <select name="author_ids[]" class="form-select" multiple size="5">
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['author_id'] ?>"><?= $author['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple authors</small>
                        </div>
                        
                        <div class="toggle-section" onclick="toggleSection('newAuthorSection')">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Authors
                                <i class="fas fa-chevron-down float-end"></i>
                            </h5>
                        </div>
                        
                        <div class="hidden-section mt-3" id="newAuthorSection">
                            <div class="new-field">
                                <label class="form-label">Author 1</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <div class="new-field">
                                <label class="form-label">Author 2</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <div class="new-field">
                                <label class="form-label">Author 3</label>
                                <input type="text" name="new_author[]" class="form-control">
                            </div>
                            
                            <button type="button" class="btn btn-outline-bg" onclick="addAuthorField()">
                                <i class="fas fa-plus me-2"></i>Add Another Author
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary-bg btn-lg">
                            <i class="fas fa-save me-2"></i>Add Material
                        </button>
                    </div>
                </form>
            </div>
            
                      <!-- CSV Upload Section -->
            <div id="csvSection" class="hidden-section">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-card">
                        <h3 class="section-title">Upload CSV File</h3>
                        
                        <div class="csv-instructions">
                            <h5><i class="fas fa-info-circle me-2"></i>CSV Format Instructions</h5>
                            <p class="mb-0">Your CSV file should follow this format exactly. The first row should be headers.</p>
                        </div>
                        
                        <div class="table-responsive">
                        <!-- In the CSV Upload Section -->
<table class="csv-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Type Name</th>
            <th>Category Name</th>
            <th>Publisher Name</th>
            <th>Language</th>
            <th>Year</th>
            <th>Edition</th>
            <th>ISBN</th>
            <th>ISSN</th>
            <th>Description</th>
            <th>Authors (semicolon separated)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Introduction to Programming</td>
            <td>Book</td>
            <td>Computer Science</td>
            <td>Tech Publishers</td>
            <td>EN</td>
            <td>2022</td>
            <td>3</td>
            <td>1234567890123</td>
            <td></td>
            <td>Basic programming concepts</td>
            <td>John Smith;Jane Doe</td>
        </tr>
        <tr>
            <td>Science Monthly</td>
            <td>Magazine</td>
            <td>Science</td>
            <td>Science Press</td>
            <td>EN</td>
            <td>2023</td>
            <td></td>
            <td></td>
            <td>9876-543</td>
            <td>Monthly science magazine</td>
            <td>Editorial Team</td>
        </tr>
    </tbody>
</table>
                        </div>
                        
                        <div class="mt-4">
                            <label class="form-label">Select CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary-bg btn-lg">
                                <i class="fas fa-upload me-2"></i>Upload CSV
                            </button>
                            
                            <a href="#" class="btn btn-outline-bg btn-lg ms-2">
                                <i class="fas fa-download me-2"></i>Download Template
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
       <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Library Management System</h5>
                    <p>A comprehensive solution for managing library resources and materials.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="search.php" class="text-white">Search Books</a></li>
                        <li><a href="insert.php" class="text-white">Insert Books</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@librarysystem.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                &copy; 2023 Library Management System. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between form and CSV sections
        document.getElementById('formToggle').addEventListener('click', function() {
            document.getElementById('formSection').classList.remove('hidden-section');
            document.getElementById('csvSection').classList.add('hidden-section');
            this.classList.add('active');
            document.getElementById('csvToggle').classList.remove('active');
        });
        
        document.getElementById('csvToggle').addEventListener('click', function() {
            document.getElementById('csvSection').classList.remove('hidden-section');
            document.getElementById('formSection').classList.add('hidden-section');
            this.classList.add('active');
            document.getElementById('formToggle').classList.remove('active');
        });
        
        // Toggle expandable sections
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const toggleBtn = section.previousElementSibling;
            
            section.classList.toggle('hidden-section');
            toggleBtn.classList.toggle('collapsed');
        }
        
        // Add additional author fields
        function addAuthorField() {
            const container = document.getElementById('newAuthorSection');
            const count = container.querySelectorAll('.new-field').length + 1;
            
            const newField = document.createElement('div');
            newField.className = 'new-field';
            newField.innerHTML = `
                <label class="form-label">Author ${count}</label>
                <input type="text" name="new_author[]" class="form-control">
            `;
            
            container.insertBefore(newField, container.lastElementChild);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default active tab
            document.getElementById('formToggle').classList.add('active');
        });
    </script>
</body>
</html>