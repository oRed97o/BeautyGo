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

    $longitude = $data['longitude'] ?? 120.6328;
    $latitude = $data['latitude'] ?? 14.0697;
    $location = "POINT($longitude $latitude)";

    $stmt = $conn->prepare("
        INSERT INTO businesses 
        (business_email, business_password, business_name, business_type, business_desc, business_num, business_address, city, location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))
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
?>