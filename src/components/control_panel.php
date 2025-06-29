<?php
// Database configuration - UPDATE THESE WITH YOUR CREDENTIALS
$host = '127.0.0.1';
$dbname = 'gplms_general';
$username = 'root'; // Your database username
$password = 'root';     // Your database password

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Fetch statistics data
    $stats = [
        'users' => $pdo->query("SELECT COUNT(*) AS count FROM users")->fetchColumn(),
        'items' => $pdo->query("SELECT COUNT(*) AS count FROM library_items")->fetchColumn(),
        'authors' => $pdo->query("SELECT COUNT(*) AS count FROM authors")->fetchColumn(),
        'activity' => $pdo->query("SELECT COUNT(*) AS count FROM activity_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
    ];
    
    // Fetch material types distribution
    $materialTypes = $pdo->query("
        SELECT mt.type_name, COUNT(li.item_id) AS count 
        FROM material_types mt 
        LEFT JOIN library_items li ON mt.type_id = li.type_id 
        GROUP BY mt.type_id
    ")->fetchAll();
    
    // Fetch language distribution
    $languages = $pdo->query("
        SELECT language, COUNT(*) AS count 
        FROM library_items 
        GROUP BY language
    ")->fetchAll();
    
    // Fetch publication timeline (last 10 years)
    $currentYear = date('Y');
    $timeline = $pdo->query("
        SELECT publication_year, COUNT(*) AS count 
        FROM library_items 
        WHERE publication_year BETWEEN ($currentYear - 9) AND $currentYear
        GROUP BY publication_year 
        ORDER BY publication_year
    ")->fetchAll();
    
    // Fill in missing years
    $timelineData = [];
    for ($year = $currentYear - 9; $year <= $currentYear; $year++) {
        $found = false;
        foreach ($timeline as $row) {
            if ($row['publication_year'] == $year) {
                $timelineData[] = $row;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $timelineData[] = ['publication_year' => $year, 'count' => 0];
        }
    }
    
    // Fetch category distribution
    $categories = $pdo->query("
        SELECT c.name, COUNT(li.item_id) AS count 
        FROM categories c 
        LEFT JOIN library_items li ON c.category_id = li.category_id 
        GROUP BY c.category_id
    ")->fetchAll();
    
    // Fetch user roles distribution
    $roles = $pdo->query("
        SELECT r.role_name, COUNT(u.user_id) AS count 
        FROM roles r 
        LEFT JOIN users u ON r.role_id = u.role_id 
        GROUP BY r.role_id
    ")->fetchAll();
    
    // Fetch recent activity
    $recentActivity = $pdo->query("
        SELECT a.*, u.full_name 
        FROM activity_logs a 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY timestamp DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #2980b9;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, #1a2530 100%);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            margin: 10px 0 0;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 14px 25px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary);
        }
        
        .nav-link i {
            font-size: 1.1rem;
            width: 24px;
        }
        
        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 10px 20px;
        }
        
        /* Main Content */
        #content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 25px;
            transition: all 0.3s;
        }
        
        .topbar {
            background: white;
            padding: 18px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }
        
        .header-title span {
            color: var(--secondary);
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--info));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .user-role {
            color: var(--secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        
        .stat-icon.users { background: rgba(52, 152, 219, 0.1); color: var(--secondary); }
        .stat-icon.books { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .stat-icon.authors { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .stat-icon.activity { background: rgba(241, 196, 15, 0.1); color: var(--warning); }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        /* Chart Grid */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: 100%;
        }
        
        .chart-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 18px 25px;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-action-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .chart-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .chart-container {
            padding: 20px;
            height: 320px;
            position: relative;
        }
        
        .chart-container canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }
        
        .no-data-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-weight: 500;
            font-size: 1.1rem;
            text-align: center;
            width: 100%;
            padding: 0 20px;
        }
        
        /* Additional Charts */
        .additional-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .activity-list {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 18px;
        }
        
        .activity-content {
            flex-grow: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-details {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .activity-time {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            gap: 15px;
        }
        
        .activity-time span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .additional-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            #sidebar {
                margin-left: -260px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-user {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .additional-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Toggle Button */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--secondary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            .sidebar-toggle {
                display: flex;
            }
        }
        
        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid #eaeaea;
            margin-top: 20px;
        }
        
        /* Fullscreen mode */
        .fullscreen-chart {
            position: fixed !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            z-index: 2000;
            background: white;
            padding: 0;
        }
        
        .fullscreen-chart .chart-container {
            height: calc(100% - 60px) !important;
            padding: 15px;
        }
        
        .fullscreen-chart .chart-header {
            border-radius: 0;
            padding: 15px 20px;
        }
        
        .fullscreen-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1999;
            display: none;
        }
        
        /* Loading spinner */
        .chart-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(52, 152, 219, 0.2);
            border-top: 5px solid var(--secondary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Close button for fullscreen */
        .fullscreen-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s;
        }
        
        .fullscreen-close:hover {
            background: rgba(0, 0, 0, 0.4);
            transform: scale(1.1);
        }
        
        /* Color indicators */
        .color-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .indicator-book { background-color: #3498db; }
        .indicator-journal { background-color: #2ecc71; }
        .indicator-magazine { background-color: #9b59b6; }
        .indicator-newspaper { background-color: #f1c40f; }
        .indicator-manuscript { background-color: #e74c3c; }
    </style>
</head>
<body>
    <!-- Fullscreen backdrop -->
    <div class="fullscreen-backdrop" id="fullscreenBackdrop"></div>
    
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sidebar-header">
            
            <h3>GPLMS</h3>
            <p>Administration Panel</p>
        </div>
        
        <div class="sidebar-divider"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="control_panel.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="materials-manager.php">
                    <i class="fas fa-book"></i> Library Materials
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users-manager.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="roles-manager.php">
                    <i class="fas fa-user-tag"></i> Roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories-manager.php">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="publishers-manager.php">
                    <i class="fas fa-print"></i> Publishers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="authors-manager.php">
                    <i class="fas fa-feather"></i> Authors
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="settings-manager.php">
                    <i class="fas fa-cog"></i> System Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="activity-log.php">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-question-circle"></i> Help
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <div>
                <h1 class="header-title">Admin <span>Dashboard</span></h1>
                <p class="text-muted">General Analytics and statistics overview</p>
            </div>
            <div class="header-user">
                <div class="user-avatar">A</div>
                <div class="user-info">
                    <div class="user-name">Admin User</div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $stats['users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon books">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?= $stats['items'] ?></div>
                <div class="stat-label">Library Items</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon authors">
                    <i class="fas fa-feather"></i>
                </div>
                <div class="stat-number"><?= $stats['authors'] ?></div>
                <div class="stat-label">Authors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon activity">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-number"><?= $stats['activity'] ?></div>
                <div class="stat-label">Activities (7d)</div>
            </div>
        </div>
        
        <!-- Main Chart Grid -->
        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-header">
                    Material Types Distribution
                    <div class="chart-actions">
                        <button class="chart-action-btn chart-refresh" data-chart="materialsChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="chart-action-btn chart-fullscreen" data-chart="materialsChart">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="materialsChart"></canvas>
                    <div id="materialsNoData" class="no-data-message" style="display: <?= empty($materialTypes) ? 'block' : 'none' ?>;">
                        No data available for material types
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    Publication Timeline
                    <div class="chart-actions">
                        <button class="chart-action-btn chart-refresh" data-chart="timelineChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="chart-action-btn chart-fullscreen" data-chart="timelineChart">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="timelineChart"></canvas>
                    <div id="timelineNoData" class="no-data-message" style="display: <?= empty($timelineData) ? 'block' : 'none' ?>;">
                        No publication data available
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Charts Grid -->
        <div class="additional-grid">
            <div class="chart-card">
                <div class="chart-header">
                    Language Distribution
                    <div class="chart-actions">
                        <button class="chart-action-btn chart-refresh" data-chart="languageChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="chart-action-btn chart-fullscreen" data-chart="languageChart">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="languageChart"></canvas>
                    <div id="languageNoData" class="no-data-message" style="display: <?= empty($languages) ? 'block' : 'none' ?>;">
                        No language data available
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    Category Distribution
                    <div class="chart-actions">
                        <button class="chart-action-btn chart-refresh" data-chart="categoryChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="chart-action-btn chart-fullscreen" data-chart="categoryChart">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="categoryChart"></canvas>
                    <div id="categoryNoData" class="no-data-message" style="display: <?= empty($categories) ? 'block' : 'none' ?>;">
                        No category data available
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    User Roles Distribution
                    <div class="chart-actions">
                        <button class="chart-action-btn chart-refresh" data-chart="rolesChart">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="chart-action-btn chart-fullscreen" data-chart="rolesChart">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="rolesChart"></canvas>
                    <div id="rolesNoData" class="no-data-message" style="display: <?= empty($roles) ? 'block' : 'none' ?>;">
                        No role data available
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <div class="chart-header">Recent Activity</div>
            <div class="activity-list">
                <?php if (empty($recentActivity)): ?>
                    <div class="text-center py-4 text-muted">No recent activity found</div>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><i class="fas fa-<?= 
                                strpos($activity['action'], 'add') !== false ? 'plus' : 
                                (strpos($activity['action'], 'update') !== false ? 'sync' : 
                                (strpos($activity['action'], 'check') !== false ? 'book' : 
                                (strpos($activity['action'], 'register') !== false ? 'user-plus' : 'history'))) 
                            ?>"></i></div>
                            <div class="activity-content">
                                <div class="activity-title"><?= ucfirst($activity['action']) ?></div>
                                <div class="activity-details"><?= $activity['details'] ?? 'No details available' ?></div>
                                <div class="activity-time">
                                    <span><i class="fas fa-clock"></i> <?= date('M j, Y g:i A', strtotime($activity['timestamp'])) ?></span>
                                    <span><i class="fas fa-user"></i> <?= $activity['full_name'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-footer">
            GPLMS Open Source Project v0.1 &copy; 2025 | Last updated: <?= date('F j, Y \a\t H:i') ?>
            <br>
            For licence Overview visit: <a href = "https://github.com/PanagiotisKotsorgios/gplms/blob/main/LICENSE">MIT LICENCE</a>
        </div>
    </div>

    <script>
        // Store chart instances
        const chartInstances = {};
        
        // Prepare chart data from PHP
        const chartData = {
            materials: {
                labels: <?= json_encode(array_column($materialTypes, 'type_name')) ?>,
                values: <?= json_encode(array_column($materialTypes, 'count')) ?>,
                colors: ['#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c']
            },
            languages: {
                labels: <?= json_encode(array_column($languages, 'language')) ?>,
                values: <?= json_encode(array_column($languages, 'count')) ?>,
                colors: ['#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c', '#1abc9c']
            },
            timeline: {
                labels: <?= json_encode(array_column($timelineData, 'publication_year')) ?>,
                values: <?= json_encode(array_column($timelineData, 'count')) ?>
            },
            categories: {
                labels: <?= json_encode(array_column($categories, 'name')) ?>,
                values: <?= json_encode(array_column($categories, 'count')) ?>
            },
            roles: {
                labels: <?= json_encode(array_column($roles, 'role_name')) ?>,
                values: <?= json_encode(array_column($roles, 'count')) ?>,
                colors: ['#3498db', '#2ecc71', '#9b59b6', '#f1c40f']
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Charts
            chartInstances.materialsChart = initMaterialChart();
            chartInstances.languageChart = initLanguageChart();
            chartInstances.timelineChart = initTimelineChart();
            chartInstances.categoryChart = initCategoryChart();
            chartInstances.rolesChart = initRolesChart();
            
            // Toggle sidebar on mobile
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
            
            // Setup refresh buttons
            document.querySelectorAll('.chart-refresh').forEach(button => {
                button.addEventListener('click', function() {
                    const chartId = this.getAttribute('data-chart');
                    refreshChart(chartId);
                });
            });
            
            // Setup fullscreen buttons
            document.querySelectorAll('.chart-fullscreen').forEach(button => {
                button.addEventListener('click', function() {
                    const chartId = this.getAttribute('data-chart');
                    openFullscreen(chartId);
                });
            });
        });
        
        function refreshChart(chartId) {
            // Show loading spinner
            const container = document.querySelector(`#${chartId}`).parentElement;
            const loading = container.querySelector('.chart-loading');
            loading.style.display = 'flex';
            
            // Destroy current chart instance
            if (chartInstances[chartId]) {
                chartInstances[chartId].destroy();
            }
            
            // Simulate data refresh with delay
            setTimeout(() => {
                // Reinitialize the chart
                switch(chartId) {
                    case 'materialsChart':
                        chartInstances[chartId] = initMaterialChart();
                        break;
                    case 'languageChart':
                        chartInstances[chartId] = initLanguageChart();
                        break;
                    case 'timelineChart':
                        chartInstances[chartId] = initTimelineChart();
                        break;
                    case 'categoryChart':
                        chartInstances[chartId] = initCategoryChart();
                        break;
                    case 'rolesChart':
                        chartInstances[chartId] = initRolesChart();
                        break;
                }
                
                // Hide loading spinner
                loading.style.display = 'none';
            }, 1000);
        }
        
        function openFullscreen(chartId) {
            const chartCard = document.querySelector(`#${chartId}`).closest('.chart-card');
            const backdrop = document.getElementById('fullscreenBackdrop');
            
            // Add fullscreen class to chart card
            chartCard.classList.add('fullscreen-chart');
            
            // Show backdrop
            backdrop.style.display = 'block';
            
            // Add close button
            const closeButton = document.createElement('button');
            closeButton.className = 'fullscreen-close';
            closeButton.innerHTML = '<i class="fas fa-times"></i>';
            closeButton.addEventListener('click', closeFullscreen);
            chartCard.appendChild(closeButton);
            
            // Re-render chart for fullscreen
            if (chartInstances[chartId]) {
                chartInstances[chartId].resize();
            }
        }
        
        function closeFullscreen() {
            const fullscreenCharts = document.querySelectorAll('.fullscreen-chart');
            const backdrop = document.getElementById('fullscreenBackdrop');
            
            fullscreenCharts.forEach(chart => {
                // Remove close button
                const closeButton = chart.querySelector('.fullscreen-close');
                if (closeButton) {
                    closeButton.remove();
                }
                
                // Remove fullscreen class
                chart.classList.remove('fullscreen-chart');
            });
            
            backdrop.style.display = 'none';
            
            // Re-render charts after exiting fullscreen
            Object.keys(chartInstances).forEach(chartId => {
                if (chartInstances[chartId]) {
                    chartInstances[chartId].resize();
                }
            });
        }
        
        function initMaterialChart() {
            const materialsCtx = document.getElementById('materialsChart').getContext('2d');
            
            if (chartData.materials.labels.length === 0) {
                return null;
            }
            
            return new Chart(materialsCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.materials.labels,
                    datasets: [{
                        data: chartData.materials.values,
                        backgroundColor: chartData.materials.colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const backgroundColor = data.datasets[0].backgroundColor[i];
                                            return {
                                                text: label,
                                                fillStyle: backgroundColor,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function initLanguageChart() {
            const languageCtx = document.getElementById('languageChart').getContext('2d');
            
            if (chartData.languages.labels.length === 0) {
                return null;
            }
            
            return new Chart(languageCtx, {
                type: 'pie',
                data: {
                    labels: chartData.languages.labels,
                    datasets: [{
                        data: chartData.languages.values,
                        backgroundColor: chartData.languages.colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function initTimelineChart() {
            const timelineCtx = document.getElementById('timelineChart').getContext('2d');
            
            if (chartData.timeline.labels.length === 0) {
                return null;
            }
            
            return new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: chartData.timeline.labels,
                    datasets: [{
                        label: 'Publications Added',
                        data: chartData.timeline.values,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: '#3498db',
                        borderWidth: 3,
                        pointBackgroundColor: '#3498db',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initCategoryChart() {
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            
            if (chartData.categories.labels.length === 0) {
                return null;
            }
            
            return new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: chartData.categories.labels,
                    datasets: [{
                        label: 'Items',
                        data: chartData.categories.values,
                        backgroundColor: '#3498db',
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initRolesChart() {
            const rolesCtx = document.getElementById('rolesChart').getContext('2d');
            
            if (chartData.roles.labels.length === 0) {
                return null;
            }
            
            return new Chart(rolesCtx, {
                type: 'polarArea',
                data: {
                    labels: chartData.roles.labels,
                    datasets: [{
                        data: chartData.roles.values,
                        backgroundColor: chartData.roles.colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>