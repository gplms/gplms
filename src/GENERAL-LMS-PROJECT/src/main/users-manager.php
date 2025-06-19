<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Database connection
$host = 'localhost';
$db   = 'gplms_general';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Add last_login column if it doesn't exist
try {
    $pdo->query("SELECT last_login FROM users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER created_at");
}

// Add status column if it doesn't exist
try {
    $pdo->query("SELECT status FROM users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER role_id");
}

// Function to log activity
function logActivity($pdo, $user_id, $action, $target_object = null, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Get username from session
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, target_object, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $target_object, $details, $ip_address]);
}

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            if ($action_type === 'add_user') {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role_id, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['role_id'],
                    $_POST['status']
                ]);
                $success_msg = "User added successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'users', 'Added new user: '.$_POST['username']);
            }
            elseif ($action_type === 'update_user') {
                $update_fields = [
                    'username' => $_POST['username'],
                    'full_name' => $_POST['full_name'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                    'role_id' => $_POST['role_id'],
                    'status' => $_POST['status'],
                    'user_id' => $_POST['user_id']
                ];
                
                // Update password only if provided
                if (!empty($_POST['password'])) {
                    $update_fields['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, 
                            role_id = ?, status = ?, password = ? WHERE user_id = ?";
                } else {
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, 
                            role_id = ?, status = ? WHERE user_id = ?";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($update_fields));
                
                $success_msg = "User updated successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'users', 'Updated user: '.$_POST['username']);
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle user status change
if (isset($_GET['status_change']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['status_change'] === 'activate' ? 'active' : 'suspended';
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$new_status, $id]);
        
        $success_msg = "User status updated successfully!";
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'users', 'Changed status for user ID: '.$id);
    } catch (Exception $e) {
        $error_msg = "Error updating status: " . $e->getMessage();
    }
}

// Handle delete user
if (isset($_GET['delete']) && $_GET['delete'] === 'user' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Check if user is trying to delete themselves
        if ($id === $_SESSION['user_id']) {
            $error_msg = "You cannot delete your own account!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$id]);
            $success_msg = "User deleted successfully!";
            logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'users', 'Deleted user ID: '.$id);
        }
    } catch (Exception $e) {
        $error_msg = "Error deleting user: " . $e->getMessage();
    }
}

// Get users and roles
$users = $pdo->query("SELECT * FROM users")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_GET['edit_user']]);
    $edit_user = $stmt->fetch();
}

// Include header template
include '../compoonents/header.php';
?>

<!-- Include sidebar template -->
<?php include '../compoonents/sidebar.php'; ?>

<div id="content">
    <!-- Include topbar template -->
    <?php include '../compoonents/topbar.php'; ?>
    
    <!-- Users Management Content -->
    <div class="admin-card">
        <link rel="stylesheet" href="../styyles/user.css">
        <div class="card-header">
            <span>Manage Users</span>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
        
        <!-- Status Messages -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            // Get role name
                            $role_stmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                            $role_stmt->execute([$user['role_id']]);
                            $role_name = $role_stmt->fetchColumn();
                        ?>
                            <tr>
                                <td><?= $user['user_id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($role_name) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $user['status'] ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?edit_user=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="?status_change=suspend&id=<?= $user['user_id'] ?>" class="action-btn btn-warning" title="Suspend">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?status_change=activate&id=<?= $user['user_id'] ?>" class="action-btn btn-success" title="Activate">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=user&id=<?= $user['user_id'] ?>" class="action-btn btn-delete" title="Delete" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">
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

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit_user ? 'Edit User' : 'Add New User' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action_type" value="<?= $edit_user ? 'update_user' : 'add_user' ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Password <?= $edit_user ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" name="password" class="form-control" <?= $edit_user ? '' : 'required' ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['full_name']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= $edit_user ? htmlspecialchars($edit_user['phone']) : '' ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['role_id'] ?>" 
                                        <?= ($edit_user && $edit_user['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= ($edit_user && $edit_user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= ($edit_user && $edit_user['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?= $edit_user ? 'Update' : 'Add' ?> User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer template -->
<?php include '../compoonents/footer.php'; ?>

<script>
// Auto-show modal if editing user
<?php if ($edit_user): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
    });
<?php endif; ?>
</script>