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
            <h2 class="mb-4">User Management Dashboard</h2>
            
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
                        <div class="stat-label">Total Users</div>
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
                        <div class="stat-label">User Roles</div>
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
                        <div class="stat-label">Active Users</div>
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
                        <div class="stat-label">Suspended Users</div>
                    </div>
                </div>
            </div>

            <!-- Users Management Card -->
            <div class="admin-card">
                <div class="card-header">
                    <span>Manage Users</span>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="fas fa-plus me-1"></i> Add User
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
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
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
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?edit_user=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <a href="?status_change=suspend&id=<?= $user['user_id'] ?>" class="action-btn btn-warning" title="Suspend">
                                                        <i class="fas fa-user-slash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?status_change=activate&id=<?= $user['user_id'] ?>" class="action-btn btn-success" title="Activate">
                                                        <i class="fas fa-user-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="?delete=user&id=<?= $user['user_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
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
            
            <!-- Roles Management Card -->
            <div class="admin-card">
                <div class="card-header">
                    <span>Manage Roles</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?= $role['role_id'] ?></td>
                                        <td><?= htmlspecialchars($role['role_name']) ?></td>
                                        <td><?= htmlspecialchars($role['description'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $role['status'] === 'active' ? 'active' : 'suspended' ?>">
                                                <?= ucfirst($role['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
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
            <p class="mb-0">GPLMS - General Purpose Library Management System</p>
            <p class="text-muted mb-0">© <?= date('Y') ?> All rights reserved</p>
        </footer>
    </div>