<?php
// components/roles-manager-main.php
?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <small class="text-muted"><?= $_SESSION['role'] ?></small>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <h2 class="mb-4"><?= $lang['roles_manager'] ?></h2>
        
        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon roles">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= $roleStats['total_roles'] ?>
                    </div>
                    <div class="stat-label"><?= $lang['total_roles'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= $roleStats['active_roles'] ?>
                    </div>
                    <div class="stat-label"><?= $lang['active_roles'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-ban"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= $roleStats['inactive_roles'] ?>
                    </div>
                    <div class="stat-label"><?= $lang['inactive_roles'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon popular">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?= $roleStats['role_with_most_users'] ?>
                    </div>
                    <div class="stat-label"><?= $lang['role_with_most_users'] ?></div>
                </div>
            </div>
        </div>

        <!-- Roles Management Card -->
        <div class="admin-card">
            <div class="card-header">
                <span><?= $lang['roles_manager'] ?></span>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
                    <i class="fas fa-plus me-1"></i> <?= $lang['add_new_role'] ?>
                </button>
            </div>
            
            <!-- Status Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= $lang['role_id'] ?></th>
                                <th><?= $lang['role_name'] ?></th>
                                <th><?= $lang['description'] ?></th>
                                <th><?= $lang['users'] ?></th>
                                <th><?= $lang['status'] ?></th>
                                <th><?= $lang['created_at'] ?></th>
                                <th><?= $lang['actions'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): 
                                $role_class = strtolower(str_replace(' ', '-', $role['role_name']));
                            ?>
                                <tr>
                                    <td><?= $role['role_id'] ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $role_class ?>">
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                                    <td><?= $role['user_count'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $role['status'] ?>">
                                            <?= $role['status'] === 'active' ? $lang['active'] : $lang['inactive'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit_role=<?= $role['role_id'] ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($role['status'] === 'active'): ?>
                                                <a href="?status_change=deactivate&id=<?= $role['role_id'] ?>" class="action-btn btn-warning" title="<?= $lang['deactivate'] ?>">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?status_change=activate&id=<?= $role['role_id'] ?>" class="action-btn btn-success" title="<?= $lang['activate'] ?>">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?delete=role&id=<?= $role['role_id'] ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" 
                                               onclick="return confirm('<?= $default_language === 'GR' ? 'Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτόν τον ρόλο;' : 'Are you sure you want to delete this role?' ?>')">
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
        
        <!-- Role Statistics -->
        <div class="admin-card">
            <div class="card-header">
                <span><?= $lang['statistics_overview'] ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="roleDistributionChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="roleActivityChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <canvas id="roleGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="roleStatusChart"></canvas>
                        </div>
                    </div>
                    
             
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p class="mb-0">GPLMS - <?= $lang['general_purpose_library_system'] ?></p>
        <p class="text-muted mb-0">© <?= date('Y') ?> <?= $lang['all_rights_reserved'] ?></p>
    </footer>
</div>