<?php

session_start();

require_once '../conf/config.php';
require_once '../conf/check-session.php';
require_once '../conf/translation.php';
require_once '../functions/fetch-lib-name.php';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
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
    <title><?= $lang['page_title_control_panel'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <link href="../styles/general/control-panel-full-styles.css" rel="stylesheet">
    <link href="../styles/components/sidebar1.css" rel="stylesheet">
    <style>
        .fullscreen-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1040;
        }
        
        .fullscreen-chart {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 80%;
            z-index: 1060;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            padding: 15px;
        }
        
        .fullscreen-chart .chart-header {
            height: 50px;
        }
        
        .fullscreen-chart .chart-container {
            height: calc(100% - 50px);
            width: 100%;
        }
        
        .fullscreen-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            z-index: 1070;
        }
        
        .fullscreen-close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Fullscreen backdrop -->
    <div class="fullscreen-backdrop" id="fullscreenBackdrop"></div>
    
    <?php include'../components/sidebar1.php' ?>
    
    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <div>
                <h1 class="header-title"><?= $lang['admin_dashboard_title'] ?></h1>
                <p class="text-muted"><?= $lang['dashboard_subtitle'] ?></p>
            </div>
            <div class="header-user">
                <div class="user-avatar"><?= substr($_SESSION['username'], 0, 1) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= $_SESSION['username'] ?></div>
                    <div class="user-role"><?= $_SESSION['role'] ?></div>
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
                <div class="stat-label"><?= $lang['total_users'] ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon books">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?= $stats['items'] ?></div>
                <div class="stat-label"><?= $lang['library_items'] ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon authors">
                    <i class="fas fa-feather"></i>
                </div>
                <div class="stat-number"><?= $stats['authors'] ?></div>
                <div class="stat-label"><?= $lang['authors'] ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon activity">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-number"><?= $stats['activity'] ?></div>
                <div class="stat-label"><?= $lang['activities_7d'] ?></div>
            </div>
        </div>
        
        <!-- Main Chart Grid -->
        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <?= $lang['material_types_distribution'] ?>
                    <div class="chart-actions">
                       
                       
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="materialsChart"></canvas>
                    <div id="materialsNoData" class="no-data-message" style="display: <?= empty($materialTypes) ? 'block' : 'none' ?>;">
                        <?= $lang['no_material_data'] ?>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <?= $lang['publication_timeline'] ?>
                    <div class="chart-actions">
                   
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="timelineChart"></canvas>
                    <div id="timelineNoData" class="no-data-message" style="display: <?= empty($timelineData) ? 'block' : 'none' ?>;">
                        <?= $lang['no_publication_data'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Charts Grid -->
        <div class="additional-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <?= $lang['language_distribution'] ?>
                    <div class="chart-actions">
                       
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="languageChart"></canvas>
                    <div id="languageNoData" class="no-data-message" style="display: <?= empty($languages) ? 'block' : 'none' ?>;">
                        <?= $lang['no_language_data'] ?>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <?= $lang['category_distribution'] ?>
                    <div class="chart-actions">
                       
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="categoryChart"></canvas>
                    <div id="categoryNoData" class="no-data-message" style="display: <?= empty($categories) ? 'block' : 'none' ?>;">
                        <?= $lang['no_category_data'] ?>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <?= $lang['user_roles_distribution'] ?>
                    <div class="chart-actions">
                       
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                    </div>
                    <canvas id="rolesChart"></canvas>
                    <div id="rolesNoData" class="no-data-message" style="display: <?= empty($roles) ? 'block' : 'none' ?>;">
                        <?= $lang['no_role_data'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <div class="chart-header"><?= $lang['recent_activity'] ?></div>
            <div class="activity-list">
                <?php if (empty($recentActivity)): ?>
                    <div class="text-center py-4 text-muted"><?= $lang['no_recent_activity'] ?></div>
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
                                <div class="activity-details"><?= $activity['details'] ?? $lang['no_details'] ?></div>
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
            <?= $lang['footer_text'] ?> <?= date('F j, Y \a\t H:i') ?>
            <br>
            <?= $lang['license_text'] ?>: <a href = "https://github.com/PanagiotisKotsorgios/gplms/blob/main/LICENSE"><?= $lang['mit_license'] ?></a>
        </div>
    </div>

    <script>
        // Store chart instances
        const chartInstances = {};
        let currentFullscreenChartId = null;
        
        // Map chart IDs to data keys
        const chartDataMap = {
            materialsChart: 'materials',
            timelineChart: 'timeline',
            languageChart: 'languages',
            categoryChart: 'categories',
            rolesChart: 'roles'
        };
        
        // Chart initialization functions
        const chartInitFunctions = {
            materialsChart: initMaterialChart,
            timelineChart: initTimelineChart,
            languageChart: initLanguageChart,
            categoryChart: initCategoryChart,
            rolesChart: initRolesChart
        };
        
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
            
            // Close fullscreen when clicking on backdrop
            document.getElementById('fullscreenBackdrop').addEventListener('click', closeFullscreen);
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
            
            // Simulate AJAX call to get new data
            setTimeout(() => {
                // Get data key for this chart
                const dataKey = chartDataMap[chartId];
                
                // Simulate data change by incrementing first value
                if (chartData[dataKey].values.length > 0) {
                    chartData[dataKey].values[0]++;
                }
                
                // Reinitialize the chart
                chartInstances[chartId] = chartInitFunctions[chartId]();
                
                // Hide loading spinner
                loading.style.display = 'none';
            }, 1000);
        }
        
        function openFullscreen(chartId) {
            if (currentFullscreenChartId) {
                closeFullscreen();
            }
            
            currentFullscreenChartId = chartId;
            
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
            currentFullscreenChartId = null;
            
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
                        label: '<?= $lang['publications_added'] ?>',
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
                        label: '<?= $lang['items'] ?>',
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