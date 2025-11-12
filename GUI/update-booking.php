<?php
require_once 'config.php';
require_once 'functions.php';

// Check if business or customer is logged in
if (!isBusinessLoggedIn() && !isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both old and new field names for backwards compatibility
    $appointmentId = $_POST['appointment_id'] ?? $_POST['booking_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($appointmentId) || empty($status)) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }
    
    // Update appointment status using the new schema
    if (updateAppointmentStatus($appointmentId, $status)) {
        $_SESSION['success'] = 'Appointment status updated to ' . ucfirst($status);
    } else {
        $_SESSION['error'] = 'Failed to update appointment status';
    }
    
    // Redirect back to appropriate page
    if (isBusinessLoggedIn()) {
        header('Location: business-dashboard.php');
    } else {
        header('Location: my-bookings.php');
    }
    exit;
}

// If accessed directly without POST, redirect to home
header('Location: index.php');
exit;
?>
