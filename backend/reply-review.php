<?php
require_once '../db_connection/config.php';
require_once 'function_utilities.php';
require_once 'function_customers.php';
require_once 'function_reviews.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    $_SESSION['error'] = 'Please login to reply to reviews';
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_review'])) {
    $reviewId = intval($_POST['review_id'] ?? 0);
    $replyText = sanitize($_POST['reply_text'] ?? '');
    $businessId = intval($_POST['business_id'] ?? 0);
    
    // Validate required fields
    if (empty($reviewId) || empty($replyText)) {
        $_SESSION['error'] = 'Invalid reply data';
        header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
        exit;
    }
    
    // Get current customer
    $currentCustomer = getCurrentCustomer();
    $customerId = $currentCustomer['customer_id'];
    
    // Process image if uploaded
    $replyImage = null;
    if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['reply_image'];
        
        // Validate file type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
            exit;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5242880) {
            $_SESSION['error'] = 'File size must be less than 5MB.';
            header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
            exit;
        }
        
        // Read file content as binary
        $replyImage = file_get_contents($file['tmp_name']);
        
        if ($replyImage === false) {
            $_SESSION['error'] = 'Failed to read image file.';
            header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
            exit;
        }
        
        error_log("Customer reply image read successfully. Size: " . strlen($replyImage) . " bytes, Type: " . $mimeType);
    }
    
    // Add reply as customer with optional image
    $result = addReviewReply($reviewId, 'customer', $customerId, $replyText, $replyImage);
    
    if ($result) {
        if ($replyImage) {
            $_SESSION['success'] = 'Reply with image posted successfully!';
        } else {
            $_SESSION['success'] = 'Reply posted successfully!';
        }
        error_log("Customer reply created successfully with ID: " . $result);
    } else {
        $_SESSION['error'] = 'Failed to post reply. Please try again.';
        error_log("Failed to create customer reply for review ID: " . $reviewId);
    }
}

// Redirect back to business detail page
$businessId = intval($_POST['business_id'] ?? $_GET['business_id'] ?? 0);
header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
exit;
?>