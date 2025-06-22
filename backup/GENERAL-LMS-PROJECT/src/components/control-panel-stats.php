    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Material Types Chart
            const materialsCtx = document.getElementById('materialsChart').getContext('2d');
            const materialLabels = <?= json_encode(array_keys($material_distribution)) ?>;
            const materialData = <?= json_encode(array_values($material_distribution)) ?>;
            
            const materialsChart = new Chart(materialsCtx, {
                type: 'doughnut',
                data: {
                    labels: materialLabels,
                    datasets: [{
                        data: materialData,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c',
                            '#1abc9c', '#34495e', '#d35400', '#8e44ad', '#16a085'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
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
            
            // Language Distribution Chart
            const languageCtx = document.getElementById('languageChart').getContext('2d');
            const languageLabels = <?= json_encode(array_keys($language_distribution)) ?>;
            const languageData = <?= json_encode(array_values($language_distribution)) ?>;
            
            const languageChart = new Chart(languageCtx, {
                type: 'pie',
                data: {
                    labels: languageLabels,
                    datasets: [{
                        data: languageData,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#9b59b6', '#f1c40f', '#e74c3c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
            
            // Publication Year Chart
            const yearCtx = document.getElementById('yearChart').getContext('2d');
            const yearLabels = <?= json_encode(array_keys($yearly_publications)) ?>;
            const yearData = <?= json_encode(array_values($yearly_publications)) ?>;
            
            const yearChart = new Chart(yearCtx, {
                type: 'bar',
                data: {
                    labels: yearLabels,
                    datasets: [{
                        label: 'Publications',
                        data: yearData,
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Tab persistence
            const tabLinks = document.querySelectorAll('#sidebar .nav-link');
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    localStorage.setItem('lastTab', target);
                });
            });
            
            // Load last active tab
            const lastTab = localStorage.getItem('lastTab');
            if (lastTab) {
                const tab = new bootstrap.Tab(document.querySelector(`a[data-bs-target="${lastTab}"]`));
                tab.show();
            }
            
            // Open modals if needed
            <?php if ($edit_user): ?>
                const userModal = new bootstrap.Modal(document.getElementById('userModal'));
                userModal.show();
            <?php endif; ?>
            
            <?php if ($edit_role): ?>
                const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
                roleModal.show();
            <?php endif; ?>
            
            <?php if ($edit_material): ?>
                const materialModal = new bootstrap.Modal(document.getElementById('materialModal'));
                materialModal.show();
            <?php endif; ?>
            
            <?php if ($edit_category): ?>
                const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
                categoryModal.show();
            <?php endif; ?>
            
            <?php if ($edit_publisher): ?>
                const publisherModal = new bootstrap.Modal(document.getElementById('publisherModal'));
                publisherModal.show();
            <?php endif; ?>
            
            <?php if ($edit_author): ?>
                const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
                authorModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
    </script>