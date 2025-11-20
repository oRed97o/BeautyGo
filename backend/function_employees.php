<?php
// ============================================================
// EMPLOYEE FUNCTIONS
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

    $photo = null;
    $hasPhoto = false;
    
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasPhoto = true;
    } elseif (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
        $photo = file_get_contents($_FILES['employ_img']['tmp_name']);
        $hasPhoto = true;
    }

    $serviceId = $data['service_id'] ?? null;
    $businessId = $data['business_id'];
    $employFname = $data['employ_fname'] ?? '';
    $employLname = $data['employ_lname'] ?? '';
    $employBio = $data['employ_bio'] ?? '';
    $specialization = $data['specialization'] ?? '';
    $skills = $data['skills'] ?? '';
    $status = $data['employ_status'] ?? 'available';
    $null = null;

    $stmt = $conn->prepare("
        INSERT INTO employees 
        (service_id, business_id, employ_fname, employ_lname, employ_bio, specialization, skills, employ_status, employ_img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("iissssss",
        $serviceId,
        $businessId,
        $employFname,
        $employLname,
        $employBio,
        $specialization,
        $skills,
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

    $photo = null;
    $hasNewPhoto = false;
    
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasNewPhoto = true;
    } elseif (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
        $photo = file_get_contents($_FILES['employ_img']['tmp_name']);
        $hasNewPhoto = true;
    }

    $employFname = $data['employ_fname'] ?? '';
    $employLname = $data['employ_lname'] ?? '';
    $employBio = $data['employ_bio'] ?? '';
    $specialization = $data['specialization'] ?? '';
    $skills = $data['skills'] ?? '';
    $status = $data['employ_status'] ?? 'available';
    $null = null;

    if ($hasNewPhoto) {
        $stmt = $conn->prepare("
            UPDATE employees 
            SET employ_fname = ?, employ_lname = ?, employ_bio = ?, 
                specialization = ?, skills = ?, employ_status = ?, employ_img = ? 
            WHERE employ_id = ?
        ");
        
        $stmt->bind_param("sssssssi",
            $employFname,
            $employLname,
            $employBio,
            $specialization,
            $skills,
            $status,
            $null,
            $id
        );
        
        $stmt->send_long_data(6, $photo);
    } else {
        $stmt = $conn->prepare("
            UPDATE employees 
            SET employ_fname = ?, employ_lname = ?, employ_bio = ?, 
                specialization = ?, skills = ?, employ_status = ?
            WHERE employ_id = ?
        ");
        
        $stmt->bind_param("ssssssi",
            $employFname,
            $employLname,
            $employBio,
            $specialization,
            $skills,
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