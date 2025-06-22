<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gplms_general');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Email configuration
define('SMTP_HOST', 'smtp.example.com'); // Replace with your SMTP host
define('SMTP_PORT', 587); // Replace with your SMTP port
define('SMTP_USERNAME', 'your_email@example.com'); // Replace with your SMTP username
define('SMTP_PASSWORD', 'your_email_password'); // Replace with your SMTP password
define('EMAIL_FROM', 'no-reply@yourdomain.com'); // Replace with your from email
define('EMAIL_FROM_NAME', 'Library System'); // Replace with your from name

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $message = "Please enter your email address";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format";
        $message_type = "error";
    } else {
        try {
            // Check if email exists in database
            $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate unique token
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration
                
                // Delete any existing tokens for this user
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['user_id']]);
                
                // Store token in database
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['user_id'], $token, $expires]);
                
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message_body = "Hello " . htmlspecialchars($user['full_name']) . ",\n\n";
                $message_body .= "You requested a password reset for your account.\n\n";
                $message_body .= "Please click the following link to reset your password:\n";
                $message_body .= $reset_link . "\n\n";
                $message_body .= "This link will expire in 1 hour.\n\n";
                $message_body .= "If you didn't request this, please ignore this email.\n\n";
                $message_body .= "Regards,\nLibrary System";
                
                // Send email using SMTP
                if (send_email($email, $user['full_name'], $subject, $message_body)) {
                    $message = "Password reset instructions have been sent to your email";
                    $message_type = "success";
                } else {
                    $message = "Failed to send email. Please try again later.";
                    $message_type = "error";
                }
            } else {
                $message = "If your email exists in our system, you will receive reset instructions";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Function to send email using SMTP
function send_email($to_email, $to_name, $subject, $body) {
    try {
        // In a real implementation, use PHPMailer or similar library
        // This is a simplified implementation for demonstration
        
        $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Use mail() function - in production, use SMTP library
        return mail($to_email, $subject, $body, $headers);
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Library System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #28a745;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .password-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .password-header {
            background: var(--primary);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        
        .password-header h2 {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .password-header p {
            opacity: 0.9;
            margin-bottom: 0;
            font-size: 1rem;
        }
        
        .password-body {
            padding: 40px 30px;
        }
        
        .instruction {
            text-align: center;
            margin-bottom: 30px;
            color: var(--gray);
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }
        
        .form-control {
            padding: 12px 15px 12px 45px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            height: 48px;
            font-size: 16px;
            width: 100%;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-submit {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        
        .btn-submit:hover {
            background: #2185d0;
        }
        
        .password-footer {
            text-align: center;
            padding: 20px;
            background-color: var(--light);
            border-top: 1px solid var(--light-gray);
            color: var(--gray);
        }
        
        .password-footer a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .password-footer a:hover {
            text-decoration: underline;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        
        .message i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .message.success {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.2);
            color: var(--success);
        }
        
        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--danger);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        
        @media (max-width: 576px) {
            .password-body {
                padding: 30px 20px;
            }
            
            .password-header {
                padding: 25px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h2>Reset Your Password</h2>
            <p>Enter your email to receive reset instructions</p>
        </div>
        
        <div class="password-body">
            <div class="logo">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%232c3e50' d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z'/%3E%3C/svg%3E" alt="Library Logo">
            </div>
            
            <div class="instruction">
                <p>Enter the email address associated with your account and we'll send you a link to reset your password.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Reset Instructions
                    </button>
                </div>
            </form>
        </div>
        
        <div class="password-footer">
            <p>Remember your password? <a href="login.php">Sign In</a></p>
            <p>© <?= date('Y') ?> Library Management System</p>
        </div>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        });
    </script>
</body>
</html>