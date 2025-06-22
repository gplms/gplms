    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Admin Control Panel</h4>
            <div>
                <span class="me-3">Welcome, <?= $_SESSION['username'] ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="tab-content">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $stats['users'] ?></div>
                        <div class="stat-label">Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon books">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $stats['library_items'] ?></div>
                        <div class="stat-label">Library Items</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon authors">
                            <i class="fas fa-feather"></i>
                        </div>
                        <div class="stat-number"><?= $stats['authors'] ?></div>
                        <div class="stat-label">Authors</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon activity">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-number"><?= $stats['recent_activity'] ?></div>
                        <div class="stat-label">Recent Activities</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Recent Activity</span>
                                <a href="#activity" class="text-white" data-bs-toggle="tab">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="activity-list">
                                    <?php foreach ($recent_activity_logs as $log): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-history"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div>
                                                    <strong><?= $log['username'] ?? 'System' ?></strong>
                                                    <?= $log['action'] ?>
                                                    <?php if ($log['target_object']): ?>
                                                        <span class="text-muted">(<?= $log['target_object'] ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="activity-time">
                                                    <?= date('M d, Y h:i A', strtotime($log['timestamp'])) ?> | 
                                                    <?= $log['ip_address'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Material Types Distribution</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="materialsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Language Distribution</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="languageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="admin-card">
                            <div class="card-header">
                                <span>Publication Years</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="yearChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>System Overview</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Database Info</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Database Name:</span>
                                        <span><?= $db ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Tables Count:</span>
                                        <span>10</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Last Backup:</span>
                                        <span>Today at <?= date('H:i') ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Server Info</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>PHP Version:</span>
                                        <span><?= phpversion() ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Server Software:</span>
                                        <span><?= $_SERVER['SERVER_SOFTWARE'] ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Server OS:</span>
                                        <span><?= php_uname('s') ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>System Status</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>System Status:</span>
                                        <span class="status-badge status-active">Operational</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Maintenance Mode:</span>
                                        <span class="status-badge status-inactive">Off</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Active Users:</span>
                                        <span><?= $stats['active_users'] ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Tab -->
            <div class="tab-pane fade" id="users">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Users</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?></td>
                                            <td><?= $user['username'] ?></td>
                                            <td><?= $user['full_name'] ?></td>
                                            <td><?= $user['email'] ?></td>
                                            <td>
                                                <?php 
                                                    $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                                                    $role_stmt->execute([$user['role_id']]);
                                                    $role_name = $role_stmt->fetchColumn();
                                                    echo $role_name;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-active">Active</span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_user=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=user&id=<?= $user['user_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
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
            
            <!-- Roles Tab -->
            <div class="tab-pane fade" id="roles">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Roles</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal">
                            <i class="fas fa-plus"></i> Add Role
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): 
                                        $user_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
                                        $user_count->execute([$role['role_id']]);
                                        $count = $user_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $role['role_id'] ?></td>
                                            <td><?= $role['role_name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_role=<?= $role['role_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=role&id=<?= $role['role_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this role?')">
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
            
            <!-- Materials Tab -->
            <div class="tab-pane fade" id="materials">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Library Materials</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#materialModal">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Authors</th>
                                        <th>Publisher</th>
                                        <th>Added By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($library_items as $item): 
                                        $type_stmt = $pdo->prepare("SELECT type_name FROM material_types WHERE type_id = ?");
                                        $type_stmt->execute([$item['type_id']]);
                                        $type_name = $type_stmt->fetchColumn();
                                        
                                        $publisher_stmt = $pdo->prepare("SELECT name FROM publishers WHERE publisher_id = ?");
                                        $publisher_stmt->execute([$item['publisher_id']]);
                                        $publisher_name = $publisher_stmt->fetchColumn();
                                        
                                        $user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                                        $user_stmt->execute([$item['added_by']]);
                                        $username = $user_stmt->fetchColumn();
                                        
                                        $author_stmt = $pdo->prepare("
                                            SELECT a.name 
                                            FROM item_authors ia
                                            JOIN authors a ON ia.author_id = a.author_id
                                            WHERE ia.item_id = ?
                                        ");
                                        $author_stmt->execute([$item['item_id']]);
                                        $authors = $author_stmt->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                        <tr>
                                            <td><?= $item['item_id'] ?></td>
                                            <td><?= $item['title'] ?></td>
                                            <td><?= $type_name ?></td>
                                            <td><?= implode(', ', $authors) ?></td>
                                            <td><?= $publisher_name ?></td>
                                            <td><?= $username ?></td>
                                            <td>
                                                <span class="status-badge status-active">Available</span>
                                            </td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_material=<?= $item['item_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=material&id=<?= $item['item_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this material?')">
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
            
            <!-- Categories Tab -->
            <div class="tab-pane fade" id="categories">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Categories</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE category_id = ?");
                                        $item_count->execute([$category['category_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $category['category_id'] ?></td>
                                            <td><?= $category['name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_category=<?= $category['category_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=category&id=<?= $category['category_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
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
            
            <!-- Publishers Tab -->
            <div class="tab-pane fade" id="publishers">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Publishers</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#publisherModal">
                            <i class="fas fa-plus"></i> Add Publisher
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Publisher Name</th>
                                        <th>Contact Info</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE publisher_id = ?");
                                        $item_count->execute([$publisher['publisher_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $publisher['publisher_id'] ?></td>
                                            <td><?= $publisher['name'] ?></td>
                                            <td><?= $publisher['contact_info'] ? substr($publisher['contact_info'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_publisher=<?= $publisher['publisher_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=publisher&id=<?= $publisher['publisher_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this publisher?')">
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
            
            <!-- Authors Tab -->
            <div class="tab-pane fade" id="authors">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Authors</span>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal">
                            <i class="fas fa-plus"></i> Add Author
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Author Name</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authors as $author): 
                                        $item_count = $pdo->prepare("SELECT COUNT(*) FROM item_authors WHERE author_id = ?");
                                        $item_count->execute([$author['author_id']]);
                                        $count = $item_count->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><?= $author['author_id'] ?></td>
                                            <td><?= $author['name'] ?></td>
                                            <td><?= $count ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_author=<?= $author['author_id'] ?>" class="action-btn btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=author&id=<?= $author['author_id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this author?')">
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
            
            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings">
                <div class="admin-card">
                    <div class="card-header">
                        <span>System Settings</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="settings-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Library Name</label>
                                        <input type="text" name="settings[library_name]" 
                                               class="form-control" 
                                               value="<?= $settings_array['library_name'] ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Max Items Per Page</label>
                                        <input type="number" name="settings[max_items_per_page]" 
                                               class="form-control" 
                                               value="<?= $settings_array['max_items_per_page'] ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Default Language</label>
                                        <select name="settings[default_language]" class="form-select">
                                            <option value="EN" <?= ($settings_array['default_language'] ?? 'EN') === 'EN' ? 'selected' : '' ?>>English</option>
                                            <option value="GR" <?= ($settings_array['default_language'] ?? 'EN') === 'GR' ? 'selected' : '' ?>>Greek</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">User Registration</label>
                                        <select name="settings[enable_user_registration]" class="form-select">
                                            <option value="1" <?= ($settings_array['enable_user_registration'] ?? 1) == '1' ? 'selected' : '' ?>>Enabled</option>
                                            <option value="0" <?= ($settings_array['enable_user_registration'] ?? 1) == '0' ? 'selected' : '' ?>>Disabled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Default Theme</label>
                                        <select name="settings[default_theme]" class="form-select">
                                            <option value="light" selected>Light</option>
                                            <option value="dark">Dark</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Maintenance Mode</label>
                                        <select name="settings[maintenance_mode]" class="form-select">
                                            <option value="0" selected>Disabled</option>
                                            <option value="1">Enabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Email Settings</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" name="settings[smtp_host]" 
                                               class="form-control" 
                                               value="smtp.example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" name="settings[smtp_port]" 
                                               class="form-control" 
                                               value="587">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" name="settings[smtp_username]" 
                                               class="form-control" 
                                               value="user@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" name="settings[smtp_password]" 
                                               class="form-control" 
                                               value="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Activity Log Tab -->
            <div class="tab-pane fade" id="activity">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Activity Logs</span>
                        <div class="d-flex">
                            <input type="text" class="form-control form-control-sm me-2" placeholder="Search logs..." style="width: 200px;">
                            <button class="btn btn-light btn-sm">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Action</th>
                                        <th>Target</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <tr>
                                            <td><?= $log['log_id'] ?></td>
                                            <td><?= $log['username'] ?></td>
                                            <td><?= $log['action'] ?></td>
                                            <td><?= $log['target_object'] ?></td>
                                            <td><?= $log['details'] ? substr($log['details'], 0, 30) . '...' : '-' ?></td>
                                            <td><?= $log['ip_address'] ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page - 1 ?>#activity" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>#activity"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $current_page + 1 ?>#activity" aria-label="Next">
                                            <span aria-hidden="true">Next &raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>