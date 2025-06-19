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
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            color: #fff;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 0;
        }
        
        #sidebar ul.components {
            padding: 20px 0;
        }
        
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 0.9rem;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        #sidebar ul li a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #fff;
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Topbar Styles */
        .topbar {
            height: 70px;
            background: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .btn-toggle {
            background: transparent;
            border: none;
            color: var(--dark-text);
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .topbar h4 {
            margin: 0;
            font-weight: 700;
            color: var(--dark-text);
            flex-grow: 1;
        }
        
        /* Main Content */
        #content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 70px);
            padding-top: 90px;
        }
        
        /* Admin Cards */
        .admin-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header span {
            font-weight: 700;
            color: var(--dark-text);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .settings-group {
            margin-bottom: 30px;
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 20px;
        }
        
        .settings-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .settings-group h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .setting-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .setting-item:hover {
            background: #eef2f7;
            transform: translateY(-2px);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .setting-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }
        
        .setting-description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .setting-value {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
            background: #fff;
        }
        
        .setting-value:focus {
            border-color: #bac8f3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-save {
            background: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
        }
        
        .btn-save:hover {
            background: #2e59d9;
            border-color: #2653d4;
        }
        
        /* Status indicators */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .status-active {
            background: #e0f7e9;
            color: #13855c;
        }
        
        .status-inactive {
            background: #fdecea;
            color: #e74a3b;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-book me-2"></i> LibrarySystem</h3>
            <p class="mb-0">Admin Control Panel</p>
        </div>
        
        <ul class="list-unstyled components">
            <li>
                <a href="control_panel.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users-manager.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="roles-manager.php" class="nav-link">
                    <i class="fas fa-user-tag"></i> Roles
                </a>
            </li>
            <li>
                <a href="materials-manager.php" class="nav-link">
                    <i class="fas fa-book"></i> Materials
                </a>
            </li>
            <li>
                <a href="categories-manager.php" class="nav-link">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="publishers-manager.php" class="nav-link">
                    <i class="fas fa-building"></i> Publishers
                </a>
            </li>
            <li>
                <a href="authors-manager.php" class="nav-link">
                    <i class="fas fa-feather"></i> Authors
                </a>
            </li>
            <li>
                <a href="settings-manager.php" class="nav-link active">
                    <i class="fas fa-cog"></i> System Settings
                </a>
            </li>
            <li>
                <a href="activity-log.php" class="nav-link">
                    <i class="fas fa-history"></i> Activity Log
                </a>
            </li>
            <li>
                <a href="search.php" class="nav-link">
                    <i class="fas fa-arrow-left"></i> Back to Library
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>System Settings</h4>
            <div>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="card-header">
                <span>Configuration Settings</span>
                <div>
                    <span class="me-2">
                        <span class="status-badge status-active">Last Modified</span>
                        <?php if ($lastModified): ?>
                            <?= date('M d, Y H:i', strtotime($lastModified)) ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="settingsForm">
                    <?php foreach ($settingsGroups as $groupName => $groupSettings): ?>
                        <div class="settings-group">
                            <h5>
                                <i class="fas fa-cog me-2"></i> <?= htmlspecialchars($groupName) ?>
                            </h5>
                            
                            <?php foreach ($groupSettings as $key => $setting): ?>
                                <?php 
                                    $value = $settings[$key] ?? '';
                                    $displayValue = ($setting['type'] === 'password' && !empty($value)) ? '********' : $value;
                                ?>
                                <div class="setting-item">
                                    <div class="setting-label"><?= htmlspecialchars($setting['label']) ?></div>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description">
                                            <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($setting['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($setting['type'] === 'select'): ?>
                                        <select name="settings[<?= htmlspecialchars($key) ?>]" class="setting-value">
                                            <?php 
                                                $options = $setting['dynamic_options'] ?? false ? $roleOptions : ($setting['options'] ?? []);
                                                foreach ($options as $optionValue => $optionLabel): 
                                            ?>
                                                <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($value == $optionValue) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($optionLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($setting['type'] === 'textarea'): ?>
                                        <textarea name="settings[<?= htmlspecialchars($key) ?>]" class="setting-value" rows="3"><?= htmlspecialchars($value) ?></textarea>
                                    
                                    <?php else: ?>
                                        <input type="<?= htmlspecialchars($setting['type']) ?>" 
                                               name="settings[<?= htmlspecialchars($key) ?>]" 
                                               class="setting-value"
                                               value="<?= htmlspecialchars($displayValue) ?>"
                                               <?= ($setting['type'] === 'number') ? 'min="1" step="1"' : '' ?>
                                               <?= ($setting['type'] === 'email') ? 'placeholder="email@example.com"' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary btn-save">
                            <i class="fas fa-save me-2"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <span>System Status</span>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-database me-2 text-primary"></i> 
                                    Database Status
                                </div>
                                <span class="status-badge status-active">Operational</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-server me-2 text-primary"></i> 
                                    Server Load
                                </div>
                                <span>15%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-users me-2 text-primary"></i> 
                                    Active Users
                                </div>
                                <span>24</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-book me-2 text-primary"></i> 
                                    Library Items
                                </div>
                                <span>1,248</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Maintenance Tools</span>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-download me-2"></i> Backup Database
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-sync-alt me-2"></i> Clear Cache
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-file-export me-2"></i> Export Data
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-file-import me-2"></i> Import Data
                            </button>
                            <button class="btn btn-outline-danger text-start">
                                <i class="fas fa-broom me-2"></i> Purge Old Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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