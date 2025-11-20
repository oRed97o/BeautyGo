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

// Create new customer
function createCustomer($data) {
    $conn = getDbConnection();
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Handle profile picture from BOTH sources
    $profilePic = null;
    $hasProfilePic = false;
    
    if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
        $profilePic = $data['profile_pic'];
        $hasProfilePic = true;
    }
    elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $profilePic = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $hasProfilePic = true;
    }

    // Prepare variables for bind_param
    $fname = $data['fname'];
    $mname = $data['mname'] ?? '';
    $surname = $data['surname'] ?? '';
    $cstmr_num = $data['cstmr_num'] ?? '';
    $cstmr_email = $data['cstmr_email'] ?? '';
    $cstmr_address = $data['cstmr_address'] ?? '';
    $null = null;

    // Insert into customers table
    $stmt1 = $conn->prepare("
        INSERT INTO customers 
        (fname, mname, surname, cstmr_num, cstmr_email, cstmr_password, cstmr_address, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt1->bind_param("ssssssss",
        $fname,
        $mname,
        $surname,
        $cstmr_num,
        $cstmr_email,
        $hashedPassword,
        $cstmr_address,
        $null
    );

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

// Update customer and profile
function updateCustomer($id, $data) {
    $conn = getDbConnection();

    // Handle profile picture update
    $profilePic = null;
    $hasNewPic = false;
    
    if (isset($data['profile_pic']) && !empty($data['profile_pic'])) {
        $profilePic = $data['profile_pic'];
        $hasNewPic = true;
    } elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $profilePic = file_get_contents($_FILES['profile_pic']['tmp_name']);
        $hasNewPic = true;
    }

    // Prepare variables
    $fname = $data['fname'];
    $mname = $data['mname'] ?? '';
    $surname = $data['surname'] ?? '';
    $cstmr_num = $data['cstmr_num'] ?? '';
    $cstmr_email = $data['cstmr_email'] ?? '';
    $cstmr_address = $data['cstmr_address'] ?? '';
    $null = null;

    if ($hasNewPic) {
        $stmt1 = $conn->prepare("
            UPDATE customers 
            SET fname = ?, mname = ?, surname = ?, cstmr_num = ?, cstmr_email = ?, cstmr_address = ?, profile_pic = ?
            WHERE customer_id = ?
        ");
        
        $stmt1->bind_param("sssssssi",
            $fname,
            $mname,
            $surname,
            $cstmr_num,
            $cstmr_email,
            $cstmr_address,
            $null,
            $id
        );
        $stmt1->send_long_data(6, $profilePic);
    } else {
        $stmt1 = $conn->prepare("
            UPDATE customers 
            SET fname = ?, mname = ?, surname = ?, cstmr_num = ?, cstmr_email = ?, cstmr_address = ?
            WHERE customer_id = ?
        ");
        $stmt1->bind_param("ssssssi",
            $fname,
            $mname,
            $surname,
            $cstmr_num,
            $cstmr_email,
            $cstmr_address,
            $id
        );
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

    // Update profiles table
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

    $success1 = $stmt1->execute();
    $success2 = $stmt2->execute();
    
    $stmt1->close();
    $stmt2->close();

    return $success1 && $success2;
}
?>