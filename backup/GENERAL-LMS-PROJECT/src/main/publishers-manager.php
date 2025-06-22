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

// Load configuration file containing constants and environment settings
require_once '../conf/config.php';


// Add website and last_modified columns to publishers if needed
try {
    $pdo->query("SELECT website FROM publishers LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE publishers ADD COLUMN website VARCHAR(255) AFTER contact_info");
}

try {
    $pdo->query("SELECT last_modified FROM publishers LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE publishers ADD COLUMN last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

function logActivity($pdo, $user_id, $action, $target_object = null, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $username = $_SESSION['username'] ?? 'System';
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action, target_object, details, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $action, $target_object, $details, $ip_address]);
}

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Handle publisher actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        
        try {
            $pdo->beginTransaction();
            
            switch ($action_type) {
                case 'add_publisher':
                    $stmt = $pdo->prepare("INSERT INTO publishers (name, contact_info, website) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact_info'] ?? '',
                        $_POST['website'] ?? ''
                    ]);
                    $success_msg = "Publisher added successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'INSERT', 'publishers', 'Added publisher: '.$_POST['name']);
                    break;
                    
                case 'update_publisher':
                    $stmt = $pdo->prepare("UPDATE publishers SET name = ?, contact_info = ?, website = ? WHERE publisher_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact_info'] ?? '',
                        $_POST['website'] ?? '',
                        $_POST['publisher_id']
                    ]);
                    $success_msg = "Publisher updated successfully!";
                    logActivity($pdo, $_SESSION['user_id'], 'UPDATE', 'publishers', 'Updated publisher: '.$_POST['name']);
                    break;
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete actions
if (isset($_GET['delete'])) {
    $entity = $_GET['delete'];
    $id = $_GET['id'] ?? null;
    
    if ($id && $entity === 'publisher') {
        try {
            // Check if publisher is used in any items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM library_items WHERE publisher_id = ?");
            $stmt->execute([$id]);
            $item_count = $stmt->fetchColumn();
            
            if ($item_count > 0) {
                $error_msg = "Cannot delete publisher because it is used in $item_count items!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM publishers WHERE publisher_id = ?");
                $stmt->execute([$id]);
                $success_msg = "Publisher deleted successfully!";
                logActivity($pdo, $_SESSION['user_id'], 'DELETE', 'publishers', 'Deleted publisher ID: '.$id);
            }
        } catch (Exception $e) {
            $error_msg = "Error deleting publisher: " . $e->getMessage();
        }
    }
}

// Get publishers with item counts
$publishers = $pdo->query("
    SELECT p.*, COUNT(li.item_id) AS item_count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    GROUP BY p.publisher_id
")->fetchAll();

// Get items for editing
$edit_publisher = null;
if (isset($_GET['edit_publisher'])) {
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE publisher_id = ?");
    $stmt->execute([$_GET['edit_publisher']]);
    $edit_publisher = $stmt->fetch();
}

// Get statistics for dashboard
$stats = [
    'total_publishers' => $pdo->query("SELECT COUNT(*) FROM publishers")->fetchColumn(),
    'publishers_with_items' => $pdo->query("SELECT COUNT(DISTINCT publisher_id) FROM library_items WHERE publisher_id IS NOT NULL")->fetchColumn(),
    'items_in_publishers' => $pdo->query("SELECT COUNT(*) FROM library_items WHERE publisher_id IS NOT NULL")->fetchColumn(),
    'recently_updated' => $pdo->query("SELECT COUNT(*) FROM publishers WHERE last_modified >= CURDATE() - INTERVAL 7 DAY")->fetchColumn()
];

// Get chart data
$publisher_distribution = $pdo->query("
    SELECT p.name, COUNT(li.item_id) AS count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    GROUP BY p.publisher_id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get recently updated publishers
$recently_updated = $pdo->query("
    SELECT p.*, COUNT(li.item_id) AS item_count 
    FROM publishers p
    LEFT JOIN library_items li ON p.publisher_id = li.publisher_id
    WHERE p.last_modified >= CURDATE() - INTERVAL 7 DAY
    GROUP BY p.publisher_id
    ORDER BY p.last_modified DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers Manager - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link rel="stylesheet" href="../styles/publishers.css">
</head>
<body>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
         SIDEBAR COMPONENT
         - Persistent navigation panel
         - Loaded from shared component file
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <?php include '../components/sidebar.php'; ?>
    <?php include '../components/publisher-main-content.php'; ?>


    
    <?php include '../components/pub-modal.php'; ?>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Publisher Distribution Chart
            const publisherCtx = document.getElementById('publisherChart').getContext('2d');
            const publisherLabels = <?= json_encode(array_keys($publisher_distribution)) ?>;
            const publisherData = <?= json_encode(array_values($publisher_distribution)) ?>;
            
            const publisherChart = new Chart(publisherCtx, {
                type: 'bar',
                data: {
                    labels: publisherLabels,
                    datasets: [{
                        label: 'Items by Publisher',
                        data: publisherData,
                        backgroundColor: '#4e73df',
                        borderColor: '#4e73df',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y} items`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Open modal if editing
            <?php if ($edit_publisher): ?>
                const publisherModal = new bootstrap.Modal(document.getElementById('publisherModal'));
                publisherModal.show();
            <?php endif; ?>
        });
        
        // Confirm before deleting
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this publisher?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>