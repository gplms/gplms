<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $edit_role ? $lang['edit_role_modal_title'] : $lang['add_role_modal_title'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_role ? 'update_role' : 'add_role' ?>">
                <?php if ($edit_role): ?>
                    <input type="hidden" name="role_id" value="<?= $edit_role['role_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['role_name_label'] ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="role_name" class="form-control" required
                               value="<?= $edit_role ? htmlspecialchars($edit_role['role_name']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['description_label'] ?></label>
                        <textarea class="form-control" name="description" rows="3"><?= $edit_role ? htmlspecialchars($edit_role['description']) : '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['status_label'] ?> <span class="text-danger">*</span>
                        </label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?= ($edit_role && $edit_role['status'] === 'active') ? 'selected' : '' ?>>
                                <?= $lang['active_option'] ?>
                            </option>
                            <option value="inactive" <?= ($edit_role && $edit_role['status'] === 'inactive') ? 'selected' : '' ?>>
                                <?= $lang['inactive_option'] ?>
                            </option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= $lang['cancel_btn'] ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?= $edit_role ? $lang['update_role_btn'] : $lang['add_role_btn'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>