    <div class="login-container">
        <div class="login-header">
            <div class="logo" style="display: flex; justify-content: center; align-items: center; height: 100px;">
                <img 
                    src="https://www.shutterstock.com/image-photo/book-open-pages-close-up-600nw-2562942291.jpg" 
                    alt="Logo" 
                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;"
                />
            </div>
            <h2><?=$library_name?> - <?= strtoupper($lang['login']) ?></h2>
            <p><?= $lang['login_subtitle'] ?></p>
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
                    <label for="username"><?= $lang['username'] ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="<?= $lang['username_placeholder'] ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><?= $lang['password'] ?></label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="<?= $lang['password_placeholder'] ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> <?= $lang['login'] ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="login-footer">
            <p><?= $lang['forgot_password_link'] ?> &nbsp;&nbsp;&nbsp;<a href="../main/forgot-password.php"><?= $lang['click_to_retrieve'] ?></a></p>
            <hr>
            <div class="btns">
                <a href="../main/search.php">
                    <button 
                        style="
                            background: #1976d2;
                            color: white;
                            border: none;
                            padding: 12px 24px;
                            margin: 8px;
                            border-radius: 4px;
                            font-size: 15px;
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        "
                        onmouseover="this.style.background='#1565c0';this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'"
                        onmouseout="this.style.background='#1976d2';this.style.transform='';this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'"
                    >
                        <i class="fa-solid fa-eye"></i> &nbsp;&nbsp;<?= $lang['view_search_items'] ?>
                    </button>
                </a>
                
                <a href="https://github.com/PanagiotisKotsorgios/gplms/tree/main/docs">
                    <button 
                        style="
                            background: #f8f9fa;
                            color: #1976d2;
                            border: 1px solid #90caf9;
                            padding: 12px 24px;
                            margin: 8px;
                            border-radius: 4px;
                            font-size: 15px;
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        "
                        onmouseover="this.style.background='#e3f2fd';this.style.borderColor='#1976d2';this.style.color='#0d47a1'"
                        onmouseout="this.style.background='#f8f9fa';this.style.borderColor='#90caf9';this.style.color='#1976d2'"
                    >
                        <i class="fa-brands fa-readme"></i> &nbsp;&nbsp;<?= $lang['read_docs_manual'] ?>
                    </button>
                </a>
            </div>
        </div>
    </div>