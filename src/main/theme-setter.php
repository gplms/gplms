<?php
// detect_theme.php
session_start();
require_once '../conf/config.php';

// Get theme from database (default to light if not set)
$theme = 'light';
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'default_theme'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['setting_value'])) {
        $theme = $result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Theme detection error: " . $e->getMessage());
}

// Apply theme to the page
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Detection Demo</title>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --accent-color: #0d6efd;
            --border-color: #dee2e6;
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #f0f0f0;
            --text-secondary: #b0b0b0;
            --accent-color: #4d8eff;
            --border-color: #444;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: system-ui, sans-serif;
            line-height: 1.6;
            padding: 2rem;
            transition: background-color 0.3s, color 0.3s;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: var(--accent-color);
            margin-top: 0;
        }

        .theme-info {
            padding: 1rem;
            background-color: var(--bg-secondary);
            border: 1px dashed var(--border-color);
            border-radius: 4px;
            margin: 2rem 0;
        }

        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
        }

        .theme-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--accent-color);
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Theme Detection Demo</h1>
            <p>This page demonstrates dynamic theme switching based on database settings.</p>
            
            <div class="theme-info">
                <p>Current theme: <strong><?= strtoupper($theme) ?></strong></p>
                <p>Theme detected from database: <code>default_theme = '<?= $theme ?>'</code></p>
            </div>
            
            <h2>How to Use This in Your System</h2>
            <ol>
                <li>Add this to the &lt;html&gt; tag: <code>&lt;html data-theme="&lt;?= $theme ?&gt;"&gt;</code></li>
                <li>Define CSS variables for both themes using the <code>[data-theme="light"]</code> and <code>[data-theme="dark"]</code> selectors</li>
                <li>Use the CSS variables throughout your stylesheets</li>
                <li>Update the theme setting in your database to see changes</li>
            </ol>
            
            <div class="card">
                <h3>Sample Components</h3>
                <p>These elements automatically adapt to the current theme:</p>
                
                <button style="background-color: var(--accent-color); color: white; padding: 10px 20px; border: none; border-radius: 4px;">
                    Sample Button
                </button>
                
                <div style="margin-top: 20px; padding: 15px; border: 1px solid var(--border-color); border-radius: 4px;">
                    <p>Sample card with border</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="theme-toggle" id="themeToggle">
        <span class="theme-status"></span>
        Toggle Theme
    </div>

    <script>
        // Client-side theme toggle for demo purposes
        document.getElementById('themeToggle').addEventListener('click', function() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            document.querySelector('.theme-info strong').textContent = newTheme.toUpperCase();
            
            // Update database via AJAX (optional)
            fetch('update_theme.php?theme=' + newTheme)
                .then(response => response.text())
                .then(data => console.log('Theme updated to:', newTheme))
                .catch(error => console.error('Error updating theme:', error));
        });
    </script>
</body>
</html>