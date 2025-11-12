<?php
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register_user') {
        // Get form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Beauty profile data (optional)
        $faceShape = $_POST['face_shape'] ?? null;
        $skinTone = $_POST['skin_tone'] ?? null;
        $bodyMass = $_POST['body_mass'] ?? null;
        $desiredHairLength = $_POST['desired_hair_length'] ?? null;
        $preferences = trim($_POST['preferences'] ?? '');
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($address)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header('Location: user-register.php');
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            header('Location: user-register.php');
            exit;
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters long.';
            header('Location: user-register.php');
            exit;
        }
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT customer_id FROM customer WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Email address already registered.';
                header('Location: user-register.php');
                exit;
            }
            
            // Handle profile picture upload
            $profilePicturePath = null;
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                
                // Validate file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                    header('Location: user-register.php');
                    exit;
                }
                
                if ($file['size'] > $maxSize) {
                    $_SESSION['error'] = 'File size must be less than 5MB.';
                    header('Location: user-register.php');
                    exit;
                }
                
                // Create uploads directory if it doesn't exist
                $uploadDir = 'uploads/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $profilePicturePath = $targetPath;
                } else {
                    $_SESSION['error'] = 'Failed to upload profile picture.';
                    header('Location: user-register.php');
                    exit;
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("
                INSERT INTO customer (
                    customer_name, email, password, phone, address,
                    face_shape, skin_tone, body_mass, desired_hair_length, 
                    preferences, profile_picture, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $name,
                $email,
                $hashedPassword,
                $phone,
                $address,
                $faceShape,
                $skinTone,
                $bodyMass,
                $desiredHairLength,
                $preferences,
                $profilePicturePath
            ]);
            
            // Get the newly created customer ID
            $customerId = $pdo->lastInsertId();
            
            // Set session variables for auto-login
            $_SESSION['user_id'] = $customerId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_type'] = 'customer';
            if ($profilePicturePath) {
                $_SESSION['user_profile_picture'] = $profilePicturePath;
            }
            
            $_SESSION['success'] = 'Registration successful! Welcome to BeautyGo!';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            // If database insert fails and profile picture was uploaded, delete it
            if (isset($profilePicturePath) && file_exists($profilePicturePath)) {
                unlink($profilePicturePath);
            }
            
            $_SESSION['error'] = 'Registration failed: ' . $e->getMessage();
            header('Location: user-register.php');
            exit;
        }
    }
    
    // Handle other authentication actions (login, logout, etc.)
    // ... rest of your auth.php code ...
}

header('Location: index.php');
exit;
?>