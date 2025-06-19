<?php
session_start();

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

// Handle login
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $password == $user['password']) { // In real app, use password_verify()
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        
        // Determine role
        $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $role_stmt->execute([$user['role_id']]);
        $role = $role_stmt->fetchColumn();
        
        $_SESSION['role'] = $role;
        
        // Redirect to search page
        header("Location: search.php");
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
    <title>Login - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background: linear-gradient(135deg, #1e2a38 0%, #2c3e50 100%);
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1495745966610-2a67f2297e5e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            position: relative;
            z-index: 10;
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 40px 20px 30px;
            text-align: center;
            position: relative;
            color: white;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .login-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .login-header p {
            opacity: 0.85;
            margin-bottom: 0;
        }
        
        .login-body {
            padding: 40px 30px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
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
            color: var(--secondary);
            font-size: 18px;
        }
        
        .form-control {
            padding: 12px 15px 12px 45px;
            border-radius: 8px;
            border: 2px solid #e1e5eb;
            height: 50px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--secondary), #1e5799);
            color: white;
            border: none;
            padding: 14px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(52, 152, 219, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background-color: var(--light);
            border-top: 1px solid #e1e5eb;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .floating-books {
            position: absolute;
            font-size: 24px;
            color: rgba(255, 255, 255, 0.15);
            z-index: 0;
        }
        
        .book-1 { top: 10%; left: 5%; animation: float 8s infinite ease-in-out; }
        .book-2 { top: 20%; right: 10%; animation: float 10s infinite ease-in-out; }
        .book-3 { bottom: 15%; left: 15%; animation: float 12s infinite ease-in-out; }
        .book-4 { bottom: 25%; right: 5%; animation: float 9s infinite ease-in-out; }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <!-- Decorative floating books -->
    <div class="floating-books book-1"><i class="fas fa-book"></i></div>
    <div class="floating-books book-2"><i class="fas fa-book-open"></i></div>
    <div class="floating-books book-3"><i class="fas fa-bookmark"></i></div>
    <div class="floating-books book-4"><i class="fas fa-book-reader"></i></div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-book"></i>
            </div>
            <h2>LibrarySystem</h2>
            <p>Access your library management account</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error_message ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </div>
            </form>
        </div>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="contact_admin.php">Contact administrator</a></p>
            <p><a href="index.php">Return to homepage</a></p>
        </div>
    </div>
    
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