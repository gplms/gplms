<?php
/**
 * Database Connection Configuration and Initialization
 * 
 * Establishes a PDO connection to MySQL with appropriate error handling.
 * Uses UTF-8 character set for proper international character support.
 * Configures PDO to throw exceptions on errors for error handling.
 */



// ===== Database connection parameters =====

$host = 'localhost';      // MySQL server hostname (use IP or domain if remote)
$db   = 'gplms_general';  // Database name to connect to
$user = 'root';           // Database username 
$pass = 'root';           // Database password 
$charset = 'utf8mb4';     // Character encoding 

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
    
    // Connection successful (optional log message)
    // error_log('Database connection established');
} catch (\PDOException $e) {
    /**
     * Critical connection error handling
     * 
     * - Terminates script execution immediately
     * - Outputs sanitized error message (avoid exposing sensitive info in production)
     * - In production, log detailed error but show generic message to users
     */
    $errorMessage = 'Database connection failed: ' . $e->getMessage();
    // Log full error for debugging (use in development)
    error_log($errorMessage);
    
    // Terminate with user-safe message
    die('Database connection failed. Please try again later.');
}
?>