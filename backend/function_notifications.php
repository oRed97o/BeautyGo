<?php
// ============================================================
// NOTIFICATION FUNCTIONS
// ============================================================

require_once __DIR__ . '/function_customers.php';
require_once __DIR__ . '/function_businesses.php';
require_once __DIR__ . '/function_appointments.php';


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

// Get customer notifications WITH booking details (for enhanced display with service, date, time, business)
function getCustomerNotificationsWithBooking($customerId, $limit = null) {
    $conn = getDbConnection();
    
    // Check if appointment_id column exists first
    $checkColumn = $conn->query("SHOW COLUMNS FROM notifications LIKE 'appointment_id'");
    $columnExists = $checkColumn && $checkColumn->num_rows > 0;
    
    // If column doesn't exist, fall back to basic notifications
    if (!$columnExists) {
        return getCustomerNotifications($customerId, $limit);
    }
    
    $sql = "SELECT 
                n.*,
                a.appointment_id,
                a.appoint_date,
                a.appoint_status,
                a.appoint_desc,
                s.service_name,
                s.duration,
                s.cost,
                b.business_name,
                b.business_id
            FROM notifications n
            LEFT JOIN appointments a ON n.appointment_id = a.appointment_id
            LEFT JOIN services s ON a.service_id = s.service_id
            LEFT JOIN businesses b ON n.business_id = b.business_id
            WHERE n.customer_id = ? 
            AND n.notif_title NOT LIKE '%New Booking%' 
            AND n.notif_title NOT LIKE '%Cancelled by Customer%'
            ORDER BY n.notif_creation DESC";
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

// Count unread notifications (using read_status field)
function countUnreadNotifications($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE customer_id = ? 
        AND read_status = 0
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
        'completed' => 'Appointment Completed',
        'unavailable' => 'Time Slot Unavailable'
    ];
    
    $texts = [
        'confirmed' => 'Your appointment has been confirmed! We look forward to seeing you.',
        'cancelled' => 'Your appointment has been cancelled. Please contact us if you have any questions.',
        'completed' => 'Thank you for visiting us! We hope you enjoyed your service.',
        'unavailable' => 'Sorry, your requested time slot is no longer available. Another customer was confirmed first. Please book a different time slot.'
    ];
    
    $title = $titles[$status] ?? 'Appointment Update';
    $text = $texts[$status] ?? 'Your appointment status has been updated.';
    
    // Insert with appointment_id to link notification to booking
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, appointment_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Create notification for business (admin) - for completed appointments and reminders
function createBusinessNotification($businessId, $title, $text, $appointmentId = null) {
    $conn = getDbConnection();
    
    // For business notifications without a specific customer, use customer_id = 0
    $customerId = 0;
    
    // Insert notification with business_id and optional appointment_id
    if ($appointmentId) {
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (business_id, customer_id, appointment_id, notif_title, notif_text)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (business_id, customer_id, notif_title, notif_text)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Create notification for business with specific customer (for reminders and status updates)
function createBusinessNotificationWithCustomer($businessId, $customerId, $title, $text, $appointmentId = null) {
    $conn = getDbConnection();
    
    // Insert notification with business_id, customer_id and optional appointment_id
    if ($appointmentId) {
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (business_id, customer_id, appointment_id, notif_title, notif_text)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (business_id, customer_id, notif_title, notif_text)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get business notifications (includes booking, cancellation, and completion notifications)
function getBusinessNotifications($businessId, $limit = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT n.*, c.fname, c.surname 
            FROM notifications n
            LEFT JOIN customers c ON n.customer_id = c.customer_id
            WHERE n.business_id = ? 
            AND (n.notif_title LIKE '%New Booking%' 
                OR n.notif_title LIKE '%Cancelled by Customer%'
                OR n.notif_title LIKE '%Appointment Completed%'
                OR n.notif_title LIKE '%Appointment Due Today%')
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

// Count recent business notifications (using read_status field)
function countRecentBusinessNotifications($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE business_id = ? 
        AND read_status = 0
        AND (notif_title LIKE '%New Booking%' 
            OR notif_title LIKE '%Cancelled by Customer%'
            OR notif_title LIKE '%Appointment Completed%'
            OR notif_title LIKE '%Appointment Due Today%')
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Mark customer notifications as read
function markCustomerNotificationsAsRead($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET read_status = 1 
        WHERE customer_id = ? 
        AND read_status = 0
        AND notif_title NOT LIKE '%New Booking%'
        AND notif_title NOT LIKE '%Cancelled by Customer%'
    ");
    $stmt->bind_param("i", $customerId);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Mark business notifications as read
function markBusinessNotificationsAsRead($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET read_status = 1 
        WHERE business_id = ? 
        AND read_status = 0
        AND (notif_title LIKE '%New Booking%' OR notif_title LIKE '%Cancelled by Customer%')
    ");
    $stmt->bind_param("i", $businessId);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
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
        (business_id, customer_id, appointment_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
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
        (business_id, customer_id, appointment_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
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

// ============================================================
// REMINDER NOTIFICATION SYSTEM
// ============================================================

// Create a reminder notification
function createReminderNotification($customerId, $businessId, $appointmentId, $reminderType) {
    $conn = getDbConnection();
    
    // $reminderType can be: '3days', '1day', 'sameday'
    $titles = [
        '3days' => 'Appointment Reminder - 3 Days Away',
        '1day' => 'Appointment Reminder - Tomorrow',
        'sameday' => 'Appointment Reminder - Today'
    ];
    
    $texts = [
        '3days' => 'Your appointment is coming up in 3 days. Get ready!',
        '1day' => 'Your appointment is tomorrow! Don\'t forget to prepare.',
        'sameday' => 'Your appointment is today! See you soon.'
    ];
    
    $title = $titles[$reminderType] ?? 'Appointment Reminder';
    $text = $texts[$reminderType] ?? 'You have an upcoming appointment.';
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, appointment_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiiss", $businessId, $customerId, $appointmentId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Check and create reminders for appointments that need them
// This should be called periodically (via cron, scheduled task, or on each page load)
function processAppointmentReminders() {
    $conn = getDbConnection();
    
    // Get all confirmed appointments that haven't had reminders sent yet
    // We'll track reminders sent using a combination of appointment_id + reminder_type
    
    $now = date('Y-m-d H:i:s');
    
    // Get appointments for the next 3 days that are confirmed or pending
    $sql = "
        SELECT 
            a.appointment_id,
            a.customer_id,
            a.employ_id,
            a.appoint_date,
            b.business_id
        FROM appointments a
        JOIN employees e ON a.employ_id = e.employ_id
        JOIN businesses b ON e.business_id = b.business_id
        WHERE a.appoint_status IN ('confirmed', 'pending')
        AND DATE(a.appoint_date) >= CURDATE()
        AND DATE(a.appoint_date) <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ";
    
    $result = $conn->query($sql);
    
    while ($appointment = $result->fetch_assoc()) {
        $appointmentId = $appointment['appointment_id'];
        $customerId = $appointment['customer_id'];
        $businessId = $appointment['business_id'];
        $appointmentDate = $appointment['appoint_date'];
        
        // Determine which reminders should be sent
        $daysUntil = (int)((strtotime($appointmentDate) - time()) / 86400);
        $remindersToSend = [];
        
        if ($daysUntil == 3) {
            $remindersToSend[] = '3days';
        } elseif ($daysUntil == 1) {
            $remindersToSend[] = '1day';
        } elseif ($daysUntil == 0 && date('H:i', strtotime($appointmentDate)) > date('H:i')) {
            // Only send same-day reminder if appointment is later today
            $remindersToSend[] = 'sameday';
        }
        
        // For each reminder type, check if it's already been sent
        foreach ($remindersToSend as $reminderType) {
            $checkStmt = $conn->prepare("
                SELECT COUNT(*) as count FROM notifications
                WHERE appointment_id = ?
                AND customer_id = ?
                AND notif_title LIKE ?
            ");
            
            $pattern = '%' . ($reminderType === '3days' ? '3 Days' : ($reminderType === '1day' ? 'Tomorrow' : 'Today')) . '%';
            $checkStmt->bind_param("iis", $appointmentId, $customerId, $pattern);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $row = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            // If reminder hasn't been sent yet, send it
            if ($row['count'] == 0) {
                createReminderNotification($customerId, $businessId, $appointmentId, $reminderType);
            }
        }
    }
    
    return true;
}

// Get reminder notification count for a customer
function getCustomerReminderCount($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM notifications
        WHERE customer_id = ?
        AND (notif_title LIKE '%Reminder%' OR notif_title LIKE '%Days Away%' OR notif_title LIKE '%Tomorrow%')
        AND read_status = 0
    ");
    
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Create appointment due today reminder for business
function createAppointmentDueTodayReminder($businessId) {
    $conn = getDbConnection();
    
    // Get appointments scheduled for today that are confirmed and haven't been reminded yet
    // Join through services table to get business_id since appointments table doesn't have it
    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.customer_id, a.appoint_date, c.fname, c.surname, s.service_name
        FROM appointments a
        JOIN services s ON a.service_id = s.service_id
        JOIN customers c ON a.customer_id = c.customer_id
        WHERE s.business_id = ?
        AND DATE(a.appoint_date) = DATE(NOW())
        AND a.appoint_status = 'confirmed'
    ");
    
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $reminderCount = 0;
    
    // Create reminder for each appointment due today
    foreach ($appointments as $appointment) {
        // Check if reminder already sent today
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as count FROM notifications
            WHERE business_id = ?
            AND appointment_id = ?
            AND notif_title LIKE '%Appointment Due Today%'
            AND DATE(notif_creation) = DATE(NOW())
        ");
        
        $checkStmt->bind_param("ii", $businessId, $appointment['appointment_id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $row = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        // If no reminder sent today, create one
        if ($row['count'] == 0) {
            $customerName = $appointment['fname'] . ' ' . $appointment['surname'];
            $serviceName = $appointment['service_name'];
            $appointmentTime = date('H:i', strtotime($appointment['appoint_date']));
            
            $title = 'Appointment Due Today';
            $text = "You have an appointment with $customerName for $serviceName at $appointmentTime.";
            
            // Use createBusinessNotificationWithCustomer to include customer_id
            createBusinessNotificationWithCustomer(
                $businessId, 
                $appointment['customer_id'], 
                $title, 
                $text, 
                $appointment['appointment_id']
            );
            $reminderCount++;
        }
    }
    
    return $reminderCount;
}
?>