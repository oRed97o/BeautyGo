<?php
session_start(); 

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'beautygo_db2');

// Application settings
define('SITE_NAME', 'BeautyGo');
define('SITE_URL', 'http://localhost');

// Create database connection
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        // Run migrations on first connection if needed
        static $migrations_run = false;
        if (!$migrations_run) {
            @require_once __DIR__ . '/../migrations/001_add_appointment_id_to_notifications.php';
            if (function_exists('addAppointmentIdToNotifications')) {
                @addAppointmentIdToNotifications();
            }
            @require_once __DIR__ . '/../migrations/002_link_old_notifications_to_appointments.php';
            if (function_exists('linkOldNotificationsToAppointments')) {
                @linkOldNotificationsToAppointments();
            }
            $migrations_run = true;
        }
    }
    
    return $conn;
}

// Note: All passwords in the database are hashed with password_hash('password123', PASSWORD_DEFAULT)
?>
