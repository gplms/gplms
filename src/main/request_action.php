<?php
session_start();
require_once '../conf/config.php';
require_once '../conf/translation.php';

// Define the email function first
function send_email($to_email, $to_name, $subject, $body, $api_key, $sender_email, $sender_name) {
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $url = "https://api.mailersend.com/v1/email";
    $headers = [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json",
        "Accept: application/json"
    ];

    $postData = [
        "from" => [
            "email" => $sender_email,
            "name" => $sender_name
        ],
        "to" => [
            [
                "email" => $to_email,
                "name" => $to_name
            ]
        ],
        "subject" => $subject,
        "text" => $body
    ];

    $jsonPayload = json_encode($postData);
    if ($jsonPayload === false) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 202;
}

/**
 * Sends email notification to admin about the request
 */
function sendRequestNotification($action, $item_id, $title, $added_by, $requester, $reason = '') {
    global $pdo;
    
    try {
        // Get MailerSend settings
        $stmt = $pdo->prepare("
            SELECT setting_key, setting_value 
            FROM system_settings 
            WHERE setting_key IN (
                'mailersend_api_key', 
                'mailersend_sender_email', 
                'mailersend_sender_name',
                'contact_form_recipient_email'
            )
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $api_key = $settings['mailersend_api_key'] ?? '';
        $sender_email = $settings['mailersend_sender_email'] ?? '';
        $sender_name = $settings['mailersend_sender_name'] ?? '';
        $admin_email = $settings['contact_form_recipient_email'] ?? '';
        
        // Get item owner details
        $stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE user_id = ?");
        $stmt->execute([$added_by]);
        $owner = $stmt->fetch();
        
        // Only send if we have all required information
        if ($api_key && $sender_email && $admin_email && $requester && $owner) {
            $actionType = ucfirst($action);
            
            $subject = "Action Request: $actionType for '$title'";
            
            $body = "A user has requested to $action an item in the library system:\n\n";
            $body .= "Item Title: $title\n";
            $body .= "Item ID: $item_id\n";
            $body .= "Requested Action: $actionType\n";
            $body .= "Requester: {$requester['full_name']} ({$requester['username']})\n";
            $body .= "Requester Email: {$requester['email']}\n";
            $body .= "Item Owner: {$owner['full_name']} ({$owner['username']})\n";
            $body .= "Owner Email: {$owner['email']}\n";
            
            // Add reason to email if provided
            if (!empty($reason)) {
                $body .= "\nReason/Details:\n";
                $body .= wordwrap($reason, 70) . "\n";
            } else {
                $body .= "\nReason: Not provided\n";
            }
            
            $body .= "\nDate: " . date('Y-m-d H:i:s') . "\n\n";
            $body .= "Please review this request and take appropriate action.\n\n";
            $body .= "This is an automated notification from the Library Management System.";
            
            // Send email
            return send_email(
                $admin_email,
                'System Administrator',
                $subject,
                $body,
                $api_key,
                $sender_email,
                $sender_name
            );
        }
        return false;
    } catch (Exception $e) {
        error_log("Failed to send request notification: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$item_id = (int)($_GET['id'] ?? $_POST['item_id'] ?? 0);
$confirmation = $_POST['confirmation'] ?? '';
$message = '';
$message_type = '';

// Validate input
$valid_actions = ['edit', 'delete'];
if (!in_array($action, $valid_actions) || $item_id <= 0) {
    $message = $lang['invalid_request'] ?? 'Invalid request';
    $message_type = "error";
}

// Get item details
if (!$message) {
    $stmt = $pdo->prepare("SELECT li.*, u.username AS added_by_username 
                          FROM library_items li
                          LEFT JOIN users u ON li.added_by = u.user_id
                          WHERE li.item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        $message = $lang['item_not_found'] ?? 'Item not found';
        $message_type = "error";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirmation === 'yes' && !$message) {
    try {
        // Get requester details
        $stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $requester = $stmt->fetch();
        
        // Get reason from form
        $reason = trim($_POST['reason'] ?? '');
        
        // Log the request
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, target_object, details, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $logDetails = "Requested to $action item: {$item['title']} (ID: $item_id)";
        if ($reason) {
            $logDetails .= ". Reason: $reason";
        }
        $stmt->execute([
            $_SESSION['user_id'],
            "REQUEST_" . strtoupper($action),
            "library_items",
            $logDetails,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Send email notification to admin
        $email_sent = sendRequestNotification($action, $item_id, $item['title'], $item['added_by'], $requester, $reason);
        
        if ($email_sent) {
            $message = $lang['request_submitted'] ?? 'Your request has been submitted to the administrator';
            $message_type = "success";
        } else {
            $message = $lang['request_submitted_no_email'] ?? 'Request submitted but email notification failed';
            $message_type = "warning";
        }
    } catch (Exception $e) {
        $message = $lang['error_processing_request'] ?? 'Error processing request: ' . $e->getMessage();
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['action_request'] ?? 'Action Request' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <style>
        .container { max-width: 600px; margin-top: 40px; }
        .card { border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .card-header { background-color: #1976d2; color: white; border-radius: 10px 10px 0 0 !important; }
        .action-icon { font-size: 4rem; margin-bottom: 20px; }
        .btn-request { background-color: #1976d2; color: white; font-weight: 500; }
        .btn-request:hover { background-color: #1565c0; }
        .reason-label .required { color: #dc3545; }
    </style>
    <script>
        <?php if ($message_type === 'success'): ?>
            // Redirect after 5 seconds
            setTimeout(function() {
                window.location.href = 'search.php';
            }, 5000);
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center py-4">
                <i class="fas fa-user-shield action-icon"></i>
                <h2><?= $lang['action_request'] ?? 'Action Request' ?></h2>
            </div>
            
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> text-center">
                        <i class="fas 
                            <?= $message_type === 'success' ? 'fa-check-circle' : '' ?>
                            <?= $message_type === 'error' ? 'fa-exclamation-circle' : '' ?>
                            <?= $message_type === 'warning' ? 'fa-exclamation-triangle' : '' ?>
                        "></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                    
                    <?php if ($message_type === 'success'): ?>
                        <div class="text-center mt-4">
                            <p><i class="fas fa-spinner fa-spin me-2"></i>
                                <?= $lang['redirecting_message'] ?? 'Redirecting to search page...' ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center mb-4">
                        <h3 class="mb-3">
                            <i class="fas 
                                <?= $action === 'edit' ? 'fa-edit' : 'fa-trash' ?> 
                                me-2 text-<?= $action === 'edit' ? 'primary' : 'danger' ?>">
                            </i>
                            <?= $action === 'edit' 
                                ? ($lang['request_edit'] ?? 'Request Edit Permission') 
                                : ($lang['request_delete'] ?? 'Request Delete Permission') ?>
                        </h3>
                        
                        <p class="lead">
                            <?= $lang['requesting_access_for'] ?? 'You are requesting access for:' ?>
                        </p>
                        <h4 class="text-primary"><?= htmlspecialchars($item['title'] ?? '') ?></h4>
                        <p class="text-muted">ID: <?= $item_id ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= $lang['request_sent_to_admin'] ?? 'Your request will be sent to the system administrator for approval.' ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <input type="hidden" name="confirmation" value="yes">
                        
                        <!-- Reason input field -->
                        <div class="mb-3">
                            <label for="reason" class="form-label reason-label">
                                <?= $lang['reason_label'] ?? 'Reason for request' ?>
                                <span class="required">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="reason" 
                                name="reason" 
                                rows="4" 
                                required
                                placeholder="<?= $lang['reason_placeholder'] ?? 'Please explain why you need to perform this action and what changes you plan to make...' ?>"></textarea>
                            <div class="form-text">
                                <?= $lang['reason_help'] ?? 'This information will be included in the notification to the administrator' ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-request btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?= $lang['submit_request'] ?? 'Submit Request' ?>
                            </button>
                            <a href="search.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>
                                <?= $lang['cancel'] ?? 'Cancel' ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>