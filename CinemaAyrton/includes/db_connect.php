<?php
/**
 * Database connection script
 * Establishes a PDO connection to the MySQL database
 */

// Database configuration
$host = 'localhost';
$dbname = 'cinema_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// PDO connection options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Create PDO instance
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Display a user-friendly message and log the error
    die('Database Connection Error: ' . $e->getMessage());
}
