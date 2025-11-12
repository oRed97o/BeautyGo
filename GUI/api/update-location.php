<?php
require_once '../config.php';
require_once '../functions.php';

if (!isCustomerLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $businessId = $data['business_id'] ?? null;
    $customerId = $_SESSION['customer_id'];
    
    if ($businessId) {
        // Add logic to toggle favorite in a favorites table
        // For now, just return success
        echo json_encode(['success' => true]);
    }
}
?>