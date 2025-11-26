<?php
// Start session at the very beginning
session_start();

// Prevent any output before JSON
ob_start();

require_once '../../db_connection/config.php';
require_once '../function_utilities.php';
require_once '../function_favorites.php';

// Clear any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Disable error display to prevent HTML in JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Check if user is logged in as CUSTOMER (not business)
    if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login first',
            'redirect' => 'login.php'
        ]);
        exit;
    }

    // Check if user is logged in as BUSINESS - prevent them from favoriting
    if (isset($_SESSION['business_id']) && !empty($_SESSION['business_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Business accounts cannot favorite'
        ]);
        exit;
    }

    // Get parameters
    $action = $_POST['action'] ?? '';
    $customerId = $_SESSION['customer_id'];
    $businessId = intval($_POST['business_id'] ?? 0);

    // Validate inputs
    if ($action !== 'toggle') {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action'
        ]);
        exit;
    }

    if ($businessId <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid business ID'
        ]);
        exit;
    }

    // Check if already favorited
    $isFavorite = isFavorite($customerId, $businessId);
    
    if ($isFavorite) {
        // Remove favorite
        $result = removeFavorite($customerId, $businessId);
        echo json_encode([
            'success' => $result,
            'is_favorite' => false,
            'message' => $result ? 'Removed from favorites' : 'Failed to remove favorite'
        ]);
    } else {
        // Add favorite
        $result = addFavorite($customerId, $businessId);
        echo json_encode([
            'success' => $result,
            'is_favorite' => true,
            'message' => $result ? 'Added to favorites' : 'Failed to add favorite'
        ]);
    }

} catch (Exception $e) {
    // Log error but don't expose details to client
    error_log('Favorites AJAX Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred. Please try again.'
    ]);
}

// Flush output buffer
ob_end_flush();
exit;
?>