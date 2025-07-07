


<?php require_once'../conf/translation.php';?> 
 
 <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
               <h3><i class="fas fa-book me-2"></i> <?= htmlspecialchars($library_name) ?></h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="../main/control_panel.php" class="">
                    <i class="fas fa-tachometer-alt"></i>
                    <span><?= $lang['dashboard'] ?></span>
                </a>
            </li>
            <li>
                <a href="../main/users-manager.php">
                    <i class="fas fa-users"></i>
                    <span><?= $lang['user_management'] ?></span>
                </a>
            </li>
            <li>
                <a href="../main/roles-manager.php">
                    <i class="fas fa-user-tag"></i>
                    <span><?= $lang['roles'] ?></span>
                </a>
            </li>
            <hr>
    
            <li>
                <a href="../main/materials-manager.php">
                    <i class="fas fa-book-open"></i>
                    <span><?= $lang['materials'] ?></span>
                </a>
            </li>
            <li>
                <a href="../main/categories-manager.php">
                    <i class="fas fa-tags"></i>
                    <span><?= $lang['categories'] ?></span>
                </a>
            </li>
            <li>
                <a href="../main/publishers-manager.php">
                    <i class="fas fa-building"></i>
                    <span><?= $lang['publishers'] ?></span>
                </a>
            </li>
            <li>
                <a href="../main/authors-manager.php">
                    <i class="fas fa-feather"></i>
                    <span><?= $lang['authors'] ?></span>
                </a>
            </li>
            
            <li>
                <a href="../main/search.php">
                    <i class="fas fa-search"></i>
                    <span><?= $lang['search'] ?></span>
                </a>
            </li>
              <li>
                <a href="../main/insert.php">
                    <i class="fa-solid fa-file-import"></i>
                    <span><?= $lang['insert'] ?></span>
                </a>
            </li>
            
            <hr>

            <li>
                <a href="../main/settings-manager.php">
                    <i class="fas fa-cog"></i>
                    <span><?= $lang['system_settings'] ?></span>
                </a>
            </li>
        
            <li>
                <a href="../main/activity-log.php">
                    <i class="fas fa-history"></i>
                    <span><?= $lang['activity_log'] ?></span>
                </a>
            </li>
    
            <hr>
            

            <li>
                <a href="../main/search.php">
                    <i class="fas fa-arrow-left"></i>
                    <span><?= $lang['back_to_library'] ?></span>
                </a>
            </li>
            <hr>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?= $lang['logout'] ?></span>
                </a>
            </li>
        </ul>
    </div>





        <script>
        // Toggle sidebar on small screens
        document.addEventListener('DOMContentLoaded', function() {
            // Add active state to clicked menu items
            const menuItems = document.querySelectorAll('.sidebar-menu a');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Simulate loading
            const stats = document.querySelectorAll('.stats');
            stats.forEach(stat => {
                const target = parseInt(stat.textContent);
                let count = 0;
                stat.textContent = count;
                
                const interval = setInterval(() => {
                    count += Math.ceil(target / 30);
                    if (count >= target) {
                        count = target;
                        clearInterval(interval);
                    }
                    stat.textContent = count.toLocaleString();
                }, 30);
            });
        });
    </script>
