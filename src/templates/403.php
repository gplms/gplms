<!--
===============================================================================
  GPLMS - General Purpose Library Management System
  File: 403.php (Custom 403 response template )
  License: MIT (See https://opensource.org/licenses/MIT)
  Copyright (c) 2025 Panagiotis Kotsorgios, Fotis Markantonatos & Contributors
  https://github.com/PanagiotisKotsorgios/gplms

    Thank you for using our software 😁💖
===============================================================================
-->
    
    <!-- Container Start -->
    <div class="container">

        <!-- Error-header Start -->
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="error-number">403</div>
            <h1 class="error-title"><?= $lang['access_denied_title'] ?></h1>

        <!-- Error-header End -->
        </div>
        
        <p class="error-message">
            <?= $lang['access_denied_message1'] ?>
        </p>
        <p class="error-message">
            <?= $lang['access_denied_message2'] ?>
        </p>
        
        <!-- btn-container Start -->
        <div class="btn-container">
            <a href="login.php" class="btn-action">
                <i class="fas fa-sign-in-alt"></i> <?= $lang['go_to_login'] ?>
            </a>
         <!-- btn-container End -->
        </div>

    <!-- Container End -->
    </div>