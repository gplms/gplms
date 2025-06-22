<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/components/header.css">
    <link rel="stylesheet" href="../styles/components/footer.css">
    <link rel="stylesheet" href="../styles/hero-styles.css">
    <link rel="stylesheet" href="../styles/general/general-styles.css">
    <link rel="stylesheet" href="../styles/features-styles.css">

</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-book"></i>
                </div>
                <div class="logo-text">GP<span>LMS</span></div>
            </div>
            
            <nav>
                <div class="hamburger" id="hamburger">
                    <i class="fas fa-bars"></i>
                </div>
                <ul id="nav-menu">
                    <li><a href="search.php"><i class="fas fa-search"></i> &nbsp;View / Search</a></li>
                    <li><a href="#"><i class="fas fa-plus-circle"></i> &nbsp;Read Documentation</a></li>
                    <li><a href="contact_devs.php"><i class="fas fa-headset"></i> &nbsp;Contact Developers</a></li>
                    <li><a href="control_panel.php"><i class="fa-sharp fa-solid fa-screwdriver-wrench"></i> &nbsp;Control Panel</a></li>
                    <li><a href="login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> &nbsp;Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>A General Purpose Library Management System</h1>
            <p>Efficiently manage your library resources with our comprehensive system. Catalog books, magazines, and more with ease.</p>
            <div class="cta-buttons">
                <a href="https://github.com/PanagiotisKotsorgios/gplms" class="btn btn-primary">Get Started</a>
                <a href="#" class="btn btn-outline">View Demo</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="section-title">
            <h2>Powerful Library Features</h2>
            <p>Our system provides everything you need for efficient library management</p>
        </div>
        
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="feature-content">
                    <h3>Comprehensive Catalog</h3>
                    <p>Manage books, magazines, newspapers, journals, and manuscripts all in one place with detailed metadata.</p>
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="feature-content">
                    <h3>Role-Based Access</h3>
                    <p>Define user roles with specific permissions for administrators, librarians, and regular users.</p>
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="feature-content">
                    <h3>Advanced Search</h3>
                    <p>Quickly find materials by title, author, publisher, category, or any other data field.</p>
                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer>
     <p class="centering">
    Made with <span>💖</span> using pure 
    <code style = "color:#E44D26;"> &nbsp;HTML</code>, <code style = "color:#2965F1;">&nbsp;CSS</code>, <code style = "color:#F7DF1E;">&nbsp;JavaScript</code>, 
    <code style = "color:#777BB3;">&nbsp;PHP</code>, & &nbsp;<code style = "color:#00758F;">MySQL&nbsp;</code> by&nbsp;=>&nbsp;&nbsp;&nbsp; [ &nbsp;&nbsp;
    <a href="#">Kotsorgios Panagiotis</a> &nbsp;& &nbsp;
    <a href="#">Fotis Markantonatos</a>&nbsp;&nbsp;]
  </p>
        <div class="copyright">
            <p>&copy; 2025 GPLMS. All rights reserved.</p>
        </div>
    </footer>

<script src = "../js/general.js"></script>
</body>
</html>