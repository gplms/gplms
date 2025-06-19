    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Open user modal if editing
        <?php if ($edit_user): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const userModal = new bootstrap.Modal(document.getElementById('userModal'));
                userModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>