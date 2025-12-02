<?php
// Migration to link old notifications (without appointment_id) to their corresponding appointments
// This allows old notifications to display booking details in the UI

function linkOldNotificationsToAppointments() {
    $conn = getDbConnection();
    
    // Guard: Only run if there are still unlinked notifications
    $checkResult = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE appointment_id IS NULL");
    $checkRow = $checkResult->fetch_assoc();
    
    if ($checkRow['count'] === 0) {
        // Already migrated, skip
        return true;
    }
    
    // Step 1: Check if appointment_id column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM notifications LIKE 'appointment_id'");
    if (!$checkColumn || $checkColumn->num_rows === 0) {
        echo "ERROR: appointment_id column does not exist. Run migration 001 first.\n";
        return false;
    }
    
    // Step 2: Find notifications with NULL appointment_id
    $result = $conn->query("
        SELECT n.notif_id, n.customer_id, n.business_id, n.notif_creation
        FROM notifications n
        WHERE n.appointment_id IS NULL
        AND n.customer_id IS NOT NULL
    ");
    
    if (!$result) {
        return false;
    }
    
    $totalNotifications = $result->num_rows;
    
    if ($totalNotifications === 0) {
        return true;
    }
    
    $updated = 0;
    $skipped = 0;
    
    while ($notif = $result->fetch_assoc()) {
        $notifId = $notif['notif_id'];
        $customerId = $notif['customer_id'];
        $businessId = $notif['business_id'];
        $notifCreation = $notif['notif_creation'];
        
        // Find the most recent appointment for this customer around this notification time
        // Since appointments don't have business_id directly, match by customer and time
        $appointmentQuery = $conn->prepare("
            SELECT appointment_id
            FROM appointments
            WHERE customer_id = ?
            AND set_date <= ?
            ORDER BY set_date DESC
            LIMIT 1
        ");
        
        $appointmentQuery->bind_param("is", $customerId, $notifCreation);
        $appointmentQuery->execute();
        $appointmentResult = $appointmentQuery->get_result();
        
        if ($appointmentResult && $appointmentResult->num_rows > 0) {
            $appointment = $appointmentResult->fetch_assoc();
            $appointmentId = $appointment['appointment_id'];
            
            // Update notification with appointment_id
            $updateStmt = $conn->prepare("
                UPDATE notifications
                SET appointment_id = ?
                WHERE notif_id = ?
            ");
            
            $updateStmt->bind_param("ii", $appointmentId, $notifId);
            if ($updateStmt->execute()) {
                $updated++;
            } else {
                $skipped++;
            }
            $updateStmt->close();
        } else {
            $skipped++;
            echo "- Notification $notifId: no matching appointment found (skipped)\n";
        }
        
        $appointmentQuery->close();
    }
    
    return true;
}

// Run migration if called directly from CLI or via browser
if (php_sapi_name() === 'cli' || isset($_GET['run_migration'])) {
    require_once __DIR__ . '/../db_connection/config.php';
    linkOldNotificationsToAppointments();
}
?>
