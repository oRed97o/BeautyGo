<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db_connection/config.php';
require_once '../backend/function_appointments.php';
require_once '../backend/function_services.php';
require_once '../backend/function_businesses.php';

header('Content-Type: application/json');

try {
    // Get fully booked dates for a business
    if (isset($_GET['action']) && $_GET['action'] === 'get_booked_dates') {
        $businessId = intval($_GET['business_id']);
        $employId = isset($_GET['employ_id']) && !empty($_GET['employ_id']) ? intval($_GET['employ_id']) : null;
        
        $conn = getDbConnection();
        
        // Get all confirmed/pending appointments for the next 3 months
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+3 months'));
        
        if ($employId) {
            // Get bookings for specific staff
            $sql = "SELECT DATE(a.appoint_date) as booking_date, 
                           TIME(a.appoint_date) as booking_time,
                           s.duration
                    FROM appointments a
                    JOIN services s ON a.service_id = s.service_id
                    WHERE a.employ_id = ?
                    AND a.appoint_status IN ('confirmed', 'pending')
                    AND DATE(a.appoint_date) BETWEEN ? AND ?
                    ORDER BY a.appoint_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $employId, $startDate, $endDate);
        } else {
            // Get bookings for all staff in business
            $sql = "SELECT DATE(a.appoint_date) as booking_date, 
                           TIME(a.appoint_date) as booking_time,
                           s.duration,
                           a.employ_id
                    FROM appointments a
                    JOIN services s ON a.service_id = s.service_id
                    WHERE s.business_id = ?
                    AND a.appoint_status IN ('confirmed', 'pending')
                    AND DATE(a.appoint_date) BETWEEN ? AND ?
                    ORDER BY a.appoint_date";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $businessId, $startDate, $endDate);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookingsByDate = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['booking_date'];
            if (!isset($bookingsByDate[$date])) {
                $bookingsByDate[$date] = [];
            }
            $bookingsByDate[$date][] = [
                'time' => $row['booking_time'],
                'duration' => $row['duration'],
                'employ_id' => $row['employ_id'] ?? null
            ];
        }
        
        $stmt->close();
        
        // Determine which dates are fully booked
        $fullyBookedDates = [];
        $businessHours = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00'];
        
        foreach ($bookingsByDate as $date => $bookings) {
            $bookedSlots = count($bookings);
            $totalSlots = count($businessHours);
            
            // If all time slots are booked, mark date as fully booked
            if ($bookedSlots >= $totalSlots) {
                $fullyBookedDates[] = $date;
            }
        }
        
        echo json_encode([
            'success' => true,
            'fully_booked_dates' => $fullyBookedDates,
            'bookings_by_date' => $bookingsByDate
        ]);
        exit;
    }

    // Get available time slots for a specific date
    if (!isset($_GET['business_id']) || !isset($_GET['date'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing parameters',
            'params' => $_GET
        ]);
        exit;
    }

    $businessId = intval($_GET['business_id']);
    $date = $_GET['date'];
    $employId = isset($_GET['employ_id']) && !empty($_GET['employ_id']) ? intval($_GET['employ_id']) : null;
    $serviceIds = isset($_GET['service_ids']) ? explode(',', $_GET['service_ids']) : [];

    // Remove empty values from serviceIds array
    $serviceIds = array_filter($serviceIds);

    // Business hours
    $businessHours = [
        '09:00' => '9:00 AM',
        '10:00' => '10:00 AM',
        '11:00' => '11:00 AM',
        '12:00' => '12:00 PM',
        '13:00' => '1:00 PM',
        '14:00' => '2:00 PM',
        '15:00' => '3:00 PM',
        '16:00' => '4:00 PM',
        '17:00' => '5:00 PM',
        '18:00' => '6:00 PM'
    ];

    $allSlots = [];

    foreach ($businessHours as $time => $display) {
        $slotDateTime = $date . ' ' . $time . ':00';
        
        // Check availability
        $available = true;
        
        if (!empty($serviceIds)) {
            // Check if ANY of the selected services can be booked at this time
            $anyServiceAvailable = false;
            
            foreach ($serviceIds as $serviceId) {
                // Verify the function exists
                if (function_exists('isTimeSlotAvailable')) {
                    if (isTimeSlotAvailable($serviceId, $employId, $slotDateTime)) {
                        $anyServiceAvailable = true;
                        break;
                    }
                } else {
                    // Fallback: manual availability check
                    $conn = getDbConnection();
                    
                    if ($employId) {
                        $checkSql = "SELECT COUNT(*) as count 
                                    FROM appointments a 
                                    WHERE a.employ_id = ? 
                                    AND a.appoint_date = ?
                                    AND a.appoint_status IN ('confirmed', 'pending')";
                        $checkStmt = $conn->prepare($checkSql);
                        $checkStmt->bind_param("is", $employId, $slotDateTime);
                    } else {
                        $checkSql = "SELECT COUNT(*) as count 
                                    FROM appointments a 
                                    JOIN services s ON a.service_id = s.service_id
                                    WHERE s.business_id = ? 
                                    AND a.appoint_date = ?
                                    AND a.appoint_status IN ('confirmed', 'pending')";
                        $checkStmt = $conn->prepare($checkSql);
                        $checkStmt->bind_param("is", $businessId, $slotDateTime);
                    }
                    
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $checkRow = $checkResult->fetch_assoc();
                    $checkStmt->close();
                    
                    if ($checkRow['count'] == 0) {
                        $anyServiceAvailable = true;
                        break;
                    }
                }
            }
            
            $available = $anyServiceAvailable;
        } else {
            // No services selected - check general availability using the function
            if (function_exists('getUnavailableTimeSlots')) {
                $unavailableSlots = getUnavailableTimeSlots($businessId, $date, $employId);
                
                $slotTime = new DateTime($slotDateTime);
                $available = true;
                
                // Check if this slot conflicts with any booked slots
                foreach ($unavailableSlots as $bookedSlot) {
                    $bookedStart = new DateTime($date . ' ' . $bookedSlot['start']);
                    $bookedEnd = new DateTime($date . ' ' . $bookedSlot['end']);
                    
                    // If the slot falls within a booked period, mark as unavailable
                    if ($slotTime >= $bookedStart && $slotTime < $bookedEnd) {
                        $available = false;
                        break;
                    }
                }
            } else {
                // Fallback: simple exact time check
                $conn = getDbConnection();
                
                if ($employId) {
                    $sql = "SELECT COUNT(*) as count 
                            FROM appointments a 
                            WHERE a.employ_id = ? 
                            AND a.appoint_date = ?
                            AND a.appoint_status IN ('confirmed', 'pending')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $employId, $slotDateTime);
                } else {
                    $sql = "SELECT COUNT(*) as count 
                            FROM appointments a 
                            JOIN services s ON a.service_id = s.service_id
                            WHERE s.business_id = ? 
                            AND a.appoint_date = ?
                            AND a.appoint_status IN ('confirmed', 'pending')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $businessId, $slotDateTime);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                // If there are bookings, consider it unavailable
                $available = ($row['count'] == 0);
            }
        }
        
        $allSlots[] = [
            'time' => $time,
            'display' => $display,
            'available' => $available
        ];
    }

    echo json_encode([
        'success' => true,
        'slots' => $allSlots,
        'date' => $date,
        'debug' => [
            'businessId' => $businessId,
            'date' => $date,
            'employId' => $employId,
            'serviceIds' => $serviceIds,
            'serviceCount' => count($serviceIds)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>