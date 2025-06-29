 <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Author Distribution Chart
            const authorCtx = document.getElementById('authorChart').getContext('2d');
            const authorLabels = <?= json_encode(array_keys($author_distribution)) ?>;
            const authorData = <?= json_encode(array_values($author_distribution)) ?>;
            
            const authorChart = new Chart(authorCtx, {
                type: 'bar',
                data: {
                    labels: authorLabels,
                    datasets: [{
                        label: 'Items by Author',
                        data: authorData,
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
            <?php if ($edit_author): ?>
                const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
                authorModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this author?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Show full bio on hover
        const bioPreviews = document.querySelectorAll('.bio-preview');
        bioPreviews.forEach(preview => {
            preview.addEventListener('mouseover', function() {
                this.style.whiteSpace = 'normal';
                this.style.overflow = 'visible';
                this.style.textOverflow = 'clip';
            });
            
            preview.addEventListener('mouseout', function() {
                this.style.whiteSpace = 'nowrap';
                this.style.overflow = 'hidden';
                this.style.textOverflow = 'ellipsis';
            });
        });
    </script>