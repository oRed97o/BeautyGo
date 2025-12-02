<?php
require_once '../db_connection/config.php';
require_once '../backend/function_utilities.php';
require_once '../backend/function_customers.php';
require_once '../backend/function_notifications.php';

header('Content-Type: application/json');

// Get the customer ID from query parameter
$customerId = intval($_GET['customer_id'] ?? 0);

if (!$customerId) {
    echo json_encode(['success' => false, 'message' => 'No customer ID provided']);
    exit;
}

// Get unread notification count
$unreadCount = countUnreadNotifications($customerId);

echo json_encode([
    'success' => true,
    'unreadCount' => $unreadCount,
    'customerId' => $customerId
]);
?>
