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
    // Check if user is logged in
    if (!isCustomerLoggedIn()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please login first'
        ]);
        exit;
    }

    // Get parameters
    $action = $_POST['action'] ?? '';
    $customerId = $_SESSION['customer_id'] ?? 0;
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

    if ($customerId <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid customer ID'
        ]);
        exit;
    }

    // Perform the toggle operation
    $success = toggleFavorite($customerId, $businessId);
    
    if ($success) {
        // Check current favorite status
        $isFav = isFavorite($customerId, $businessId);
        
        echo json_encode([
            'success' => true,
            'is_favorite' => $isFav,
            'message' => $isFav ? 'Added to favorites' : 'Removed from favorites'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database operation failed'
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