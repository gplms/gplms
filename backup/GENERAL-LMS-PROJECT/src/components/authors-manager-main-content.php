    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Authors Manager</h4>
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
                <div class="stat-icon authors">
                    <i class="fas fa-feather"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['total_authors'] ?></div>
                    <div class="stat-label">Total Authors</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['authors_with_items'] ?></div>
                    <div class="stat-label">Authors with Items</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_by_authors'] ?></div>
                    <div class="stat-label">Items by Authors</div>
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
                        <span>Manage Authors</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#authorModal">
                            <i class="fas fa-plus me-1"></i> Add Author
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Author Name</th>
                                        <th>Bio</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authors as $author): ?>
                                        <tr>
                                            <td><?= $author['author_id'] ?></td>
                                            <td><?= $author['name'] ?></td>
                                            <td class="bio-preview" title="<?= htmlspecialchars($author['bio']) ?>">
                                                <?= $author['bio'] ? substr($author['bio'], 0, 50) . '...' : 'N/A' ?>
                                            </td>
                                            <td><?= $author['item_count'] ?></td>
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
            
            <div class="col-md-5">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Top Authors</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="authorChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span>Recently Updated Authors</span>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recently_updated as $author): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-user text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= $author['name'] ?></div>
                                        <div class="text-muted small">
                                            Updated: <?= date('M d, Y', strtotime($author['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $author['item_count'] ?> items
                                            </span>
                                        </div>
                                        <div class="mt-1 small text-truncate" title="<?= htmlspecialchars($author['bio']) ?>">
                                            <?= $author['bio'] ? substr($author['bio'], 0, 80) . '...' : 'No bio available' ?>
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