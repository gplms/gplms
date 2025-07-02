<?php
// maintenance_check.php

require_once '../conf/config.php'; // Adjust path as needed

// Get maintenance mode status from database
$maintenance_mode = false;
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['setting_value'] === '1') {
        $maintenance_mode = true;
    }
} catch (PDOException $e) {
    error_log("Maintenance check error: " . $e->getMessage());
}

// Current script name
$current_page = basename($_SERVER['PHP_SELF']);

// Allow access if:
// 1. Maintenance mode is disabled
// 2. User is admin (role_id 1)
// 3. Requesting the maintenance page itself
// 4. Requesting the login page (so admin can log in)
$allowed = !$maintenance_mode || 
           (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) || 
           $current_page === 'maintenance.php' ||
           $current_page === 'login.php';

if (!$allowed) {
    // Redirect to maintenance page
    header("Location: maintenance.php");
    exit;
}
?>


