<!--
===============================================================================
  GPLMS - General Purpose Library Management System
  File: 404.php (Custom 404 response template) 
  License: MIT (See https://opensource.org/licenses/MIT)
  Copyright (c) 2025 Panagiotis Kotsorgios, Fotis Markantonatos & Contributors
  https://github.com/PanagiotisKotsorgios/gplms

    Thank you for using our software 😁💖
===============================================================================
-->

<!-- 404 error content container -->
<div class="error-container">
    <div class="error-header">
        <div class="error-number">404</div>
        <!-- Localized error heading -->
        <h1 class="error-title"><?= $lang['page_not_found_title'] ?></h1>
    </div>

    <p class="error-message">
        <?= $lang['page_not_found_message'] ?>
    </p>
    
    <div class="btn-container">
        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> <?= $lang['go_to_login'] ?>
        </a>
    </div>
</div>