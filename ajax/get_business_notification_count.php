<?php
require_once '../db_connection/config.php';
require_once '../backend/function_utilities.php';
require_once '../backend/function_businesses.php';
require_once '../backend/function_notifications.php';

header('Content-Type: application/json');

// Get the business ID from query parameter
$businessId = intval($_GET['business_id'] ?? 0);

if (!$businessId) {
    echo json_encode(['success' => false, 'message' => 'No business ID provided']);
    exit;
}

// Get unread notification count
$unreadCount = countRecentBusinessNotifications($businessId);

echo json_encode([
    'success' => true,
    'unreadCount' => $unreadCount,
    'businessId' => $businessId
]);
?>
