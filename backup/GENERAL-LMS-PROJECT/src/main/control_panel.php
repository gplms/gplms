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

// Add last_login column if it doesn't exist
try {
    $pdo->query("SELECT last_login FROM users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER created_at");
}

// Add username column to activity_logs if missing
try {
    $pdo->query("SELECT username FROM activity_logs LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE activity_logs ADD COLUMN username VARCHAR(255) AFTER user_id");
}

// Add status to library_items if missing
try {
    $pdo->query("SELECT status FROM library_items LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE library_items ADD COLUMN status ENUM('available', 'archived') NOT NULL DEFAULT 'available'");
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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $success_msg = "Settings updated successfully!";
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'system_settings', 'Updated system settings');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error updating settings: " . $e->getMessage();
    }
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                // User actions
                case 'add_user':
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role_id) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['username'],
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        $_POST['full_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['role_id']
                    ]);
                    $success_msg = "User added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'users', 'Added new user: '.$_POST['username']);
                    break;
                    
                case 'update_user':
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role_id = ? 
                                          WHERE user_id = ?");
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['full_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['role_id'],
                        $_POST['user_id']
                    ]);
                    $success_msg = "User updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'users', 'Updated user: '.$_POST['username']);
                    break;
                    
                // Role actions
                case 'add_role':
                    $stmt = $pdo->prepare("INSERT INTO roles (role_name) VALUES (?)");
                    $stmt->execute([$_POST['role_name']]);
                    $success_msg = "Role added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'roles', 'Added new role: '.$_POST['role_name']);
                    break;
                    
                case 'update_role':
                    $stmt = $pdo->prepare("UPDATE roles SET role_name = ? WHERE role_id = ?");
                    $stmt->execute([$_POST['role_name'], $_POST['role_id']]);
                    $success_msg = "Role updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'roles', 'Updated role: '.$_POST['role_name']);
                    break;
                    
                // Material actions
                case 'add_material':
                    $stmt = $pdo->prepare("INSERT INTO library_items (title, type_id, category_id, publisher_id, language, 
                                         publication_year, edition, isbn, issn, description, added_by) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['type_id'],
                        $_POST['category_id'] ?: null,
                        $_POST['publisher_id'] ?: null,
                        $_POST['language'],
                        $_POST['publication_year'] ?: null,
                        $_POST['edition'] ?: null,
                        $_POST['isbn'] ?: null,
                        $_POST['issn'] ?: null,
                        $_POST['description'] ?: null,
                        $_SESSION['user_id']
                    ]);
                    $item_id = $pdo->lastInsertId();
                    
                    // Handle authors
                    if (!empty($_POST['authors'])) {
                        foreach ($_POST['authors'] as $author_id) {
                            $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                            $stmt->execute([$item_id, $author_id]);
                        }
                    }
                    
                    $success_msg = "Material added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'library_items', 'Added material: '.$_POST['title']);
                    break;
                    
                case 'update_material':
                    $stmt = $pdo->prepare("UPDATE library_items SET title = ?, type_id = ?, category_id = ?, publisher_id = ?, 
                                          language = ?, publication_year = ?, edition = ?, isbn = ?, issn = ?, description = ?
                                          WHERE item_id = ?");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['type_id'],
                        $_POST['category_id'] ?: null,
                        $_POST['publisher_id'] ?: null,
                        $_POST['language'],
                        $_POST['publication_year'] ?: null,
                        $_POST['edition'] ?: null,
                        $_POST['isbn'] ?: null,
                        $_POST['issn'] ?: null,
                        $_POST['description'] ?: null,
                        $_POST['item_id']
                    ]);
                    
                    // Update authors
                    $pdo->prepare("DELETE FROM item_authors WHERE item_id = ?")->execute([$_POST['item_id']]);
                    if (!empty($_POST['authors'])) {
                        foreach ($_POST['authors'] as $author_id) {
                            $stmt = $pdo->prepare("INSERT INTO item_authors (item_id, author_id) VALUES (?, ?)");
                            $stmt->execute([$_POST['item_id'], $author_id]);
                        }
                    }
                    
                    $success_msg = "Material updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'library_items', 'Updated material: '.$_POST['title']);
                    break;
                    
                // Category actions
                case 'add_category':
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$_POST['name']]);
                    $success_msg = "Category added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'categories', 'Added category: '.$_POST['name']);
                    break;
                    
                case 'update_category':
                    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['category_id']]);
                    $success_msg = "Category updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'categories', 'Updated category: '.$_POST['name']);
                    break;
                    
                // Publisher actions
                case 'add_publisher':
                    $stmt = $pdo->prepare("INSERT INTO publishers (name, contact_info) VALUES (?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['contact_info']]);
                    $success_msg = "Publisher added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'publishers', 'Added publisher: '.$_POST['name']);
                    break;
                    
                case 'update_publisher':
                    $stmt = $pdo->prepare("UPDATE publishers SET name = ?, contact_info = ? WHERE publisher_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['contact_info'], $_POST['publisher_id']]);
                    $success_msg = "Publisher updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'publishers', 'Updated publisher: '.$_POST['name']);
                    break;
                    
                // Author actions
                case 'add_author':
                    $stmt = $pdo->prepare("INSERT INTO authors (name, bio) VALUES (?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['bio']]);
                    $success_msg = "Author added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'authors', 'Added author: '.$_POST['name']);
                    break;
                    
                case 'update_author':
                    $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ? WHERE author_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['bio'], $_POST['author_id']]);
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
    
    if ($id) {
        try {
            switch ($entity) {
                case 'user':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "User deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'users', 'Deleted user ID: '.$id);
                    break;
                    
                case 'role':
                    $stmt = $pdo->prepare("DELETE FROM roles WHERE role_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Role deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'roles', 'Deleted role ID: '.$id);
                    break;
                    
                case 'material':
                    $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Material deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'library_items', 'Deleted material ID: '.$id);
                    break;
                    
                case 'category':
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Category deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'categories', 'Deleted category ID: '.$id);
                    break;
                    
                case 'publisher':
                    $stmt = $pdo->prepare("DELETE FROM publishers WHERE publisher_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Publisher deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'publishers', 'Deleted publisher ID: '.$id);
                    break;
                    
                case 'author':
                    $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
                    $stmt->execute([$id]);
                    $success_msg = "Author deleted successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'authors', 'Deleted author ID: '.$id);
                    break;
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting $entity: " . $e->getMessage();
        }
    }
}

// Get statistics for dashboard
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'library_items' => $pdo->query("SELECT COUNT(*) FROM library_items")->fetchColumn(),
    'authors' => $pdo->query("SELECT COUNT(*) FROM authors")->fetchColumn(),
    'activity_logs' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(),
    'recent_activity' => $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE timestamp >= CURDATE() - INTERVAL 7 DAY")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login >= NOW() - INTERVAL 15 MINUTE")->fetchColumn()
];

