<?php
// Utility functions for BeautyGo - MySQL Version (Updated for ERD Schema)

// ============================================================
// CUSTOMER FUNCTIONS (formerly users)
// ============================================================

// Get all customers
function getAllCustomers() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get customer by ID
function getCustomerById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get customer by email
function getCustomerByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Create new customer
function createCustomer($data) {
    $conn = getDbConnection();
    $id = generateId();
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO customers (customer_id, email, password, name, surname, celler_num, celler_email, face_shape, skin_tone, body_mass, hair_type, hair_color, total_length) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssss", 
        $id,
        $data['email'],
        $hashedPassword,
        $data['name'],
        $data['surname'] ?? '',
        $data['celler_num'] ?? $data['phone'] ?? '',
        $data['celler_email'] ?? $data['email'],
        $data['face_shape'] ?? '',
        $data['skin_tone'] ?? '',
        $data['body_mass'] ?? '',
        $data['hair_type'] ?? '',
        $data['hair_color'] ?? '',
        $data['total_length'] ?? $data['desired_hair_length'] ?? ''
    );
    
    if ($stmt->execute()) {
        return $id;
    }
    return false;
}

// Update customer
function updateCustomer($id, $data) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE customers SET name = ?, surname = ?, celler_num = ?, celler_email = ?, face_shape = ?, skin_tone = ?, body_mass = ?, hair_type = ?, hair_color = ?, total_length = ? WHERE customer_id = ?");
    $stmt->bind_param("sssssssssss",
        $data['name'],
        $data['surname'] ?? '',
        $data['celler_num'] ?? $data['phone'] ?? '',
        $data['celler_email'] ?? $data['email'],
        $data['face_shape'] ?? '',
        $data['skin_tone'] ?? '',
        $data['body_mass'] ?? '',
        $data['hair_type'] ?? '',
        $data['hair_color'] ?? '',
        $data['total_length'] ?? $data['desired_hair_length'] ?? '',
        $id
    );
    return $stmt->execute();
}

// LEGACY SUPPORT - Backwards compatibility
function getAllUsers() { return getAllCustomers(); }
function getUserById($id) { return getCustomerById($id); }
function getUserByEmail($email) { return getCustomerByEmail($email); }
function createUser($data) { return createCustomer($data); }
function updateUser($id, $data) { return updateCustomer($id, $data); }

// ============================================================
// BUSINESS FUNCTIONS
// ============================================================

// Get all businesses
function getAllBusinesses() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses ORDER BY created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get business by ID
function getBusinessById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses WHERE business_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get business by email
function getBusinessByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT *, ST_X(location) AS longitude, ST_Y(location) AS latitude FROM businesses WHERE business_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Create new business
function createBusiness($data) {
    $conn = getDbConnection();
    $id = generateId();
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Create POINT from latitude/longitude (Note: POINT uses longitude, latitude order)
    $location = "POINT(" . ($data['longitude'] ?? 120.6328) . " " . ($data['latitude'] ?? 14.0697) . ")";
    
    $stmt = $conn->prepare("INSERT INTO businesses (business_id, business_email, business_password, business_name, business_type, business_desc, business_services, business_address, city, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))");
    $stmt->bind_param("ssssssssss",
        $id,
        $data['email'],
        $hashedPassword,
        $data['business_name'],
        $data['business_type'] ?? '',
        $data['description'] ?? $data['business_desc'] ?? '',
        $data['business_services'] ?? '',
        $data['address'] ?? $data['business_address'] ?? '',
        $data['city'] ?? '',
        $location
    );
    
    if ($stmt->execute()) {
        // Create album entry for this business
        createAlbumForBusiness($id);
        return $id;
    }
    return false;
}

// Update business
function updateBusiness($id, $data) {
    $conn = getDbConnection();
    
    // Create POINT from latitude/longitude if provided
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $location = "POINT(" . $data['longitude'] . " " . $data['latitude'] . ")";
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_services = ?, business_address = ?, city = ?, location = ST_GeomFromText(?) WHERE business_id = ?");
        $stmt->bind_param("ssssssss",
            $data['business_name'],
            $data['business_type'] ?? '',
            $data['description'] ?? $data['business_desc'] ?? '',
            $data['business_services'] ?? '',
            $data['address'] ?? $data['business_address'] ?? '',
            $data['city'] ?? '',
            $location,
            $id
        );
    } else {
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_services = ?, business_address = ?, city = ? WHERE business_id = ?");
        $stmt->bind_param("sssssss",
            $data['business_name'],
            $data['business_type'] ?? '',
            $data['description'] ?? $data['business_desc'] ?? '',
            $data['business_services'] ?? '',
            $data['address'] ?? $data['business_address'] ?? '',
            $data['city'] ?? '',
            $id
        );
    }
    
    return $stmt->execute();
}

