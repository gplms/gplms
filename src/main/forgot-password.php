<?php
// Load configuration file
require_once '../conf/config.php';
require_once '../conf/translation.php'; // Include translation file

// Get MailerSend settings from database
$mailersend_api_key = '';
$mailersend_sender_email = '';
$mailersend_sender_name = '';
$password_reset_expiry_hours = 24; // Default value

try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings 
                          WHERE setting_key IN ('mailersend_api_key', 'mailersend_sender_email', 'mailersend_sender_name', 'password_reset_expiry_hours')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $mailersend_api_key = $settings['mailersend_api_key'] ?? '';
    $mailersend_sender_email = $settings['mailersend_sender_email'] ?? '';
    $mailersend_sender_name = $settings['mailersend_sender_name'] ?? '';
    $password_reset_expiry_hours = isset($settings['password_reset_expiry_hours']) ? 
        (int)$settings['password_reset_expiry_hours'] : 24;
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Database error fetching settings: " . $e->getMessage());
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $message = $lang['please_enter_email'];
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = $lang['invalid_email_format'];
        $message_type = "error";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                // Calculate expiration based on database setting
                $expires = date("Y-m-d H:i:s", time() + ($password_reset_expiry_hours * 3600));

                // Clear previous tokens
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['user_id']]);

                // Store new token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['user_id'], $token, $expires]);

                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/GENERAL-LMS-PROJECT/src/main/reset_password.php?token=" . urlencode($token);
                
                // Email content
                $subject = $lang['password_reset_request'];
                $message_body = $lang['hello'] . " " . htmlspecialchars($user['full_name']) . ",\n\n";
                $message_body .= $lang['password_reset_request_message'] . "\n\n";
                $message_body .= $lang['reset_link_message'] . "\n";
                $message_body .= $reset_link . "\n\n";
                $message_body .= str_replace('{hours}', $password_reset_expiry_hours, $lang['link_expiry_message']) . "\n\n";
                $message_body .= $lang['ignore_email_message'] . "\n\n";
                $message_body .= $lang['regards'] . ",\n" . htmlspecialchars($mailersend_sender_name);

                // Send email
                $email_result = send_email(
                    $email, 
                    $user['full_name'], 
                    $subject, 
                    $message_body,
                    $mailersend_api_key,
                    $mailersend_sender_email,
                    $mailersend_sender_name
                );
                
                if ($email_result === true) {
                    $message = $lang['reset_instructions_sent'];
                    $message_type = "success";
                } else {
                    $message = $lang['email_send_error'] . $email_result;
                    $message_type = "error";
                }
            } else {
                // Prevent email enumeration
                $message = $lang['reset_instructions_sent_generic'];
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = $lang['database_error'] . $e->getMessage();
            $message_type = "error";
        } catch (Exception $e) {
            $message = $lang['error'] . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Enhanced email function with MailerSend// Enhanced email function with MailerSend
function send_email($to_email, $to_name, $subject, $body, $api_key, $sender_email, $sender_name) {
    // Make $lang accessible inside this function
    global $lang;
    
    // Validate email parameters
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return $lang['invalid_recipient_email'];
    }
    
    // Ensure names are properly formatted
    $to_name = trim(preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $to_name));
    $from_name = trim(preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $sender_name));

    $url = "https://api.mailersend.com/v1/email";
    $headers = [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json",
        "Accept: application/json"
    ];

    $postData = [
        "from" => [
            "email" => $sender_email,
            "name" => $from_name
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

    // Validate JSON encoding
    $jsonPayload = json_encode($postData);
    if ($jsonPayload === false) {
        return $lang['json_encoding_error'] . json_last_error_msg();
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FAILONERROR => false // We handle errors manually
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Create error log directory if needed
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    if ($httpCode === 202) {
        return true;
    } else {
        // Parse MailerSend error response
        $errorDetails = "HTTP Status: $httpCode\n";
        
        if ($response && $decoded = json_decode($response, true)) {
            $errorDetails .= $lang['mailersend_errors'] . "\n";
            foreach ($decoded['errors'] as $error) {
                $errorDetails .= "- " . $error['message'] . "\n";
                if (isset($error['field'])) {
                    $errorDetails .= "  " . $lang['field'] . ": " . $error['field'] . "\n";
                }
            }
        } elseif ($curlError) {
            $errorDetails .= $lang['curl_error'] . "$curlError\n";
        } else {
            $errorDetails .= $lang['response'] . ": " . substr($response, 0, 500) . "\n";
        }
        
        $errorDetails .= $lang['recipient'] . ": $to_email\n";
        $errorDetails .= $lang['subject'] . ": $subject\n";
        $errorDetails .= $lang['payload'] . ": " . print_r($postData, true) . "\n";
        
        file_put_contents($logDir . '/mailersend_errors.log', "[" . date('Y-m-d H:i:s') . "]\n" . $errorDetails . "\n", FILE_APPEND);
        
        return $lang['email_validation_error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['forgot_password_title'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
<style>
    /* Enhanced Blue/White Theme */
    body {
        background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }

    .password-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(25, 118, 210, 0.15);
        border-top: 4px solid #1976d2;
        max-width: 450px;
        width: 100%;
        padding: 30px;
        box-sizing: border-box;
    }

    .password-header {
        text-align: center;
        margin-bottom: 25px;
    }

    .password-header h2 {
        color: #1976d2;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 28px;
    }

    .password-header p {
        color: #546e7a;
        font-size: 16px;
        margin: 0;
    }

    .password-body {
        padding: 0 15px;
    }

    .logo {
        text-align: center;
        margin: 15px 0 25px;
    }

    .logo img {
        width: 80px;
        height: 80px;
        filter: drop-shadow(0 3px 6px rgba(25, 118, 210, 0.2));
        transition: transform 0.3s ease;
    }

    .logo:hover img {
        transform: scale(1.05);
    }

    .instruction p {
        color: #546e7a;
        text-align: center;
        margin: 0 0 25px;
        font-size: 15px;
        line-height: 1.5;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        color: #1976d2;
        font-weight: 500;
        display: block;
        margin-bottom: 8px;
        font-size: 15px;
    }

    .input-with-icon {
        position: relative;
        display: flex;
        align-items: center;
        border: 1px solid #bbdefb;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .input-with-icon:hover {
        border-color: #64b5f6;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .input-with-icon i {
        color: #64b5f6;
        position: absolute;
        left: 15px;
        font-size: 18px;
    }

    .form-control {
        width: 100%;
        padding: 14px 20px 14px 50px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        color: #37474f;
        box-sizing: border-box;
        outline: none;
    }

    .btn-submit {
        background: #1976d2;
        color: white;
        border-radius: 8px;
        padding: 14px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 6px rgba(25, 118, 210, 0.2);
        width: 100%;
        font-size: 16px;
        cursor: pointer;
    }

    .btn-submit:hover {
        background: #1565c0;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(21, 101, 192, 0.25);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .password-footer {
        text-align: center;
        margin-top: 30px;
        color: #546e7a;
        font-size: 14px;
    }

    .password-footer p {
        margin: 8px 0;
    }

    .password-footer a {
        color: #1976d2;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }

    .password-footer a:hover {
        color: #0d47a1;
        text-decoration: underline;
    }

    .message {
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid;
        margin: 0 0 25px 0;
        display: flex;
        align-items: center;
    }

    .message i {
        margin-right: 12px;
        font-size: 20px;
    }

    .success {
        background-color: #e8f5e9;
        border-left-color: #2e7d32;
        color: #1b5e20;
    }

    .error {
        background-color: #ffebee;
        border-left-color: #c62828;
        color: #b71c1c;
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
        .password-container {
            padding: 25px 20px;
        }
        
        .password-header h2 {
            font-size: 24px;
        }
    }
</style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h2><?= $lang['forgot_password_title'] ?></h2>
            <p><?= $lang['reset_password_instructions'] ?></p>
        </div>
        
        <div class="password-body">
            <div class="logo">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%232c3e50' d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z'/%3E%3C/svg%3E" alt="Library Logo">
            </div>
            
            <div class="instruction">
                <p><?= $lang['enter_email_associated'] ?></p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label for="email"><?= $lang['email_address'] ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="<?= $lang['email_placeholder'] ?>" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> <?= $lang['send_reset_instructions'] ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="password-footer">
            <p><?= $lang['remember_password'] ?> <a href="login.php"><?= $lang['sign_in'] ?></a></p>
            <p>© <?= date('Y') ?> GPLMS <?= $lang['open_source_project'] ?></p>
        </div>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= $lang['sending'] ?>...';
        });
    </script>
</body>
</html>