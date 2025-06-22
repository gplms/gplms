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