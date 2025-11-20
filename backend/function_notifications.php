<?php
// ============================================================
// NOTIFICATION FUNCTIONS
// ============================================================

// Get customer notifications
function getCustomerNotifications($customerId, $limit = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT * FROM notifications 
            WHERE customer_id = ? 
            AND notif_title NOT LIKE '%New Booking%' 
            AND notif_title NOT LIKE '%Cancelled by Customer%'
            ORDER BY notif_creation DESC";
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($limit) {
        $stmt->bind_param("ii", $customerId, $limit);
    } else {
        $stmt->bind_param("i", $customerId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Count unread notifications (last 24 hours)
function countUnreadNotifications($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE customer_id = ? 
        AND notif_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND notif_title NOT LIKE '%New Booking%'
        AND notif_title NOT LIKE '%Cancelled by Customer%'
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Create notification for appointment status change
function createAppointmentNotification($customerId, $businessId, $appointmentId, $status) {
    $conn = getDbConnection();
    
    $appointment = getAppointmentById($appointmentId);
    if (!$appointment) return false;
    
    $titles = [
        'confirmed' => 'Appointment Confirmed',
        'cancelled' => 'Appointment Cancelled',
        'completed' => 'Appointment Completed'
    ];
    
    $texts = [
        'confirmed' => 'Your appointment has been confirmed! We look forward to seeing you.',
        'cancelled' => 'Your appointment has been cancelled. Please contact us if you have any questions.',
        'completed' => 'Thank you for visiting us! We hope you enjoyed your service.'
    ];
    
    $title = $titles[$status] ?? 'Appointment Update';
    $text = $texts[$status] ?? 'Your appointment status has been updated.';
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get business notifications
function getBusinessNotifications($businessId, $limit = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT n.*, c.fname, c.surname 
            FROM notifications n
            LEFT JOIN customers c ON n.customer_id = c.customer_id
            WHERE n.business_id = ? 
            ORDER BY n.notif_creation DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($limit) {
        $stmt->bind_param("ii", $businessId, $limit);
    } else {
        $stmt->bind_param("i", $businessId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Count recent business notifications (last 24 hours)
function countRecentBusinessNotifications($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE business_id = ? 
        AND notif_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND notif_title LIKE '%New Booking%'
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Create notification when customer books appointment
function createBusinessBookingNotification($businessId, $customerId, $appointmentId) {
    $conn = getDbConnection();
    
    $customer = getCustomerById($customerId);
    $appointment = getAppointmentById($appointmentId);
    
    if (!$customer || !$appointment) return false;
    
    $customerName = trim($customer['fname'] . ' ' . ($customer['surname'] ?? ''));
    $appointDate = date('F j, Y g:i A', strtotime($appointment['appoint_date']));
    
    $title = 'New Booking Request';
    $text = "{$customerName} has booked an appointment for {$appointDate}. Please review and confirm.";
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Create notification when customer cancels appointment
function createBusinessCancellationNotification($businessId, $customerId, $appointmentId) {
    $conn = getDbConnection();
    
    $customer = getCustomerById($customerId);
    $appointment = getAppointmentById($appointmentId);
    
    if (!$customer || !$appointment) return false;
    
    $customerName = trim($customer['fname'] . ' ' . ($customer['surname'] ?? ''));
    $appointDate = date('F j, Y g:i A', strtotime($appointment['appoint_date']));
    
    $title = 'Booking Cancelled by Customer';
    $text = "{$customerName} has cancelled their appointment scheduled for {$appointDate}.";
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Time ago function for notifications
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>