    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4><?= $lang['publishers_manager'] ?></h4>
            <div>
                <span class="me-3"><?= $lang['welcome'] ?>, <?= $_SESSION['username'] ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> <?= $lang['logout'] ?>
                </a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $lang['close'] ?>"></button>
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
                    <div class="stat-label"><?= $lang['total_publishers'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['publishers_with_items'] ?></div>
                    <div class="stat-label"><?= $lang['publishers_with_items'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon items">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['items_in_publishers'] ?></div>
                    <div class="stat-label"><?= $lang['items_in_publishers'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon updated">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $stats['recently_updated'] ?></div>
                    <div class="stat-label"><?= $lang['recently_updated'] ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-7">
                <div class="admin-card">
                    <div class="card-header">
                        <span><?= $lang['manage_publishers'] ?></span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#publisherModal">
                            <i class="fas fa-plus me-1"></i> <?= $lang['add_publisher'] ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th><?= $lang['id'] ?></th>
                                        <th><?= $lang['publisher_name'] ?></th>
                                        <th><?= $lang['contact_info'] ?></th>
                                        <th><?= $lang['website'] ?></th>
                                        <th><?= $lang['items'] ?></th>
                                        <th><?= $lang['actions'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($publishers as $publisher): ?>
                                        <tr>
                                            <td><?= $publisher['publisher_id'] ?></td>
                                            <td><?= $publisher['name'] ?></td>
                                            <td><?= $publisher['contact_info'] ? substr($publisher['contact_info'], 0, 30) . '...' : $lang['na'] ?></td>
                                            <td>
                                                <?php if (!empty($publisher['website'])): ?>
                                                    <a href="<?= $publisher['website'] ?>" class="website-link" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> <?= $lang['visit'] ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= $lang['na'] ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $publisher['item_count'] ?></td>
                                            <td>
                                                <div class="action-btns">
                                                    <a href="?edit_publisher=<?= $publisher['publisher_id'] ?>" class="action-btn btn-edit" title="<?= $lang['edit'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=publisher&id=<?= $publisher['publisher_id'] ?>" class="action-btn btn-delete" title="<?= $lang['delete'] ?>" onclick="return confirm('<?= $lang['confirm_delete_publisher'] ?>')">
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
                        <span><?= $lang['publisher_distribution'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="publisherChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <span><?= $lang['recently_updated_publishers'] ?></span>
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
                                            <?= $lang['updated'] ?>: <?= date('M d, Y', strtotime($publisher['last_modified'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i> <?= $publisher['item_count'] ?> <?= $lang['items'] ?>
                                            </span>
                                            <?php if (!empty($publisher['website'])): ?>
                                                <a href="<?= $publisher['website'] ?>" class="badge bg-info text-white ms-1" target="_blank">
                                                    <i class="fas fa-external-link-alt me-1"></i> <?= $lang['website'] ?>
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