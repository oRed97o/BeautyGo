<?php
// Remove session_start() - it's already started in config.php
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

// Process images FIRST before preparing insert statement
$imageData = [null, null, null, null, null]; // Array for up to 5 images

if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
    $maxImages = 5;
    $imageCount = 0;
    
    foreach ($_FILES['review_images']['tmp_name'] as $key => $tmpName) {
        if ($imageCount >= $maxImages) break;
        
        if (empty($tmpName)) continue;
        
        if ($_FILES['review_images']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        // Validate file type
        $fileType = $_FILES['review_images']['type'][$key];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }
        
        // Validate file size (max 5MB per image)
        if ($_FILES['review_images']['size'][$key] > 5242880) {
            continue;
        }
        
        // Read file content
        $fileContent = file_get_contents($tmpName);
        
        if ($fileContent !== false && !empty($fileContent)) {
            $imageData[$imageCount] = $fileContent;
            $imageCount++;
        }
    }
}

// NOW prepare and execute the insert with proper parameter binding
$insertReview = $conn->prepare("
    INSERT INTO reviews (
        business_id, customer_id, rating, review_text, 
        review_img1, review_img2, review_img3, review_img4, review_img5,
        review_date
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$insertReview) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header('Location: ../business-detail.php?id=' . urlencode($businessId));
    exit;
}

// Bind parameters with CORRECT type string (9 parameters total: i, i, i, s, b, b, b, b, b)
$insertReview->bind_param(
    "iiisbbbbb", 
    $businessId, 
    $customerId, 
    $rating, 
    $reviewText,
    $imageData[0], 
    $imageData[1], 
    $imageData[2], 
    $imageData[3], 
    $imageData[4]
);

// Send BLOB data BEFORE execution
for ($i = 0; $i < 5; $i++) {
    if ($imageData[$i] !== null) {
        $insertReview->send_long_data($i + 4, $imageData[$i]);
    }
}

// Execute the insert
if ($insertReview->execute()) {
    $uploadedCount = count(array_filter($imageData)); // Count non-null images
    if ($uploadedCount > 0) {
        $_SESSION['success'] = "Review submitted successfully with {$uploadedCount} image(s)! Thank you for your feedback.";
    } else {
        $_SESSION['success'] = "Review submitted successfully! Thank you for your feedback.";
    }
} else {
    $_SESSION['error'] = "Failed to submit review: " . $insertReview->error;
}

$insertReview->close();
$conn->close();

header('Location: ../business-detail.php?id=' . urlencode($businessId) . '#reviews');
exit;
?>