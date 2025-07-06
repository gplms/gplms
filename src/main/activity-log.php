<?php
/*
===============================================================================
  GPLMS (General Purpose Library Management System)
===============================================================================
  Project Repository : https://github.com/PanagiotisKotsorgios/gplms
  License            : MIT Licence
  Copyright          : (c) 2025 Panagiotis Kotsorgios, Fotis Markantonatos & Contributors
  Website            : [+]

  Description:
    GPLMS is a free and open-source Library Management System for schools,
    universities, and public libraries. It is built using PHP, HTML, JavaScript,
    and MySQL, and is designed to be modular, extensible, and easy to deploy.

  Creates At:
    - SAEK MESOLOGHIOY [MESOLOGHI] [GREECE]
    - WEBSITE: [https://www.saekmesol.gr/]
            
  This File:
    - [activity-log.php]
    - Purpose: [Logging every possible user's action to the database & then displays it in frontend]

  Documentation:
    - Setup Guide         : https://github.com/PanagiotisKotsorgios/gplms/blob/main/README.md
    - User Guide          : https://github.com/PanagiotisKotsorgios/gplms/blob/main/docs/README.md

  Contributing:
    - Please see the contributing guide at 
      https://github.com/PanagiotisKotsorgios/gplms/blob/main/CONTRIBUTION.md

  License Notice:

    This project was originally created by students and independent open-source developers,
    not by a professional company. It is made for the community, by the community, in the
    spirit of open source and collective learning. Contributions, use, and sharing are
    greatelly encouraged!

    This program is free software: you can use, copy, modify, merge, publish,
    distribute, sublicense, and/or sell copies of it under the terms of the MIT License.
    See https://opensource.org/licenses/MIT for details.

    WARNING: This software is provided as-is, without any warranty of any kind.
    That means there are no guarantees, either express or implied, including but not limited to
    merchantability, fitness for a particular purpose, or non-infringement.
    The authors and contributors are not responsible for any issues, damages, or losses
    that may arise from using, modifying, or distributing this software. 
    You use this project entirely at your own risk.

    Thank you for using our software 😁💖
===============================================================================
*/



// Start session here
session_start();

// Including the check-session file to prevent anauthorised access
require_once '../conf/check-session.php';

// Load configuration file containing db conn parametersw and logic
require_once '../conf/config.php';

//Checks if maintenance mode is enabled and acts according to it
require_once 'maintenance_check.php';

// Include the translation component
require_once '../conf/translation.php'; 

// Fetches the library name directly from the database
require_once '../functions/fetch-lib-name.php';


/**
 * Filter Handling Section
 * 
 * Processes GET parameters for search/filter operations and sanitizes inputs.
 * All user inputs are treated as untrusted and properly sanitized.
 */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';  // Global search term
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';          // Filter by specific action
$targetFilter = isset($_GET['target']) ? $_GET['target'] : '';          // Filter by target object
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';        // Start date filter
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';              // End date filter



/**
 * Query Building Section
 * 
 * Dynamically constructs SQL query based on applied filters.
 * Uses parameterized queries to prevent SQL injection vulnerabilities.
 */
$baseQuery = "SELECT * FROM activity_logs";  // Base query without filters
$whereClauses = [];                      // Array to store WHERE conditions
$params = [];                               // Array to store bound parameters

// With this
if (!empty($search)) {
    $whereClauses[] = "(username LIKE :search_user OR action LIKE :search_action OR target_object LIKE :search_target OR details LIKE :search_details OR ip_address LIKE :search_ip)";
    $params[':search_user'] = "%$search%";
    $params[':search_action'] = "%$search%";
    $params[':search_target'] = "%$search%";
    $params[':search_details'] = "%$search%";
    $params[':search_ip'] = "%$search%";
}
// Add action type filter
if (!empty($actionFilter)) {
    $whereClauses[] = "action = :action";
    $params[':action'] = $actionFilter;
}
// Add target object filter
if (!empty($targetFilter)) {
    $whereClauses[] = "target_object = :target";
    $params[':target'] = $targetFilter;
}
// Add date range filters
if (!empty($dateFrom)) {
    $whereClauses[] = "timestamp >= :date_from";
    $params[':date_from'] = $dateFrom;      // Expected format: YYYY-MM-DD
}
if (!empty($dateTo)) {
    $whereClauses[] = "timestamp <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';  // Include entire end day
}
// Combine WHERE clauses if any filters exist
$where = '';
if (!empty($whereClauses)) {
    $where = " WHERE " . implode(" AND ", $whereClauses);
}



/**
 * Pagination Implementation
 * 
 * Calculates pagination values and ensures page number validity.
 * Uses separate count query to determine total pages.
 */
// Fetch items per page from database
$perPage = 10;  // Default value if setting not found
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'items_per_page'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && is_numeric($result['setting_value'])) {
        $perPage = (int)$result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Items per page setting error: " . $e->getMessage());
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage; 


// Get total log count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $where");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}

$countStmt->execute();
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);  // wE calculate total pages



/**
 * Data Retrieval Section
 * 
 * Fetches paginated log data using prepared statements.
 * Separately binds pagination parameters for type safety.
 */
