<?php
require_once '../conf/config.php';

$message = '';
$message_type = '';
$valid_token = false;
$user_id = null;

// Check if token exists
if (!empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Validate token
        $stmt = $pdo->prepare("SELECT pr.user_id, pr.expires_at, u.email 
                               FROM password_resets pr
                               JOIN users u ON pr.user_id = u.user_id
                               WHERE token = ?");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();

        if ($reset_request) {
            // Check token expiration
            $now = new DateTime();
            $expires_at = new DateTime($reset_request['expires_at']);
            
            if ($now < $expires_at) {
                $valid_token = true;
                $user_id = $reset_request['user_id'];
            } else {
                $message = "This password reset link has expired";
                $message_type = "error";
            }
        } else {
            $message = "Invalid password reset link";
            $message_type = "error";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($password)) {
        $message = "Please enter a new password";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters";
        $message_type = "error";
    } else {
        try {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user's password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Delete the used token
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);
            
            $message = "Your password has been updated successfully";
            $message_type = "success";
            $valid_token = false; // Disable form after success
            
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Password Reset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/reset-password-styles.css">
   <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>


<body>
    <div class="password-container">
        <div class="password-header">
            <h2>Reset Your Password</h2>
            <p>Create a new secure password</p>
        </div>
        
        <div class="password-body">
            <div class="logo">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%232c3e50' d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z'/%3E%3C/svg%3E" alt="Library Logo">
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <div class="instruction">
                    <p>Please enter your new password below. Make sure it's at least 8 characters long.</p>
                </div>
                
                <form method="POST" id="resetForm">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Enter new password" required minlength="8">
                            <span class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm new password" required minlength="8">
                            <span class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                    </div>
                </form>
            <?php elseif (empty($message)): ?>
                <div class="instruction">
                    <p>Invalid password reset request. Please make sure you're using the correct link from your email.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="password-footer">
            <p>Remember your password? <a href="login.php">Sign In</a></p>
            <p>© <?= date('Y') ?> Library Management System</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            function setupPasswordToggle(inputId, toggleId) {
                const passwordInput = document.getElementById(inputId);
                const toggleButton = document.getElementById(toggleId);
                
                if (passwordInput && toggleButton) {
                    toggleButton.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        this.querySelector('i').classList.toggle('fa-eye');
                        this.querySelector('i').classList.toggle('fa-eye-slash');
                    });
                }
            }
            
            setupPasswordToggle('password', 'togglePassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
            
            // Form submission handler
            const form = document.getElementById('resetForm');
            if (form) {
                form.addEventListener('submit', function() {
                    const btn = this.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                });
            }
            
            // Password strength indicator (optional enhancement)
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    // Add password strength meter implementation if desired
                });
            }
        });
    </script>
</body>
</html>