// Get chart data
$material_distribution = $pdo->query("
    SELECT mt.type_name, COUNT(li.item_id) AS count 
    FROM material_types mt
    LEFT JOIN library_items li ON mt.type_id = li.type_id
    GROUP BY mt.type_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$language_distribution = $pdo->query("
    SELECT language, COUNT(*) as count 
    FROM library_items 
    GROUP BY language
")->fetchAll(PDO::FETCH_KEY_PAIR);

$yearly_publications = $pdo->query("
    SELECT publication_year, COUNT(*) as count 
    FROM library_items 
    WHERE publication_year IS NOT NULL
    GROUP BY publication_year
    ORDER BY publication_year DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get all data for dashboard
$users = $pdo->query("SELECT * FROM users")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$material_types = $pdo->query("SELECT * FROM material_types")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();
$authors = $pdo->query("SELECT * FROM authors")->fetchAll();
$library_items = $pdo->query("SELECT * FROM library_items")->fetchAll();
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll();

// Activity logs pagination
$logs_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Count total activity logs
$total_logs = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$total_pages = ceil($total_logs / $logs_per_page);

// Fetch activity logs for current page
$offset = ($current_page - 1) * $logs_per_page;
$activity_logs = $pdo->prepare("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
$activity_logs->bindValue(':limit', $logs_per_page, PDO::PARAM_INT);
$activity_logs->bindValue(':offset', $offset, PDO::PARAM_INT);
$activity_logs->execute();
$activity_logs = $activity_logs->fetchAll();

// Recent activity for dashboard
$recent_activity_logs = $pdo->query("SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 5")->fetchAll();

// Prepare settings array
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

// Get items for editing
$edit_user = null;
$edit_role = null;
$edit_material = null;
$edit_category = null;
$edit_publisher = null;
$edit_author = null;

if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_GET['edit_user']]);
    $edit_user = $stmt->fetch();
}

if (isset($_GET['edit_role'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->execute([$_GET['edit_role']]);
    $edit_role = $stmt->fetch();
}

if (isset($_GET['edit_material'])) {
    $stmt = $pdo->prepare("SELECT * FROM library_items WHERE item_id = ?");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material = $stmt->fetch();
    
    // Get authors for this material
    $stmt = $pdo->prepare("SELECT author_id FROM item_authors WHERE item_id = ?");
    $stmt->execute([$_GET['edit_material']]);
    $edit_material['authors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (isset($_GET['edit_category'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$_GET['edit_category']]);
    $edit_category = $stmt->fetch();
}

if (isset($_GET['edit_publisher'])) {
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE publisher_id = ?");
    $stmt->execute([$_GET['edit_publisher']]);
    $edit_publisher = $stmt->fetch();
}

if (isset($_GET['edit_author'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->execute([$_GET['edit_author']]);
    $edit_author = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../styles/control_panel-styles.css">
   
    <style>
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>

    <?php include '../components/control-panel-main-content.php'; ?>
    
    <!-- Modals (User, Role, Material, Category, Publisher, Author) -->
    <!-- ... (All modal code remains unchanged from your original) ... -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

      <?php include '../components/control-panel-stats.php'; ?>
</body>
</html>