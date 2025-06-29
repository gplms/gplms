    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Publishers Manager</h4>
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
                <div class="stat-icon publishers">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_publishers'] ?></div>
                    <div class="stat-label">Total Publishers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['publishers_with_items'] ?></div>
                    <div class="stat-label">Publishers with Items</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_in_publishers'] ?></div>
                    <div class="stat-label">Items in Publishers</div>
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
                        <span>Manage Publishers</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#publisherModal">
                            <i class="fas fa-plus me-1"></i> Add Publisher
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
                                        <th>Website</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                        <tr>
                                            <td><?= $publisher['publisher_id'] ?></td>
                                            <td><?= $publisher['name'] ?></td>
                                            <td><?= $publisher['contact_info'] ? substr($publisher['contact_info'], 0, 30) . '...' : 'N/A' ?></td>
                                            <td>
                                                <?php if (!empty($publisher['website'])): ?>
                                                    <a href="<?= $publisher['website'] ?>" class="website-link" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> Visit
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $publisher['item_count'] ?></td>
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
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Publisher Distribution</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="publisherChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Publishers</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recently_updated as $publisher): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-building text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= $publisher['name'] ?></div>
                                        <div class="text-muted small">
                                            Updated: <?= date('M d, Y', strtotime($publisher['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $publisher['item_count'] ?> items
                                            </span>
                                            <?php if (!empty($publisher['website'])): ?>
                                                <a href="<?= $publisher['website'] ?>" class="badge bg-info text-white ms-1" target="_blank">
                                                    <i class="fas fa-external-link-alt me-1"></i> Website
                                                </a>
                                            <?php endif; ?>
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