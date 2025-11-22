<?php
require_once '../db_connection/config.php';
require_once 'function_utilities.php';
require_once 'function_appointments.php';
require_once 'function_employees.php';
require_once 'function_notifications.php';

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
    $appointment = getAppointmentById($appointmentId);
    
    if (!$appointment) {
        $_SESSION['error'] = 'Appointment not found';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
        exit;
    }
    
    // Get business_id from employee
    $businessId = null;
    if (!empty($appointment['employ_id'])) {
        $employee = getEmployeeById($appointment['employ_id']);
        $businessId = $employee ? $employee['business_id'] : null;
    }
    
    // Authorization check
    if (isBusinessLoggedIn()) {
        // Business can only update their own appointments
        $sessionBusinessId = $_SESSION['business_id'];
        if ($businessId != $sessionBusinessId) {
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
        
        // Customers can only cancel pending or confirmed appointments
        if ($appointStatus != 'cancelled' || !in_array($appointment['appoint_status'], ['pending', 'confirmed'])) {
            $_SESSION['error'] = 'You can only cancel pending or confirmed appointments';
            header('Location: ../my-bookings.php');
            exit;
        }
    }
    
    // Handle cancellation with notifications
    if ($appointStatus === 'cancelled') {
        if (updateAppointmentStatus($appointmentId, $appointStatus)) {
            // Send notification to business when customer cancels
            if (isCustomerLoggedIn() && $businessId) {
                createBusinessCancellationNotification($businessId, $appointment['customer_id'], $appointmentId);
            }
            // Send notification to customer when business cancels
            elseif (isBusinessLoggedIn()) {
                createAppointmentNotification(
                    $appointment['customer_id'],
                    $businessId,
                    $appointmentId,
                    'cancelled'
                );
            }
            
            $_SESSION['success'] = 'Appointment cancelled successfully';
        } else {
            $_SESSION['error'] = 'Failed to cancel appointment';
        }
    }
    // Handle confirmation (business only)
    elseif ($appointStatus === 'confirmed' && isBusinessLoggedIn()) {
        if (updateAppointmentStatus($appointmentId, $appointStatus)) {
            createAppointmentNotification(
                $appointment['customer_id'],
                $businessId,
                $appointmentId,
                'confirmed'
            );
            $_SESSION['success'] = 'Appointment confirmed successfully';
        } else {
            $_SESSION['error'] = 'Failed to confirm appointment';
        }
    }
    // Handle completion (business only)
    elseif ($appointStatus === 'completed' && isBusinessLoggedIn()) {
        if (updateAppointmentStatus($appointmentId, $appointStatus)) {
            createAppointmentNotification(
                $appointment['customer_id'],
                $businessId,
                $appointmentId,
                'completed'
            );
            $_SESSION['success'] = 'Appointment marked as completed';
        } else {
            $_SESSION['error'] = 'Failed to update appointment';
        }
    }
    // Generic update
    else {
        if (updateAppointmentStatus($appointmentId, $appointStatus)) {
            $_SESSION['success'] = 'Appointment status updated to ' . ucfirst($appointStatus);
        } else {
            $_SESSION['error'] = 'Failed to update appointment status';
        }
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