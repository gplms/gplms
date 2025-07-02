<?php
session_start();
require_once '../conf/config.php';
require_once '../conf/translation.php';

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
                $success_msg = $lang['material_type_added'];
            }
            elseif ($_POST['action_type'] === 'update_type') {
                $stmt = $pdo->prepare("UPDATE material_types SET type_name = ? WHERE type_id = ?");
                $stmt->execute([$_POST['type_name'], $_POST['type_id']]);
                $success_msg = $lang['material_type_updated'];
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = sprintf($lang['error_processing_request'], $e->getMessage());
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
                $error_msg = sprintf($lang['cannot_delete_type'], $materialCount);
            } else {
                $stmt = $pdo->prepare("DELETE FROM material_types WHERE type_id = ?");
                $stmt->execute([$id]);
                $success_msg = $lang['material_type_deleted'];
            }
        } catch (Exception $e) {
            $error_msg = sprintf($lang['error_processing_request'], $e->getMessage());
        }
    }
}

// Fetch material types
$types = $pdo->query("SELECT mt.*, 
    (SELECT COUNT(*) FROM library_items WHERE type_id = mt.type_id) AS material_count
    FROM material_types mt
    ORDER BY type_name")->fetchAll();

// Get type for editing (if using traditional method)
$edit_type = null;
if (isset($_GET['edit_type'])) {
    $stmt = $pdo->prepare("SELECT * FROM material_types WHERE type_id = ?");
    $stmt->execute([$_GET['edit_type']]);
    $edit_type = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?= $default_language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['manage_material_types'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 800px; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .admin-table th { background: #f8f9fa; }
        .action-btns { display: flex; gap: 10px; }
        .modal-content { border-radius: 10px; }
        .modal-header { background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
        .modal-title { font-weight: 600; }
    </style>
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center mb-4"><i class="fas fa-tags me-2"></i><?= $lang['manage_material_types'] ?></h1>
        
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
                <h3 class="mb-0"><?= $lang['add_new_material_type'] ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action_type" value="add_type">
                    
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['type_name'] ?> <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" class="form-control" required>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> <?= $lang['add_type'] ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><?= $lang['existing_material_types'] ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= $lang['id'] ?></th>
                                <th><?= $lang['type_name'] ?></th>
                                <th><?= $lang['materials_count'] ?></th>
                                <th><?= $lang['actions'] ?></th>
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
                                            <button type="button" class="btn btn-sm btn-primary edit-btn"
                                                    data-id="<?= $type['type_id'] ?>"
                                                    data-name="<?= htmlspecialchars($type['type_name']) ?>">
                                                <i class="fas fa-edit"></i> <?= $lang['edit'] ?>
                                            </button>
                                            <a href="manage-types.php?action=delete_type&id=<?= $type['type_id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('<?= $lang['confirm_delete_type'] ?>');">
                                                <i class="fas fa-trash"></i> <?= $lang['delete'] ?>
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
                <i class="fas fa-arrow-left me-2"></i> <?= $lang['back_to_materials_manager'] ?>
            </a>
        </div>
    </div>

    <!-- Edit Material Type Modal -->
    <div class="modal fade" id="editTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i><?= $lang['edit_material_type'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="update_type">
                        <input type="hidden" name="type_id" id="editTypeId">
                        
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['type_name'] ?> <span class="text-danger">*</span></label>
                            <input type="text" name="type_name" id="editTypeName" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <?= $lang['cancel'] ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?= $lang['update_type'] ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit button functionality
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editTypeModal'));
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const typeId = this.getAttribute('data-id');
                    const typeName = this.getAttribute('data-name');
                    
                    document.getElementById('editTypeId').value = typeId;
                    document.getElementById('editTypeName').value = typeName;
                    
                    editModal.show();
                });
            });
            
            // Auto-focus on input when modal opens
            editModal._element.addEventListener('shown.bs.modal', function() {
                document.getElementById('editTypeName').focus();
            });
        });
    </script>
</body>
</html>