
<?php
// Access Control: Verify user role
if (!isset($_SESSION['role'])) {
    // Redirect unauthenticated users to login
    header("Location: login.php");
    exit;
} elseif ($_SESSION['role'] !== 'Administrator') {
    // Redirect non-admin users to search page
    header("Location: search.php");
    exit;
}
?>