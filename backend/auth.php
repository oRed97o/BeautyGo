<?php
require_once __DIR__ . '/../db_connection/config.php';
require_once __DIR__ . '/function_utilities.php';
require_once __DIR__ . '/function_customers.php';
require_once __DIR__ . '/function_businesses.php';
require_once __DIR__ . '/function_albums.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$conn = getDbConnection();

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
// Register New Customer - FIXED
// ==========================
function registerUser() {
    global $conn;
    
    $fname = sanitize($_POST['fname']);
    $mname = sanitize($_POST['mname'] ?? '');
    $surname = sanitize($_POST['surname']);
    $cstmr_email = sanitize($_POST['cstmr_email']);
    $cstmr_num = sanitize($_POST['cstmr_num']);
    $cstmr_address = sanitize($_POST['cstmr_address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get latitude and longitude - CRITICAL FIX
    $latitude = isset($_POST['customer_latitude']) ? floatval($_POST['customer_latitude']) : 14.0697;
    $longitude = isset($_POST['customer_longitude']) ? floatval($_POST['customer_longitude']) : 120.6328;
    
    error_log("Registration attempt - Email: $cstmr_email, Lat: $latitude, Lng: $longitude");

    // Validation
    if (empty($fname) || empty($surname) || empty($cstmr_email) || empty($cstmr_num) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit;
    }

    if (!isValidEmail($cstmr_email)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }
    
    // Validate coordinates are set properly
    if ($latitude == 0 || $longitude == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please pin your location on the map']);
        exit;
    }

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT customer_id FROM customers WHERE cstmr_email = ?");
    if (!$checkEmail) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        exit;
    }

    $checkEmail->bind_param('s', $cstmr_email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
        $checkEmail->close();
        exit;
    }
    $checkEmail->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create POINT geometry - Format: POINT(longitude latitude)
    $point = "POINT(" . $longitude . " " . $latitude . ")";

    error_log("Registering customer with geometry: " . $point);
    error_log("Lat: " . $latitude . ", Lng: " . $longitude);

    // Insert with latitude and longitude columns
    $query = "INSERT INTO customers 
              (fname, mname, surname, cstmr_email, cstmr_num, cstmr_address, cstmr_password, 
               latitude, longitude, customer_location) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
        exit;
    }

    $stmt->bind_param('sssssssdds', 
        $fname, $mname, $surname, $cstmr_email, $cstmr_num, 
        $cstmr_address, $hashedPassword, 
        $latitude, $longitude, $point
    );

    if ($stmt->execute()) {
        $customerId = $stmt->insert_id;
        error_log("Customer registered successfully with ID: " . $customerId);

        // Handle profile picture if uploaded
        if (!empty($_POST['cropped_image_data'])) {
            $imageData = $_POST['cropped_image_data'];
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = base64_decode($imageData);

            $updatePic = $conn->prepare("UPDATE customers SET profile_pic = ? WHERE customer_id = ?");
            if ($updatePic) {
                $updatePic->bind_param('si', $imageData, $customerId);
                $updatePic->execute();
                $updatePic->close();
            }
        }

        // Set session variables
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_email'] = $cstmr_email;
        $_SESSION['user_type'] = 'customer';
        $_SESSION['user_latitude'] = $latitude;
        $_SESSION['user_longitude'] = $longitude;
        $_SESSION['user_address'] = $cstmr_address;
        $_SESSION['success'] = 'Registration successful! Welcome to BeautyGo.';

        echo json_encode([
            'status' => 'success', 
            'message' => 'Registration successful', 
            'redirect' => 'index.php'
        ]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $stmt->error]);
    }
    $stmt->close();
}
// ==========================
// Register New Business
// ==========================
function registerBusiness() {
    $email = sanitize($_POST['business_email']);
    $password = $_POST['business_password'] ?? '';
    $businessName = sanitize($_POST['business_name'] ?? '');
    $businessType = $_POST['business_type'] ?? '';
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

    // Get coordinates and validate they're not default/empty
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 14.0697;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 120.6328;

    // Log coordinates for debugging
    error_log("Business Registration - Latitude: $latitude, Longitude: $longitude");

    $businessData = [
        'business_email' => $email,
        'business_password' => $password,
        'business_name' => $businessName,
        'business_type' => $businessType,
        'business_desc' => sanitize($_POST['business_desc'] ?? $_POST['description'] ?? ''),
        'business_address' => sanitize($_POST['business_address'] ?? $_POST['address'] ?? ''),
        'business_num' => sanitize($_POST['business_num'] ?? ''),
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'opening_hour' => $_POST['opening_hour'] ?? '09:00',
        'closing_hour' => $_POST['closing_hour'] ?? '18:00'
    ];

    $businessId = createBusiness($businessData);

    if ($businessId) {
        // Handle logo upload if provided
        $logoData = null;

        // Check for cropped logo data first (from the crop modal)
        if (!empty($_POST['cropped_logo_data'])) {
            $croppedData = $_POST['cropped_logo_data'];

            // Remove the data URL prefix (e.g., "data:image/jpeg;base64,")
            if (preg_match('/^data:image\/(\w+);base64,/', $croppedData)) {
                $croppedData = substr($croppedData, strpos($croppedData, ',') + 1);
                $croppedData = base64_decode($croppedData);

                if ($croppedData !== false) {
                    $logoData = $croppedData;
                    error_log("Using cropped business logo");
                }
            }
        }
        // Fallback to regular file upload if no cropped data
        elseif (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

            if (in_array($_FILES['business_logo']['type'], $allowedTypes) &&
                $_FILES['business_logo']['size'] <= 5 * 1024 * 1024) {

                $logoData = file_get_contents($_FILES['business_logo']['tmp_name']);
                error_log("Using uploaded file business logo");
            }
        }

        // If we have logo data, compress and save it
        if ($logoData !== null) {
            $compressedLogo = compressImage($logoData, 800, 800, 85);
            updateSingleAlbumImage($businessId, 'logo', $compressedLogo);
        }

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
// Login - Auto-detect user type
// ==========================
function login() {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // First, try to find if email belongs to a business
    $business = getBusinessByEmail($email);
    if ($business && password_verify($password, $business['business_password'])) {
        $_SESSION['business_id'] = $business['business_id'];
        $_SESSION['user_type'] = 'business';
        $_SESSION['success'] = 'Welcome back, ' . $business['business_name'] . '!';
        header('Location: ../business-dashboard.php');
        exit;
    }

    // Otherwise, try to find if email belongs to a customer
    $customer = getCustomerByEmail($email);
    if ($customer && password_verify($password, $customer['cstmr_password'])) {
        $_SESSION['customer_id'] = $customer['customer_id'];
        $_SESSION['user_type'] = 'customer';
        $_SESSION['success'] = 'Welcome back, ' . $customer['fname'] . '!';
        header('Location: ../index.php');
        exit;
    }

    // Store failed login info to display in login form
    $_SESSION['error'] = 'Invalid email or password.';
    $_SESSION['failed_email'] = $email;
    $_SESSION['failed_type'] = ''; // No type selection anymore
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