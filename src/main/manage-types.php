<?php
session_start();
require_once '../conf/config.php';

// Check if user is admin
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    header("Location: search.php");
    exit;
}

// Handle form submissions
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        try {
            $pdo->beginTransaction();
            
            if ($_POST['action_type'] === 'add_type') {
                $stmt = $pdo->prepare("INSERT INTO material_types (type_name) VALUES (?)");
                $stmt->execute([$_POST['type_name']]);
                $success_msg = "Material type added successfully!";
            }
            elseif ($_POST['action_type'] === 'update_type') {
                $stmt = $pdo->prepare("UPDATE material_types SET type_name = ? WHERE type_id = ?");
                $stmt->execute([$_POST['type_name'], $_POST['type_id']]);
                $success_msg = "Material type updated successfully!";
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_type') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        try {
            // Check if any materials are using this type
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE type_id = ?");
            $checkStmt->execute([$id]);
            $materialCount = $checkStmt->fetchColumn();
            
            if ($materialCount > 0) {
                $error_msg = "Cannot delete type: $materialCount material(s) are using this type.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM material_types WHERE type_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Material type deleted successfully!";
            }
        } catch (Exception $e) {
            $error_msg = "Error processing request: " . $e->getMessage();
        }
    }
}

// Fetch material types
$types = $pdo->query("SELECT mt.*, 
    (SELECT COUNT(*) FROM library_items WHERE type_id = mt.type_id) AS material_count
    FROM material_types mt
    ORDER BY type_name")->fetchAll();

// Get type for editing
$edit_type = null;
if (isset($_GET['edit_type'])) {
    $stmt = $pdo->prepare("SELECT * FROM material_types WHERE type_id = ?");
    $stmt->execute([$_GET['edit_type']]);
    $edit_type = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Manage Material Types</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 800px; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .admin-table th { background: #f8f9fa; }
        .action-btns { display: flex; gap: 10px; }
    </style>
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center mb-4"><i class="fas fa-tags me-2"></i>Manage Material Types</h1>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="mb-0"><?= isset($edit_type) ? 'Edit Material Type' : 'Add New Material Type' ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_type" value="<?= isset($edit_type) ? 'update_type' : 'add_type' ?>">
                    <?php if (isset($edit_type)): ?>
                        <input type="hidden" name="type_id" value="<?= $edit_type['type_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" class="form-control" required
                               value="<?= $edit_type['type_name'] ?? '' ?>">
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <?= isset($edit_type) ? 'Update Type' : 'Add Type' ?>
                        </button>
                        <?php if (isset($edit_type)): ?>
                            <a href="manage-types.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Existing Material Types</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type Name</th>
                                <th>Materials Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><?= $type['type_id'] ?></td>
                                    <td><?= htmlspecialchars($type['type_name']) ?></td>
                                    <td><?= $type['material_count'] ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="manage-types.php?edit_type=<?= $type['type_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="manage-types.php?action=delete_type&id=<?= $type['type_id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this type?');">
                                                <i class="fas fa-trash"></i> Delete
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
        
        <div class="mt-4">
            <a href="materials-manager.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Materials Manager
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($edit_type)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to the form when editing a type
            document.querySelector('.card').scrollIntoView();
        });
    </script>
    <?php endif; ?>
</body>
</html>