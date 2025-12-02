<?php
/**
 * AJAX endpoint to fetch businesses near user coordinates
 * GET Parameters:
 * - latitude: User's latitude
 * - longitude: User's longitude
 * - radius: Search radius in kilometers (default: 10)
 * - limit: Maximum number of results (default: 8)
 */

require_once __DIR__ . '/../db_connection/config.php';
require_once __DIR__ . '/../backend/function_businesses.php';
require_once __DIR__ . '/../backend/function_utilities.php';

header('Content-Type: application/json');

// Get parameters
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;
$radiusKm = isset($_GET['radius']) ? intval($_GET['radius']) : 10;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;

// Validate parameters
if (!$latitude || !$longitude) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing latitude or longitude parameters'
    ]);
    exit;
}

// Validate coordinates are reasonable
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid coordinates'
    ]);
    exit;
}

try {
    // Get businesses near the user's location
    $businesses = getBusinessesByCoordinates($latitude, $longitude, $radiusKm, $limit);
    
    if ($businesses) {
        // Format response with clean data
        $formattedBusinesses = array_map(function($business) {
            return [
                'business_id' => $business['business_id'],
                'business_name' => $business['business_name'],
                'business_type' => $business['business_type'],
                'business_desc' => substr($business['business_desc'], 0, 150) . '...',
                'business_email' => $business['business_email'],
                'business_num' => $business['business_num'],
                'business_address' => $business['business_address'],
                'city' => $business['city'],
                'opening_hour' => $business['opening_hour'],
                'closing_hour' => $business['closing_hour'],
                'latitude' => floatval($business['latitude']),
                'longitude' => floatval($business['longitude']),
                'distance' => isset($business['distance']) ? floatval($business['distance']) . ' km' : 'N/A'
            ];
        }, $businesses);
        
        echo json_encode([
            'status' => 'success',
            'count' => count($formattedBusinesses),
            'radius' => $radiusKm,
            'businesses' => $formattedBusinesses
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'count' => 0,
            'message' => 'No businesses found in the specified radius',
            'businesses' => []
        ]);
    }
} catch (Exception $e) {
    error_log("Error fetching nearby businesses: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching businesses'
    ]);
}
?>
