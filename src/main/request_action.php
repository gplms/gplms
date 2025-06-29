<?php
session_start();

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get request data
$action = $_POST['action'] ?? '';
$item_id = (int)($_POST['item_id'] ?? 0);

// Validate input
if (!in_array($action, ['edit', 'delete']) || $item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get item details
$stmt = $pdo->prepare("SELECT title FROM library_items WHERE item_id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// Log the request in activity_logs
try {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, target_object, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        "REQUEST_" . strtoupper($action),
        "library_items",
        "Requested to $action item: {$item['title']} (ID: $item_id)",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}