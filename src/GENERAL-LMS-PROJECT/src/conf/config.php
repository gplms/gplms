<?php
// Database Configuration
define('DB_HOST', 'localhost');     // Database server (usually localhost)
define('DB_NAME', 'uni_library');   // Database name
define('DB_USER', 'library_admin'); // Database username
define('DB_PASS', 'Lib@2023!');     // Database password - CHANGE IN PRODUCTION

// Application Settings
define('BASE_URL', 'http://localhost/library'); // Base URL of your application
date_default_timezone_set('Africa/Johannesburg'); // Set to your university's timezone

// Security Settings (Enable these in production)
// define('ENFORCE_SSL', true);      // Uncomment to force HTTPS
// define('DEBUG_MODE', false);      // Uncomment to hide errors in production

// Basic Path Configuration
define('BOOK_COVERS_DIR', __DIR__ . '/uploads/covers/'); // Book cover storage
?>