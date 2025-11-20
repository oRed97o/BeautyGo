<?php
require_once '../db_connection/config.php';
require_once 'function_utilities.php';
require_once 'function_appointments.php';

// Check if business or customer is logged in
if (!isBusinessLoggedIn() && !isCustomerLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    $appointStatus = $_POST['appoint_status'] ?? '';
    
    if (empty($appointmentId) || empty($appointStatus)) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
        exit;
    }
    
    // Validate status
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($appointStatus, $validStatuses)) {
        $_SESSION['error'] = 'Invalid appointment status';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
        exit;
    }
    
    // Get appointment details for verification
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT a.*, e.business_id 
        FROM appointments a
        LEFT JOIN employees e ON a.employ_id = e.employ_id
        WHERE a.appointment_id = ?
    ");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$appointment) {
        $_SESSION['error'] = 'Appointment not found';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
        exit;
    }
    
    // Authorization check
    if (isBusinessLoggedIn()) {
        // Business can only update their own appointments
        $businessId = $_SESSION['business_id'];
        if ($appointment['business_id'] != $businessId) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: ../business-dashboard.php');
            exit;
        }
    } elseif (isCustomerLoggedIn()) {
        // Customer can only update their own appointments
        $customerId = $_SESSION['customer_id'];
        if ($appointment['customer_id'] != $customerId) {
            $_SESSION['error'] = 'Unauthorized action';
            header('Location: ../my-bookings.php');
            exit;
        }
        
        // Customers can only cancel pending appointments
        if ($appointStatus != 'cancelled' || $appointment['appoint_status'] != 'pending') {
            $_SESSION['error'] = 'You can only cancel pending appointments';
            header('Location: ../my-bookings.php');
            exit;
        }
    }
    
    // Update appointment status
    if (updateAppointmentStatus($appointmentId, $appointStatus)) {
        $_SESSION['success'] = 'Appointment status updated to ' . ucfirst($appointStatus);
    } else {
        $_SESSION['error'] = 'Failed to update appointment status';
    }
    
    // Redirect back to appropriate page
    if (isBusinessLoggedIn()) {
        header('Location: ../business-dashboard.php');
    } else {
        header('Location: ../my-bookings.php');
    }
    exit;
}

// If accessed directly without POST, redirect to home
header('Location: ../index.php');
exit;
?>