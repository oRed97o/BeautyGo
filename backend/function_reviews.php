<?php
// ============================================================
// REVIEW FUNCTIONS
// ============================================================

// Get reviews for a business (with review images)
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
        $reviews[] = $row;
    }

    $stmt->close();
    return $reviews;
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