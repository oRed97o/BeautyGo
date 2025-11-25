<?php
// ============================================================
// APPOINTMENT FUNCTIONS - UPDATED WITH STAFF-DEPENDENT AVAILABILITY
// ============================================================

// Get appointments for a specific customer
function getCustomerAppointments($customerId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            a.*, 
            e.employ_fname AS staff_fname, 
            e.employ_lname AS staff_lname, 
            e.specialization, 
            s.service_name, 
            s.cost, 
            s.duration, 
            b.business_name, 
            b.business_address,
            b.business_id
        FROM appointments a
        LEFT JOIN employees e ON a.employ_id = e.employ_id
        LEFT JOIN services s ON a.service_id = s.service_id
        LEFT JOIN businesses b ON s.business_id = b.business_id
        WHERE a.customer_id = ?
        ORDER BY a.appoint_date DESC
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get appointments for a business
function getBusinessAppointments($businessId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            a.*, 
            c.fname AS customer_fname, 
            c.surname AS customer_lname,
            c.cstmr_num AS customer_phone, 
            s.service_name, 
            s.cost, 
            s.duration, 
            e.employ_fname AS staff_fname, 
            e.employ_lname AS staff_lname
        FROM appointments a
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        LEFT JOIN employees e ON a.employ_id = e.employ_id
        LEFT JOIN services s ON a.service_id = s.service_id
        WHERE s.business_id = ?
        ORDER BY a.appoint_date DESC
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get appointment by ID
function getAppointmentById($appointmentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

// UPDATED: Create appointment with staff-dependent availability checking
function createAppointment($data) {
    $conn = getDbConnection();
    
    // Validate customer
    $customer = getCustomerById($data['customer_id']);
    if (!$customer) {
        error_log("Invalid customer_id: " . $data['customer_id']);
        return false;
    }
    
    // Validate and get service
    if (empty($data['service_id'])) {
        error_log("Missing service_id");
        return false;
    }
    
    $service = getServiceById($data['service_id']);
    if (!$service) {
        error_log("Invalid service_id: " . $data['service_id']);
        return false;
    }
    
    $businessId = $service['business_id'];
    $employeeId = null;
    $isSpecificStaff = false;
    
    // Check if specific employee was selected
    if (!empty($data['employ_id'])) {
        $employee = getEmployeeById($data['employ_id']);
        if (!$employee) {
            error_log("Invalid employ_id: " . $data['employ_id']);
            return false;
        }
        $employeeId = $data['employ_id'];
        $isSpecificStaff = true;
    }
    
    $appointDate = $data['appoint_date'] 
        ?? (($data['booking_date'] ?? '') . ' ' . ($data['booking_time'] ?? ''));
    
    $customerId = $data['customer_id'];
    $serviceId = $data['service_id'];
    $status = $data['appoint_status'] ?? 'pending';
    $notes = $data['appoint_desc'] ?? '';
    
    // Extract date and time for checking
    $dateTime = new DateTime($appointDate);
    $date = $dateTime->format('Y-m-d');
    $time = $dateTime->format('H:i:s');
    
    // START TRANSACTION WITH ROW LOCKING
    $conn->begin_transaction();
    
    try {
        // CASE 1: SPECIFIC STAFF SELECTED
        if ($isSpecificStaff) {
            error_log("Checking availability for SPECIFIC staff: Employee ID $employeeId at $appointDate");
            
            // Check if THIS SPECIFIC staff member is already booked at this exact time
            $checkQuery = "SELECT appointment_id, customer_id
                          FROM appointments a
                          JOIN services s ON a.service_id = s.service_id
                          WHERE a.employ_id = ?
                          AND DATE(a.appoint_date) = ?
                          AND TIME(a.appoint_date) = ?
                          AND a.appoint_status IN ('pending', 'confirmed')
                          FOR UPDATE"; // Row lock prevents concurrent bookings
            
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("iss", $employeeId, $date, $time);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $existingBooking = $checkResult->fetch_assoc();
                $checkStmt->close();
                error_log("BLOCKED: Staff member $employeeId already booked at $appointDate by customer " . $existingBooking['customer_id']);
                throw new Exception("staff_unavailable");
            }
            $checkStmt->close();
            
            error_log("SUCCESS: Staff member $employeeId is available at $appointDate");
            
        } 
        // CASE 2: "ANY AVAILABLE" STAFF SELECTED
        else {
            error_log("Checking availability for ANY available staff at $appointDate");
            
            // Get all available employees for this business with lock
            $employeeQuery = "SELECT employ_id
                             FROM employees 
                             WHERE business_id = ? 
                             AND employ_status = 'available'
                             FOR UPDATE";
            
            $empStmt = $conn->prepare($employeeQuery);
            $empStmt->bind_param("i", $businessId);
            $empStmt->execute();
            $empResult = $empStmt->get_result();
            $allEmployees = $empResult->fetch_all(MYSQLI_ASSOC);
            $totalEmployees = count($allEmployees);
            $empStmt->close();
            
            if ($totalEmployees == 0) {
                error_log("BLOCKED: No available employees found for business $businessId");
                throw new Exception("no_employees_available");
            }
            
            error_log("Found $totalEmployees available staff members for business $businessId");
            
            // Count how many staff are ALREADY booked at this time
            $checkQuery = "SELECT COUNT(DISTINCT a.employ_id) as booked_staff_count
                          FROM appointments a
                          JOIN services s ON a.service_id = s.service_id
                          WHERE s.business_id = ?
                          AND DATE(a.appoint_date) = ?
                          AND TIME(a.appoint_date) = ?
                          AND a.appoint_status IN ('pending', 'confirmed')
                          AND a.employ_id IS NOT NULL
                          FOR UPDATE"; // Row lock
            
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("iss", $businessId, $date, $time);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkRow = $checkResult->fetch_assoc();
            $bookedStaffCount = intval($checkRow['booked_staff_count']);
            $checkStmt->close();
            
            error_log("Booked staff at this time: $bookedStaffCount out of $totalEmployees total");
            
            // If ALL staff are booked, time slot is unavailable
            if ($bookedStaffCount >= $totalEmployees) {
                error_log("BLOCKED: All $totalEmployees staff members are booked at $appointDate");
                throw new Exception("all_staff_booked");
            }
            
            // Find a staff member who is NOT booked at this time
            $findEmployeeQuery = "SELECT e.employ_id, e.employ_fname, e.employ_lname
                                 FROM employees e
                                 WHERE e.business_id = ?
                                 AND e.employ_status = 'available'
                                 AND e.employ_id NOT IN (
                                     SELECT a.employ_id 
                                     FROM appointments a
                                     JOIN services s ON a.service_id = s.service_id
                                     WHERE s.business_id = ?
                                     AND DATE(a.appoint_date) = ?
                                     AND TIME(a.appoint_date) = ?
                                     AND a.appoint_status IN ('pending', 'confirmed')
                                     AND a.employ_id IS NOT NULL
                                 )
                                 LIMIT 1";
            
            $findEmpStmt = $conn->prepare($findEmployeeQuery);
            $findEmpStmt->bind_param("iiss", $businessId, $businessId, $date, $time);
            $findEmpStmt->execute();
            $findEmpResult = $findEmpStmt->get_result();
            
            if ($findEmpResult->num_rows > 0) {
                $availableEmp = $findEmpResult->fetch_assoc();
                $employeeId = $availableEmp['employ_id'];
                $staffName = $availableEmp['employ_fname'] . ' ' . $availableEmp['employ_lname'];
                error_log("SUCCESS: Assigned to available staff member: $staffName (ID: $employeeId)");
            } else {
                error_log("WARNING: Could not find available staff. Will leave employ_id as NULL for business to assign.");
                // Leave $employeeId as NULL - business owner can assign later
            }
            $findEmpStmt->close();
        }
        
        // INSERT THE APPOINTMENT
        $insertStmt = $conn->prepare("
            INSERT INTO appointments 
            (customer_id, employ_id, service_id, appoint_date, appoint_status, appoint_desc)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param("iiisss",
            $customerId,
            $employeeId,
            $serviceId,
            $appointDate,
            $status,
            $notes
        );
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert appointment: " . $insertStmt->error);
        }
        
        $appointmentId = $conn->insert_id;
        $insertStmt->close();
        
        // COMMIT TRANSACTION
        $conn->commit();
        
        $staffInfo = $employeeId ? "Staff ID: $employeeId" : "Staff: TBD";
        error_log("✓ Appointment created successfully: ID=$appointmentId, Customer=$customerId, Service=$serviceId, $staffInfo, DateTime=$appointDate");
        
        // Send notification after successful commit
        if ($businessId) {
            createBusinessBookingNotification($businessId, $customerId, $appointmentId);
        }
        
        return $appointmentId;
        
    } catch (Exception $e) {
        // ROLLBACK ON ERROR
        $conn->rollback();
        
        error_log("✗ Appointment creation failed: " . $e->getMessage());
        
        // Return specific error messages based on scenario
        if ($e->getMessage() === "staff_unavailable") {
            return [
                'error' => 'staff_unavailable', 
                'message' => 'The selected staff member is already booked at this time. Please select another time or staff member.'
            ];
        }
        
        if ($e->getMessage() === "all_staff_booked") {
            return [
                'error' => 'all_staff_booked', 
                'message' => 'All staff members are booked at this time. Please select another time slot.'
            ];
        }
        
        if ($e->getMessage() === "no_employees_available") {
            return [
                'error' => 'no_employees_available', 
                'message' => 'No staff members are currently available. Please contact the business.'
            ];
        }
        
        return false;
    }
}

// Update appointment status
function updateAppointmentStatus($id, $status) {
    $conn = getDbConnection();
    
    // If confirming an appointment, mark conflicting ones as unavailable
    if ($status === 'confirmed') {
        markConflictingAppointments($id);
    }
    
    $stmt = $conn->prepare("UPDATE appointments SET appoint_status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Delete appointment
function deleteAppointment($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $id); 
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Cancel appointment with business notification
function cancelAppointment($appointmentId) {
    $conn = getDbConnection();
    
    $appointment = getAppointmentById($appointmentId);
    if (!$appointment) {
        return false;
    }
    
    $service = getServiceById($appointment['service_id']);
    $businessId = $service ? $service['business_id'] : null;
    
    $stmt = $conn->prepare("UPDATE appointments SET appoint_status = 'cancelled' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointmentId);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        if ($businessId) {
            createBusinessCancellationNotification($businessId, $appointment['customer_id'], $appointmentId);
        }
        
        createAppointmentNotification(
            $appointment['customer_id'],
            $businessId,
            $appointmentId,
            'cancelled'
        );
        
        return true;
    }
    
    $stmt->close();
    return false;
}

// UPDATED: Check if time slot is available for specific staff OR any staff
function isTimeSlotAvailable($serviceId, $employId, $appointDate, $excludeAppointmentId = null) {
    $conn = getDbConnection();
    
    // Get service duration and business info
    $service = getServiceById($serviceId);
    if (!$service) return false;
    
    $duration = $service['duration'];
    $businessId = $service['business_id'];
    
    // Get business hours
    $business = getBusinessById($businessId);
    if (!$business) return false;
    
    $openingHour = $business['opening_hour'] ?? '09:00:00';
    $closingHour = $business['closing_hour'] ?? '18:00:00';
    
    // Calculate time range for the new appointment
    $newStart = new DateTime($appointDate);
    $newEnd = clone $newStart;
    $newEnd->modify("+{$duration} minutes");
    
    // Check if appointment is within business hours
    $appointmentDate = $newStart->format('Y-m-d');
    $businessOpen = new DateTime($appointmentDate . ' ' . $openingHour);
    $businessClose = new DateTime($appointmentDate . ' ' . $closingHour);
    
    if ($newStart < $businessOpen || $newEnd > $businessClose) {
        return false; // Outside business hours
    }
    
    // CASE 1: Check specific staff member's availability
    if ($employId) {
        $sql = "SELECT a.appointment_id, a.appoint_date, s.duration
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE a.employ_id = ?
                AND a.appoint_status IN ('confirmed', 'pending')
                AND DATE(a.appoint_date) = DATE(?)";
        
        if ($excludeAppointmentId) {
            $sql .= " AND a.appointment_id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        if ($excludeAppointmentId) {
            $stmt->bind_param("isi", $employId, $appointDate, $excludeAppointmentId);
        } else {
            $stmt->bind_param("is", $employId, $appointDate);
        }
    } 
    // CASE 2: Check if ANY staff member is available (for "Any Available" option)
    else {
        // Count how many staff members are available
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE business_id = ? AND employ_status = 'available'");
        $countStmt->bind_param("i", $businessId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $totalStaff = intval($countRow['total']);
        $countStmt->close();
        
        if ($totalStaff == 0) {
            return false; // No staff available
        }
        
        // Count how many are already booked at this exact time
        $sql = "SELECT COUNT(DISTINCT a.employ_id) as booked_count
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE s.business_id = ?
                AND DATE(a.appoint_date) = DATE(?)
                AND TIME(a.appoint_date) = TIME(?)
                AND a.appoint_status IN ('confirmed', 'pending')
                AND a.employ_id IS NOT NULL";
        
        if ($excludeAppointmentId) {
            $sql .= " AND a.appointment_id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        if ($excludeAppointmentId) {
            $stmt->bind_param("issi", $businessId, $appointDate, $appointDate, $excludeAppointmentId);
        } else {
            $stmt->bind_param("iss", $businessId, $appointDate, $appointDate);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $bookedStaffCount = intval($row['booked_count']);
        $stmt->close();
        
        // Available if at least one staff member is free
        return ($bookedStaffCount < $totalStaff);
    }
    
    // For specific staff, check for time conflicts
    if ($employId) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $existingStart = new DateTime($row['appoint_date']);
            $existingEnd = clone $existingStart;
            $existingEnd->modify("+{$row['duration']} minutes");
            
            // Check if times overlap
            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                $stmt->close();
                return false; // Conflict found for this specific staff
            }
        }
        
        $stmt->close();
    }
    
    return true; // No conflicts
}

// Get unavailable time slots for a specific date and service
function getUnavailableTimeSlots($businessId, $date, $employId = null) {
    $conn = getDbConnection();
    
    if ($employId) {
        // Get unavailable slots for specific staff
        $sql = "SELECT a.appoint_date, s.duration
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE a.employ_id = ?
                AND a.appoint_status IN ('confirmed', 'pending')
                AND DATE(a.appoint_date) = ?
                ORDER BY a.appoint_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $employId, $date);
    } else {
        // Get unavailable slots for all staff in the business
        $sql = "SELECT a.appoint_date, s.duration, a.employ_id
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                JOIN employees e ON a.employ_id = e.employ_id
                WHERE e.business_id = ?
                AND a.appoint_status IN ('confirmed', 'pending')
                AND DATE(a.appoint_date) = ?
                ORDER BY a.appoint_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $businessId, $date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $unavailableSlots = [];
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['appoint_date']);
        $end = clone $start;
        $end->modify("+{$row['duration']} minutes");
        
        $unavailableSlots[] = [
            'start' => $start->format('H:i:s'),
            'end' => $end->format('H:i:s'),
            'employ_id' => $row['employ_id'] ?? null
        ];
    }
    
    $stmt->close();
    return $unavailableSlots;
}

