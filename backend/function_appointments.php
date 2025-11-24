<?php
// ============================================================
// APPOINTMENT FUNCTIONS - UPDATED WITH BUSINESS HOURS
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

// Create a new appointment
function createAppointment($data) {
    $conn = getDbConnection();

    $customer = getCustomerById($data['customer_id']);
    if (!$customer) {
        error_log("Invalid customer_id: " . $data['customer_id']);
        return false;
    }
    
    $employeeId = null;
    $businessId = null;
    
    if (!empty($data['employ_id'])) {
        $employee = getEmployeeById($data['employ_id']);
        if (!$employee) {
            error_log("Invalid employ_id: " . $data['employ_id']);
            return false;
        }
        $employeeId = $data['employ_id'];
        $businessId = $employee['business_id'];
    }

    if (!empty($data['service_id'])) {
        $service = getServiceById($data['service_id']);
        if (!$service) {
            error_log("Invalid service_id: " . $data['service_id']);
            return false;
        }
        if (!$businessId) {
            $businessId = $service['business_id'];
        }
    }

    $appointDate = $data['appoint_date'] 
        ?? (($data['booking_date'] ?? '') . ' ' . ($data['booking_time'] ?? ''));

    // CHECK AVAILABILITY BEFORE CREATING
    if (!isTimeSlotAvailable($data['service_id'], $employeeId, $appointDate)) {
        error_log("Time slot not available: " . $appointDate);
        return ['error' => 'time_slot_unavailable'];
    }

    $customerId = $data['customer_id'];
    $serviceId = !empty($data['service_id']) ? $data['service_id'] : null;
    $status = $data['appoint_status'] ?? 'pending';
    $notes = $data['appoint_desc'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO appointments 
        (customer_id, employ_id, service_id, appoint_date, appoint_status, appoint_desc)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisss",
        $customerId,
        $employeeId,
        $serviceId,
        $appointDate,
        $status,
        $notes
    );

    if ($stmt->execute()) {
        $appointmentId = $conn->insert_id;
        $stmt->close();
        
        if ($businessId) {
            createBusinessBookingNotification($businessId, $customerId, $appointmentId);
        }
        
        return $appointmentId;
    }

    error_log("Appointment creation failed: " . $stmt->error);
    $stmt->close();
    return false;
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
    
    $employee = getEmployeeById($appointment['employ_id']);
    $businessId = $employee ? $employee['business_id'] : null;
    
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

function isTimeSlotAvailable($serviceId, $employId, $appointDate, $excludeAppointmentId = null) {
    $conn = getDbConnection();
    
    // Get service duration and business hours
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
    
    // Check for conflicting appointments
    if ($employId) {
        // Check specific staff member
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
    } else {
        // Check ALL staff for this business (for "Any Available" bookings)
        $sql = "SELECT a.appointment_id, a.appoint_date, s.duration, a.employ_id
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE s.business_id = ?
                AND a.appoint_status IN ('confirmed', 'pending')
                AND DATE(a.appoint_date) = DATE(?)";
        
        if ($excludeAppointmentId) {
            $sql .= " AND a.appointment_id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        if ($excludeAppointmentId) {
            $stmt->bind_param("isi", $businessId, $appointDate, $excludeAppointmentId);
        } else {
            $stmt->bind_param("is", $businessId, $appointDate);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check each existing appointment for time conflicts
    while ($row = $result->fetch_assoc()) {
        $existingStart = new DateTime($row['appoint_date']);
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$row['duration']} minutes");
        
        // Check if times overlap
        if ($newStart < $existingEnd && $newEnd > $existingStart) {
            $stmt->close();
            return false; // Conflict found
        }
    }
    
    $stmt->close();
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
    
    // Find conflicting appointments
    if ($appointment['employ_id']) {
        // Check same staff member
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
        // Check all staff in the business
        $sql = "SELECT a.appointment_id, a.appoint_date, s.duration, a.customer_id
                FROM appointments a
                JOIN services s ON a.service_id = s.service_id
                WHERE s.business_id = ?
                AND a.appointment_id != ?
                AND a.appoint_status = 'pending'
                AND DATE(a.appoint_date) = DATE(?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $service['business_id'], $appointmentId, $appointment['appoint_date']);
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
    // Use your shared DB connection
    $conn = getDbConnection();

    // Current date/time
    $now = date("Y-m-d H:i:s");

    $sql = "SELECT COUNT(*) AS total 
            FROM appointments
            WHERE customer_id = ?
              AND appoint_status IN ('pending', 'confirmed')
              AND appoint_date >= ?";

    // Prepare statement
    if (!$stmt = $conn->prepare($sql)) {
        return 0; // Safety return if prepare fails
    }

    // Bind parameters: i = integer, s = string
    $stmt->bind_param("is", $customer_id, $now);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }

    return 0; // Default fallback
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