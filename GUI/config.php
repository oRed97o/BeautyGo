<?php
// Configuration file for BeautyGo
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'beautygo_db2');

// Application settings
define('SITE_NAME', 'BeautyGo');
define('SITE_URL', 'http://localhost');

// Color scheme
define('COLOR_BURGUNDY', '#850E35');
define('COLOR_ROSE', '#EE6983');
define('COLOR_PINK', '#FFC4C4');
define('COLOR_CREAM', '#FFF5E4');

// Create database connection
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Note: All passwords in the database are hashed with password_hash('password123', PASSWORD_DEFAULT)
?>
