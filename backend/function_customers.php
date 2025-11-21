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
function updateCustomer($id, $data) {
    $conn = getDbConnection();

    // Check if user wants to remove profile picture
    $removePhoto = isset($data['remove_profile_pic']) && $data['remove_profile_pic'];

    // Handle profile picture update with compression
    $hasNewPic = false;
    $profilePic = null;
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        // Compress the uploaded image
        $imageData = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $profilePic = compressImage($imageData, 800, 800, 85);
        $hasNewPic = true;
        
        error_log("Profile pic uploaded and compressed for customer update");
    }

    // Escape all string values
    $fname = $conn->real_escape_string($data['fname']);
    $mname = $conn->real_escape_string($data['mname'] ?? '');
    $surname = $conn->real_escape_string($data['surname'] ?? '');
    $cstmr_num = $conn->real_escape_string($data['cstmr_num'] ?? '');
    $cstmr_email = $conn->real_escape_string($data['cstmr_email'] ?? '');
    $cstmr_address = $conn->real_escape_string($data['cstmr_address'] ?? '');
    $id = (int)$id;

    // Update customers table
    if ($removePhoto) {
        // Remove profile picture (set to NULL)
        $sql = "UPDATE customers 
                SET fname = '$fname', 
                    mname = '$mname', 
                    surname = '$surname', 
                    cstmr_num = '$cstmr_num', 
                    cstmr_email = '$cstmr_email', 
                    cstmr_address = '$cstmr_address', 
                    profile_pic = NULL
                WHERE customer_id = $id";
        
        $success1 = $conn->query($sql);
        
        if (!$success1) {
            error_log("Customer update (remove photo) failed: " . $conn->error);
        } else {
            error_log("Profile picture removed for customer ID: $id");
        }
        
    } elseif ($hasNewPic) {
        // Update with new profile picture
        $profilePicEscaped = $conn->real_escape_string($profilePic);
        
        $sql = "UPDATE customers 
                SET fname = '$fname', 
                    mname = '$mname', 
                    surname = '$surname', 
                    cstmr_num = '$cstmr_num', 
                    cstmr_email = '$cstmr_email', 
                    cstmr_address = '$cstmr_address', 
                    profile_pic = '$profilePicEscaped'
                WHERE customer_id = $id";
        
        $success1 = $conn->query($sql);
        
        if (!$success1) {
            error_log("Customer update failed: " . $conn->error);
        }
        
    } else {
        // Update without changing profile picture
        $stmt1 = $conn->prepare("
            UPDATE customers 
            SET fname = ?, mname = ?, surname = ?, cstmr_num = ?, cstmr_email = ?, cstmr_address = ?
            WHERE customer_id = ?
        ");
        $stmt1->bind_param("ssssssi",
            $data['fname'],
            $data['mname'],
            $data['surname'],
            $data['cstmr_num'],
            $data['cstmr_email'],
            $data['cstmr_address'],
            $id
        );
        $success1 = $stmt1->execute();
        
        if (!$success1) {
            error_log("Customer update failed: " . $stmt1->error);
        }
        
        $stmt1->close();
    }

    // Prepare variables for profiles
    $face_shape = $data['face_shape'] ?? '';
    $body_type = $data['body_type'] ?? '';
    $eye_color = $data['eye_color'] ?? '';
    $skin_tone = $data['skin_tone'] ?? '';
    $hair_type = $data['hair_type'] ?? '';
    $hair_color = $data['hair_color'] ?? '';
    $current_hair_length = $data['current_hair_length'] ?? '';
    $desired_hair_length = $data['desired_hair_length'] ?? '';

    // Check if profile exists
    $checkStmt = $conn->prepare("SELECT profile_id FROM profiles WHERE customer_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $profileExists = $result->num_rows > 0;
    $checkStmt->close();

    if ($profileExists) {
        // Update existing profile
        $stmt2 = $conn->prepare("
            UPDATE profiles 
            SET face_shape = ?, body_type = ?, eye_color = ?, skin_tone = ?, hair_type = ?, hair_color = ?, current_hair_length = ?, desired_hair_length = ?
            WHERE customer_id = ?
        ");
        $stmt2->bind_param("ssssssssi",
            $face_shape,
            $body_type,
            $eye_color,
            $skin_tone,
            $hair_type,
            $hair_color,
            $current_hair_length,
            $desired_hair_length,
            $id
        );
    } else {
        // Insert new profile
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
    }

    $success2 = $stmt2->execute();
    
    if (!$success2) {
        error_log("Profile update failed: " . $stmt2->error);
    }
    
    $stmt2->close();

    return $success1 && $success2;
}
?>