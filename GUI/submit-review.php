<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessId = $_POST['business_id'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $userName = $_POST['user_name'] ?? '';
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    
    if (empty($businessId) || empty($userId) || $rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Invalid review data';
        header('Location: my-bookings.php');
        exit;
    }
    
    $reviewData = [
        'business_id' => $businessId,
        'user_id' => $userId,
        'rating' => $rating,
        'comment' => $comment
    ];
    
    if (createReview($reviewData)) {
        $_SESSION['success'] = 'Thank you for your review!';
    } else {
        $_SESSION['error'] = 'Failed to submit review. Please try again.';
    }
}

header('Location: my-bookings.php');
exit;
?>
