<?php
session_start();
require_once '../conf/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get item ID
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$item_id) {
    header("Location: search.php");
    exit;
}

// Get item details
$stmt = $pdo->prepare("
    SELECT li.*, u.username AS added_by_username
    FROM library_items li
    LEFT JOIN users u ON li.added_by = u.user_id
    WHERE li.item_id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: search.php");
    exit;
}

// Check permissions
$can_edit = false;
if ($_SESSION['role'] === 'Administrator') {
    $can_edit = true;
} elseif ($_SESSION['role'] === 'Librarian' && $item['added_by'] == $_SESSION['user_id']) {
    $can_edit = true;
}

if (!$can_edit) {
    header("Location: search.php");
    exit;
}

// Get data for dropdowns
$types = $pdo->query("SELECT * FROM material_types")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers")->fetchAll();

// Get authors for this item
$stmt = $pdo->prepare("
    SELECT a.author_id, a.name 
    FROM item_authors ia
    JOIN authors a ON ia.author_id = a.author_id
    WHERE ia.item_id = ?
");
$stmt->execute([$item_id]);
$current_authors = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
$all_authors = $pdo->query("SELECT * FROM authors")->fetchAll();

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Update library item
        $stmt = $pdo->prepare("
            UPDATE library_items
            SET title = ?, type_id = ?, category_id = ?, publisher_id = ?, 
                language = ?, publication_year = ?, edition = ?, 
                isbn = ?, issn = ?, description = ?
            WHERE item_id = ?
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['type_id'],
            $_POST['category_id'] ?: null,
            $_POST['publisher_id'],
            $_POST['language'],
            $_POST['publication_year'] ?: null,
            $_POST['edition'] ?: null,
            $_POST['isbn'] ?: null,
            $_POST['issn'] ?: null,
            $_POST['description'] ?: null,
            $item_id
        ]);
        
        // Process authors
        $new_author_ids = $_POST['author_ids'] ?? [];
        
        // Remove existing author relationships not in new selection
        $stmt = $pdo->prepare("
            DELETE FROM item_authors 
            WHERE item_id = ? 
            AND author_id NOT IN (" . implode(',', array_map('intval', $new_author_ids)) . ")
        ");
        $stmt->execute([$item_id]);
        
        // Add new author relationships
        foreach ($new_author_ids as $author_id) {
            // Check if relationship already exists
            $stmt = $pdo->prepare("
                SELECT 1 FROM item_authors 
                WHERE item_id = ? AND author_id = ?
            ");
            $stmt->execute([$item_id, $author_id]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("
                    INSERT INTO item_authors (item_id, author_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$item_id, $author_id]);
            }
        }
        
        $pdo->commit();
        $success_message = "Material updated successfully!";
        
        // Refresh item data
        $stmt = $pdo->prepare("SELECT * FROM library_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error updating material: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Edit Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 800px; }
        .form-card { background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .section-title { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center mb-4"><i class="fas fa-edit me-2"></i>Edit Material</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> 
            You are editing material added by: <strong><?= $item['added_by_username'] ?></strong>
        </div>
        
        <form method="POST">
            <div class="form-card">
                <h3 class="section-title">Material Information</h3>
                
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($item['title']) ?>">
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Material Type <span class="text-danger">*</span></label>
                        <select name="type_id" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= $type['type_id'] ?>" 
                                    <?= $item['type_id'] == $type['type_id'] ? 'selected' : '' ?>>
                                    <?= $type['type_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>" 
                                    <?= $item['category_id'] == $category['category_id'] ? 'selected' : '' ?>>
                                    <?= $category['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Language <span class="text-danger">*</span></label>
                        <select name="language" class="form-select" required>
                            <option value="EN" <?= $item['language'] === 'EN' ? 'selected' : '' ?>>English</option>
                            <option value="GR" <?= $item['language'] === 'GR' ? 'selected' : '' ?>>Greek</option>
                            <option value="Other" <?= $item['language'] === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Publication Year</label>
                        <input type="number" name="publication_year" class="form-control" 
                               min="1000" max="<?= date('Y') ?>"
                               value="<?= $item['publication_year'] ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Edition</label>
                        <input type="number" name="edition" class="form-control" min="1"
                               value="<?= $item['edition'] ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= 
                        htmlspecialchars($item['description']) 
                    ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3 class="section-title">Identifiers</h3>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control"
                               value="<?= $item['isbn'] ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">ISSN</label>
                        <input type="text" name="issn" class="form-control"
                               value="<?= $item['issn'] ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <h3 class="section-title">Publisher</h3>
                
                <div class="mb-3">
                    <label class="form-label">Select Publisher</label>
                    <select name="publisher_id" class="form-select">
                        <option value="">Select Publisher</option>
                        <?php foreach ($publishers as $publisher): ?>
                            <option value="<?= $publisher['publisher_id'] ?>" 
                                <?= $item['publisher_id'] == $publisher['publisher_id'] ? 'selected' : '' ?>>
                                <?= $publisher['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-card">
                <h3 class="section-title">Authors</h3>
                
                <div class="mb-3">
                    <label class="form-label">Select Authors</label>
                    <select name="author_ids[]" class="form-select" multiple size="8">
                        <?php foreach ($all_authors as $author): ?>
                            <option value="<?= $author['author_id'] ?>" 
                                <?= in_array($author['author_id'], $current_authors) ? 'selected' : '' ?>>
                                <?= $author['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple authors</small>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Update Material
                </button>
                <a href="materials-manager.php" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>