// ============================================================
// ALBUM FUNCTIONS (for business images)
// ============================================================

// Create album for business
function createAlbumForBusiness($businessId) {
    $conn = getDbConnection();
    $albumId = 'album_' . $businessId;
    
    $stmt = $conn->prepare("INSERT INTO albums (album_id, business_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE album_id = album_id");
    $stmt->bind_param("ss", $albumId, $businessId);
    return $stmt->execute();
}

// Get album for business
function getBusinessAlbum($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM albums WHERE business_id = ?");
    $stmt->bind_param("s", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ============================================================
// SERVICE FUNCTIONS
// ============================================================

// Get services for a business
function getBusinessServices($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE business_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get all services
function getAllServices() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT s.*, b.business_name, b.city FROM services s JOIN businesses b ON s.business_id = b.business_id ORDER BY s.created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get service by ID
function getServiceById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Create service
function createService($data) {
    $conn = getDbConnection();
    $id = generateId();
    
    // Convert duration to string format if it's a number
    $duration = $data['duration'];
    if (is_numeric($duration)) {
        $duration = $duration . ' minutes';
    }
    
    $stmt = $conn->prepare("INSERT INTO services (service_id, business_id, service_name, service_type, service_desc, cost, duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssds",
        $id,
        $data['business_id'],
        $data['service_name'],
        $data['service_type'] ?? $data['category'] ?? '',
        $data['description'] ?? $data['service_desc'] ?? '',
        $data['price'] ?? $data['cost'] ?? 0,
        $duration
    );
    
    if ($stmt->execute()) {
        return $id;
    }
    return false;
}

// Update service
function updateService($id, $data) {
    $conn = getDbConnection();
    
    // Convert duration to string format if it's a number
    $duration = $data['duration'];
    if (is_numeric($duration)) {
        $duration = $duration . ' minutes';
    }
    
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, service_type = ?, service_desc = ?, cost = ?, duration = ? WHERE service_id = ?");
    $stmt->bind_param("sssdss",
        $data['service_name'],
        $data['service_type'] ?? $data['category'] ?? '',
        $data['description'] ?? $data['service_desc'] ?? '',
        $data['price'] ?? $data['cost'] ?? 0,
        $duration,
        $id
    );
    return $stmt->execute();
}

// Delete service
function deleteService($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("s", $id);
    return $stmt->execute();
}

// ============================================================
// EMPLOYEE FUNCTIONS (formerly staff)
// ============================================================

// Get employees for a business
function getBusinessEmployees($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE business_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get employee by ID
function getEmployeeById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Create employee
function createEmployee($data) {
    $conn = getDbConnection();
    $id = generateId();
    
    $stmt = $conn->prepare("INSERT INTO employees (employee_id, business_id, employee_name, specialization, photo, bio, experience_years) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi",
        $id,
        $data['business_id'],
        $data['employee_name'] ?? $data['name'] ?? '',
        $data['specialization'] ?? $data['specialty'] ?? '',
        $data['photo'] ?? '',
        $data['bio'] ?? '',
        $data['experience_years'] ?? 0
    );
    
    if ($stmt->execute()) {
        return $id;
    }
    return false;
}

// Update employee
function updateEmployee($id, $data) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE employees SET employee_name = ?, specialization = ?, photo = ?, bio = ?, experience_years = ? WHERE employee_id = ?");
    $stmt->bind_param("ssssis",
        $data['employee_name'] ?? $data['name'] ?? '',
        $data['specialization'] ?? $data['specialty'] ?? '',
        $data['photo'] ?? '',
        $data['bio'] ?? '',
        $data['experience_years'] ?? 0,
        $id
    );
    return $stmt->execute();
}

// Delete employee
function deleteEmployee($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $id);
    return $stmt->execute();
}

// LEGACY SUPPORT - Backwards compatibility
function getBusinessStaff($businessId) { return getBusinessEmployees($businessId); }
function getStaffById($id) { return getEmployeeById($id); }
function createStaff($data) { return createEmployee($data); }
function updateStaff($id, $data) { return updateEmployee($id, $data); }
function deleteStaff($id) { return deleteEmployee($id); }

// ============================================================
// REVIEW FUNCTIONS
// ============================================================

// Get reviews for a business
function getBusinessReviews($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT r.*, c.name as customer_name FROM reviews r JOIN customers c ON r.customer_id = c.customer_id WHERE r.business_id = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("s", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Create review
function createReview($data) {
    $conn = getDbConnection();
    $id = generateId();
    
    $stmt = $conn->prepare("INSERT INTO reviews (review_id, business_id, customer_id, review_date, review_text) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("ssss",
        $id,
        $data['business_id'],
        $data['user_id'] ?? $data['customer_id'],
        $data['comment'] ?? $data['review_text'] ?? ''
    );
    
    if ($stmt->execute()) {
        return $id;
    }
    return false;
}

// Calculate average rating (Note: rating column removed in new schema)
function calculateAverageRating($businessId) {
    // Since rating column is removed, return a default or calculate differently
    return 0;
}

// ============================================================
// APPOINTMENT FUNCTIONS (formerly bookings)
// ============================================================

// Get appointments for customer
function getCustomerAppointments($customerId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT a.*, s.service_name, s.cost, s.duration, b.business_name, b.business_address, e.employ_fname, e.employ_lname FROM appointments a JOIN services s ON a.service_id = s.service_id JOIN businesses b ON a.business_id = b.business_id LEFT JOIN employees e ON a.staff_id = e.employ_id WHERE a.customer_id = ? ORDER BY a.appoint_date DESC");
    $stmt->bind_param("s", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get appointments for business
function getBusinessAppointments($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT a.*, c.name as customer_name, c.celler_num as customer_phone, s.service_name, s.cost, s.duration, e.employ_fname, e.employ_lname FROM appointments a JOIN customers c ON a.customer_id = c.customer_id JOIN services s ON a.service_id = s.service_id LEFT JOIN employees e ON a.staff_id = e.employ_id WHERE a.business_id = ? ORDER BY a.appoint_date DESC");
    $stmt->bind_param("s", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Create appointment
function createAppointment($data) {
    $conn = getDbConnection();
    $id = generateId();
    
    // Combine date and time into datetime
    $appointDate = $data['appoint_date'] ?? ($data['booking_date'] . ' ' . $data['booking_time']);
    
    $stmt = $conn->prepare("INSERT INTO appointments (appointment_id, customer_id, business_id, service_id, staff_id, appoint_date, appoint_status, appoint_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss",
        $id,
        $data['user_id'] ?? $data['customer_id'],
        $data['business_id'],
        $data['service_id'],
        $data['staff_id'] ?? null,
        $appointDate,
        $data['status'] ?? $data['appoint_status'] ?? 'pending',
        $data['notes'] ?? $data['appoint_desc'] ?? ''
    );
    
    if ($stmt->execute()) {
        return $id;
    }
    return false;
}

// Update appointment status
function updateAppointmentStatus($id, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE appointments SET appoint_status = ? WHERE appointment_id = ?");
    $stmt->bind_param("ss", $status, $id);
    return $stmt->execute();
}

// Delete appointment
function deleteAppointment($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("s", $id);
    return $stmt->execute();
}

// LEGACY SUPPORT - Backwards compatibility
function getUserBookings($userId) { return getCustomerAppointments($userId); }
function getBusinessBookings($businessId) { return getBusinessAppointments($businessId); }
function createBooking($data) { return createAppointment($data); }
function updateBookingStatus($id, $status) { return updateAppointmentStatus($id, $status); }
function deleteBooking($id) { return deleteAppointment($id); }

// ============================================================
// LOCATION & DISTANCE FUNCTIONS
// ============================================================

// Get businesses with distance using POINT datatype
function getBusinessesWithDistance($userLat = null, $userLon = null) {
    $businesses = getAllBusinesses();
    
    if ($userLat && $userLon) {
        $conn = getDbConnection();
        $userPoint = "POINT($userLon $userLat)";
        
        // Get distances using MySQL spatial functions
        $stmt = $conn->prepare("SELECT business_id, ST_Distance_Sphere(location, ST_GeomFromText(?)) / 1000 AS distance_km FROM businesses");
        $stmt->bind_param("s", $userPoint);
        $stmt->execute();
        $result = $stmt->get_result();
        $distances = [];
        
        while ($row = $result->fetch_assoc()) {
            $distances[$row['business_id']] = round($row['distance_km'], 1);
        }
        
        // Add distances to businesses
        foreach ($businesses as &$business) {
            $business['distance'] = $distances[$business['business_id']] ?? 999;
        }
        
        // Sort by distance
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

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

// Generate unique ID
function generateId() {
    return uniqid() . '_' . time();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['customer_id']) || isset($_SESSION['business_id']);
}

// Check if business is logged in
function isBusinessLoggedIn() {
    return isset($_SESSION['business_id']);
}

// Check if customer is logged in
function isCustomerLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['customer_id']);
}

// Get current user/customer
function getCurrentUser() {
    if (isset($_SESSION['customer_id'])) {
        return getCustomerById($_SESSION['customer_id']);
    } elseif (isset($_SESSION['user_id'])) {
        return getCustomerById($_SESSION['user_id']);
    }
    return null;
}

// Get current business
function getCurrentBusiness() {
    if (isset($_SESSION['business_id'])) {
        return getBusinessById($_SESSION['business_id']);
    }
    return null;
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Format time
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Format datetime
function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}
?>
