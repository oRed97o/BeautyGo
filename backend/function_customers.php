<?php
// ============================================================
// CUSTOMER FUNCTIONS
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

// UPDATED createCustomer() with compression
function createCustomer($data) {
    $conn = getDbConnection();
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Handle profile picture with compression
    $hasProfilePic = false;
    $profilePic = null;
    
    if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
        // If profile_pic is already binary data
        $profilePic = compressImage($data['profile_pic'], 800, 800, 85);
        $hasProfilePic = true;
    }
    elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        // Compress the uploaded image
        $imageData = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $profilePic = compressImage($imageData, 800, 800, 85);
        $hasProfilePic = true;
        
        error_log("Profile pic uploaded and compressed for new customer");
    }

    // Escape all string values
    $fname = $conn->real_escape_string($data['fname']);
    $mname = $conn->real_escape_string($data['mname'] ?? '');
    $surname = $conn->real_escape_string($data['surname'] ?? '');
    $cstmr_num = $conn->real_escape_string($data['cstmr_num'] ?? '');
    $cstmr_email = $conn->real_escape_string($data['cstmr_email'] ?? '');
    $cstmr_address = $conn->real_escape_string($data['cstmr_address'] ?? '');
    $hashedPasswordEscaped = $conn->real_escape_string($hashedPassword);

    // Insert into customers table
    if ($hasProfilePic) {
        $profilePicEscaped = $conn->real_escape_string($profilePic);
        
        $sql = "INSERT INTO customers 
                (fname, mname, surname, cstmr_num, cstmr_email, cstmr_password, cstmr_address, profile_pic)
                VALUES ('$fname', '$mname', '$surname', '$cstmr_num', '$cstmr_email', '$hashedPasswordEscaped', '$cstmr_address', '$profilePicEscaped')";
        
        $success = $conn->query($sql);
        
        if (!$success) {
            error_log("Customer creation failed: " . $conn->error);
            return false;
        }
        
    } else {
        $stmt1 = $conn->prepare("
            INSERT INTO customers 
            (fname, mname, surname, cstmr_num, cstmr_email, cstmr_password, cstmr_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt1->bind_param("sssssss",
            $data['fname'],
            $data['mname'],
            $data['surname'],
            $data['cstmr_num'],
            $data['cstmr_email'],
            $hashedPassword,
            $data['cstmr_address']
        );

        if (!$stmt1->execute()) {
            error_log("Customer creation failed: " . $stmt1->error);
            $stmt1->close();
            return false;
        }
        
        $stmt1->close();
    }

    $id = $conn->insert_id;

    // Prepare variables for profiles table
    $face_shape = $data['face_shape'] ?? '';
    $body_type = $data['body_type'] ?? '';
    $eye_color = $data['eye_color'] ?? '';
    $skin_tone = $data['skin_tone'] ?? '';
    $hair_type = $data['hair_type'] ?? '';
    $hair_color = $data['hair_color'] ?? '';
    $current_hair_length = $data['current_hair_length'] ?? '';
    $desired_hair_length = $data['desired_hair_length'] ?? '';

    // Insert into profiles table
    $stmt2 = $conn->prepare("
        INSERT INTO profiles 
        (customer_id, face_shape, body_type, eye_color, skin_tone, hair_type, hair_color, current_hair_length, desired_hair_length)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("issssssss",
        $id,
        $face_shape,
        $body_type,
        $eye_color,
        $skin_tone,
        $hair_type,
        $hair_color,
        $current_hair_length,
        $desired_hair_length
    );

    if ($stmt2->execute()) {
        $stmt2->close();
        return $id;
    }

    error_log("Profile creation failed: " . $stmt2->error);
    $stmt2->close();
    return false;
}

// UPDATED updateCustomer() with compression and removal
function updateCustomer($customerId, $userData) {
    $conn = getDbConnection();
    
    // Build the update query
    $updates = [];
    $types = '';
    $values = [];
    
    if (isset($userData['fname'])) {
        $updates[] = "fname = ?";
        $types .= 's';
        $values[] = $userData['fname'];
    }
    if (isset($userData['mname'])) {
        $updates[] = "mname = ?";
        $types .= 's';
        $values[] = $userData['mname'];
    }
    if (isset($userData['surname'])) {
        $updates[] = "surname = ?";
        $types .= 's';
        $values[] = $userData['surname'];
    }
    if (isset($userData['cstmr_num'])) {
        $updates[] = "cstmr_num = ?";
        $types .= 's';
        $values[] = $userData['cstmr_num'];
    }
    if (isset($userData['cstmr_address'])) {
        $updates[] = "cstmr_address = ?";
        $types .= 's';
        $values[] = $userData['cstmr_address'];
    }
    
    // Handle geometry update
    if (isset($userData['latitude']) && isset($userData['longitude'])) {
        $updates[] = "customer_location = ST_GeomFromText(?)";
        $types .= 's';
        $lat = floatval($userData['latitude']);
        $lng = floatval($userData['longitude']);
        $point = "POINT(" . $lng . " " . $lat . ")";
        $values[] = $point;
        error_log("Updating customer location: " . $point . " for customer ID: " . $customerId);
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $values[] = $customerId;
    $types .= 'i';
    
    $query = "UPDATE customers SET " . implode(", ", $updates) . " WHERE customer_id = ?";
    
    error_log("Update query: " . $query);
    error_log("Update types: " . $types);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!$stmt->bind_param($types, ...$values)) {
        error_log("Bind param failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}
?>