$query = $pdo->prepare("SELECT * FROM activity_logs $where ORDER BY timestamp DESC LIMIT :offset, :perPage");

// Bind filter parameters
foreach ($params as $key => $value) {
    $query->bindValue($key, $value);
}

// Bind pagination parameters with explicit type casting
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$query->execute();
$logs = $query->fetchAll();



/**
 * Filter Option Data
 * 
 * Pre-fetches distinct values for filter dropdowns.
 * These are used to populate action and target selection filters.
 */
// Get unique actions for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Get unique targets (excluding empty values)
$targets = $pdo->query("SELECT DISTINCT target_object FROM activity_logs WHERE target_object IS NOT NULL AND target_object != '' ORDER BY target_object")->fetchAll(PDO::FETCH_COLUMN);



/**
 * Data Visualization Queries
 * 
 * Fetches aggregated data for dashboard charts and statistics.
 * These queries power visual representations of activity.
 */
// Action distribution data (for pie/bar chart)
$actionData = $pdo->query("SELECT action, COUNT(*) as count FROM activity_logs GROUP BY action ORDER BY count DESC")->fetchAll();

// Last 7 days activity (for trend chart)
$dailyActivity = $pdo->query("SELECT DATE(timestamp) as date, COUNT(*) as count 
                              FROM activity_logs 
                              WHERE timestamp >= CURDATE() - INTERVAL 7 DAY
                              GROUP BY DATE(timestamp) 
                              ORDER BY date")->fetchAll();

// Top 5 active users (for leaderboard)
$topUsers = $pdo->query("SELECT username, COUNT(*) as count 
                         FROM activity_logs 
                         GROUP BY username 
                         ORDER BY count DESC 
                         LIMIT 5")->fetchAll();



/**
 * Formatting Functions
 * 
 * Helper functions to transform raw data into presentable HTML.
 */

/**
 * Formats action type with appropriate icon and color
 * 
 */
function formatAction($action) {
    // Icon mapping with Bootstrap classes
    $icons = [
        'CREATE' => 'fas fa-plus-circle text-success',
        'UPDATE' => 'fas fa-edit text-primary',
        'DELETE' => 'fas fa-trash-alt text-danger',
        'LOGIN' => 'fas fa-sign-in-alt text-info',
        'LOGOUT' => 'fas fa-sign-out-alt text-warning',
        'ACCESS' => 'fas fa-unlock text-secondary',
        'ERROR' => 'fas fa-exclamation-circle text-danger',
        'REGISTER' => 'fas fa-user-plus text-info',
        'SETTINGS' => 'fas fa-cog text-primary',
        'BACKUP' => 'fas fa-save text-warning'
    ];
    
    $icon = $icons[$action] ?? 'fas fa-circle';  
    return "<i class='$icon'></i> $action";      // Returns formatted HTML
}


/**
 * Formats target object with icon representation
 */
function formatTarget($target) {
    if (!$target) return '';  // Handle empty targets
    
    // Target-specific icon mapping
    $icons = [
        'user' => 'fas fa-user',
        'system_settings' => 'fas fa-cog',
        'library_item' => 'fas fa-book',
        'role' => 'fas fa-user-tag',
        'category' => 'fas fa-tag',
        'publisher' => 'fas fa-building',
        'author' => 'fas fa-feather',
        'material' => 'fas fa-book-open',
        'activity_log' => 'fas fa-history'
    ];
    
    // Extract base name for icon matching
    $parts = explode('_', $target);
    $base = $parts[0] ?? '';
    $icon = $icons[$base] ?? $icons[$target] ?? 'fas fa-file';  
    
    return "<i class='$icon'></i> " . ucwords(str_replace('_', ' ', $target));
}



/**
 * Formats IP address with monospace font for readability
 */
function formatIP($ip) {
    return "<span class='font-monospace'>$ip</span>";  
}

/**
 * Formats timestamp into human-readable date/time components
 */
function formatTimestamp($timestamp) {
    $date = date('M d, Y', strtotime($timestamp));  // Friendly date format
    $time = date('H:i', strtotime($timestamp));      // Time only
    return "<div>$date</div><div class='small text-muted'>$time</div>";  // Two-line format
}
?>
<!-- ========================== END OF PHP CODE ========================= -->




<!-- ========================= HTML CODE START POINT ===========================-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Activity Log</title>
    
    <!-- External CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Charting Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Local Styles -->
    <link rel="stylesheet" href="../styles/activity-log.css">
    <link rel="stylesheet" href="../styles/general/general-main-styles.css">
    <link rel="stylesheet" href="../styles/components/topbar.css">
    <link rel="stylesheet" href="../styles/responsive/responsive.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <link rel="stylesheet" href="../styles/components/sidebar1.css">
<body>
    
    <!-- Public Sidebar Component -->
    <?php include '../components/sidebar1.php'; ?>

    <!-- MAIN CONTENT COMPONENT -->
    <?php include '../components/activity-main-content.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ACTIVITY STATISTICS COMPONENT -->
    <?php include '../components/activity-log-stats.php'; ?>

</body>
</html>
<!-- ========================= HTML CODE END POINT ===========================-->