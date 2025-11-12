<?php
require_once 'config.php';
require_once 'functions.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $businessId = $_POST['business_id'] ?? '';
    
    if ($action === 'add') {
        $employeeName = $_POST['employee_name'] ?? '';
        $specialization = $_POST['specialization'] ?? '';
        $photoPath = null;
        
        // Handle file upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                header('Location: business-dashboard.php');
                exit;
            }
            
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File size must be less than 5MB.';
                header('Location: business-dashboard.php');
                exit;
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/staff/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'staff_' . $businessId . '_' . time() . '_' . uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $photoPath = $targetPath;
            } else {
                $_SESSION['error'] = 'Failed to upload photo.';
                header('Location: business-dashboard.php');
                exit;
            }
        }
        
        // Insert staff member into database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO employee (business_id, employee_name, specialization, photo) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $businessId,
                $employeeName,
                $specialization,
                $photoPath
            ]);
            
            $_SESSION['success'] = 'Staff member added successfully!';
        } catch (PDOException $e) {
            // If database insert fails and photo was uploaded, delete the photo
            if ($photoPath && file_exists($photoPath)) {
                unlink($photoPath);
            }
            $_SESSION['error'] = 'Failed to add staff member: ' . $e->getMessage();
        }
        
    } elseif ($action === 'delete') {
        $employeeId = $_POST['employee_id'] ?? '';
        
        try {
            // Get photo path before deleting
            $stmt = $pdo->prepare("SELECT photo FROM employee WHERE employee_id = ? AND business_id = ?");
            $stmt->execute([$employeeId, $businessId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM employee WHERE employee_id = ? AND business_id = ?");
            $stmt->execute([$employeeId, $businessId]);
            
            // Delete photo file if it exists
            if ($employee && $employee['photo'] && file_exists($employee['photo'])) {
                unlink($employee['photo']);
            }
            
            $_SESSION['success'] = 'Staff member removed successfully!';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Failed to remove staff member: ' . $e->getMessage();
        }
    }
}

header('Location: business-dashboard.php');
exit;
?>