<?php
session_start();


// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';



require_once '../conf/translation.php';

require_once '../functions/fetch-lib-name.php';


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
    <title>GPLMS - Free & Open Source Project | Insert Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link href="../styles/insert.css" rel="stylesheet">
   <link href="../styles/components/header.css" rel="stylesheet">

   <link rel="icon" type="image/png" href="../../assets/logo-l.png">

</head>
<body>
   

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/header.php'; ?>
    
    <?php include '../components/insert-main-content.php'; ?>
    

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../components/insert-js.php'; ?>
</body>
</html>