    <div class="login-container">
        <div class="login-header">
            
<div class="logo" style="display: flex; justify-content: center; align-items: center; height: 100px;">
  <img 
    src="https://www.shutterstock.com/image-photo/book-open-pages-close-up-600nw-2562942291.jpg" 
    alt="Logo" 
    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;"
  />
</div>


            <h2>GPLMS - LOGIN</h2>
            <p>Access your library management system</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error_message ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </div>
            </form>
        </div>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="contact_admin.php">Contact administrator</a></p>
            <p><a href="index.php">Return to homepage</a></p>
            <hr>
             <p><a href="index.php">Forgot Password ?</a></p>
        </div>
    </div>
    