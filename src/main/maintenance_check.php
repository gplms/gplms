<?php
// maintenance_check.php

require_once '../conf/config.php'; // Adjust path as needed

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Define public pages accessible without session
$public_pages = ['login.php', 'maintenance.php', 'search.php'];

// Current script name
$current_page = basename($_SERVER['PHP_SELF']);

// Redirect to login if:
// - Current page isn't public
// - No active session exists
if (!in_array($current_page, $public_pages) && empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

// Allow access if:
// 1. Maintenance mode is disabled
// 2. User is admin (role_id 1)
// 3. Requesting the maintenance page itself
// 4. Requesting the login page
$allowed = !$maintenance_mode || 
           (!empty($_SESSION['role_id']) && $_SESSION['role_id'] == 1) || 
           $current_page === 'maintenance.php' ||
           $current_page === 'login.php';

if (!$allowed) {
    // Redirect to maintenance page
    header("Location: maintenance.php");
    exit;
}