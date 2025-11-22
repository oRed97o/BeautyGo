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
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = "review_img$i";
            if (!empty($row[$key])) {
                $images[] = 'data:image/jpeg;base64,' . base64_encode($row[$key]);
            }
            unset($row[$key]);
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
        $replies[] = $row;
    }
    
    $stmt->close();
    return $replies;
}

// Add a reply to a review
function addReviewReply($reviewId, $senderType, $senderId, $replyText) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO review_replies (review_id, sender_type, sender_id, reply_text, reply_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("isis", $reviewId, $senderType, $senderId, $replyText);
    
    if ($stmt->execute()) {
        $replyId = $conn->insert_id;
        $stmt->close();
        return $replyId;
    }
    
    error_log("Reply creation failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Create review (with up to 5 images)
function createReview($data) {
    $conn = getDbConnection();

    $images = [];
    for ($i = 1; $i <= 5; $i++) {
        $key = "review_img$i";
        $images[$i] = !empty($data[$key]) ? $data[$key] : null;
    }

    $businessId = $data['business_id'];
    $customerId = $data['customer_id'];
    $rating = $data['rating'] ?? null;
    $reviewText = $data['review_text'] ?? '';
    $null = null;

    $stmt = $conn->prepare("
        INSERT INTO reviews (
            business_id, customer_id, rating, review_text, review_img1, review_img2, review_img3, review_img4, review_img5, review_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "iiissssss",
        $businessId,
        $customerId,
        $rating,
        $reviewText,
        $null, $null, $null, $null, $null
    );

    foreach (range(1, 5) as $index) {
        if (!empty($images[$index])) {
            $stmt->send_long_data($index + 3, $images[$index]); 
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

// Calculate average rating from reviews table
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