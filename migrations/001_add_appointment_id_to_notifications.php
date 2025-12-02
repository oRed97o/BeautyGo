<?php
/**
 * Migration: Add appointment_id column to notifications table
 * This allows notifications to be linked to specific appointments for better tracking
 */

require_once __DIR__ . '/../db_connection/config.php';

function addAppointmentIdToNotifications() {
    $conn = getDbConnection();
    
    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'appointment_id'");
    
    if ($result && $result->num_rows > 0) {
        return true;
    }
    
    // Add the column
    $sql = "ALTER TABLE notifications 
            ADD COLUMN appointment_id INT NULL AFTER customer_id,
            ADD FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL";
    
    if ($conn->query($sql)) {
        // Create index for better query performance
        $conn->query("CREATE INDEX idx_notif_appointment ON notifications(appointment_id)");
        
        return true;
    } else {
        return false;
    }
}

// Run migration if this file is called directly
if (php_sapi_name() === 'cli' || (isset($_GET['run_migration']) && $_GET['run_migration'] === 'notifications')) {
    echo "\n=== Running Notifications Migration ===\n";
    addAppointmentIdToNotifications();
    echo "=== Migration Complete ===\n\n";
}

// Auto-run migration on first page load if column doesn't exist
if (!isset($_SESSION['notifications_migrated'])) {
    $conn = getDbConnection();
    $result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'appointment_id'");
    
    if (!$result || $result->num_rows === 0) {
        addAppointmentIdToNotifications();
    }
    
    $_SESSION['notifications_migrated'] = true;
}
?>
