<?php
require_once '../db_connection/config.php';
require_once 'function_utilities.php';
require_once 'function_employees.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$businessId = $_SESSION['business_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Get form data
        $data = [
            'business_id' => $businessId,
            'employ_fname' => sanitize($_POST['employ_fname'] ?? ''),
            'employ_lname' => sanitize($_POST['employ_lname'] ?? ''),
            'employ_bio' => sanitize($_POST['employ_bio'] ?? ''),
            'specialization' => sanitize($_POST['specialization'] ?? ''),
            'skills' => sanitize($_POST['skills'] ?? ''),
            'employ_status' => $_POST['employ_status'] ?? 'available',
            'service_id' => !empty($_POST['service_id']) ? intval($_POST['service_id']) : null
        ];
        
        // Validate required fields
        if (empty($data['employ_fname']) || empty($data['employ_lname'])) {
            $_SESSION['error'] = 'First name and last name are required.';
            header('Location: ../business-dashboard.php');
            exit;
        }
        
        // Handle photo upload (stored as BLOB in database)
        if (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['employ_img'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                header('Location: ../business-dashboard.php');
                exit;
            }
            
            // Validate file size (5MB max)
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File size must be less than 5MB.';
                header('Location: ../business-dashboard.php');
                exit;
            }
            
            // Read file contents (will be stored as BLOB)
            $data['employ_img'] = file_get_contents($file['tmp_name']);
        }
        
        // Create employee
        $employeeId = createEmployee($data);
        
        if ($employeeId) {
            $_SESSION['success'] = 'Staff member added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add staff member. Please try again.';
        }
        
    } elseif ($action === 'edit') {
        $employId = intval($_POST['employ_id'] ?? 0);
        
        // Verify this employee belongs to the current business
        $employee = getEmployeeById($employId);
        if (!$employee || $employee['business_id'] != $businessId) {
            $_SESSION['error'] = 'Invalid employee or access denied.';
            header('Location: ../business-dashboard.php');
            exit;
        }
        
        // Get form data
        $data = [
            'employ_fname' => sanitize($_POST['employ_fname'] ?? ''),
            'employ_lname' => sanitize($_POST['employ_lname'] ?? ''),
            'employ_bio' => sanitize($_POST['employ_bio'] ?? ''),
            'specialization' => sanitize($_POST['specialization'] ?? ''),
            'skills' => sanitize($_POST['skills'] ?? ''),
            'employ_status' => $_POST['employ_status'] ?? 'available'
        ];
        
        // Validate required fields
        if (empty($data['employ_fname']) || empty($data['employ_lname'])) {
            $_SESSION['error'] = 'First name and last name are required.';
            header('Location: ../business-dashboard.php');
            exit;
        }
        
        // Handle photo upload if provided
        if (isset($_FILES['employ_img']) && $_FILES['employ_img']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['employ_img'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                header('Location: business-dashboard.php');
                exit;
            }
            
            // Validate file size
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File size must be less than 5MB.';
                header('Location: ../business-dashboard.php');
                exit;
            }
            
            // Read file contents
            $data['employ_img'] = file_get_contents($file['tmp_name']);
        }
        
        // Update employee
        $success = updateEmployee($employId, $data);
        
        if ($success) {
            $_SESSION['success'] = 'Staff member updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update staff member. Please try again.';
        }
        
    } elseif ($action === 'delete') {
        $employId = intval($_POST['employ_id'] ?? 0);
        
        // Verify this employee belongs to the current business
        $employee = getEmployeeById($employId);
        if (!$employee || $employee['business_id'] != $businessId) {
            $_SESSION['error'] = 'Invalid employee or access denied.';
            header('Location: ../business-dashboard.php');
            exit;
        }
        
        // Check if employee has any appointments
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE employ_id = ?");
        $stmt->bind_param("i", $employId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete staff member with existing appointments. Consider changing their status to "unavailable" instead.';
            header('Location: business-dashboard.php');
            exit;
        }
        
        // Delete employee
        $success = deleteEmployee($employId);
        
        if ($success) {
            $_SESSION['success'] = 'Staff member removed successfully!';
        } else {
            $_SESSION['error'] = 'Failed to remove staff member. Please try again.';
        }
        
    } elseif ($action === 'toggle_status') {
        $employId = intval($_POST['employ_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'available';
        
        // Verify this employee belongs to the current business
        $employee = getEmployeeById($employId);
        if (!$employee || $employee['business_id'] != $businessId) {
            $_SESSION['error'] = 'Invalid employee or access denied.';
            header('Location: ../business-dashboard.php');
            exit;
        }
        
        // Validate status
        $validStatuses = ['available', 'unavailable', 'on_leave'];
        if (!in_array($newStatus, $validStatuses)) {
            $newStatus = 'available';
        }
        
        // Update status
        $data = [
            'employ_fname' => $employee['employ_fname'],
            'employ_lname' => $employee['employ_lname'],
            'employ_bio' => $employee['employ_bio'],
            'specialization' => $employee['specialization'],
            'skills' => $employee['skills'],
            'employ_status' => $newStatus
        ];
        
        $success = updateEmployee($employId, $data);
        
        if ($success) {
            $_SESSION['success'] = 'Staff status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update staff status.';
        }
    }
}

header('Location: ../business-dashboard.php');
exit;
?>