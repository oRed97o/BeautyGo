<?php
// ============================================================
// APPOINTMENT FUNCTIONS
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
        WHERE e.business_id = ?
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
?>