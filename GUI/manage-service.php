<?php
require_once 'config.php';
require_once 'functions.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

$business = getCurrentBusiness();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Map to correct database field names
        $serviceData = [
            'business_id' => $business['business_id'],
            'service_name' => sanitize($_POST['name']),
            'service_desc' => sanitize($_POST['description']),
            'duration' => intval($_POST['duration']),
            'cost' => floatval($_POST['price']),
            'service_type' => sanitize($_POST['category'] ?? 'General')
        ];
        
        if (createService($serviceData)) {
            $_SESSION['success'] = 'Service added successfully';
        } else {
            $_SESSION['error'] = 'Failed to add service';
        }
    }
    
    elseif ($action === 'edit') {
        $serviceId = intval($_POST['service_id']);
        
        // Verify this service belongs to the logged-in business
        $existingService = getServiceById($serviceId);
        if (!$existingService || $existingService['business_id'] != $business['business_id']) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: business-dashboard.php');
            exit;
        }
        
        $serviceData = [
            'service_name' => sanitize($_POST['name']),
            'service_desc' => sanitize($_POST['description']),
            'duration' => intval($_POST['duration']),
            'cost' => floatval($_POST['price']),
            'service_type' => sanitize($_POST['category'] ?? 'General')
        ];
        
        if (updateService($serviceId, $serviceData)) {
            $_SESSION['success'] = 'Service updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update service';
        }
    }
    
    elseif ($action === 'delete') {
        $serviceId = intval($_POST['service_id']);
        
        // Verify this service belongs to the logged-in business
        $existingService = getServiceById($serviceId);
        if (!$existingService || $existingService['business_id'] != $business['business_id']) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: business-dashboard.php');
            exit;
        }
        
        if (deleteService($serviceId)) {
            $_SESSION['success'] = 'Service deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete service';
        }
    }
}

header('Location: business-dashboard.php');
exit;
?>