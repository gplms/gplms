<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($current_user['full_name'], 0, 1)) ?>
            </div>
            <div class="ms-2">
                <div class="fw-bold"><?= htmlspecialchars($current_user['full_name']) ?></div>
                <small class="text-muted"><?= $_SESSION['role'] ?></small>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <h2 class="mb-4"><?= $lang['user_management_dashboard'] ?></h2>
        
        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= count($users) ?>
                    </div>
                    <div class="stat-label"><?= $lang['total_users'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon roles">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= count($roles) ?>
                    </div>
                    <div class="stat-label"><?= $lang['user_roles'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= count(array_filter($users, function($user) { return $user['status'] === 'active'; })) ?>
                    </div>
                    <div class="stat-label"><?= $lang['active_users'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon suspended">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= count(array_filter($users, function($user) { return $user['status'] === 'suspended'; })) ?>
                    </div>
                    <div class="stat-label"><?= $lang['suspended_users'] ?></div>
                </div>
            </div>
        </div>

        <!-- Users Management Card -->
        <div class="admin-card">
            <div class="card-header">
                <span><?= $lang['manage_users'] ?></span>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="fas fa-plus me-1"></i> <?= $lang['add_user'] ?>
                </button>
            </div>
            
            <!-- Status Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
                </div>
            <?php endif; ?>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= $lang['id'] ?></th>
                                <th><?= $lang['username'] ?></th>
                                <th><?= $lang['full_name'] ?></th>
                                <th><?= $lang['email'] ?></th>
                                <th><?= $lang['role'] ?></th>
                                <th><?= $lang['status'] ?></th>
                                <th><?= $lang['created_at'] ?></th>
                                <th><?= $lang['last_login'] ?></th>
                                <th><?= $lang['actions'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                // Get role name
                                $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                                $role_stmt->execute([$user['role_id']]);
                                $role_name = $role_stmt->fetchColumn();
                            ?>
                                <tr>
                                    <td><?= $user['user_id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($role_name) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $user['status'] ?>">
                                            <?= $lang[$user['status']] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : $lang['never'] ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit_user=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($user['status'] === 'active'): ?>
                                                <a href="?status_change=suspend&id=<?= $user['user_id'] ?>" class="action-btn btn-warning" title="<?= $lang['suspend'] ?>">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?status_change=activate&id=<?= $user['user_id'] ?>" class="action-btn btn-success" title="<?= $lang['activate'] ?>">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?delete=user&id=<?= $user['user_id'] ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" 
                                               onclick="return confirm('<?= $lang['confirm_delete_user'] ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
   
    </div>

    <!-- Footer -->
    <footer>
        <p class="mb-0"><?= $lang['gplms_footer'] ?></p>
        <p class="text-muted mb-0">© <?= date('Y') ?> <?= $lang['all_rights_reserved'] ?></p>
    </footer>
</div>