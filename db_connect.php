<?php
// Database credentials
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'osrs_db';

// Create connection with error handling
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Could not connect to MySQL: " . $e->getMessage());
}
?>
