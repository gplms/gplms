    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="logo">
                    <i class="fas fa-book"></i>
                </div>
                <span class="logo-text">LibrarySystem</span>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home&nbsp; </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php"><i class="fas fa-search me-1"></i> Search Books &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="insert.php"><i class="fas fa-plus-circle me-1"></i> Insert Books &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php"><i class="fas fa-headset me-1"></i> Contact Developers &nbsp;</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="?logout=true">
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
    