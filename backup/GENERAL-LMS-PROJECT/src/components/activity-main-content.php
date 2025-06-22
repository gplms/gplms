    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>Activity Log</h4>
            <div>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <span>System Activity Log</span>
                <div>
                    <span class="status-badge status-active">Total Records: <?= $totalLogs ?></span>
                </div>
            </div>
            
            <div class="filter-container">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="page" value="1">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?= $action ?>" <?= $actionFilter === $action ? 'selected' : '' ?>>
                                        <?= $action ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="target" class="form-select">
                                <option value="">All Targets</option>
                                <?php foreach ($targets as $target): ?>
                                    <option value="<?= $target ?>" <?= $targetFilter === $target ? 'selected' : '' ?>>
                                        <?= ucwords(str_replace('_', ' ', $target)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Target Object</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="log-id">#<?= $log['log_id'] ?></td>
                                        <td class="log-timestamp"><?= formatTimestamp($log['timestamp']) ?></td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($log['username']) ?></div>
                                            <div class="small text-muted">ID: <?= $log['user_id'] ?></div>
                                        </td>
                                        <td class="log-action"><?= formatAction($log['action']) ?></td>
                                        <td><?= formatTarget($log['target_object']) ?></td>
                                        <td class="log-details"><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= formatIP($log['ip_address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <h5>No activity logs found</h5>
                                        <p class="text-muted">Try adjusting your filters or search term</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-container">
                        <ul class="pagination">
                            <!-- Previous button -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page - 1]
                                    )) 
                                ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => 1])) . 
                                        '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $active = $i == $page ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => $i])) . 
                                        '">' . $i . '</a></li>';
                                }
                                
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?' . 
                                        http_build_query(array_merge($_GET, ['page' => $totalPages])) . 
                                        '">' . $totalPages . '</a></li>';
                                }
                            ?>
                            
                            <!-- Next button -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= 
                                    http_build_query(array_merge(
                                        $_GET,
                                        ['page' => $page + 1]
                                    )) 
                                ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Activity Analysis</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Action Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="actionChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Last 7 Days Activity</h5>
                                <div class="chart-container">
                                    <canvas id="dailyActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Top Active Users</span>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if (count($topUsers) > 0): ?>
                                <?php foreach ($topUsers as $index => $user): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($user['username']) ?></div>
                                            <div class="small text-muted"><?= $user['count'] ?> actions</div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">#<?= $index + 1 ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">No user activity data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>