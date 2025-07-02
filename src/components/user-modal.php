<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $edit_user ? $lang['edit_user'] : $lang['add_new_user'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['username'] ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="username" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['password'] ?> 
                            <?php if ($edit_user): ?>
                                <span class="text-muted">(<?= $lang['password_keep_current'] ?>)</span>
                            <?php else: ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <input type="password" name="password" class="form-control" <?= $edit_user ? '' : 'required' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['full_name'] ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['full_name']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $lang['email'] ?> <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['phone'] ?></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= $edit_user ? htmlspecialchars($edit_user['phone']) : '' ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <?= $lang['role'] ?> <span class="text-danger">*</span>
                            </label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['role_id'] ?>" 
                                        <?= ($edit_user && $edit_user['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <?= $lang['status'] ?> <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= ($edit_user && $edit_user['status'] === 'active') ? 'selected' : '' ?>>
                                    <?= $lang['active'] ?>
                                </option>
                                <option value="suspended" <?= ($edit_user && $edit_user['status'] === 'suspended') ? 'selected' : '' ?>>
                                    <?= $lang['suspended'] ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
                    <button type="submit" class="btn btn-primary">
                        <?= $edit_user ? $lang['update_user'] : $lang['add_user'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>