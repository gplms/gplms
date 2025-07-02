<?php
session_start();
require_once '../conf/config.php';
require_once '../conf/translation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$item_id) {
    header("Location: search.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT li.*, u.username AS added_by_username, u.user_id AS creator_id
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
$is_admin = ($_SESSION['role'] === 'Administrator');
$is_creator = ($item['creator_id'] == $_SESSION['user_id']);

if ($is_admin || ($_SESSION['role'] === 'Librarian' && $is_creator)) {
    $can_delete = true;
}

if (!$can_delete) {
    // Redirect to request_action.php for delete request
    header("Location: request_action.php?action=delete&id=$item_id");
    exit;
}

// Handle deletion if user has permission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM item_authors WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        $stmt = $pdo->prepare("DELETE FROM library_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        
        $pdo->commit();
        $success = true;
        
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

if ($success) {
    header("Location: search.php?success=" . urlencode($lang['material_deleted_successfully'] ?? 'Material deleted successfully'));
} else {
    header("Location: search.php?error=" . urlencode($error ?: ($lang['error_deleting_material'] ?? 'Error deleting material')));
}
exit;