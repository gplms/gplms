<?php
// update_theme.php
session_start();
require_once '../conf/config.php';

if (isset($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'])) {
    try {
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'default_theme'");
        $stmt->execute([$_GET['theme']]);
        echo "Theme updated successfully";
    } catch (PDOException $e) {
        error_log("Theme update error: " . $e->getMessage());
        echo "Error updating theme";
    }
} else {
    echo "Invalid theme specified";
}
?>