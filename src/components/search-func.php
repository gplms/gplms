    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Create materials map for quick lookup
        const materialsMap = {};
        <?php foreach ($materials as $item): ?>
            materialsMap[<?= $item['item_id'] ?>] = <?= json_encode($item) ?>;
        <?php endforeach; ?>
        
        // Auto-submit form when filters change
        document.querySelectorAll('select[name], input[name="search"]').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('searchForm').submit();
            });
        });
        
        // Request functions
        function requestEdit(itemId) {
            alert(`Edit request sent to admin for item ID: ${itemId}`);
            // In a real app, you would send this request to the server
        }
        
        function requestDelete(itemId) {
            alert(`Delete request sent to admin for item ID: ${itemId}`);
            // In a real app, you would send this request to the server
        }
        
        // Detail Modal Handling
        const detailModal = document.getElementById('detailModal');
        if (detailModal) {
            detailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const itemId = button.getAttribute('data-item-id');
                const item = materialsMap[itemId];
                
                if (item) {
                    // Update modal content
                    document.getElementById('detailModalLabel').textContent = `Details for ${item.title}`;
                    document.getElementById('detailTitle').textContent = item.title;
                    
                    // Authors
                    const authorsElement = document.getElementById('detailAuthors');
                    authorsElement.innerHTML = '';
                    if (item.authors) {
                        const authors = item.authors.split(', ');
                        const authorsList = document.createElement('div');
                        authorsList.className = 'authors-list';
                        
                        authors.forEach(author => {
                            const badge = document.createElement('span');
                            badge.className = 'author-badge';
                            badge.textContent = author;
                            authorsList.appendChild(badge);
                        });
                        
                        authorsElement.appendChild(authorsList);
                    } else {
                        authorsElement.textContent = 'N/A';
                    }
                    
                    // Set other details
                    document.getElementById('detailPublisher').textContent = item.publisher_name || 'N/A';
                    document.getElementById('detailYear').textContent = item.publication_year || 'N/A';
                    document.getElementById('detailLanguage').textContent = item.language || 'N/A';
                    document.getElementById('detailEdition').textContent = item.edition || 'N/A';
                    document.getElementById('detailISBN').textContent = item.isbn || 'N/A';
                    document.getElementById('detailISSN').textContent = item.issn || 'N/A';
                    document.getElementById('detailCategory').textContent = item.category_name || 'N/A';
                    
                    // Material Type with icon
                    let typeIconClass = '';
                    if (item.type_name.includes('Book')) typeIconClass = 'material-book';
                    if (item.type_name.includes('Magazine')) typeIconClass = 'material-magazine';
                    if (item.type_name.includes('Newspaper')) typeIconClass = 'material-newspaper';
                    if (item.type_name.includes('Journal')) typeIconClass = 'material-journal';
                    if (item.type_name.includes('Manuscript')) typeIconClass = 'material-manuscript';
                    
                    const typeElement = document.getElementById('detailType');
                    typeElement.innerHTML = `<i class="fas fa-book material-icon ${typeIconClass}"></i> ${item.type_name}`;
                    
                    // Status
                    const statusElement = document.getElementById('detailStatus');
                    statusElement.textContent = item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'N/A';
                    statusElement.className = 'status-badge';
                    statusElement.classList.add(`status-${item.status}`);
                    
                    document.getElementById('detailAddedBy').textContent = item.added_by_username || 'N/A';
                    document.getElementById('detailAddedDate').textContent = item.added_date ? new Date(item.added_date).toLocaleDateString() : 'N/A';
                    
                    // Description
                    document.getElementById('detailDescription').textContent = item.description || 'No description available';
                    
                    // Add action buttons to modal footer
                    const modalActions = document.getElementById('modalActions');
                    if (modalActions) {
                        modalActions.innerHTML = '';
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                modalActions.innerHTML = `
                                    <a href="edit.php?id=${item.item_id}" class="btn btn-primary me-2">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <a href="delete.php?id=${item.item_id}" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                `;
                            <?php else: ?>
                                <?php if ($item['added_by'] == $_SESSION['user_id']): ?>
                                    modalActions.innerHTML = `
                                        <a href="edit.php?id=${item.item_id}" class="btn btn-primary me-2">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <a href="delete.php?id=${item.item_id}" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                    `;
                                <?php else: ?>
                                    modalActions.innerHTML = `
                                        <button class="btn btn-warning me-2" onclick="requestEdit(${item.item_id})">
                                            <i class="fas fa-edit me-1"></i> Request Edit
                                        </button>
                                        <button class="btn btn-warning" onclick="requestDelete(${item.item_id})">
                                            <i class="fas fa-trash me-1"></i> Request Delete
                                        </button>
                                    `;
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    }
                }
            });
        }
        
        // Highlight row on hover
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(52, 152, 219, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>