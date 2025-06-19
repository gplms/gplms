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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get item ID
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$item_id) {
    header("Location: search.php");
    exit;
}

// Get item details
$stmt = $pdo->prepare("
    SELECT li.*, u.username AS added_by_username
    FROM library_items li
    LEFT JOIN users u ON li.added_by = u.user_id
    WHERE li.item_id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: search.php");
    exit;
}

// Check permissions
$can_delete = false;
if ($_SESSION['role'] === 'Administrator') {
    $can_delete = true;
} elseif ($item['added_by'] == $_SESSION['user_id']) {
    $can_delete = true;
}

if (!$can_delete) {
    header("Location: search.php");
    exit;
}

// Handle deletion
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo->beginTransaction();
        
        // Delete author relationships
        $stmt = $pdo->prepare("DELETE FROM item_authors WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        // Delete the item
        $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        $pdo->commit();
        $success = true;
        
        // Log the deletion
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, target_object, details, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            "DELETE",
            "library_items",
            "Deleted item: {$item['title']} (ID: $item_id)",
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting material: " . $e->getMessage();
    }
}

// Redirect with status message
if ($success) {
    header("Location: search.php?success=Material+deleted+successfully");
} else {
    header("Location: search.php?error=" . urlencode($error ?: "Error deleting material"));
}
exit;