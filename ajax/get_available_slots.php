<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent any accidental output
ob_start();

// Set JSON header early
header('Content-Type: application/json');

try {
    // Include config file
    require_once __DIR__ . '/../db_connection/config.php';
    
    // Get database connection using your config function
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Failed to get database connection");
    }
    
    // Log the request
    error_log("get_available_slots.php called with: " . print_r($_GET, true));
    
    $action = $_GET['action'] ?? 'get_slots';

    if ($action === 'get_booked_dates') {
        $businessId = $_GET['business_id'] ?? '';
        $employId = $_GET['employ_id'] ?? '';
        
        if (empty($businessId)) {
            throw new Exception('Missing business_id');
        }
        
        // Get fully booked dates
        $fullyBookedDates = getFullyBookedDates($conn, intval($businessId), $employId);
        
        // Clear any output buffer
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'fully_booked_dates' => $fullyBookedDates
        ]);
        
        exit;
    }

    // Get available time slots (default action)
    $businessId = $_GET['business_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $employId = $_GET['employ_id'] ?? '';
    $serviceIds = $_GET['service_ids'] ?? '';

    if (empty($businessId)) {
        throw new Exception('Missing business_id');
    }

    if (empty($date)) {
        throw new Exception('Missing date');
    }
    
    // Validate and sanitize inputs
    $businessId = intval($businessId);
    $employId = !empty($employId) ? intval($employId) : null;
    
    // Parse service IDs
    $selectedServiceIds = [];
    if (!empty($serviceIds)) {
        $selectedServiceIds = array_map('intval', explode(',', $serviceIds));
    }

    // Get business details
    $stmt = $conn->prepare("SELECT opening_hour, closing_hour FROM businesses WHERE business_id = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $business = $result->fetch_assoc();

    if (!$business) {
        throw new Exception('Business not found with ID: ' . $businessId);
    }

    $openingHour = $business['opening_hour'];
    $closingHour = $business['closing_hour'];

    // Generate time slots
    $slots = [];
    $currentTime = strtotime($openingHour);
    $endTime = strtotime($closingHour);

    // If opening and closing are too close, provide default hours
    if (($endTime - $currentTime) < 3600) {
        error_log("Business hours too short (opening: $openingHour, closing: $closingHour), using default 9 AM to 6 PM");
        $currentTime = strtotime('09:00:00');
        $endTime = strtotime('18:00:00');
    }

    while ($currentTime < $endTime) {
        $timeStr = date('H:i', $currentTime);
        $displayTime = date('g:i A', $currentTime);
        
        // Check if this time slot is available considering service duration
        $isAvailable = isTimeSlotAvailableWithDuration($conn, $businessId, $date, $timeStr, $employId, $selectedServiceIds);
        
        $slots[] = [
            'time' => $timeStr,
            'display' => $displayTime,
            'available' => $isAvailable
        ];
        
        // Move to next 30-minute slot
        $currentTime = strtotime('+30 minutes', $currentTime);
    }

    // Clear any output buffer
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'date' => $date,
        'total_slots' => count($slots),
        'available_count' => count(array_filter($slots, function($s) { return $s['available']; })),
        'business_hours' => [
            'opening' => $openingHour,
            'closing' => $closingHour
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_available_slots.php: " . $e->getMessage());
    
    // Clear any output buffer
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'trace' => $e->getTraceAsString()
    ]);
}

// End output buffering and send
ob_end_flush();

/**
 * Check if a time slot is available considering SERVICE DURATION
 * CRITICAL: A 120-minute service starting at 2:00 PM blocks slots until 4:00 PM
 */
