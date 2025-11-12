<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessId = intval($_POST['business_id'] ?? 0);
    $customerId = intval($_POST['customer_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $reviewText = sanitize($_POST['review_text'] ?? '');
    
    // Validate required fields
    if (empty($businessId) || empty($customerId) || $rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Invalid review data';
        header('Location: my-bookings.php');
        exit;
    }
    
    // Verify the customer is the logged-in user
    $currentCustomer = getCurrentCustomer();
    if ($customerId != $currentCustomer['customer_id']) {
        $_SESSION['error'] = 'Unauthorized action';
        header('Location: my-bookings.php');
        exit;
    }
    
    // Verify business exists
    $business = getBusinessById($businessId);
    if (!$business) {
        $_SESSION['error'] = 'Invalid business';
        header('Location: my-bookings.php');
        exit;
    }
    
    // Handle review images (up to 5)
    $reviewImages = [];
    for ($i = 1; $i <= 5; $i++) {
        $fileKey = "review_img$i";
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                continue; // Skip invalid file types
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                continue; // Skip files that are too large
            }
            
            // Read file contents
            $reviewImages[$fileKey] = file_get_contents($file['tmp_name']);
        }
    }
    
    // Prepare review data
    $reviewData = [
        'business_id' => $businessId,
        'customer_id' => $customerId,
        'rating' => $rating,
        'review_text' => $reviewText
    ];
    
    // Add images to review data
    foreach ($reviewImages as $key => $imageData) {
        $reviewData[$key] = $imageData;
    }
    
    // Create review
    $reviewId = createReview($reviewData);
    
    if ($reviewId) {
        $_SESSION['success'] = 'Thank you for your review!';
    } else {
        $_SESSION['error'] = 'Failed to submit review. Please try again.';
    }
}

header('Location: my-bookings.php');
exit;
?>