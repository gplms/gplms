

<?php

/*
===============================================================================
  GPLMS (General Purpose Library Management System)
===============================================================================

    Thank you for using our software 😁💖
===============================================================================
*/


// Start the session here
session_start();

// Including the config.php that has the db connection credentials
require_once '../conf/config.php';

// Include the translation component that has the greek-english translation in a multidimensional associative array
require_once '../conf/translation.php'; 

?> 
<!-- End of php -->


<!-- Start of HTML -->
<!DOCTYPE html>
<html lang="<?= $default_language === 'GR' ? 'el' : 'en' ?>"> <!-- Sets language: Greek if default language is 'GR', otherwise English -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - <?= $lang['403_page_title'] ?></title>    <!-- Sets language: Greek if default language is 'GR', otherwise English -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- FONTAWESOME icons free include cdn -->
    
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">

    <!-- Linking the css file containing the styles for this page -->
    <link rel="stylesheet" href = "../styles/templates/403.css">
    <link rel="stylesheet" href = "../styles/templates/general-styles.css">
    <link rel="stylesheet" href = "../styles/templates/footer.css">
    <link rel="stylesheet" href = "../styles/templates/error.css">
</head>

<!-- Main Html Template Content -->
<body>

<!-- The 403 Main Component -->
<?php include '../templates/403.php' ?>
    
     <!-- Footer -->
    <div class="footer">
        &copy; <?= date('Y') ?> GPLMS. <?= $lang['all_rights_reserved'] ?> 
    </div>
</body>
</html>
