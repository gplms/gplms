    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Categories Manager</h4>
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
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon categories">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_categories'] ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['active_categories'] ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_in_categories'] ?></div>
                    <div class="stat-label">Items in Categories</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon updated">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['recently_updated'] ?></div>
                    <div class="stat-label">Recently Updated</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-7">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Manage Categories</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-1"></i> Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= $category['category_id'] ?></td>
                                            <td><?= $category['name'] ?></td>
                                            <td><?= $category['description'] ? substr($category['description'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td><?= $category['item_count'] ?></td>
                                            <td>
                                                <span class="status-badge <?= $category['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                                    <?= ucfirst($category['status']) ?>
                                                </span>
                                            </td>
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
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Category Distribution</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Categories</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recently_updated as $category): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-tag text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= $category['name'] ?></div>
                                        <div class="text-muted small">
                                            Updated: <?= date('M d, Y', strtotime($category['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $category['item_count'] ?> items
                                            </span>
                                            <span class="badge <?= $category['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> ms-1">
                                                <?= ucfirst($category['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>