// Mark conflicting appointments as unavailable when one is confirmed
function markConflictingAppointments($appointmentId) {
    $conn = getDbConnection();
    
    // Get the confirmed appointment details
    $appointment = getAppointmentById($appointmentId);
    if (!$appointment) return false;
    
    $service = getServiceById($appointment['service_id']);
    if (!$service) return false;
    
    $duration = $service['duration'];
    
    // Calculate time range
    $confirmedStart = new DateTime($appointment['appoint_date']);
    $confirmedEnd = clone $confirmedStart;
    $confirmedEnd->modify("+{$duration} minutes");
    
    // Find conflicting appointments for THE SAME STAFF MEMBER
    if ($appointment['employ_id']) {
        $sql = "SELECT a.appointment_id, a.appoint_date, s.duration, a.customer_id
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE a.employ_id = ?
                AND a.appointment_id != ?
                AND a.appoint_status = 'pending'
                AND DATE(a.appoint_date) = DATE(?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $appointment['employ_id'], $appointmentId, $appointment['appoint_date']);
    } else {
        // If no specific staff, don't mark conflicts (let business assign)
        return 0;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conflictingIds = [];
    
    while ($row = $result->fetch_assoc()) {
        $existingStart = new DateTime($row['appoint_date']);
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$row['duration']} minutes");
        
        // Check if times overlap
        if ($confirmedStart < $existingEnd && $confirmedEnd > $existingStart) {
            $conflictingIds[] = [
                'appointment_id' => $row['appointment_id'],
                'customer_id' => $row['customer_id']
            ];
        }
    }
    
    $stmt->close();
    
    // Update conflicting appointments
    if (!empty($conflictingIds)) {
        $updateStmt = $conn->prepare("UPDATE appointments SET appoint_status = 'unavailable' WHERE appointment_id = ?");
        
        foreach ($conflictingIds as $conflict) {
            $updateStmt->bind_param("i", $conflict['appointment_id']);
            $updateStmt->execute();
            
            // Notify customers about unavailability
            createAppointmentNotification(
                $conflict['customer_id'],
                $service['business_id'],
                $conflict['appointment_id'],
                'unavailable'
            );
        }
        
        $updateStmt->close();
    }
    
    return count($conflictingIds);
}

