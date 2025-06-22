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

// Get authors with item counts
$authors = $pdo->query("
    SELECT a.*, COUNT(ia.item_id) AS item_count 
    FROM authors a
    LEFT JOIN item_authors ia ON a.author_id = ia.author_id
    GROUP BY a.author_id
")->fetchAll();

// Get items for editing
$edit_author = null;
if (isset($_GET['edit_author'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->execute([$_GET['edit_author']]);
    $edit_author = $stmt->fetch();
}

// Get statistics for dashboard
$stats = [
    'total_authors' => $pdo->query("SELECT COUNT(*) FROM authors")->fetchColumn(),
    'authors_with_items' => $pdo->query("SELECT COUNT(DISTINCT author_id) FROM item_authors")->fetchColumn(),
    'items_by_authors' => $pdo->query("SELECT COUNT(*) FROM item_authors")->fetchColumn(),
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM authors WHERE last_modified >= CURDATE() - INTERVAL 7 DAY")->fetchColumn()
];

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
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors Manager - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/authors-manager.css">
    <link rel="stylesheet" href="../styles/general/general-main-styles.css">
    <link rel="stylesheet" href="../styles/components/sidebar.css">
    <link rel="stylesheet" href="../styles/components/topbar.css">
    <link rel="stylesheet" href="../styles/components/stat-main.css">
    <link rel="stylesheet" href="../styles/responsive/responsive.css">
    
</head>
<body>
  
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         MAIN CONTENT COMPONENT
         - Core activity log interface
         - Contains filters, table, and pagination
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/authors-manager-main-content.php'; ?>
    
    <?php include '../components/author-modal.php'; ?>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   
      <?php include '../components/author-stats.php'; ?>
</body>
</html>