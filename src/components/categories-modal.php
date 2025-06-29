<!-- categories-modal.php -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel"><?= isset($edit_category) ? 'Edit Category' : 'Add New Category' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action_type" value="<?= isset($edit_category) ? 'update_category' : 'add_category' ?>">
                    <?php if (isset($edit_category)): ?>
                        <input type="hidden" name="category_id" value="<?= $edit_category['category_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= $edit_category['name'] ?? '' ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?= $edit_category['description'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= (isset($edit_category) && $edit_category['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (isset($edit_category) && $edit_category['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><?= isset($edit_category) ? 'Update Category' : 'Add Category' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>