<?php/*
===============================================================================
  GPLMS (General Purpose Library Management System)
===============================================================================

    Thank you for using our software 😁💖
===============================================================================
*/




// We start the session here
session_start();

// Include the Configuration Component (db connection attributes and handling)
require_once '../conf/config.php';

// Include the translation component
require_once '../conf/translation.php'; 

// End of php code
?>


<!DOCTYPE html>
<html lang="<?= $default_language === 'GR' ? 'el' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['404_page_title'] ?></title>
    
    <!-- External dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    
    <!-- 404-specific styles -->
    <link rel="stylesheet" href="../styles/templates/404.css">
    <link rel="stylesheet" href = "../styles/templates/general-styles.css">
    <link rel="stylesheet" href = "../styles/templates/footer.css">
    <link rel="stylesheet" href = "../styles/templates/error.css">
</head>
<body>
    <!-- Embedded 404 error template component -->
    <?php include '../templates/404.php' ?>
    
    <!-- Copyright footer with dynamic year -->
    <div class="footer">
        &copy; <?= date('Y') ?> GPLMS. <?= $lang['all_rights_reserved'] ?>
    </div>
</body>
</html>
