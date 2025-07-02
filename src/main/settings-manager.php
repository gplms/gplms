<?php
session_start();

require_once '../conf/config.php';
require_once '../conf/translation.php';
require_once '../functions/fetch-lib-name.php';

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Load configuration file
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
        
        $updateStmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, last_modified = NOW() WHERE setting_key = ?");
        
        foreach ($_POST['settings'] as $key => $value) {
            // Skip empty password field (preserve existing value)
            if ($key === 'mailersend_api_key' && empty($value)) {
                continue;
            }
            $updateStmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $success_msg = $lang['settings_update_success'];
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'system_settings', $lang['log_updated_settings']);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = $lang['settings_update_error'] . $e->getMessage();
    }
}

// Get all system settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get last modified timestamp
try {
    $lastModified = $pdo->query("SELECT MAX(last_modified) AS last_modified FROM system_settings")->fetchColumn();
} catch (\PDOException $e) {
    $lastModified = null;
}

// Prepare settings array for display
$settingsGroups = [
    $lang['general_settings'] => [
        'library_name' => [
            'label' => $lang['setting_library_name'],
            'type' => 'text',
            'description' => $lang['setting_library_name_desc']
        ],
        'default_language' => [
            'label' => $lang['setting_default_language'],
            'type' => 'select',
            'options' => [
                'EN' => $lang['english'], 
                'GR' => $lang['greek'], 
                'Other' => $lang['other']
            ],
            'description' => $lang['setting_default_language_desc']
        ],
        'items_per_page' => [
            'label' => $lang['setting_items_per_page'],
            'type' => 'number',
            'description' => $lang['setting_items_per_page_desc'],
            'min' => 5,
            'max' => 100
        ],
        'default_theme' => [
            'label' => $lang['setting_default_theme'],
            'type' => 'select',
            'options' => [
                'light' => $lang['light_theme'], 
                'dark' => $lang['dark_theme']
            ],
            'description' => $lang['setting_default_theme_desc']
        ],
        'maintenance_mode' => [
            'label' => $lang['setting_maintenance_mode'],
            'type' => 'select',
            'options' => [
                '0' => $lang['disabled'], 
                '1' => $lang['enabled']
            ],
            'description' => $lang['setting_maintenance_mode_desc']
        ],
    ],
    $lang['user_settings'] => [
        'allow_user_registration' => [
            'label' => $lang['setting_allow_registration'],
            'type' => 'select',
            'options' => [
                '1' => $lang['enabled'], 
                '0' => $lang['disabled']
            ],
            'description' => $lang['setting_allow_registration_desc']
        ],
        'default_user_role' => [
            'label' => $lang['setting_default_role'],
            'type' => 'select',
            'description' => $lang['setting_default_role_desc'],
            'dynamic_options' => true
        ],
        'password_reset_expiry_hours' => [
            'label' => $lang['setting_password_reset_expiry'],
            'type' => 'number',
            'description' => $lang['setting_password_reset_expiry_desc'],
            'min' => 1,
            'max' => 72
        ],
    ],
    $lang['mailersend_settings'] => [
        'mailersend_api_key' => [
            'label' => $lang['setting_mailersend_api_key'],
            'type' => 'password',
            'description' => $lang['setting_mailersend_api_key_desc'],
            'placeholder' => $lang['password_keep_current']
        ],
        'mailersend_sender_email' => [
            'label' => $lang['setting_mailersend_sender_email'],
            'type' => 'email',
            'description' => $lang['setting_mailersend_sender_email_desc']
        ],
        'mailersend_sender_name' => [
            'label' => $lang['setting_mailersend_sender_name'],
            'type' => 'text',
            'description' => $lang['setting_mailersend_sender_name_desc']
        ],
        'contact_form_recipient_email' => [
            'label' => $lang['setting_contact_recipient_email'],
            'type' => 'email',
            'description' => $lang['setting_contact_recipient_email_desc']
        ],
        'contact_form_recipient_name' => [
            'label' => $lang['setting_contact_recipient_name'],
            'type' => 'text',
            'description' => $lang['setting_contact_recipient_name_desc']
        ],
        'email_notifications' => [
            'label' => $lang['setting_email_notifications'],
            'type' => 'select',
            'options' => [
                '1' => $lang['enabled'], 
                '0' => $lang['disabled']
            ],
            'description' => $lang['setting_email_notifications_desc']
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
<html lang="<?= $default_language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['page_title_system_settings'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../styles/components/sidebar1.css" rel="stylesheet">
    <link href="../styles/general/general-main-styles.css" rel="stylesheet">
    <link href="../styles/general/settings.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>

<?php include '../components/sidebar1.php' ?>

    <div class="wrapper">
    
        <div class="main">
            <!-- Main Content -->
            <main class="content px-4 py-3">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0"><?= $lang['system_settings_heading'] ?></h1>
                        <?php if ($lastModified): ?>
                            <span class="last-modified">
                                <?= $lang['last_updated'] ?>: <?= date('M j, Y \a\t g:i a', strtotime($lastModified)) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
                        </div>
                    <?php endif; ?>

                    <form id="settingsForm" method="post">
                        <?php foreach ($settingsGroups as $groupTitle => $settingsGroup): ?>
                            <div class="card settings-section">
                                <div class="card-header">
                                    <h2 class="h5 mb-0"><?= $groupTitle ?></h2>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($settingsGroup as $key => $settingDef): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="<?= $key ?>">
                                                    <?= $settingDef['label'] ?>
                                                    <?php if ($key === 'mailersend_api_key'): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <?php if ($settingDef['type'] === 'text' || 
                                                         $settingDef['type'] === 'number' || 
                                                         $settingDef['type'] === 'email' || 
                                                         $settingDef['type'] === 'password'): ?>
                                                    <input type="<?= $settingDef['type'] ?>" 
                                                        class="form-control" 
                                                        id="<?= $key ?>" 
                                                        name="settings[<?= $key ?>]"
                                                        value="<?= ($settingDef['type'] !== 'password') ? htmlspecialchars($settings[$key] ?? '') : '' ?>"
                                                        <?= isset($settingDef['placeholder']) ? 'placeholder="' . $settingDef['placeholder'] . '"' : '' ?>
                                                        <?= isset($settingDef['min']) ? 'min="' . $settingDef['min'] . '"' : '' ?>
                                                        <?= isset($settingDef['max']) ? 'max="' . $settingDef['max'] . '"' : '' ?>
                                                        <?= ($settingDef['type'] === 'password') ? 'autocomplete="new-password"' : '' ?>
                                                        <?= ($key === 'mailersend_api_key') ? '' : '' ?>>
                                                
                                                <?php if ($key === 'mailersend_api_key'): ?>
                                                    <div class="api-key-info">
                                                        <i class="fas fa-info-circle me-1"></i> 
                                                        <?= $lang['api_key_info'] ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php elseif ($settingDef['type'] === 'select'): ?>
                                                    <select class="form-select" id="<?= $key ?>" name="settings[<?= $key ?>]">
                                                        <?php if (isset($settingDef['dynamic_options']) && $settingDef['dynamic_options']): ?>
                                                            <?php foreach ($roleOptions as $value => $label): ?>
                                                                <option value="<?= $value ?>" <?= (isset($settings[$key]) && $settings[$key] == $value ? 'selected' : '') ?>>
                                                                    <?= $label ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <?php foreach ($settingDef['options'] as $value => $label): ?>
                                                                <option value="<?= $value ?>" <?= (isset($settings[$key]) && $settings[$key] == $value ? 'selected' : '') ?>>
                                                                    <?= $label ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                <?php endif; ?>
                                                
                                                <div class="form-text">
                                                    <?= $settingDef['description'] ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i><?= $lang['save_settings'] ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm before leaving page with unsaved changes
        let formChanged = false;
        const form = document.getElementById('settingsForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => formChanged = true);
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', () => formChanged = false);
    </script>
</body>
</html>