<?php
session_start();
require_once '../db_connection/config.php';
require_once 'function_utilities.php';

// Check if customer is logged in
if (!isCustomerLoggedIn()) {
    $_SESSION['error'] = "Please login to submit a review.";
    header('Location: ../login.php');
    exit;
}

// Check if form was submitted
if (!isset($_POST['submit_review'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: ../index.php');
    exit;
}

// Get current customer
$conn = getDbConnection();
$customerId = $_SESSION['customer_id'];

// Validate required fields
$businessId = $_POST['business_id'] ?? '';
$rating = $_POST['rating'] ?? '';
$reviewText = trim($_POST['review_text'] ?? '');

// Validation
if (empty($businessId) || empty($rating) || empty($reviewText)) {
    $_SESSION['error'] = "Invalid review data. Please fill in all required fields.";
    header('Location: ../business-detail.php?id=' . urlencode($businessId));
    exit;
}

// Validate rating is between 1-5
if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Invalid rating. Please select a rating between 1 and 5 stars.";
    header('Location: ../business-detail.php?id=' . urlencode($businessId));
    exit;
}

// Check if business exists
$checkBusiness = $conn->prepare("SELECT business_id FROM businesses WHERE business_id = ?");
$checkBusiness->bind_param("i", $businessId);
$checkBusiness->execute();
if ($checkBusiness->get_result()->num_rows === 0) {
    $_SESSION['error'] = "Business not found.";
    header('Location: ../index.php');
    exit;
}
$checkBusiness->close();

// Check if customer has already reviewed this business
$checkReview = $conn->prepare("SELECT review_id FROM reviews WHERE customer_id = ? AND business_id = ?");
$checkReview->bind_param("ii", $customerId, $businessId);
$checkReview->execute();
if ($checkReview->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You have already reviewed this business.";
    header('Location: ../business-detail.php?id=' . urlencode($businessId));
    exit;
}
$checkReview->close();

// Insert review
$insertReview = $conn->prepare("INSERT INTO reviews (business_id, customer_id, rating, review_text, review_date) VALUES (?, ?, ?, ?, NOW())");
$insertReview->bind_param("iiis", $businessId, $customerId, $rating, $reviewText);

if ($insertReview->execute()) {
    $reviewId = $conn->insert_id;
    
    // Handle image uploads (optional)
    if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
        $uploadedCount = 0;
        $maxImages = 5;
        
        foreach ($_FILES['review_images']['tmp_name'] as $key => $tmpName) {
            if ($uploadedCount >= $maxImages) break;
            
            if (!empty($tmpName) && $_FILES['review_images']['error'][$key] === UPLOAD_ERR_OK) {
                // Validate file type
                $fileType = $_FILES['review_images']['type'][$key];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip non-image files
                }
                
                // Validate file size (max 5MB per image)
                if ($_FILES['review_images']['size'][$key] > 5242880) {
                    continue; // Skip files larger than 5MB
                }
                
                // Read file content
                $imageData = file_get_contents($tmpName);
                
                if ($imageData !== false) {
                    // Insert image into review_images table
                    $insertImage = $conn->prepare("INSERT INTO review_images (review_id, image_data) VALUES (?, ?)");
                    // Use 'b' for blob binding
                    $null = NULL;
                    $insertImage->bind_param("ib", $reviewId, $null);
                    $insertImage->send_long_data(1, $imageData);
                    
                    if ($insertImage->execute()) {
                        $uploadedCount++;
                    }
                    $insertImage->close();
                }
            }
        }
    }
    
    $_SESSION['success'] = "Review submitted successfully! Thank you for your feedback.";
} else {
    $_SESSION['error'] = "Failed to submit review. Please try again.";
}

$insertReview->close();
$conn->close();

header('Location: ../business-detail.php?id=' . urlencode($businessId) . '#reviews');
exit;
?>