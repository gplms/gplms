<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel"><?= $lang['material_details'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="book-cover">
                            <i class="fas fa-book"></i>
                        </div>
                        <h4 id="detailTitle" class="mb-3"><?= $lang['title'] ?></h4>
                        <div class="mb-4">
                            <span class="status-badge" id="detailStatus"></span>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['authors'] ?>:</span>
                            <div class="detail-content" id="detailAuthors"></div>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['publisher'] ?>:</span>
                            <span class="detail-content" id="detailPublisher"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['publication_year'] ?>:</span>
                            <span class="detail-content" id="detailYear"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['language'] ?>:</span>
                            <span class="detail-content" id="detailLanguage"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['edition'] ?>:</span>
                            <span class="detail-content" id="detailEdition"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['isbn'] ?>:</span>
                            <span class="detail-content" id="detailISBN"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['issn'] ?>:</span>
                            <span class="detail-content" id="detailISSN"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['category'] ?>:</span>
                            <span class="detail-content" id="detailCategory"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['material_type'] ?>:</span>
                            <span class="detail-content" id="detailType"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['added_by'] ?>:</span>
                            <span class="detail-content" id="detailAddedBy"></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label"><?= $lang['added_date'] ?>:</span>
                            <span class="detail-content" id="detailAddedDate"></span>
                        </div>
                        
                        <div class="mt-4">
                            <h5><?= $lang['description'] ?>:</h5>
                            <p id="detailDescription" class="card-text"><?= $lang['no_description_available'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
                <div class="action-buttons" id="modalActions">
                    <!-- Actions will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to populate modal with actions
function setupModalActions(itemId, addedByUserId) {
    const actionsContainer = document.getElementById('modalActions');
    actionsContainer.innerHTML = '';
    
    <?php if (isset($_SESSION['user_id'])): ?>
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        const isAdmin = <?= ($_SESSION['role'] === 'admin') ? 'true' : 'false' ?>;
        
        // Create action buttons container
        const btnGroup = document.createElement('div');
        btnGroup.className = 'btn-group';
        
        // Add Edit button
        const editBtn = document.createElement('a');
        editBtn.href = `edit.php?id=${itemId}`;
        editBtn.className = 'btn btn-primary';
        editBtn.title = '<?= $lang["edit"] ?>';
        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
        
        // Add Delete button
        const deleteBtn = document.createElement('a');
        deleteBtn.href = `delete.php?id=${itemId}`;
        deleteBtn.className = 'btn btn-danger';
        deleteBtn.title = '<?= $lang["delete"] ?>';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.onclick = function() {
            return confirm('<?= $lang["confirm_delete_item"] ?>');
        };
        
        // Add Request buttons
        const requestEditBtn = document.createElement('button');
        requestEditBtn.className = 'btn btn-warning';
        requestEditBtn.title = '<?= $lang["request_edit"] ?>';
        requestEditBtn.innerHTML = '<i class="fas fa-edit"></i>';
        requestEditBtn.onclick = function(e) {
            requestEdit(itemId, e);
        };
        
        const requestDeleteBtn = document.createElement('button');
        requestDeleteBtn.className = 'btn btn-danger';
        requestDeleteBtn.title = '<?= $lang["request_delete"] ?>';
        requestDeleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        requestDeleteBtn.onclick = function(e) {
            requestDelete(itemId, e);
        };
        
        // Determine which buttons to show
        if (isAdmin) {
            // Admin can edit/delete all items
            btnGroup.appendChild(editBtn);
            btnGroup.appendChild(deleteBtn);
        } else if (currentUserId == addedByUserId) {
            // User can edit/delete their own items
            btnGroup.appendChild(editBtn);
            btnGroup.appendChild(deleteBtn);
        } else {
            // Request buttons for others' items
            btnGroup.appendChild(requestEditBtn);
            btnGroup.appendChild(requestDeleteBtn);
        }
        
        actionsContainer.appendChild(btnGroup);
    <?php endif; ?>
}

// Event handler for modal show
$('#detailModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const itemId = button.data('item-id');
    const addedBy = button.data('added-by');
    
    // Setup action buttons
    setupModalActions(itemId, addedBy);
});
</script>