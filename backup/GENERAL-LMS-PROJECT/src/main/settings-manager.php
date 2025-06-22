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


// Function to log activity
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
        
        // Prepare all settings for update
        $updateStmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, last_modified = NOW() WHERE setting_key = ?");
        
        // Update each setting
        foreach ($_POST['settings'] as $key => $value) {
            $updateStmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $success_msg = "System settings updated successfully!";
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'system_settings', 'Updated system configuration');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error updating settings: " . $e->getMessage();
    }
}

// Get all system settings - FIXED QUERY
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get last modified timestamp
try {
    $lastModified = $pdo->query("SELECT MAX(last_modified) AS last_modified FROM system_settings")->fetchColumn();
} catch (\PDOException $e) {
    $lastModified = null;
}

// Prepare settings array for display
$settingsGroups = [
    'General Settings' => [
        'library_name' => [
            'label' => 'Library Name',
            'type' => 'text',
            'description' => 'The display name for your library'
        ],
        'default_language' => [
            'label' => 'Default Language',
            'type' => 'select',
            'options' => ['EN' => 'English', 'GR' => 'Greek', 'Other' => 'Other'],
            'description' => 'Default language for the system'
        ],
        'max_items_per_page' => [
            'label' => 'Items Per Page',
            'type' => 'number',
            'description' => 'Number of items to display per page in search results'
        ],
        'default_theme' => [
            'label' => 'Default Theme',
            'type' => 'select',
            'options' => ['light' => 'Light', 'dark' => 'Dark'],
            'description' => 'Default visual theme for the application'
        ],
        'maintenance_mode' => [
            'label' => 'Maintenance Mode',
            'type' => 'select',
            'options' => ['0' => 'Disabled', '1' => 'Enabled'],
            'description' => 'When enabled, only administrators can access the system'
        ],
    ],
    'User Settings' => [
        'enable_user_registration' => [
            'label' => 'User Registration',
            'type' => 'select',
            'options' => ['1' => 'Enabled', '0' => 'Disabled'],
            'description' => 'Allow new users to register accounts'
        ],
        'default_user_role' => [
            'label' => 'Default User Role',
            'type' => 'select',
            'description' => 'Default role for new users',
            'dynamic_options' => true
        ],
        'password_reset_expiry' => [
            'label' => 'Password Reset Expiry (hours)',
            'type' => 'number',
            'description' => 'How long password reset links remain valid'
        ],
    ],
    'Email Settings' => [
        'smtp_host' => [
            'label' => 'SMTP Host',
            'type' => 'text',
            'description' => 'Server address for outgoing email'
        ],
        'smtp_port' => [
            'label' => 'SMTP Port',
            'type' => 'number',
            'description' => 'Port for SMTP server'
        ],
        'smtp_username' => [
            'label' => 'SMTP Username',
            'type' => 'text',
            'description' => 'Username for email server'
        ],
        'smtp_password' => [
            'label' => 'SMTP Password',
            'type' => 'password',
            'description' => 'Password for email server'
        ],
        'system_email' => [
            'label' => 'System Email Address',
            'type' => 'email',
            'description' => 'Email address used for system notifications'
        ],
        'email_notifications' => [
            'label' => 'Email Notifications',
            'type' => 'select',
            'options' => ['1' => 'Enabled', '0' => 'Disabled'],
            'description' => 'Enable system email notifications'
        ],
    ],
    'Catalog Settings' => [
        'default_material_status' => [
            'label' => 'Default Material Status',
            'type' => 'select',
            'options' => ['available' => 'Available', 'archived' => 'Archived'],
            'description' => 'Default status for new library materials'
        ],
        'max_loan_period' => [
            'label' => 'Max Loan Period (days)',
            'type' => 'number',
            'description' => 'Maximum number of days items can be borrowed'
        ],
        'renewal_limit' => [
            'label' => 'Renewal Limit',
            'type' => 'number',
            'description' => 'Maximum number of times an item can be renewed'
        ],
        'reservation_period' => [
            'label' => 'Reservation Period (days)',
            'type' => 'number',
            'description' => 'How long to hold reserved items for pickup'
        ],
    ],
    'Advanced Settings' => [
        'backup_frequency' => [
            'label' => 'Backup Frequency',
            'type' => 'select',
            'options' => ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'],
            'description' => 'How often to create system backups'
        ],
        'log_retention' => [
            'label' => 'Log Retention (days)',
            'type' => 'number',
            'description' => 'How long to keep activity logs'
        ],
        'api_enabled' => [
            'label' => 'API Access',
            'type' => 'select',
            'options' => ['1' => 'Enabled', '0' => 'Disabled'],
            'description' => 'Enable system API for integrations'
        ],
    ]
];

// Get roles for dynamic dropdown
$roles = $pdo->query("SELECT role_id, role_name FROM roles")->fetchAll();
$roleOptions = [];
foreach ($roles as $role) {
    $roleOptions[$role['role_id']] = $role['role_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link href="../styles/settings-man.css" rel="stylesheet">
   <link href="../styles/components/sidebar.css" rel="stylesheet">
   <link href="../styles/components/topbar.css" rel="stylesheet">
   <link href="../styles/general/general-main-styles.css" rel="stylesheet">
   <link href="../styles/responsive/responsive.css" rel="stylesheet">
</head>
<body>
   
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>

  <?php include '../components/settings-main-content.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Confirm before leaving page with unsaved changes
        let formChanged = false;
        const form = document.getElementById('settingsForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>