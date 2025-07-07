<?php


// ===== Database connection parameters =====

$host = 'localhost';      
$db   = 'gplms_general';  
$user = 'root';           
$pass = 'root';           
$charset = 'utf8mb4';     

// Data Source Name (DSN) - Connection string for PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use native prepared statements
];

try {
    // Create PDO database connection instance
    $pdo = new PDO($dsn, $user, $pass, $options);
    
} catch (\PDOException $e) {
    /**
     * Critical connection error handling
     * 
     * - Terminates script execution immediately
     * - Outputs sanitized error message (avoid exposing sensitive info in production)
     */
    $errorMessage = 'Database connection failed: ' . $e->getMessage();
    // Log full error for debugging
    error_log($errorMessage);
    
    die('Database connection failed. Please try again later.');
}
?>