function getUpcomingBookingsCount($customer_id) {
    $conn = getDbConnection();
    $now = date("Y-m-d H:i:s");

    $sql = "SELECT COUNT(*) AS total 
            FROM appointments
            WHERE customer_id = ?
              AND appoint_status IN ('pending', 'confirmed')
              AND appoint_date >= ?";

    if (!$stmt = $conn->prepare($sql)) {
        return 0;
    }

    $stmt->bind_param("is", $customer_id, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }

    return 0;
}

// UPDATED: Get available time slots using business hours
function getAvailableTimeSlotsForBooking($businessId, $date, $serviceIds = [], $employId = null) {
    $conn = getDbConnection();
    
    // Get business hours from database
    $business = getBusinessById($businessId);
    if (!$business) {
        return [];
    }
    
    // Use business hours or default to 9 AM - 6 PM
    $openingHour = !empty($business['opening_hour']) ? $business['opening_hour'] : '09:00:00';
    $closingHour = !empty($business['closing_hour']) ? $business['closing_hour'] : '18:00:00';
    
    // Generate all possible time slots based on business hours
    $allSlots = [];
    $currentTime = new DateTime($date . ' ' . $openingHour);
    $endTime = new DateTime($date . ' ' . $closingHour);
    
    // Generate hourly slots from opening to closing
    while ($currentTime < $endTime) {
        $slotTime = $currentTime->format('H:i');
        $allSlots[] = [
            'time' => $slotTime,
            'display' => $currentTime->format('g:i A'),
            'available' => true
        ];
        $currentTime->modify('+1 hour');
    }
    
    // Check availability for each slot
    foreach ($allSlots as &$slot) {
        $slotDateTime = $date . ' ' . $slot['time'] . ':00';
        
        if (empty($serviceIds)) {
            // No services selected - check general availability
            $unavailableSlots = getUnavailableTimeSlots($businessId, $date, $employId);
            $slotTime = new DateTime($slotDateTime);
            $slot['available'] = true;
            
            foreach ($unavailableSlots as $bookedSlot) {
                $bookedStart = new DateTime($date . ' ' . $bookedSlot['start']);
                $bookedEnd = new DateTime($date . ' ' . $bookedSlot['end']);
                
                if ($slotTime >= $bookedStart && $slotTime < $bookedEnd) {
                    $slot['available'] = false;
                    break;
                }
            }
        } else {
            // Check if ANY selected service can be booked at this time
            $anyServiceAvailable = false;
            foreach ($serviceIds as $serviceId) {
                if (isTimeSlotAvailable($serviceId, $employId, $slotDateTime)) {
                    $anyServiceAvailable = true;
                    break;
                }
            }
            $slot['available'] = $anyServiceAvailable;
        }
    }
    
    return $allSlots;
}

?>