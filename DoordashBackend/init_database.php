<?php
// Run this file once to set up the database tables
// $host = 'localhost';
// $dbname = 'doordash';
// $username = 'root';
// $password = '';

    $host = "localhost";
$dbname = "doordash";
$username = "root";
$password = "";
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Create users table with user_type column
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            user_type ENUM('customer', 'restaurant', 'delivery', 'admin') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Database setup completed successfully!";
    
} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>