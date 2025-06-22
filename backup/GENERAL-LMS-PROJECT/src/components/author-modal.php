    <!-- Author Modal -->
    <div class="modal fade" id="authorModal" tabindex="-1" aria-labelledby="authorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="authorModalLabel">
                            <?= $edit_author ? 'Edit Author' : 'Add New Author' ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($edit_author): ?>
                            <input type="hidden" name="author_id" value="<?= $edit_author['author_id'] ?>">
                            <input type="hidden" name="action_type" value="update_author">
                        <?php else: ?>
                            <input type="hidden" name="action_type" value="add_author">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Author Name</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= $edit_author['name'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Biography</label>
                            <textarea name="bio" class="form-control" rows="5"><?= $edit_author['bio'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Author</button>
                    </div>
                </form>
            </div>
        </div>
    </div>