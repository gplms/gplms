    <!-- Main Content -->
    <div id="content">
        <div class="topbar">
            <button class="btn btn-primary btn-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4>System Settings</h4>
            <div>
                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="?logout" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="card-header">
                <span>Configuration Settings</span>
                <div>
                    <span class="me-2">
                        <span class="status-badge status-active">Last Modified</span>
                        <?php if ($lastModified): ?>
                            <?= date('M d, Y H:i', strtotime($lastModified)) ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="settingsForm">
                    <?php foreach ($settingsGroups as $groupName => $groupSettings): ?>
                        <div class="settings-group">
                            <h5>
                                <i class="fas fa-cog me-2"></i> <?= htmlspecialchars($groupName) ?>
                            </h5>
                            
                            <?php foreach ($groupSettings as $key => $setting): ?>
                                <?php 
                                    $value = $settings[$key] ?? '';
                                    $displayValue = ($setting['type'] === 'password' && !empty($value)) ? '********' : $value;
                                ?>
                                <div class="setting-item">
                                    <div class="setting-label"><?= htmlspecialchars($setting['label']) ?></div>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description">
                                            <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($setting['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($setting['type'] === 'select'): ?>
                                        <select name="settings[<?= htmlspecialchars($key) ?>]" class="setting-value">
                                            <?php 
                                                $options = $setting['dynamic_options'] ?? false ? $roleOptions : ($setting['options'] ?? []);
                                                foreach ($options as $optionValue => $optionLabel): 
                                            ?>
                                                <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($value == $optionValue) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($optionLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($setting['type'] === 'textarea'): ?>
                                        <textarea name="settings[<?= htmlspecialchars($key) ?>]" class="setting-value" rows="3"><?= htmlspecialchars($value) ?></textarea>
                                    
                                    <?php else: ?>
                                        <input type="<?= htmlspecialchars($setting['type']) ?>" 
                                               name="settings[<?= htmlspecialchars($key) ?>]" 
                                               class="setting-value"
                                               value="<?= htmlspecialchars($displayValue) ?>"
                                               <?= ($setting['type'] === 'number') ? 'min="1" step="1"' : '' ?>
                                               <?= ($setting['type'] === 'email') ? 'placeholder="email@example.com"' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary btn-save">
                            <i class="fas fa-save me-2"></i> Save All Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <span>System Status</span>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-database me-2 text-primary"></i> 
                                    Database Status
                                </div>
                                <span class="status-badge status-active">Operational</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-server me-2 text-primary"></i> 
                                    Server Load
                                </div>
                                <span>15%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-users me-2 text-primary"></i> 
                                    Active Users
                                </div>
                                <span>24</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-book me-2 text-primary"></i> 
                                    Library Items
                                </div>
                                <span>1,248</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="card-header">
                        <span>Maintenance Tools</span>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-download me-2"></i> Backup Database
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-sync-alt me-2"></i> Clear Cache
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-file-export me-2"></i> Export Data
                            </button>
                            <button class="btn btn-outline-primary text-start">
                                <i class="fas fa-file-import me-2"></i> Import Data
                            </button>
                            <button class="btn btn-outline-danger text-start">
                                <i class="fas fa-broom me-2"></i> Purge Old Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    