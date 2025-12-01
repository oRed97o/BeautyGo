<?php
// ============================================================
// REVIEW FUNCTIONS
// ============================================================

// Get reviews for a business (with review images and replies)
function getBusinessReviews($businessId) {  
    $conn = getDbConnection();

    $stmt = $conn->prepare("
        SELECT 
            r.review_id,
            r.customer_id,
            r.business_id,
            r.review_date,
            r.rating,
            r.review_text,
            r.review_img1,
            r.review_img2,
            r.review_img3,
            r.review_img4,
            r.review_img5,
            c.fname AS customer_fname,
            c.surname AS customer_lname
        FROM reviews r
        JOIN customers c ON r.customer_id = c.customer_id
        WHERE r.business_id = ?
        ORDER BY r.review_date DESC
    ");

    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        // Get images from review_img1 to review_img5 columns
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $imgKey = 'review_img' . $i;
            if (isset($row[$imgKey]) && !empty($row[$imgKey])) {
                // Properly encode blob to base64
                $images[] = 'data:image/jpeg;base64,' . base64_encode($row[$imgKey]);
            }
            unset($row[$imgKey]); // Remove blob data from array
        }
        
        $row['images'] = $images;
        
        // Get replies for this review
        $row['replies'] = getReviewReplies($row['review_id']);
        
        $reviews[] = $row;
    }

    $stmt->close();
    return $reviews;
}

// Get replies for a specific review
function getReviewReplies($reviewId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            rr.reply_id,
            rr.review_id,
            rr.sender_type,
            rr.sender_id,
            rr.reply_text,
            rr.reply_image,
            rr.reply_date,
            CASE 
                WHEN rr.sender_type = 'customer' THEN CONCAT(c.fname, ' ', c.surname)
                WHEN rr.sender_type = 'business' THEN b.business_name
            END AS sender_name
        FROM review_replies rr
        LEFT JOIN customers c ON rr.sender_type = 'customer' AND rr.sender_id = c.customer_id
        LEFT JOIN businesses b ON rr.sender_type = 'business' AND rr.sender_id = b.business_id
        WHERE rr.review_id = ?
        ORDER BY rr.reply_date ASC
    ");
    
    $stmt->bind_param("i", $reviewId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        // Convert reply image blob to base64 if exists
        if (!empty($row['reply_image'])) {
            $row['reply_image'] = 'data:image/jpeg;base64,' . base64_encode($row['reply_image']);
        }
        $replies[] = $row;
    }
    
    $stmt->close();
    return $replies;
}

// Add a reply to a review with optional image - FULLY CORRECTED VERSION
function addReviewReply($reviewId, $senderType, $senderId, $replyText, $replyImage = null) {
    $conn = getDbConnection();
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("
        INSERT INTO review_replies (review_id, sender_type, sender_id, reply_text, reply_image, reply_date)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    // CRITICAL: Use NULL placeholder for blob parameter
    $null = NULL;
    
    // CORRECTED: Proper parameter binding - 'isisb' (int, string, int, string, blob)
    // Parameters in order: reviewId, senderType, senderId, replyText, replyImage
    if (!$stmt->bind_param("isisb", $reviewId, $senderType, $senderId, $replyText, $null)) {
        error_log("Bind param failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // CRITICAL: send_long_data MUST be called BEFORE execute()
    // Parameter index 4 corresponds to the 5th parameter (reply_image/blob) - zero-indexed
    if ($replyImage !== null && !empty($replyImage)) {
        if (!$stmt->send_long_data(4, $replyImage)) {
            error_log("Send long data failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    // Execute the statement AFTER send_long_data
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $replyId = $conn->insert_id;
    $stmt->close();
    
    error_log("Reply created successfully with ID: " . $replyId . " (Image: " . ($replyImage ? "Yes (" . strlen($replyImage) . " bytes)" : "No") . ")");
    return $replyId;
}

// Create review (with images in review_img1 to review_img5 columns)
function createReview($data) {
    $conn = getDbConnection();

    $businessId = $data['business_id'];
    $customerId = $data['customer_id'];
    $rating = $data['rating'] ?? null;
    $reviewText = $data['review_text'] ?? '';
    
    // Prepare image data (up to 5 images)
    $images = [];
    for ($i = 1; $i <= 5; $i++) {
        $imgKey = 'review_img' . $i;
        $images[$i] = isset($data[$imgKey]) ? $data[$imgKey] : null;
    }

    $stmt = $conn->prepare("
        INSERT INTO reviews (
            business_id, customer_id, rating, review_text, 
            review_img1, review_img2, review_img3, review_img4, review_img5,
            review_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $null = NULL;
    $stmt->bind_param(
        "iiissbbbbb",
        $businessId,
        $customerId,
        $rating,
        $reviewText,
        $null, $null, $null, $null, $null
    );

    // Send blob data for each image
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($images[$i])) {
            $stmt->send_long_data($i + 3, $images[$i]); // +3 because first 4 params are not blobs
        }
    }

    if ($stmt->execute()) {
        $reviewId = $conn->insert_id;
        $stmt->close();
        return $reviewId;
    }

    error_log("Review creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Calculate average rating from reviews table with half-star support
function calculateAverageRating($businessId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row && $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
}
?>