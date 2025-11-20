<?php
// CRITICAL: Start session first
session_start();

session_start();

require_once '../../db_connection/config.php';
require_once '../function_utilities.php';  // for isCustomerLoggedIn()
require_once '../function_favorites.php';  // for toggleFavorite(), isFavorite()

header('Content-Type: application/json');

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$action = $_POST['action'] ?? '';
$customerId = $_SESSION['customer_id'];
$businessId = intval($_POST['business_id'] ?? 0);

if ($action === 'toggle' && $businessId > 0) {
    try {
        $success = toggleFavorite($customerId, $businessId);
        
        if ($success) {
            $isFav = isFavorite($customerId, $businessId);
            
            echo json_encode([
                'success' => true,
                'is_favorite' => $isFav,
                'message' => $isFav ? 'Added to favorites' : 'Removed from favorites'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database operation failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>