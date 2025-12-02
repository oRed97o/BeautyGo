<?php
require_once '../db_connection/config.php';
require_once '../backend/function_utilities.php';
require_once '../backend/function_customers.php';
require_once '../backend/function_businesses.php';
require_once '../backend/function_notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$notificationId = intval($_POST['notification_id'] ?? 0);

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$conn = getDbConnection();

// Mark the specific notification as read
$stmt = $conn->prepare("UPDATE notifications SET read_status = 1 WHERE notif_id = ?");
$stmt->bind_param("i", $notificationId);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Get the current user to calculate new unread count
    if (isCustomerLoggedIn()) {
        $customer = getCurrentCustomer();
        $unreadCount = countUnreadNotifications($customer['customer_id']);
        $userType = 'customer';
    } else {
        $business = getCurrentBusiness();
        $unreadCount = countRecentBusinessNotifications($business['business_id']);
        $userType = 'business';
    }
    
    echo json_encode([
        'success' => true,
        'unreadCount' => $unreadCount,
        'userType' => $userType
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}
?>
