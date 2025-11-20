<?php
// ============================================================
// UTILITY FUNCTIONS
// ============================================================

// ---------------------------
// SESSION HELPERS
// ---------------------------

// Check if any user (customer or business) is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['customer_id']) || isset($_SESSION['business_id']);
}

// Check if a business is logged in
function isBusinessLoggedIn(): bool {
    return isset($_SESSION['business_id']);
}

// Check if a customer is logged in
function isCustomerLoggedIn(): bool {
    return isset($_SESSION['customer_id']);
}

// ---------------------------
// GET CURRENT USER/BUSINESS
// ---------------------------

// Get the current logged-in customer (if any)
function getCurrentCustomer() {
    if (!empty($_SESSION['customer_id'])) {
        return getCustomerById($_SESSION['customer_id']);
    }
    return null;
}

// Get the current logged-in business (if any)
function getCurrentBusiness() {
    if (!empty($_SESSION['business_id'])) {
        return getBusinessById($_SESSION['business_id']);
    }
    return null;
}

// ---------------------------
// DATA VALIDATION + SANITIZATION
// ---------------------------

// Sanitize input (remove HTML, JS, and trim spaces)
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email format
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ---------------------------
// DATE/TIME FORMATTERS
// ---------------------------

// Format date (e.g., October 28, 2025)
function formatDate(?string $date): string {
    if (empty($date)) return '';
    return date('F j, Y', strtotime($date));
}

// Format time (e.g., 3:45 PM)
function formatTime(?string $time): string {
    if (empty($time)) return '';
    return date('g:i A', strtotime($time));
}

// Format datetime (e.g., October 28, 2025 3:45 PM)
function formatDateTime(?string $datetime): string {
    if (empty($datetime)) return '';
    return date('F j, Y g:i A', strtotime($datetime));
}

// ---------------------------
// LOCATION & DISTANCE
// ---------------------------

// Get businesses with distance using POINT datatype
function getBusinessesWithDistance($userLat = null, $userLon = null) {
    $businesses = getAllBusinesses();
    
    if ($userLat && $userLon) {
        $conn = getDbConnection();
        $userPoint = "POINT($userLon $userLat)";
        
        $stmt = $conn->prepare("SELECT business_id, ST_Distance_Sphere(location, ST_GeomFromText(?)) / 1000 AS distance_km FROM businesses");
        $stmt->bind_param("s", $userPoint);
        $stmt->execute();
        $result = $stmt->get_result();
        $distances = [];
        
        while ($row = $result->fetch_assoc()) {
            $distances[$row['business_id']] = round($row['distance_km'], 1);
        }
        
        $stmt->close();
        
        foreach ($businesses as &$business) {
            $business['distance'] = $distances[$business['business_id']] ?? 999;
        }
        
        usort($businesses, function($a, $b) {
            return ($a['distance'] ?? 999) <=> ($b['distance'] ?? 999);
        });
    }
    
    return $businesses;
}

// Calculate distance between two coordinates (Haversine formula) - LEGACY
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance, 1);
}
?>