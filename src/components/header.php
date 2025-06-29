    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="logo">
                    <i class="fas fa-book"></i>
                </div>
                <span class="logo-text">GPLMS</span>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../main/search.php"><i class="fas fa-search me-1"></i> &nbsp;SEARCH ITEMS&nbsp; </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../main/insert.php"><i class="fa-solid fa-cloud-arrow-up"></i> &nbsp;INSERT ITEMS &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../main/contact_devs.php"><i class="fa-solid fa-address-card"></i> &nbsp;CONTACT DEVELOPERS &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://www.github.com"><i class="fa-solid fa-code-pull-request"></i>  CONTRIBUTE &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout (<?= $_SESSION['username'] ?>)
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    