<script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Category Distribution Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryLabels = <?= json_encode(array_keys($category_distribution)) ?>;
            const categoryData = <?= json_encode(array_values($category_distribution)) ?>;
            
            const categoryChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Items in Category',
                        data: categoryData,
                        backgroundColor: '#4e73df',
                        borderColor: '#4e73df',
                        borderWidth: 1
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
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y} items`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Open modal if editing
            <?php if ($edit_category): ?>
                const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
                categoryModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this category?')) {
                    e.preventDefault();
                }
            });
        });
    </script>