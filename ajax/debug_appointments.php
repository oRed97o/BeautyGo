<?php
/**
 * DEBUG ENDPOINT: Check what appointments exist in the database
 * Usage: ajax/debug_appointments.php?business_id=1&date=2025-11-27
 */

require_once __DIR__ . '/../db_connection/config.php';

header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    $businessId = $_GET['business_id'] ?? '';
    $date = $_GET['date'] ?? '';
    
    if (empty($businessId) || empty($date)) {
        throw new Exception('Missing business_id or date parameter');
    }
    
    // Get all appointments for this business on this date
    $query = "SELECT 
                a.appointment_id,
                a.customer_id,
                a.employ_id,
                a.service_id,
                a.appoint_date,
                a.appoint_status,
                a.duration,
                s.duration as service_duration,
                s.service_name,
                e.employ_fname,
                e.employ_lname,
                c.fname as customer_fname
              FROM appointments a
              JOIN services s ON a.service_id = s.service_id
              LEFT JOIN employees e ON a.employ_id = e.employ_id
              LEFT JOIN customers c ON a.customer_id = c.customer_id
              WHERE s.business_id = ?
              AND DATE(a.appoint_date) = ?
              AND a.appoint_status IN ('pending', 'confirmed')
              ORDER BY a.appoint_date ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("is", $businessId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['appoint_date']);
        $duration = $row['duration'] ?? $row['service_duration'];
        $end = clone $start;
        $end->modify("+{$duration} minutes");
        
        $appointments[] = [
            'appointment_id' => $row['appointment_id'],
            'service' => $row['service_name'],
            'customer' => $row['customer_fname'],
            'staff' => trim(($row['employ_fname'] ?? '') . ' ' . ($row['employ_lname'] ?? '')) ?: 'Unassigned',
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i'),
            'duration_minutes' => $duration,
            'status' => $row['appoint_status'],
            'raw_datetime' => $row['appoint_date']
        ];
    }
    
    $stmt->close();
    
    ob_start();
    echo json_encode([
        'success' => true,
        'business_id' => $businessId,
        'date' => $date,
        'appointment_count' => count($appointments),
        'appointments' => $appointments
    ], JSON_PRETTY_PRINT);
    ob_end_flush();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
