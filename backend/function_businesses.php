<?php
// ============================================================
// BUSINESS FUNCTIONS
// ============================================================

// Get all businesses
function getAllBusinesses() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses ORDER BY business_id DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get business by ID
function getBusinessById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses WHERE business_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Get business by email
function getBusinessByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses WHERE business_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Create new business
function createBusiness($data) {
    $conn = getDbConnection();
    
    // Hash the password
    $hashedPassword = password_hash($data['business_password'], PASSWORD_DEFAULT);
    
    // Ensure coordinates are valid numbers
    $latitude = floatval($data['latitude']);
    $longitude = floatval($data['longitude']);
    
    // Log for debugging
    error_log("createBusiness - Latitude: $latitude, Longitude: $longitude");
    
    // First, insert business without location
    $sql = "INSERT INTO businesses (
        business_email, 
        business_password, 
        business_name, 
        business_type, 
        business_desc, 
        business_num, 
        business_address, 
        city, 
        opening_hour, 
        closing_hour
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    // Bind parameters: 10 parameters (without location)
    $stmt->bind_param(
        'ssssssssss',  // 10 's' characters for 10 parameters
        $data['business_email'],
        $hashedPassword,
        $data['business_name'],
        $data['business_type'],
        $data['business_desc'],
        $data['business_num'],
        $data['business_address'],
        $data['city'],
        $data['opening_hour'],
        $data['closing_hour']
    );
    
    if ($stmt->execute()) {
        $businessId = $stmt->insert_id;
        error_log("Business created - ID: $businessId, Now updating location...");
        $stmt->close();
        
        // Update location using POINT() constructor AND save latitude/longitude separately
        $lon = floatval($longitude);
        $lat = floatval($latitude);
        $updateSql = "UPDATE businesses SET location = POINT(" . $lon . ", " . $lat . "), latitude = " . $lat . ", longitude = " . $lon . " WHERE business_id = " . intval($businessId);
        
        error_log("Executing location update SQL: $updateSql");
        
        if ($conn->query($updateSql)) {
            // Verify the update
            $verifySql = "SELECT latitude, longitude, ST_X(location) as gis_lng, ST_Y(location) as gis_lat FROM businesses WHERE business_id = " . intval($businessId);
            $verifyResult = $conn->query($verifySql);
            if ($verifyResult) {
                $verifyRow = $verifyResult->fetch_assoc();
                error_log("Location updated - ID: $businessId, Saved Lat: " . $verifyRow['latitude'] . ", Lon: " . $verifyRow['longitude'] . " | GIS Lat: " . $verifyRow['gis_lat'] . ", GIS Lon: " . $verifyRow['gis_lng']);
            }
            return $businessId;
        } else {
            error_log("Location update FAILED: " . $conn->error . " | SQL: $updateSql");
            return $businessId; // Still return ID, business is created
        }
    } else {
        error_log("Insert execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

// Update business
function updateBusiness($id, $data) {
    $conn = getDbConnection();
    
    $businessName = $data['business_name'];
    $businessType = $data['business_type'] ?? '';
    $businessDesc = $data['business_desc'] ?? '';
    $businessAddress = $data['business_address'] ?? '';
    $city = $data['city'] ?? '';
    
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $latitude = floatval($data['latitude']);
        $longitude = floatval($data['longitude']);
        
        // Update without location first
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_address = ?, city = ? WHERE business_id = ?");
        $stmt->bind_param("sssssi",
            $businessName,
            $businessType,
            $businessDesc,
            $businessAddress,
            $city,
            $id
        );
        
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Now update location using POINT() constructor AND save latitude/longitude separately
            $lon = floatval($longitude);
            $lat = floatval($latitude);
            $updateSql = "UPDATE businesses SET location = POINT(" . $lon . ", " . $lat . "), latitude = " . $lat . ", longitude = " . $lon . " WHERE business_id = " . intval($id);
            
            if ($conn->query($updateSql)) {
                error_log("Business updated with location - ID: $id, Lat: $lat, Lon: $lon");
            } else {
                error_log("Location update failed: " . $conn->error . " | SQL: $updateSql");
            }
        }
        
        return $success;
    } else {
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_address = ?, city = ? WHERE business_id = ?");
        $stmt->bind_param("sssssi",
            $businessName,
            $businessType,
            $businessDesc,
            $businessAddress,
            $city,
            $id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            error_log("updateBusiness failed: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }
}

// Get businesses with distance calculation
function getBusinessesWithDistance($userLat = null, $userLon = null) {
    $conn = getDbConnection();
    
    if ($userLat && $userLon) {
        // Calculate distance using Haversine formula
        $sql = "SELECT *, 
                ST_X(location) AS longitude, 
                ST_Y(location) AS latitude,
                ROUND(
                    6371 * acos(
                        cos(radians(?)) * cos(radians(ST_Y(location))) * 
                        cos(radians(ST_X(location)) - radians(?)) + 
                        sin(radians(?)) * sin(radians(ST_Y(location)))
                    ), 1
                ) AS distance
                FROM businesses 
                ORDER BY distance ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddd", $userLat, $userLon, $userLat);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    } else {
        return getAllBusinesses();
    }
}

// Get top rated businesses (4.5+ stars)
function getTopRatedBusinesses($limit = 8) {
    $conn = getDbConnection();
    
    $sql = "SELECT b.*, 
            ST_X(b.location) AS longitude, 
            ST_Y(b.location) AS latitude,
            AVG(r.rating) as avg_rating,
            COUNT(r.review_id) as review_count
            FROM businesses b
            LEFT JOIN reviews r ON b.business_id = r.business_id
            GROUP BY b.business_id
            HAVING avg_rating >= 4.5
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get new businesses (registered within last 30 days)
function getNewBusinesses($limit = 8) {
    $conn = getDbConnection();
    
    $sql = "SELECT *, 
            ST_X(location) AS longitude, 
            ST_Y(location) AS latitude
            FROM businesses 
            WHERE business_id >= (SELECT MAX(business_id) - 10 FROM businesses)
            ORDER BY business_id DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get popular businesses (most bookings)
function getPopularBusinesses($limit = 8) {
    $conn = getDbConnection();
    
    $sql = "SELECT b.*, 
            ST_X(b.location) AS longitude, 
            ST_Y(b.location) AS latitude,
            COUNT(DISTINCT a.appointment_id) as booking_count
            FROM businesses b
            LEFT JOIN services s ON b.business_id = s.business_id
            LEFT JOIN appointments a ON s.service_id = a.service_id
            WHERE a.appoint_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY b.business_id
            HAVING booking_count > 0
            ORDER BY booking_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get all businesses excluding featured ones
function getAllBusinessesExcludingFeatured($excludeIds = []) {
    $conn = getDbConnection();
    
    if (empty($excludeIds)) {
        return getAllBusinesses();
    }
    
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $types = str_repeat('i', count($excludeIds));
    
    $sql = "SELECT *, 
            ST_X(location) AS longitude, 
            ST_Y(location) AS latitude
            FROM businesses 
            WHERE business_id NOT IN ($placeholders)
            ORDER BY business_id DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically
    if (count($excludeIds) > 0) {
        $stmt->bind_param($types, ...$excludeIds);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data;
}

// Get businesses by category
function getBusinessesByCategory($category) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT *, 
            ST_X(location) AS longitude, 
            ST_Y(location) AS latitude
            FROM businesses 
            WHERE business_type = ?
            ORDER BY business_id DESC");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get businesses by address
function getBusinessesByAddress($customerAddress, $limit = 8) {
    $conn = getDbConnection();
    
    if (!$conn || empty($customerAddress)) {
        error_log("getBusinessesByAddress: Missing connection or address");
        return [];
    }
    
    // Extract city/barangay from address
    $addressParts = explode(',', $customerAddress);
    $searchLocation = trim($addressParts[0]);
    
    // Search for businesses in the same city/barangay
    $query = "SELECT *, 
              ST_X(location) AS longitude, 
              ST_Y(location) AS latitude
              FROM businesses 
              WHERE (business_address LIKE ? OR city LIKE ?)
              ORDER BY business_id DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed in getBusinessesByAddress: " . $conn->error);
        return [];
    }
    
    $searchTerm = '%' . $searchLocation . '%';
    $limit = intval($limit);
    $stmt->bind_param('ssi', $searchTerm, $searchTerm, $limit);
    
    if (!$stmt->execute()) {
        error_log("Execute failed in getBusinessesByAddress: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $result = $stmt->get_result();
    $businesses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    error_log("getBusinessesByAddress: Found " . count($businesses) . " businesses for address: " . $searchLocation);
    
    return $businesses ?? [];
}

// Get businesses by coordinates (latitude and longitude)
function getBusinessesByCoordinates($latitude, $longitude, $radiusKm = 10, $limit = 8) {
    $conn = getDbConnection();
    
    if (!$conn || !$latitude || !$longitude) {
        error_log("getBusinessesByCoordinates: Missing connection or coordinates");
        return [];
    }
    
    // Haversine formula to find businesses within radius
    $query = "SELECT b.*, 
                     ST_X(b.location) AS longitude,
                     ST_Y(b.location) AS latitude,
                     (6371 * acos(cos(radians(?)) * cos(radians(ST_Y(b.location))) * 
                     cos(radians(ST_X(b.location)) - radians(?)) + 
                     sin(radians(?)) * sin(radians(ST_Y(b.location))))) AS distance
              FROM businesses b
              WHERE b.location IS NOT NULL 
              HAVING distance <= ?
              ORDER BY distance ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed in getBusinessesByCoordinates: " . $conn->error);
        return [];
    }
    
    $latitude = floatval($latitude);
    $longitude = floatval($longitude);
    $radiusKm = intval($radiusKm);
    $limit = intval($limit);
    
    $stmt->bind_param('dddii', $latitude, $longitude, $latitude, $radiusKm, $limit);
    
    if (!$stmt->execute()) {
        error_log("Execute failed in getBusinessesByCoordinates: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $result = $stmt->get_result();
    $businesses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    error_log("getBusinessesByCoordinates: Found " . count($businesses) . " businesses");
    
    return $businesses ?? [];
}
?>

