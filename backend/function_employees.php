<?php
// ============================================================
// EMPLOYEE FUNCTIONS - FIXED VERSION
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
    
    // Check if image data is provided directly in $data array
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasPhoto = true;
        error_log("Image data provided in array. Size: " . strlen($photo) . " bytes");
    }

    $serviceId = $data['service_id'] ?? null;
    $businessId = $data['business_id'];
    $employFname = $data['employ_fname'] ?? '';
    $employLname = $data['employ_lname'] ?? '';
    $employBio = $data['employ_bio'] ?? '';
    $specialization = $data['specialization'] ?? '';
    $skills = $data['skills'] ?? '';
    $status = $data['employ_status'] ?? 'available';

    if ($hasPhoto) {
        // Insert with image - FIXED: Proper blob handling
        $stmt = $conn->prepare("
            INSERT INTO employees 
            (service_id, business_id, employ_fname, employ_lname, employ_bio, specialization, skills, employ_status, employ_img)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Bind parameters with 'b' for blob at the end
        $stmt->bind_param("iissssssb",
            $serviceId,
            $businessId,
            $employFname,
            $employLname,
            $employBio,
            $specialization,
            $skills,
            $status,
            $photo  // This will be a placeholder
        );

        // CRITICAL FIX: send_long_data parameter index is 8 (0-indexed, 9th parameter)
        $stmt->send_long_data(8, $photo);
        
    } else {
        // Insert without image
        $stmt = $conn->prepare("
            INSERT INTO employees 
            (service_id, business_id, employ_fname, employ_lname, employ_bio, specialization, skills, employ_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iissssss",
            $serviceId,
            $businessId,
            $employFname,
            $employLname,
            $employBio,
            $specialization,
            $skills,
            $status
        );
    }

    if ($stmt->execute()) {
        $employeeId = $conn->insert_id;
        error_log("Employee created successfully with ID: " . $employeeId);
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
    
    // Check if image data is provided directly in $data array
    if (isset($data['employ_img']) && !empty($data['employ_img'])) {
        $photo = $data['employ_img'];
        $hasNewPhoto = true;
        error_log("Updating employee $id with new image. Size: " . strlen($photo) . " bytes");
    }

    $employFname = $data['employ_fname'] ?? '';
    $employLname = $data['employ_lname'] ?? '';
    $employBio = $data['employ_bio'] ?? '';
    $specialization = $data['specialization'] ?? '';
    $skills = $data['skills'] ?? '';
    $status = $data['employ_status'] ?? 'available';

    if ($hasNewPhoto) {
        // Update with new image - FIXED: Proper blob handling
        $stmt = $conn->prepare("
            UPDATE employees 
            SET employ_fname = ?, employ_lname = ?, employ_bio = ?, 
                specialization = ?, skills = ?, employ_status = ?, employ_img = ? 
            WHERE employ_id = ?
        ");
        
        // Bind parameters with 'b' for blob
        $stmt->bind_param("ssssssbi",
            $employFname,
            $employLname,
            $employBio,
            $specialization,
            $skills,
            $status,
            $photo,  // This will be a placeholder
            $id
        );
        
        // CRITICAL FIX: send_long_data parameter index is 6 (0-indexed, 7th parameter)
        $stmt->send_long_data(6, $photo);
        
    } else {
        // Update without changing image
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
    
    if ($success) {
        error_log("Employee $id updated successfully");
    } else {
        error_log("Employee update failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

// Delete employee
function deleteEmployee($id) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, set employ_id to NULL in all appointments associated with this employee
        $stmt1 = $conn->prepare("UPDATE appointments SET employ_id = NULL WHERE employ_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Now delete the employee
        $stmt2 = $conn->prepare("DELETE FROM employees WHERE employ_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $success = $stmt2->affected_rows > 0;
        $stmt2->close();
        
        // Commit transaction
        $conn->commit();
        
        if ($success) {
            error_log("Employee $id deleted successfully");
        }
        
        return $success;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Employee deletion failed: " . $e->getMessage());
        return false;
    }
}