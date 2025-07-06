

<?php

/*
===============================================================================
  GPLMS (General Purpose Library Management System)
===============================================================================
  Project Repository : https://github.com/PanagiotisKotsorgios/gplms
  License            : MIT Licence
  Copyright          : (c) 2025 Panagiotis Kotsorgios, Fotis Markantonatos & Contributors
  Website            : [+]

  Description:
    GPLMS is a free and open-source Library Management System for schools,
    universities, and public libraries. It is built using PHP, HTML, JavaScript,
    and MySQL, and is designed to be modular, extensible, and easy to deploy.

  Created At:
    - SAEK MESOLOGHIOY [MESOLOGHI] [GREECE]
    - WEBSITE: [https://www.saekmesol.gr/]
            
  This File:
    - [403.php]
    - Purpose: [A Custom made 403 response template]

  Documentation:
    - Setup Guide         : https://github.com/PanagiotisKotsorgios/gplms/blob/main/README.md
    - User Guide          : https://github.com/PanagiotisKotsorgios/gplms/blob/main/docs/README.md

  Contributing:
    - Please see the contributing guide at 
      https://github.com/PanagiotisKotsorgios/gplms/blob/main/CONTRIBUTION.md

  License Notice:

    This project was originally created by students and independent open-source developers,
    not by a professional company. It is made for the community, by the community, in the
    spirit of open source and collective learning. Contributions, use, and sharing are
    greatelly encouraged!

    This program is free software: you can use, copy, modify, merge, publish,
    distribute, sublicense, and/or sell copies of it under the terms of the MIT License.
    See https://opensource.org/licenses/MIT for details.

    WARNING: This software is provided as-is, without any warranty of any kind.
    That means there are no guarantees, either express or implied, including but not limited to
    merchantability, fitness for a particular purpose, or non-infringement.
    The authors and contributors are not responsible for any issues, damages, or losses
    that may arise from using, modifying, or distributing this software. 
    You use this project entirely at your own risk.

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