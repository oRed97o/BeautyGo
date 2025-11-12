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
        $serviceData = [
            'business_id' => $business['id'],
            'service_name' => sanitize($_POST['name']),
            'description' => sanitize($_POST['description']),
            'duration' => intval($_POST['duration']),
            'price' => floatval($_POST['price']),
            'category' => sanitize($_POST['category'] ?? 'General')
        ];
        
        if (createService($serviceData)) {
            $_SESSION['success'] = 'Service added successfully';
        } else {
            $_SESSION['error'] = 'Failed to add service';
        }
    }
}

header('Location: business-dashboard.php');
exit;
?>
