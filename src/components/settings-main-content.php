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
            
            

        </div>
    </div>
    