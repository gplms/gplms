<?php
session_start();

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';

// Handle login
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // Verify password against bcrypt hash
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        
        // Determine role
        $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $role_stmt->execute([$user['role_id']]);
        $role = $role_stmt->fetchColumn();
        
        $_SESSION['role'] = $role;
        
        // Redirect to search page
        header("Location: control_panel.php");
        exit;
    } else {
        $error_message = "Invalid username or password";
    }
}

// If user is already logged in, redirect to search page
if (isset($_SESSION['user_id'])) {
    header("Location: search.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Default Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="../styles/login-2.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>

    
    <?php include '../components/login-page-component.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form animation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Add a slight delay to floating animations
        document.querySelectorAll('.floating-books').forEach((book, index) => {
            book.style.animationDelay = `${index * 1}s`;
        });
    </script>
</body>
</html>