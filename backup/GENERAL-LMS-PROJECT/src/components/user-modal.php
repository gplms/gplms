    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_user ? 'Edit User' : 'Add New User' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action_type" value="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                Password <?= $edit_user ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" name="password" class="form-control" <?= $edit_user ? '' : 'required' ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= $edit_user ? htmlspecialchars($edit_user['full_name']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= $edit_user ? htmlspecialchars($edit_user['phone']) : '' ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
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
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?= ($edit_user && $edit_user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="suspended" <?= ($edit_user && $edit_user['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?= $edit_user ? 'Update' : 'Add' ?> User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>