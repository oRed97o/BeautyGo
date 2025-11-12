<?php
// Utility functions for BeautyGo - MySQL Version with Business Notifications

// ============================================================
// CUSTOMER FUNCTIONS (formerly users)
// ============================================================

// Get all customers
function getAllCustomers() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM customers ORDER BY customer_id DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get customer by ID
function getCustomerById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Get customer by email
function getCustomerByEmail($email) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM customers WHERE cstmr_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Create new customer
function createCustomer($data) {
    $conn = getDbConnection();
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Handle profile picture from BOTH sources
    $profilePic = null;
    $hasProfilePic = false;
    
    // Check if profile_pic is already binary data (from auth.php)
    if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
        $profilePic = $data['profile_pic'];
        $hasProfilePic = true;
    }
    // Or if it's from $_FILES (direct upload)
    elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $profilePic = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $hasProfilePic = true;
    }

    // Insert into customers table
    $stmt1 = $conn->prepare("
        INSERT INTO customers 
        (fname, mname, surname, cstmr_num, cstmr_email, cstmr_password, cstmr_address, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $null = null;
    $stmt1->bind_param("ssssssss",
        $data['fname'],
        $data['mname'] ?? '',
        $data['surname'] ?? '',
        $data['cstmr_num'] ?? '',
        $data['cstmr_email'] ?? '',
        $hashedPassword,
        $data['cstmr_address'] ?? '',
        $null
    );

    // Send BLOB data if available
    if ($hasProfilePic) {
        $stmt1->send_long_data(7, $profilePic);
    }

    if (!$stmt1->execute()) {
        error_log("Customer creation failed: " . $stmt1->error);
        $stmt1->close();
        return false;
    }

    $id = $conn->insert_id;
    $stmt1->close();

    // Insert into profiles table
    $stmt2 = $conn->prepare("
        INSERT INTO profiles 
        (customer_id, face_shape, body_type, eye_color, skin_tone, hair_type, hair_color, current_hair_length, desired_hair_length)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("issssssss",
        $id,
        $data['face_shape'] ?? '',
        $data['body_type'] ?? '',
        $data['eye_color'] ?? '',
        $data['skin_tone'] ?? '',
        $data['hair_type'] ?? '',
        $data['hair_color'] ?? '',
        $data['current_hair_length'] ?? '',
        $data['desired_hair_length'] ?? ''
    );

    if ($stmt2->execute()) {
        $stmt2->close();
        return $id;
    }

    error_log("Profile creation failed: " . $stmt2->error);
    $stmt2->close();
    return false;
}

// Update customer and profile
function updateCustomer($id, $data) {
    $conn = getDbConnection();

    // Handle profile picture update (if provided)
    $profilePic = null;
    $hasNewPic = false;
    
    if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
        $profilePic = $data['profile_pic'];
        $hasNewPic = true;
    } elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $profilePic = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $hasNewPic = true;
    }

    if ($hasNewPic) {
        // Update including profile picture
        $stmt1 = $conn->prepare("
            UPDATE customers 
            SET fname = ?, mname = ?, surname = ?, cstmr_num = ?, cstmr_email = ?, cstmr_address = ?, profile_pic = ?
            WHERE customer_id = ?
        ");
        
        $null = null;
        $stmt1->bind_param("sssssssi",
            $data['fname'],
            $data['mname'] ?? '',
            $data['surname'] ?? '',
            $data['cstmr_num'] ?? '',
            $data['cstmr_email'] ?? '',
            $data['cstmr_address'] ?? '',
            $null,
            $id
        );
        $stmt1->send_long_data(6, $profilePic);
    } else {
        // Update without changing the existing profile picture
        $stmt1 = $conn->prepare("
            UPDATE customers 
            SET fname = ?, mname = ?, surname = ?, cstmr_num = ?, cstmr_email = ?, cstmr_address = ?
            WHERE customer_id = ?
        ");
        $stmt1->bind_param("ssssssi",
            $data['fname'],
            $data['mname'] ?? '',
            $data['surname'] ?? '',
            $data['cstmr_num'] ?? '',
            $data['cstmr_email'] ?? '',
            $data['cstmr_address'] ?? '',
            $id
        );
    }

    // Update profiles table
    $stmt2 = $conn->prepare("
        UPDATE profiles 
        SET face_shape = ?, body_type = ?, eye_color = ?, skin_tone = ?, hair_type = ?, hair_color = ?, current_hair_length = ?, desired_hair_length = ?
        WHERE customer_id = ?
    ");
    $stmt2->bind_param("ssssssssi",
        $data['face_shape'] ?? '',
        $data['body_type'] ?? '',
        $data['eye_color'] ?? '',
        $data['skin_tone'] ?? '',
        $data['hair_type'] ?? '',
        $data['hair_color'] ?? '',
        $data['current_hair_length'] ?? '',
        $data['desired_hair_length'] ?? '',
        $id
    );

    // Execute both updates
    $success1 = $stmt1->execute();
    $success2 = $stmt2->execute();
    
    $stmt1->close();
    $stmt2->close();

    return $success1 && $success2;
}

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

    // Assign all values to variables first (required for bind_param)
    $email = $data['business_email'];
    $businessName = $data['business_name'];
    $businessType = $data['business_type'] ?? '';
    $businessDesc = $data['business_desc'] ?? '';
    $businessNum = $data['business_num'] ?? '';
    $businessAddress = $data['business_address'] ?? '';
    $city = $data['city'] ?? '';

    // Create POINT from latitude/longitude (MySQL expects "POINT(longitude latitude)")
    $longitude = $data['longitude'] ?? 120.6328;
    $latitude = $data['latitude'] ?? 14.0697;
    $location = "POINT($longitude $latitude)";

    // Insert into businesses
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
        
        // Create album entry for this business
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
    
    // Create POINT from latitude/longitude if provided
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $location = "POINT(" . $data['longitude'] . " " . $data['latitude'] . ")";
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_address = ?, city = ?, location = ST_GeomFromText(?) WHERE business_id = ?");
        $stmt->bind_param("ssssssi",
            $data['business_name'],
            $data['business_type'] ?? '',
            $data['business_desc'] ?? '',
            $data['business_address'] ?? '',
            $data['city'] ?? '',
            $location,
            $id
        );
    } else {
        $stmt = $conn->prepare("UPDATE businesses SET business_name = ?, business_type = ?, business_desc = ?, business_address = ?, city = ? WHERE business_id = ?");
        $stmt->bind_param("sssssi",
            $data['business_name'],
            $data['business_type'] ?? '',
            $data['business_desc'] ?? '',
            $data['business_address'] ?? '',
            $data['city'] ?? '',
            $id
        );
    }
    
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// ============================================================
// ALBUM FUNCTIONS (for business images)
// ============================================================

// Create album for business
function createAlbumForBusiness($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO albums (business_id)
        VALUES (?)
        ON DUPLICATE KEY UPDATE business_id = business_id
    ");
    $stmt->bind_param("i", $businessId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get album for business
function getBusinessAlbum($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM albums WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Get existing album or create a new one if none exists 
function getOrCreateBusinessAlbum($businessId) {
    $conn = getDbConnection();

    // Try to fetch an existing album
    $stmt = $conn->prepare("SELECT * FROM albums WHERE business_id = ?");
    $stmt->bind_param("i", $businessId); 
    $stmt->execute();
    $result = $stmt->get_result();
    $album = $result->fetch_assoc();
    $stmt->close();

    // If no album exists, create one
    if (!$album) {
        $stmt = $conn->prepare("INSERT INTO albums (business_id) VALUES (?)");
        $stmt->bind_param("i", $businessId);

        if ($stmt->execute()) {
            // Retrieve the newly created album record
            $newId = $conn->insert_id;
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT * FROM albums WHERE album_id = ?");
            $stmt->bind_param("i", $newId);
            $stmt->execute();
            $result = $stmt->get_result();
            $album = $result->fetch_assoc();
            $stmt->close();
        }
    }

    return $album;
}

// Update album images (logo + up to 10 images)
function updateAlbumImages($businessId, $images) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        UPDATE albums 
        SET logo = ?, image1 = ?, image2 = ?, image3 = ?, image4 = ?, 
            image5 = ?, image6 = ?, image7 = ?, image8 = ?, image9 = ?, image10 = ?
        WHERE business_id = ?
    ");

    $null = null;
    $stmt->bind_param(
        "sssssssssssi",
        $null, $null, $null, $null, $null,
        $null, $null, $null, $null, $null, $null,
        $businessId
    );

    // Send binary data for each non-empty image
    $imageSlots = [
        'logo' => 0,
        0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5,
        5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10
    ];

    foreach ($imageSlots as $key => $index) {
        if (isset($images[$key]) && !empty($images[$key])) {
            $stmt->send_long_data($index, $images[$key]);
        }
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get all album images (logo + 10 gallery images)
function getAlbumImagesArray($businessId, $asBase64 = true) {
    $album = getOrCreateBusinessAlbum($businessId);
    $images = [];

    if ($album) {
        // Include logo if available
        if (!empty($album['logo'])) {
            $images['logo'] = $asBase64
                ? 'data:image/jpeg;base64,' . base64_encode($album['logo'])
                : $album['logo'];
        }

        // Include images 1-10
        for ($i = 1; $i <= 10; $i++) {
            $key = 'image' . $i;
            if (!empty($album[$key])) {
                $images[$key] = $asBase64
                    ? 'data:image/jpeg;base64,' . base64_encode($album[$key])
                    : $album[$key];
            }
        }
    }

    return $images;
}

// ============================================================
// SERVICE FUNCTIONS
// ============================================================

// Get services for a business
function getBusinessServices($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE business_id = ? ORDER BY service_id DESC");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get all services
function getAllServices() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT s.*, b.business_name, b.city FROM services s JOIN businesses b ON s.business_id = b.business_id ORDER BY s.service_id DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get service by ID
function getServiceById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Create service
function createService($data) {
    $conn = getDbConnection();

    // Duration stored as INTEGER (minutes)
    $duration = is_numeric($data['duration']) ? intval($data['duration']) : 0;

    $stmt = $conn->prepare("
        INSERT INTO services 
        (business_id, service_name, service_type, service_desc, cost, duration)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdi",
        $data['business_id'], 
        $data['service_name'], 
        $data['service_type'] ?? '', 
        $data['service_desc'] ?? '', 
        $data['cost'] ?? 0, 
        $duration
    );

    if ($stmt->execute()) {
        $serviceId = $conn->insert_id;
        $stmt->close();
        return $serviceId;
    }

    error_log("Service creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Update service
function updateService($id, $data) {
    $conn = getDbConnection();
    
    // Duration stored as INTEGER (minutes)
    $duration = is_numeric($data['duration']) ? intval($data['duration']) : 0;
    
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, service_type = ?, service_desc = ?, cost = ?, duration = ? WHERE service_id = ?");
    $stmt->bind_param("sssdii",
        $data['service_name'],
        $data['service_type'] ?? '',
        $data['service_desc'] ?? '',
        $data['cost'] ?? 0,
        $duration,
        $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Delete service
function deleteService($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// ============================================================
// EMPLOYEE FUNCTIONS (formerly staff)
// ============================================================

// Get employees for a business
function getBusinessEmployees($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE business_id = ? ORDER BY employ_id DESC");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get employee by ID
function getEmployeeById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employ_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Create employee
function createEmployee($data) {
    $conn = getDbConnection();

    // Handle image from both sources
    $photo = null;
    $hasPhoto = false;
    
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasPhoto = true;
    } elseif (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
        $photo = file_get_contents($_FILES['employ_img']['tmp_name']);
        $hasPhoto = true;
    }

    $stmt = $conn->prepare("
        INSERT INTO employees 
        (service_id, business_id, employ_fname, employ_lname, employ_bio, specialization, skills, employ_status, employ_img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $null = null;
    $serviceId = $data['service_id'] ?? null;
    $status = $data['employ_status'] ?? 'available';
    
    $stmt->bind_param("iisssssss",
        $serviceId,
        $data['business_id'],
        $data['employ_fname'] ?? '',
        $data['employ_lname'] ?? '',
        $data['employ_bio'] ?? '',
        $data['specialization'] ?? '',
        $data['skills'] ?? '',
        $status,
        $null
    );

    if ($hasPhoto) {
        $stmt->send_long_data(8, $photo);
    }

    if ($stmt->execute()) {
        $employeeId = $conn->insert_id;
        $stmt->close();
        return $employeeId;
    }

    error_log("Employee creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Update employee
function updateEmployee($id, $data) {
    $conn = getDbConnection();

    // Handle image update
    $photo = null;
    $hasNewPhoto = false;
    
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasNewPhoto = true;
    } elseif (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
        $photo = file_get_contents($_FILES['employ_img']['tmp_name']);
        $hasNewPhoto = true;
    }

    if ($hasNewPhoto) {
        // Update WITH image
        $stmt = $conn->prepare("
            UPDATE employees 
            SET employ_fname = ?, employ_lname = ?, employ_bio = ?, 
                specialization = ?, skills = ?, employ_status = ?, employ_img = ? 
            WHERE employ_id = ?
        ");
        
        $null = null;
        $status = $data['employ_status'] ?? 'available';
        
        $stmt->bind_param("ssssssi",
            $data['employ_fname'] ?? '',
            $data['employ_lname'] ?? '',
            $data['employ_bio'] ?? '',
            $data['specialization'] ?? '',
            $data['skills'] ?? '',
            $status,
            $null,
            $id
        );
        
        $stmt->send_long_data(6, $photo);
    } else {
        // Update WITHOUT changing image
        $stmt = $conn->prepare("
            UPDATE employees 
            SET employ_fname = ?, employ_lname = ?, employ_bio = ?, 
                specialization = ?, skills = ?, employ_status = ?
            WHERE employ_id = ?
        ");
        
        $status = $data['employ_status'] ?? 'available';
        
        $stmt->bind_param("sssssi",
            $data['employ_fname'] ?? '',
            $data['employ_lname'] ?? '',
            $data['employ_bio'] ?? '',
            $data['specialization'] ?? '',
            $data['skills'] ?? '',
            $status,
            $id
        );
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Delete employee
function deleteEmployee($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM employees WHERE employ_id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// ============================================================
// REVIEW FUNCTIONS
// ============================================================

// Get reviews for a business (with review images)
function getBusinessReviews($businessId) {  
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            r.review_id,
            r.customer_id,
            r.business_id,
            r.review_date,
            r.rating,
            r.review_text,
            r.review_img1,
            r.review_img2,
            r.review_img3,
            r.review_img4,
            r.review_img5,
            c.fname AS customer_fname,
            c.surname AS customer_lname
        FROM reviews r
        JOIN customers c ON r.customer_id = c.customer_id
        WHERE r.business_id = ?
        ORDER BY r.review_date DESC
    ");

    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Gather review images into an array (filter out empty slots)
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = "review_img$i";
            if (!empty($row[$key])) {
                // Convert binary to base64 for easy frontend display
                $images[] = 'data:image/jpeg;base64,' . base64_encode($row[$key]);
            }
            unset($row[$key]); // remove raw blobs to avoid heavy payload
        }

        $row['images'] = $images;
        $reviews[] = $row;
    }

    $stmt->close();
    return $reviews;
}

// Create review (with up to 5 images)
function createReview($data) {
    $conn = getDbConnection();

    // Extract and prepare images (binary data or base64-decoded)
    $images = [];
    for ($i = 1; $i <= 5; $i++) {
        $key = "review_img$i";
        $images[$i] = !empty($data[$key]) ? $data[$key] : null;
    }

    // Prepare SQL insert query including image columns
    $stmt = $conn->prepare("
        INSERT INTO reviews (
            business_id, customer_id, rating, review_text, review_img1, review_img2, review_img3, review_img4, review_img5, review_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // Initial binding (use 's' for BLOB)
    $null = null;
    $stmt->bind_param(
        "iiissssss",
        $data['business_id'],
        $data['customer_id'],
        $data['rating'] ?? null,
        $data['review_text'] ?? '',
        $null, $null, $null, $null, $null
    );

    // Handle large image data properly
    foreach (range(1, 5) as $index) {
        if (!empty($images[$index])) {
            $stmt->send_long_data($index + 3, $images[$index]); 
        }
    }

    if ($stmt->execute()) {
        $reviewId = $conn->insert_id;
        $stmt->close();
        return $reviewId;
    }

    error_log("Review creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Calculate average rating from reviews table
function calculateAverageRating($businessId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row && $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
}

// ============================================================
// APPOINTMENT FUNCTIONS (formerly bookings)
// ============================================================

// Get appointments for a specific customer
function getCustomerAppointments($customerId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            a.*, 
            e.employ_fname AS staff_fname, 
            e.employ_lname AS staff_lname, 
            e.specialization, 
            s.service_name, 
            s.cost, 
            s.duration, 
            b.business_name, 
            b.business_address
        FROM appointments a
        LEFT JOIN employees e ON a.employ_id = e.employ_id
        LEFT JOIN services s ON a.service_id = s.service_id
        LEFT JOIN businesses b ON s.business_id = b.business_id
        WHERE a.customer_id = ?
        ORDER BY a.appoint_date DESC
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get appointments for a business
function getBusinessAppointments($businessId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            a.*, 
            c.fname AS customer_fname, 
            c.surname AS customer_lname,
            c.cstmr_num AS customer_phone, 
            s.service_name, 
            s.cost, 
            s.duration, 
            e.employ_fname AS staff_fname, 
            e.employ_lname AS staff_lname
        FROM appointments a
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        LEFT JOIN employees e ON a.employ_id = e.employ_id
        LEFT JOIN services s ON a.service_id = s.service_id
        WHERE e.business_id = ?
        ORDER BY a.appoint_date DESC
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get appointment by ID
function getAppointmentById($appointmentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

// Create a new appointment - WITH BUSINESS NOTIFICATION
function createAppointment($data) {
    $conn = getDbConnection();

    // Validate customer exists
    $customer = getCustomerById($data['customer_id']);
    if (!$customer) {
        error_log("Invalid customer_id: " . $data['customer_id']);
        return false;
    }
    
    // Validate employee exists (if provided) and get business_id
    $employeeId = null;
    $businessId = null;
    
    if (!empty($data['employ_id'])) {
        $employee = getEmployeeById($data['employ_id']);
        if (!$employee) {
            error_log("Invalid employ_id: " . $data['employ_id']);
            return false;
        }
        $employeeId = $data['employ_id'];
        $businessId = $employee['business_id']; // Get business ID from employee
    }

    // Validate service_id exists and get business_id if not already set
    if (!empty($data['service_id'])) {
        $service = getServiceById($data['service_id']);
        if (!$service) {
            error_log("Invalid service_id: " . $data['service_id']);
            return false;
        }
        // Get business ID from service if not already set
        if (!$businessId) {
            $businessId = $service['business_id'];
        }
    }

    // Combine date and time if provided separately
    $appointDate = $data['appoint_date'] 
        ?? (($data['booking_date'] ?? '') . ' ' . ($data['booking_time'] ?? ''));

    $customerId = $data['customer_id'];
    $serviceId = !empty($data['service_id']) ? $data['service_id'] : null;
    $status = $data['appoint_status'] ?? 'pending';
    $notes = $data['appoint_desc'] ?? '';

    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments 
        (customer_id, employ_id, service_id, appoint_date, appoint_status, appoint_desc)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisss",
        $customerId,
        $employeeId,
        $serviceId,
        $appointDate,
        $status,
        $notes
    );

    if ($stmt->execute()) {
        $appointmentId = $conn->insert_id;
        $stmt->close();
        
        // ✅ NOTIFY BUSINESS about new booking
        if ($businessId) {
            createBusinessBookingNotification($businessId, $customerId, $appointmentId);
        }
        
        return $appointmentId;
    }

    error_log("Appointment creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Update appointment status
function updateAppointmentStatus($id, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE appointments SET appoint_status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Delete appointment
function deleteAppointment($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $id); 
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Cancel appointment with business notification
function cancelAppointment($appointmentId) {
    $conn = getDbConnection();
    
    // Get appointment details before canceling
    $appointment = getAppointmentById($appointmentId);
    if (!$appointment) {
        return false;
    }
    
    // Get business ID from employee
    $employee = getEmployeeById($appointment['employ_id']);
    $businessId = $employee ? $employee['business_id'] : null;
    
    // Update appointment status to cancelled
    $stmt = $conn->prepare("UPDATE appointments SET appoint_status = 'cancelled' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointmentId);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Notify business about cancellation
        if ($businessId) {
            createBusinessCancellationNotification($businessId, $appointment['customer_id'], $appointmentId);
        }
        
        // Also notify customer
        createAppointmentNotification(
            $appointment['customer_id'],
            $businessId,
            $appointmentId,
            'cancelled'
        );
        
        return true;
    }
    
    $stmt->close();
    return false;
}

// ============================================================
// NOTIFICATION FUNCTIONS
// ============================================================

// Get customer notifications (ONLY customer-related, NOT business bookings)
function getCustomerNotifications($customerId, $limit = null) {
    $conn = getDbConnection();
    
    // Exclude "New Booking Request" and "Booking Cancelled by Customer" - those are for business only
    $sql = "SELECT * FROM notifications 
            WHERE customer_id = ? 
            AND notif_title NOT LIKE '%New Booking%' 
            AND notif_title NOT LIKE '%Cancelled by Customer%'
            ORDER BY notif_creation DESC";
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($limit) {
        $stmt->bind_param("ii", $customerId, $limit);
    } else {
        $stmt->bind_param("i", $customerId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Count unread notifications (last 24 hours) - ONLY customer notifications
function countUnreadNotifications($customerId) {
    $conn = getDbConnection();
    
    // Exclude business-only notifications
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE customer_id = ? 
        AND notif_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND notif_title NOT LIKE '%New Booking%'
        AND notif_title NOT LIKE '%Cancelled by Customer%'
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Create notification for appointment status change
function createAppointmentNotification($customerId, $businessId, $appointmentId, $status) {
    $conn = getDbConnection();
    
    // Get appointment details
    $appointment = getAppointmentById($appointmentId);
    if (!$appointment) return false;
    
    // Create notification title and text based on status
    $titles = [
        'confirmed' => 'Appointment Confirmed',
        'cancelled' => 'Appointment Cancelled',
        'completed' => 'Appointment Completed'
    ];
    
    $texts = [
        'confirmed' => 'Your appointment has been confirmed! We look forward to seeing you.',
        'cancelled' => 'Your appointment has been cancelled. Please contact us if you have any questions.',
        'completed' => 'Thank you for visiting us! We hope you enjoyed your service.'
    ];
    
    $title = $titles[$status] ?? 'Appointment Update';
    $text = $texts[$status] ?? 'Your appointment status has been updated.';
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get business notifications (reusing notifications table)
function getBusinessNotifications($businessId, $limit = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT n.*, c.fname, c.surname 
            FROM notifications n
            LEFT JOIN customers c ON n.customer_id = c.customer_id
            WHERE n.business_id = ? 
            ORDER BY n.notif_creation DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($limit) {
        $stmt->bind_param("ii", $businessId, $limit);
    } else {
        $stmt->bind_param("i", $businessId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Count recent business notifications (last 24 hours with "New Booking")
function countRecentBusinessNotifications($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE business_id = ? 
        AND notif_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND notif_title LIKE '%New Booking%'
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Create notification when customer books appointment
function createBusinessBookingNotification($businessId, $customerId, $appointmentId) {
    $conn = getDbConnection();
    
    // Get customer and appointment details
    $customer = getCustomerById($customerId);
    $appointment = getAppointmentById($appointmentId);
    
    if (!$customer || !$appointment) return false;
    
    $customerName = trim($customer['fname'] . ' ' . ($customer['surname'] ?? ''));
    $appointDate = date('F j, Y g:i A', strtotime($appointment['appoint_date']));
    
    $title = 'New Booking Request';
    $text = "{$customerName} has booked an appointment for {$appointDate}. Please review and confirm.";
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Create notification when customer cancels appointment
function createBusinessCancellationNotification($businessId, $customerId, $appointmentId) {
    $conn = getDbConnection();
    
    $customer = getCustomerById($customerId);
    $appointment = getAppointmentById($appointmentId);
    
    if (!$customer || !$appointment) return false;
    
    $customerName = trim($customer['fname'] . ' ' . ($customer['surname'] ?? ''));
    $appointDate = date('F j, Y g:i A', strtotime($appointment['appoint_date']));
    
    $title = 'Booking Cancelled by Customer';
    $text = "{$customerName} has cancelled their appointment scheduled for {$appointDate}.";
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (business_id, customer_id, notif_title, notif_text)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iiss", $businessId, $customerId, $title, $text);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Time ago function for notifications
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

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
        
        $stmt->close();
        
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

// Calculate distance between two coordinates (Haversine formula)
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

// Check if any user (customer or business) is logged in
function isLoggedIn() {
    return isset($_SESSION['customer_id']) || isset($_SESSION['business_id']);
}

// Check if a business is logged in
function isBusinessLoggedIn() {
    return isset($_SESSION['business_id']);
}

// Check if a customer is logged in
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

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

// Sanitize data
function sanitize($data) {
    // Handle null or non-string values
    if ($data === null || $data === '') {
        return '';
    }
    
    // Convert to string if not already
    if (!is_string($data)) {
        $data = (string)$data;
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Format date (e.g., October 28, 2025)
function formatDate($date) {
    if (empty($date)) return '';
    return date('F j, Y', strtotime($date));
}

// Format time (e.g., 3:45 PM)
function formatTime($time) {
    if (empty($time)) return '';
    return date('g:i A', strtotime($time));
}

// Format datetime (e.g., October 28, 2025 3:45 PM)
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    return date('F j, Y g:i A', strtotime($datetime));
}

?>