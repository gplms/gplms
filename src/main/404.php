<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | 404</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="icon" type="image/png" href="../../assets/logo-l.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            border-top: 4px solid #dc3545;
        }
        
        .error-header {
            margin-bottom: 30px;
        }
        
        .error-number {
            font-size: 5rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .error-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
        }
        
        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-container {
            margin-bottom: 30px;
        }
        
        .btn-login {
            background: #0d6efd;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 4px;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-login:hover {
            background: #0b5ed7;
        }
        
        .btn-login i {
            margin-right: 8px;
        }
        
        .error-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .error-links a {
            color: #495057;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
        }
        
        .error-links a:hover {
            color: #0d6efd;
            text-decoration: underline;
        }
        
        .footer {
            margin-top: 40px;
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
        }
        
        @media (max-width: 576px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-number {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-number">404</div>
            <h1 class="error-title">Page Not Found</h1>
        </div>
        
        <p class="error-message">
            The requested page could not be located. It may have been moved, deleted, or the URL was entered incorrectly.
        </p>
        
        <div class="btn-container">
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Go to Login Page
            </a>
        </div>
        
        
    </div>
    
    <div class="footer">
        &copy; 2025 GPLMS. All rights reserved.
    </div>
</body>
</html>