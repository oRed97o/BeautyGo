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
        header('Location: ../business-detail.php?id=' . $businessId);
        exit;
    }
    
    // Get current customer
    $currentCustomer = getCurrentCustomer();
    $customerId = $currentCustomer['customer_id'];
    
    // Add reply as customer
    if (addReviewReply($reviewId, 'customer', $customerId, $replyText)) {
        $_SESSION['success'] = 'Reply posted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to post reply. Please try again.';
    }
}

// Redirect back to business detail page
$businessId = intval($_POST['business_id'] ?? $_GET['business_id'] ?? 0);
header('Location: ../business-detail.php?id=' . $businessId . '#reviews');
exit;
?>