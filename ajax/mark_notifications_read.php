<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connection/config.php';
require_once __DIR__ . '/../backend/function_utilities.php';
require_once __DIR__ . '/../backend/function_customers.php';
require_once __DIR__ . '/../backend/function_notifications.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Determine if customer or business
if (isCustomerLoggedIn()) {
    $user = getCurrentCustomer();
    $customerId = $user['customer_id'];
    
    // Mark customer notifications as read
    if (function_exists('markCustomerNotificationsAsRead')) {
        markCustomerNotificationsAsRead($customerId);
    }
    
    // Get updated unread count
    $unreadCount = function_exists('countUnreadNotifications') ? countUnreadNotifications($customerId) : 0;
    
    echo json_encode([
        'success' => true,
        'unreadCount' => $unreadCount,
        'type' => 'customer'
    ]);
} elseif (isBusinessLoggedIn()) {
    $user = getCurrentBusiness();
    $businessId = $user['business_id'];
    
    // Mark business notifications as read
    if (function_exists('markBusinessNotificationsAsRead')) {
        markBusinessNotificationsAsRead($businessId);
    }
    
    // Get updated unread count
    $unreadCount = function_exists('countRecentBusinessNotifications') ? countRecentBusinessNotifications($businessId) : 0;
    
    echo json_encode([
        'success' => true,
        'unreadCount' => $unreadCount,
        'type' => 'business'
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'User type not determined']);
    exit;
}
?>
