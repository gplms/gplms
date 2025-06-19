<?php
// Handle form submission
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Simulate email sending (in a real app, you would use mail() or PHPMailer)
        $to = "developers@librarysystem.com";
        $headers = "From: $email" . "\r\n" .
                   "Reply-To: $email" . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        // This would be the actual email sending code:
        // $mail_sent = mail($to, $subject, $message, $headers);
        $mail_sent = true; // Simulating success
        
        if ($mail_sent) {
            $message_sent = true;
        } else {
            $error_message = "There was a problem sending your message. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Developers - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/contact-devs-styles.css">
    
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="logo">
                    <i class="fas fa-book"></i>
                </div>
                <span class="logo-text">LibrarySystem</span>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php"><i class="fas fa-search me-1"></i> Search Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="insert.php"><i class="fas fa-plus-circle me-1"></i> Insert Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php"><i class="fas fa-headset me-1"></i> Contact Developers</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="?logout=true">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout (<?= $_SESSION['username'] ?>)
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contact Header -->
    <div class="contact-header">
        <div class="container">
            <h1 class="contact-title">Contact Our Development Team</h1>
            <p class="contact-subtitle">Have questions, suggestions, or need support? We're here to help you with any inquiries about our Library Management System.</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="contact-container">
            <div class="contact-content">
                <div class="row">
                    <div class="col-lg-7 mb-5 mb-lg-0">
                        <div class="contact-form">
                            <h2 class="mb-4">Send us a Message</h2>
                            
                            <?php if ($message_sent): ?>
                                <div class="success-message">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Thank you for your message!</strong> We'll get back to you as soon as possible.
                                </div>
                            <?php elseif ($error_message): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error:</strong> <?= $error_message ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="contact.php">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label">Your Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Your Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea name="message" class="form-control" rows="6" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-send">
                                    <i class="fas fa-paper-plane me-2"></i> Send Message
                                </button>
                            </form>
                        </div>
                        
                        <div class="github-section mt-5">
                            <div class="row">
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div class="github-card text-center">
                                        <i class="fab fa-github github-icon"></i>
                                        <h3>Project Repository</h3>
                                        <p>Explore our open-source code, contribute to the project, or report issues.</p>
                                        <a href="https://github.com/librarysystem" class="github-btn" target="_blank">
                                            <i class="fab fa-github me-2"></i> View on GitHub
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="github-card text-center">
                                        <i class="fas fa-book github-icon"></i>
                                        <h3>Documentation</h3>
                                        <p>Read our comprehensive documentation to learn how to use the system.</p>
                                        <a href="https://github.com/librarysystem/docs" class="github-btn" target="_blank">
                                            <i class="fas fa-book me-2"></i> Read Documentation
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-5">
                        <h2 class="mb-4">Get in Touch</h2>
                        <p class="mb-4">Our development team is ready to assist you with any questions, feedback, or support requests regarding the Library Management System.</p>
                        
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h5>Email Us</h5>
                                <p class="mb-0">developers@librarysystem.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h5>Visit Us</h5>
                                <p class="mb-0">123 Library Street, Athens, Greece</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h5>Call Us</h5>
                                <p class="mb-0">+30 210 123 4567</p>
                            </div>
                        </div>
                        
                        <div class="info-card mt-4">
                            <h3 class="info-title">Development Team</h3>
                            <ul class="feature-list">
                                <li>Panagiotis Kotsorgios - Lead Developer</li>
                                <li>Maria Papadopoulou - Frontend Specialist</li>
                                <li>Dimitris Georgiou - Database Architect</li>
                                <li>Eleni Nikolaou - UX/UI Designer</li>
                            </ul>
                        </div>
                        
                        <div class="info-card">
                            <h3 class="info-title">Project Information</h3>
                            <p>The Library Management System is an open-source project designed to help libraries manage their collections efficiently. Our goal is to provide a free, powerful tool for libraries of all sizes.</p>
                            <div class="mt-3">
                                <strong>Current Version:</strong> 2.1.0<br>
                                <strong>Last Updated:</strong> June 18, 2025<br>
                                <strong>License:</strong> MIT Open Source
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Library Management System</h5>
                    <p>A comprehensive solution for managing library resources and materials.</p>
                    <div class="mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="search.php" class="text-white">Search Books</a></li>
                        <li><a href="insert.php" class="text-white">Insert Books</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="https://github.com/librarysystem" class="text-white" target="_blank">GitHub Repository</a></li>
                        <li><a href="https://github.com/librarysystem/docs" class="text-white" target="_blank">Documentation</a></li>
                        <li><a href="#" class="text-white">User Guides</a></li>
                        <li><a href="#" class="text-white">API Documentation</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@librarysystem.com</li>
                        <li><i class="fas fa-phone me-2"></i> +30 210 123 4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Athens, Greece</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light my-4">
            <div class="text-center">
                &copy; 2023 Library Management System. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animation for form elements
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>