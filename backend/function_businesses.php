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
    $hashedPassword = password_hash($data['business_password'], PASSWORD_DEFAULT);

    $email = $data['business_email'];
    $businessName = $data['business_name'];
    $businessType = $data['business_type'] ?? '';
    $businessDesc = $data['business_desc'] ?? '';
    $businessNum = $data['business_num'] ?? '';
    $businessAddress = $data['business_address'] ?? '';
    $city = $data['city'] ?? '';
    $openingHour = $data['opening_hour'] ?? '09:00';
    $closingHour = $data['closing_hour'] ?? '18:00';

    $longitude = $data['longitude'] ?? 120.6328;
    $latitude = $data['latitude'] ?? 14.0697;
    $location = "POINT($longitude $latitude)";

    $stmt = $conn->prepare("
        INSERT INTO businesses 
        (business_email, business_password, business_name, business_type, business_desc, business_num, business_address, city, opening_hour, closing_hour, location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))
    ");
    $stmt->bind_param("sssssssss",
        $email,
        $hashedPassword,
        $businessName,
        $businessType,
        $businessDesc,
        $businessNum,
        $businessAddress,
        $city,
        $openingHour,
        $closingHour,
        $location
    );

    if ($stmt->execute()) {
        $businessId = $conn->insert_id;
        $stmt->close();
        createAlbumForBusiness($businessId);
        return $businessId;
    }

    error_log("Business creation failed: " . $stmt->error);
    $stmt->close();
    return false;
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
        $location = "POINT(" . $data['longitude'] . " " . $data['latitude'] . ")";
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_address = ?, city = ?, location = ST_GeomFromText(?) WHERE business_id = ?");
        $stmt->bind_param("ssssssi",
            $businessName,
            $businessType,
            $businessDesc,
            $businessAddress,
            $city,
            $location,
            $id
        );
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
    }
    
    $success = $stmt->execute();
    $stmt->close();
    return $success;
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
?>