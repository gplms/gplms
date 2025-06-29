<!-- ========================== START OF PHP CODE ========================= -->

<?php

/**
 * Admin Activity Log Dashboard
 * 
 * Provides an administrative interface for viewing and filtering system activity logs.
 * Includes access control, search/filter functionality, pagination, and data visualization.
 */

// Start session to access user authentication data
session_start();

require_once '../conf/check-session.php';

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';








/**
 * Filter Handling Section
 * 
 * Processes GET parameters for search/filter operations and sanitizes inputs.
 * All user inputs are treated as untrusted and properly sanitized.
 */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';          // Global search term
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
$whereClauses = [];                         // Array to store WHERE conditions
$params = [];                               // Array to store bound parameters

// With this:
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
$perPage = 10;  // Number of logs per page
// Validate and sanitize page input
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;  // Calculate SQL offset

// Get total log count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $where");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);  // Calculate total pages

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
 * These enhance readability and provide visual cues through icons.
 */

/**
 * Formats action type with appropriate icon and color
 * 
 * @param string $action The action type from log
 * @return string HTML-formatted action with icon
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
    
    $icon = $icons[$action] ?? 'fas fa-circle';  // Default icon
    return "<i class='$icon'></i> $action";      // Returns formatted HTML
}

/**
 * Formats target object with icon representation
 * 
 * @param string $target The target object from log
 * @return string HTML-formatted target with icon
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
    $icon = $icons[$base] ?? $icons[$target] ?? 'fas fa-file';  // Fallback icons
    
    return "<i class='$icon'></i> " . ucwords(str_replace('_', ' ', $target));
}

/**
 * Formats IP address with monospace font for readability
 * 
 * @param string $ip IP address
 * @return string HTML-formatted IP
 */
function formatIP($ip) {
    return "<span class='font-monospace'>$ip</span>";  // Monospace for alignment
}

/**
 * Formats timestamp into human-readable date/time components
 * 
 * @param string $timestamp Database timestamp
 * @return string HTML-formatted date/time
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
    <title>GPLMS - Free & Open Source Project | Activity Log</title>
    
    <!-- External CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Charting Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Local Styles -->
    <link rel="stylesheet" href="../styles/activity-log.css">
        <link rel="stylesheet" href="../styles/general/general-main-styles.css">
        <link rel="stylesheet" href="../styles/components/topbar.css">
        <link rel="stylesheet" href="../styles/components/sidebar.css">
        <link rel="stylesheet" href="../styles/responsive/responsive.css">

            <link rel="icon" type="image/png" href="../../assets/logo-l.png">

<body>
    
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         MAIN CONTENT COMPONENT
         - Core activity log interface
         - Contains filters, table, and pagination
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/activity-main-content.php'; ?>
    
    <!-- Bootstrap Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         ACTIVITY STATISTICS COMPONENT
         - Charts and visualization elements
         - JavaScript-powered functionality
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/activity-log-stats.php'; ?>

</body>
</html>
<!-- ========================= HTML CODE END POINT ===========================-->