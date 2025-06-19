<div class="topbar">
    <button class="btn btn-primary btn-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <h4>User Management</h4>
    <div>
        <span class="me-3">Welcome, <?= $_SESSION['username'] ?></span>
        <a href="?logout" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>