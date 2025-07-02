<?php
// Start session and include translation component
session_start();
require_once '../conf/config.php';
require_once '../conf/translation.php'; // Include the translation component
?>

<!DOCTYPE html>
<html lang="<?= $default_language === 'GR' ? 'el' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['403_page_title'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 40px;
            text-align: center;
            border-top: 4px solid #dc3545;
        }
        
        .error-header {
            margin-bottom: 30px;
        }
        
        .error-icon {
            font-size: 3.5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .error-number {
            font-size: 5rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 15px;
            line-height: 1;
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
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0d6efd;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 4px;
            transition: background 0.2s;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn-action:hover {
            background: #0b5ed7;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5c636a;
        }
        
        .btn-action i {
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
            .container {
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
            
            .btn-container {
                display: flex;
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="error-number">403</div>
            <h1 class="error-title"><?= $lang['access_denied_title'] ?></h1>
        </div>
        
        <p class="error-message">
            <?= $lang['access_denied_message1'] ?>
        </p>
        <p class="error-message">
            <?= $lang['access_denied_message2'] ?>
        </p>
        
        <div class="btn-container">
            <a href="login.php" class="btn-action">
                <i class="fas fa-sign-in-alt"></i> <?= $lang['go_to_login'] ?>
            </a>
        </div>
    </div>
    
    <div class="footer">
        &copy; <?= date('Y') ?> GPLMS. <?= $lang['all_rights_reserved'] ?> 
    </div>
</body>
</html>