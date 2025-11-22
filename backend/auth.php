<?php
require_once __DIR__ . '/../db_connection/config.php';
require_once __DIR__ . '/function_utilities.php';
require_once __DIR__ . '/function_customers.php';
require_once __DIR__ . '/function_businesses.php';
require_once __DIR__ . '/function_albums.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register_user':
            registerUser();
            break;
        case 'register_business':
            registerBusiness();
            break;
        case 'login':
            login();
            break;
        case 'logout':
            logout();
            break;
    }
}

// ==========================
// Register New Customer
// ==========================
function registerUser() {
    $email = sanitize($_POST['cstmr_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fname = sanitize($_POST['fname'] ?? '');
    $surname = sanitize($_POST['surname'] ?? '');
    $address = sanitize($_POST['cstmr_address'] ?? '');
    $phone = sanitize($_POST['cstmr_num'] ?? '');

    // Required field validation
    if (empty($fname) || empty($email) || empty($password) || empty($phone)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: ../register-user.php');
        exit;
    }

    // Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: ../register-user.php');
        exit;
    }

    // Password strength
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long.';
        header('Location: ../register-user.php');
        exit;
    }

    // Check if email exists
    if (getCustomerByEmail($email)) {
        $_SESSION['error'] = 'Email already registered.';
        header('Location: ../register-user.php');
        exit;
    }

    // Handle profile picture upload (still stored as BLOB)
    $profilePic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB limit

        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            header('Location: ../register-user.php');
            exit;
        }

        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'Profile picture must be less than 5MB.';
            header('Location: ../register-user.php');
            exit;
        }

        $profilePic = file_get_contents($file['tmp_name']);
    }

    // Build customer data array
    $userData = [
        'fname' => $fname,
        'mname' => sanitize($_POST['mname'] ?? ''),
        'surname' => $surname,
        'cstmr_num' => $phone,
        'cstmr_email' => $email,
        'password' => $password,
        'cstmr_address' => $address,
        'face_shape' => $_POST['face_shape'] ?? '',
        'body_type' => $_POST['body_type'] ?? '',
        'eye_color' => $_POST['eye_color'] ?? '',
        'skin_tone' => $_POST['skin_tone'] ?? '',
        'hair_type' => $_POST['hair_type'] ?? '',
        'hair_color' => $_POST['hair_color'] ?? '',
        'current_hair_length' => $_POST['current_hair_length'] ?? '',
        'desired_hair_length' => $_POST['desired_hair_length'] ?? '',
        'profile_pic' => $profilePic
    ];

    // Insert new customer
    $customerId = createCustomer($userData);

    if ($customerId) {
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['user_type'] = 'customer';
        $_SESSION['success'] = 'Registration successful! Welcome to BeautyGo!';
        header('Location: ../index.php');
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: ../register-user.php');
    }
    exit;
}

// ==========================
// Register New Business
// ==========================
function registerBusiness() {
    $email = sanitize($_POST['business_email']);
    $password = $_POST['business_password'] ?? '';
    $businessName = sanitize($_POST['business_name'] ?? '');
    $businessType = sanitize($_POST['business_type'] ?? '');
    $city = sanitize($_POST['city'] ?? 'Nasugbu');

    // Validate required fields
    if (empty($businessName) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: ../register-business.php');
        exit;
    }

    // Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: ../register-business.php');
        exit;
    }

    // Password strength
    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
        header('Location: ../register-business.php');
        exit;
    }

    // Check if email exists
    if (getBusinessByEmail($email)) {
        $_SESSION['error'] = 'Email already registered.';
        header('Location: ../register-business.php');
        exit;
    }

    $businessData = [
        'business_email' => $email,
        'business_password' => $password,
        'business_name' => $businessName,
        'business_type' => $businessType,
        'business_desc' => sanitize($_POST['business_desc'] ?? $_POST['description'] ?? ''),
        'business_address' => sanitize($_POST['business_address'] ?? $_POST['address'] ?? ''),
        'business_num' => sanitize($_POST['business_num'] ?? ''),
        'city' => $city,
        'latitude' => $_POST['latitude'] ?? 14.0697,
        'longitude' => $_POST['longitude'] ?? 120.6328
    ];

    $businessId = createBusiness($businessData);

    if ($businessId) {
        // ===== ADD THIS SECTION HERE =====
        // Handle logo upload if provided
        if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (in_array($_FILES['business_logo']['type'], $allowedTypes) && 
                $_FILES['business_logo']['size'] <= 5 * 1024 * 1024) {
                
                $imageData = file_get_contents($_FILES['business_logo']['tmp_name']);
                $compressedLogo = compressImage($imageData, 800, 800, 85);
                
                // Update the album with the logo
                updateSingleAlbumImage($businessId, 'logo', $compressedLogo);
            }
        }
        // ===== END OF NEW SECTION =====
        
        $_SESSION['business_id'] = $businessId;
        $_SESSION['user_type'] = 'business';
        $_SESSION['success'] = 'Business registration successful!';
        header('Location: ../business-dashboard.php');
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header('Location: ../register-business.php');
    }
    exit;
}

// ==========================
// Login
// ==========================
function login() {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $type = $_POST['type'] ?? 'customer';

    if ($type === 'business') {
        $business = getBusinessByEmail($email);
        if ($business && password_verify($password, $business['business_password'])) {
            $_SESSION['business_id'] = $business['business_id'];
            $_SESSION['user_type'] = 'business';
            $_SESSION['success'] = 'Welcome back, ' . $business['business_name'] . '!';
            header('Location: ../business-dashboard.php');
            exit;
        }
    } else {
        $customer = getCustomerByEmail($email);
        if ($customer && password_verify($password, $customer['cstmr_password'])) {
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['user_type'] = 'customer';
            $_SESSION['success'] = 'Welcome back, ' . $customer['fname'] . '!';
            header('Location: ../index.php');
            exit;
        }
    }

    $_SESSION['error'] = 'Invalid email or password.';
    header('Location: ../login.php');
    exit;
}

// ==========================
// Logout
// ==========================
function logout() {
    session_start();
    session_unset();        // removes all session variables
    session_destroy();      // destroys session data
    setcookie(session_name(), '', time() - 3600, '/');  // remove session cookie

    header('Location: ../index.php');
    exit;
}

?>
