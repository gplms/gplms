
    <!-- Main Content -->
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
            <h2 class="mb-4">Role Management Dashboard</h2>
            
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
                        <div class="stat-label">Total Roles</div>
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
                        <div class="stat-label">Active Roles</div>
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
                        <div class="stat-label">Inactive Roles</div>
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
                        <div class="stat-label">Most Popular Role</div>
                    </div>
                </div>
            </div>

            <!-- Roles Management Card -->
            <div class="admin-card">
                <div class="card-header">
                    <span>Manage Roles</span>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
                        <i class="fas fa-plus me-1"></i> Add Role
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
                                    <th>ID</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Users</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
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
                                                <?= ucfirst($role['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?edit_role=<?= $role['role_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($role['status'] === 'active'): ?>
                                                    <a href="?status_change=deactivate&id=<?= $role['role_id'] ?>" class="action-btn btn-warning" title="Deactivate">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?status_change=activate&id=<?= $role['role_id'] ?>" class="action-btn btn-success" title="Activate">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="?delete=role&id=<?= $role['role_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this role?')">
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
                    <span>Role Statistics & Charts</span>
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
                        
                        <div class="col-md-6">
                            <div class="admin-card h-100">
                                <div class="card-header">
                                    <span>Role Performance Metrics</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="admin-table">
                                            <thead>
                                                <tr>
                                                    <th>Role</th>
                                                    <th>Users</th>
                                                    <th>Activity (30d)</th>
                                                    <th>Avg. Activity/User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($roleActivity as $role): 
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = ?)");
                                                    $stmt->execute([$role['role_name']]);
                                                    $user_count = $stmt->fetchColumn();
                                                    $avg_activity = $user_count > 0 ? round($role['activity_count'] / $user_count, 1) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?= $role['role_name'] ?></td>
                                                        <td><?= $user_count ?></td>
                                                        <td><?= $role['activity_count'] ?></td>
                                                        <td><?= $avg_activity ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p class="mb-0">GPLMS - General Purpose Library Management System</p>
            <p class="text-muted mb-0">© <?= date('Y') ?> All rights reserved</p>
        </footer>
    </div>