<script>
    // ====================================================
    // SIDEBAR TOGGLE FUNCTIONALITY
    // Toggles sidebar visibility on mobile devices
    // ====================================================
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // ====================================================
    // TABLE ROW HOVER EFFECTS
    // Enhances UX by highlighting rows on hover
    // ====================================================
    const rows = document.querySelectorAll('.log-table tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.backgroundColor = '#f8f9fc';  // Light highlight color
        });
        row.addEventListener('mouseleave', () => {
            row.style.backgroundColor = '';  // Revert to default
        });
    });
    
    // ====================================================
    // ACTION DISTRIBUTION CHART (Doughnut)
    // Visualizes log actions by type and frequency
    // ====================================================
    const actionCtx = document.getElementById('actionChart').getContext('2d');
    const actionChart = new Chart(actionCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                    // Generate quoted action names for chart labels
                    echo implode(',', array_map(function($a) { 
                        return "'" . $a['action'] . "'"; 
                    }, $actionData)) 
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                        // Get action counts for chart data
                        echo implode(',', array_map(function($a) { 
                            return $a['count']; 
                        }, $actionData)) 
                    ?>
                ],
                backgroundColor: [
                    // Color palette for chart segments
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#5a5c69', '#858796', '#3a3b45', '#f8f9fc', '#e3e6f0'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',  // Legend placement
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            // Custom tooltip content
                            return context.label + ': ' + context.raw + ' actions';
                        }
                    }
                }
            }
        }
    });
    
    // ====================================================
    // DAILY ACTIVITY CHART (Bar)
    // Shows activity trends over last 7 days
    // ====================================================
    const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                    // Format dates for chart labels (e.g. "Jun 20")
                    echo implode(',', array_map(function($d) { 
                        $date = new DateTime($d['date']);
                        return "'" . $date->format('M d') . "'"; 
                    }, $dailyActivity)) 
                ?>
            ],
            datasets: [{
                label: 'Actions per day',
                data: [
                    <?php 
                        // Get daily action counts
                        echo implode(',', array_map(function($d) { 
                            return $d['count']; 
                        }, $dailyActivity)) 
                    ?>
                ],
                backgroundColor: '#4e73df',  // Primary brand color
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,  // Start Y-axis at 0
                    ticks: {
                        precision: 0    // Whole numbers only
                    }
                }
            }
        }
    });
    
    // ====================================================
    // FILTER FORM RESET HANDLER
    // Ensces page resets to 1 when clearing filters
    // ====================================================
    document.querySelector('.filter-form').addEventListener('reset', function() {
        // Reset pagination to first page
        document.querySelector('input[name="page"]').value = 1;
    });
</script>