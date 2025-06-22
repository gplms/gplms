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
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE category_id = ?");
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
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM categories WHERE last_modified >= CURDATE() - INTERVAL 7 DAY")->fetchColumn()
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

// Get recently updated categories
$recently_updated = $pdo->query("
    SELECT c.*, COUNT(li.item_id) AS item_count 
    FROM categories c
    LEFT JOIN library_items li ON c.category_id = li.category_id
    WHERE c.last_modified >= CURDATE() - INTERVAL 7 DAY
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

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>

<?php include '../components/categories-main.php'; ?>
    
       <?php include '../components/categories-modal.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
          <?php include '../components/categories-manager-stats.php'; ?>
</body>
</html>