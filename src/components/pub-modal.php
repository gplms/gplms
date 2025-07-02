    <!-- Publisher Modal -->
    <div class="modal fade" id="publisherModal" tabindex="-1" aria-labelledby="publisherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="publisherModalLabel">
                            <?= $edit_publisher ? $lang['edit_publisher'] : $lang['add_new_publisher'] ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($edit_publisher): ?>
                            <input type="hidden" name="publisher_id" value="<?= $edit_publisher['publisher_id'] ?>">
                            <input type="hidden" name="action_type" value="update_publisher">
                        <?php else: ?>
                            <input type="hidden" name="action_type" value="add_publisher">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['publisher_name'] ?></label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= $edit_publisher['name'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['contact_information'] ?></label>
                            <textarea name="contact_info" class="form-control" rows="3"><?= $edit_publisher['contact_info'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['website_url'] ?></label>
                            <input type="url" name="website" class="form-control" 
                                   placeholder="https://example.com"
                                   value="<?= $edit_publisher['website'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
                        <button type="submit" class="btn btn-primary"><?= $lang['save_publisher'] ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>