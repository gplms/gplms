 <script>
        // Toggle between form and CSV sections
        document.getElementById('formToggle').addEventListener('click', function() {
            document.getElementById('formSection').classList.remove('hidden-section');
            document.getElementById('csvSection').classList.add('hidden-section');
            this.classList.add('active');
            document.getElementById('csvToggle').classList.remove('active');
        });
        
        document.getElementById('csvToggle').addEventListener('click', function() {
            document.getElementById('csvSection').classList.remove('hidden-section');
            document.getElementById('formSection').classList.add('hidden-section');
            this.classList.add('active');
            document.getElementById('formToggle').classList.remove('active');
        });
        
        // Toggle expandable sections
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const toggleBtn = section.previousElementSibling;
            
            section.classList.toggle('hidden-section');
            toggleBtn.classList.toggle('collapsed');
        }
        
        // Add additional author fields
        function addAuthorField() {
            const container = document.getElementById('newAuthorSection');
            const count = container.querySelectorAll('.new-field').length + 1;
            
            const newField = document.createElement('div');
            newField.className = 'new-field';
            newField.innerHTML = `
                <label class="form-label">Author ${count}</label>
                <input type="text" name="new_author[]" class="form-control">
            `;
            
            container.insertBefore(newField, container.lastElementChild);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default active tab
            document.getElementById('formToggle').classList.add('active');
        });
    </script>