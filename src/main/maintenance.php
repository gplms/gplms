<?php
// maintenance.php
require_once '../conf/config.php'; // Database connection
require_once '../conf/translation.php'; // Include translation system

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user session is active; if not, redirect to login.php
if (!isset($_SESSION['user_id'])) { // Adjust 'user_id' to your session variable name
    header("Location: login.php");
    exit();
}

// Get library name for display
$library_name = $lang['library_system']; // Use translation key as default
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'library_name'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['setting_value'])) {
        $library_name = $result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Library name fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?= $default_language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['maintenance_mode'] ?></title>
    <style>
            body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .maintenance-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        .maintenance-icon {
            font-size: 5rem;
            color: #ff6b6b;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        h1 {
            color: #ff6b6b;
            margin-bottom: 15px;
            font-size: 2.5rem;
        }
        
        p {
            color: #555;
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .admin-login {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        
        .admin-login a {
            display: inline-block;
            background: #4e73df;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .admin-login a:hover {
            background: #2e59d9;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        }
        
        .status {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .library-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="library-name"><?= htmlspecialchars($library_name) ?></div>
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <div class="status"><?= strtoupper($lang['maintenance_mode']) ?></div>
        <h1><?= $lang['system_under_maintenance'] ?></h1>
        
        <p><?= $lang['maintenance_message1'] ?></p>
        <p><?= $lang['maintenance_message2'] ?></p>
        
        <div class="admin-login">
            <p><?= $lang['admin_login_prompt'] ?></p>
            <a href="login.php"><i class="fas fa-lock"></i> <?= $lang['admin_login'] ?></a>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>