
<?php
// Fetch library name if not already defined
if (!isset($library_name)) {
    $library_name = 'GPLMS'; // Default value
    try {
        if (isset($pdo)) { // Ensure PDO connection exists
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'library_name'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['setting_value'])) {
                $library_name = $result['setting_value'];
            }
        }
    } catch (Exception $e) {
        // Keep default name on error
        error_log("Error fetching library name: " . $e->getMessage());
    }
}

?>