function isTimeSlotAvailableWithDuration($conn, $businessId, $date, $time, $employId = null, $selectedServiceIds = []) {
    try {
        $appointmentTime = $time . ':00';
        $slotDateTime = new DateTime($date . ' ' . $appointmentTime);
        
        // Get maximum service duration from selected services
        $maxDuration = 30; // Default 30 minutes
        if (!empty($selectedServiceIds)) {
            $serviceIdsStr = implode(',', $selectedServiceIds);
            $durationQuery = "SELECT MAX(duration) as max_duration FROM services WHERE service_id IN ($serviceIdsStr)";
            $durationStmt = $conn->prepare($durationQuery);
            if ($durationStmt) {
                $durationStmt->execute();
                $durationResult = $durationStmt->get_result();
                if ($row = $durationResult->fetch_assoc()) {
                    $maxDuration = intval($row['max_duration']);
                }
                $durationStmt->close();
            }
        }
        
        // Calculate the end time of the proposed appointment
        $proposedEnd = clone $slotDateTime;
        $proposedEnd->modify("+{$maxDuration} minutes");
        
        error_log("Checking slot at $time (duration: {$maxDuration}min, ends at " . $proposedEnd->format('H:i') . ")");
        
        // Get total number of available employees for this business
        $employeeQuery = "SELECT COUNT(*) as total 
                         FROM employees 
                         WHERE business_id = ? 
                         AND employ_status = 'available'";
        
        if (!empty($employId)) {
            $employeeQuery .= " AND employ_id = ?";
        }
        
        $employeeStmt = $conn->prepare($employeeQuery);
        if (!$employeeStmt) {
            error_log("Employee query prepare failed: " . $conn->error);
            return true;
        }
        
        if (!empty($employId)) {
            $employeeStmt->bind_param("ii", $businessId, $employId);
        } else {
            $employeeStmt->bind_param("i", $businessId);
        }
        
        $employeeStmt->execute();
        $employeeResult = $employeeStmt->get_result();
        $employeeRow = $employeeResult->fetch_assoc();
        $totalEmployees = max(1, intval($employeeRow['total']));
        $employeeStmt->close();
        
        // Get all appointments on this date that could conflict
        $query = "SELECT a.appointment_id, a.employ_id, a.appoint_date, s.duration
                  FROM appointments a 
                  JOIN services s ON a.service_id = s.service_id 
                  WHERE s.business_id = ? 
                  AND DATE(a.appoint_date) = ? 
                  AND a.appoint_status IN ('pending', 'confirmed')";
        
        $params = [$businessId, $date];
        $types = "is";
        
        // If specific employee is selected, only check that employee's bookings
        if (!empty($employId)) {
            $query .= " AND a.employ_id = ?";
            $params[] = $employId;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in isTimeSlotAvailableWithDuration: " . $conn->error);
            return true;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Track which employees are busy during the proposed time slot
        $busyEmployees = [];
        
        while ($row = $result->fetch_assoc()) {
            $existingStart = new DateTime($row['appoint_date']);
            $existingEnd = clone $existingStart;
            $existingEnd->modify("+{$row['duration']} minutes");
            
            // Check if the proposed appointment overlaps with this existing appointment
            // Overlap occurs if: (proposedStart < existingEnd) AND (proposedEnd > existingStart)
            $overlaps = ($slotDateTime < $existingEnd && $proposedEnd > $existingStart);
            
            if ($overlaps) {
                $employeeId = $row['employ_id'];
                if ($employeeId) {
                    $busyEmployees[$employeeId] = true;
                }
                
                error_log("  - Conflict found: Existing appointment from " . 
                         $existingStart->format('H:i') . " to " . $existingEnd->format('H:i') . 
                         " (duration: {$row['duration']}min) for employee $employeeId");
            }
        }
        $stmt->close();
        
        $busyCount = count($busyEmployees);
        
        // LOGIC:
        // - If specific employee selected: available if that employee is NOT in busyEmployees
        // - If no employee selected: available if busyCount < totalEmployees (at least one free)
        if (!empty($employId)) {
            $isAvailable = !isset($busyEmployees[$employId]);
            error_log("  → Employee $employId is " . ($isAvailable ? 'AVAILABLE' : 'BUSY'));
        } else {
            $isAvailable = ($busyCount < $totalEmployees);
            error_log("  → Busy staff: $busyCount / $totalEmployees total → " . ($isAvailable ? 'AVAILABLE' : 'FULLY BOOKED'));
        }
        
        return $isAvailable;
        
    } catch (Exception $e) {
        error_log("Error checking time slot: " . $e->getMessage());
        return true; // Default to available if error occurs
    }
}

/**
 * Get fully booked dates for the next 90 days
 * UPDATED: Considers service durations when calculating capacity
 */
function getFullyBookedDates($conn, $businessId, $employId = null) {
    try {
        $fullyBookedDates = [];
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+90 days'));
        
        // Get business hours
        $stmt = $conn->prepare("SELECT opening_hour, closing_hour FROM businesses WHERE business_id = ?");
        if (!$stmt) {
            error_log("Failed to prepare business query: " . $conn->error);
            return $fullyBookedDates;
        }
        
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $result = $stmt->get_result();
        $business = $result->fetch_assoc();
        
        if (!$business) {
            return $fullyBookedDates;
        }
        
        $openingHour = $business['opening_hour'];
        $closingHour = $business['closing_hour'];
        
        // Calculate total time slots per day (30-minute intervals)
        $openTime = strtotime($openingHour);
        $closeTime = strtotime($closingHour);
        
        // If hours are invalid, use default
        if (($closeTime - $openTime) < 3600) {
            $openTime = strtotime('09:00:00');
            $closeTime = strtotime('18:00:00');
        }
        
        $totalSlotsPerDay = ($closeTime - $openTime) / 1800; // 30-minute slots
        
        // Get total employees
        $employeeQuery = "SELECT COUNT(*) as total 
                         FROM employees 
                         WHERE business_id = ? 
                         AND employ_status = 'available'";
        
        if (!empty($employId)) {
            $employeeQuery .= " AND employ_id = ?";
        }
        
        $stmt = $conn->prepare($employeeQuery);
        if (!$stmt) {
            error_log("Failed to prepare employee query: " . $conn->error);
            return $fullyBookedDates;
        }
        
        if (!empty($employId)) {
            $stmt->bind_param("ii", $businessId, intval($employId));
        } else {
            $stmt->bind_param("i", $businessId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalEmployees = max(1, intval($row['total']));
        
        // Get appointments and calculate occupied time per date
        $query = "SELECT DATE(a.appoint_date) as date, 
                         a.appoint_date,
                         s.duration,
                         a.employ_id
                  FROM appointments a 
                  JOIN services s ON a.service_id = s.service_id 
                  WHERE s.business_id = ? 
                  AND DATE(a.appoint_date) BETWEEN ? AND ? 
                  AND a.appoint_status IN ('pending', 'confirmed')";
        
        if (!empty($employId)) {
            $query .= " AND a.employ_id = ?";
        }
        
        $query .= " ORDER BY DATE(a.appoint_date), a.appoint_date";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare bookings query: " . $conn->error);
            return $fullyBookedDates;
        }
        
        if (!empty($employId)) {
            $employIdInt = intval($employId);
            $stmt->bind_param("issi", $businessId, $startDate, $endDate, $employIdInt);
        } else {
            $stmt->bind_param("iss", $businessId, $startDate, $endDate);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Group appointments by date
        $appointmentsByDate = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['date'];
            if (!isset($appointmentsByDate[$date])) {
                $appointmentsByDate[$date] = [];
            }
            $appointmentsByDate[$date][] = $row;
        }
        
        // Check each date to see if it's fully booked
        foreach ($appointmentsByDate as $checkDate => $appointments) {
            $dayStart = new DateTime($checkDate . ' ' . $openingHour);
            $dayEnd = new DateTime($checkDate . ' ' . $closingHour);
            
            // For specific employee: check if all time slots are blocked
            if (!empty($employId)) {
                $allSlotsBlocked = true;
                $currentSlot = clone $dayStart;
                
                while ($currentSlot < $dayEnd) {
                    $slotIsBlocked = false;
                    $slotEnd = clone $currentSlot;
                    $slotEnd->modify('+30 minutes');
                    
                    foreach ($appointments as $apt) {
                        $aptStart = new DateTime($apt['appoint_date']);
                        $aptEnd = clone $aptStart;
                        $aptEnd->modify("+{$apt['duration']} minutes");
                        
                        // Check if this appointment blocks this slot
                        if ($currentSlot < $aptEnd && $slotEnd > $aptStart) {
                            $slotIsBlocked = true;
                            break;
                        }
                    }
                    
                    if (!$slotIsBlocked) {
                        $allSlotsBlocked = false;
                        break;
                    }
                    
                    $currentSlot->modify('+30 minutes');
                }
                
                if ($allSlotsBlocked) {
                    $fullyBookedDates[] = $checkDate;
                    error_log("Date $checkDate is FULLY BOOKED for employee $employId");
                }
            } 
            // For all employees: check if all employees are busy for every time slot
            else {
                $allSlotsBlocked = true;
                $currentSlot = clone $dayStart;
                
                while ($currentSlot < $dayEnd) {
                    $slotEnd = clone $currentSlot;
                    $slotEnd->modify('+30 minutes');
                    
                    // Count how many employees are busy during this slot
                    $busyEmployees = [];
                    foreach ($appointments as $apt) {
                        $aptStart = new DateTime($apt['appoint_date']);
                        $aptEnd = clone $aptStart;
                        $aptEnd->modify("+{$apt['duration']} minutes");
                        
                        if ($currentSlot < $aptEnd && $slotEnd > $aptStart) {
                            $busyEmployees[$apt['employ_id']] = true;
                        }
                    }
                    
                    $busyCount = count($busyEmployees);
                    if ($busyCount < $totalEmployees) {
                        // At least one employee is free for this slot
                        $allSlotsBlocked = false;
                        break;
                    }
                    
                    $currentSlot->modify('+30 minutes');
                }
                
                if ($allSlotsBlocked) {
                    $fullyBookedDates[] = $checkDate;
                    error_log("Date $checkDate is FULLY BOOKED: All $totalEmployees employees busy for all slots");
                }
            }
        }
        
        error_log("Found " . count($fullyBookedDates) . " fully booked dates");
        return $fullyBookedDates;
        
    } catch (Exception $e) {
        error_log("Error getting booked dates: " . $e->getMessage());
        return [];
    }
}
?>