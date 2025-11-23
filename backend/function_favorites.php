<?php
// ============================================================
// FAVORITES FUNCTIONS
// ============================================================

// Add a business to favorites
function addFavorite($customerId, $businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO favorites (customer_id, business_id, is_new) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE customer_id = customer_id
    ");
    $stmt->bind_param("ii", $customerId, $businessId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Remove a business from favorites
function removeFavorite($customerId, $businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        DELETE FROM favorites 
        WHERE customer_id = ? AND business_id = ?
    ");
    $stmt->bind_param("ii", $customerId, $businessId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Check if a business is favorited by customer
function isFavorite($customerId, $businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM favorites 
        WHERE customer_id = ? AND business_id = ?
    ");
    $stmt->bind_param("ii", $customerId, $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Get all favorite businesses for a customer
function getCustomerFavorites($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            f.created_at as favorited_at,
            f.is_new,
            ST_X(b.location) AS longitude, 
            ST_Y(b.location) AS latitude
        FROM favorites f
        JOIN businesses b ON f.business_id = b.business_id
        WHERE f.customer_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Get count of NEW favorites only (unseen)
function getNewFavoritesCount($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM favorites 
        WHERE customer_id = ? AND is_new = 1
    ");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Mark all favorites as seen/viewed
function markFavoritesAsSeen($customerId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        UPDATE favorites 
        SET is_new = 0 
        WHERE customer_id = ? AND is_new = 1
    ");
    $stmt->bind_param("i", $customerId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get count of favorites for a business
function getBusinessFavoriteCount($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM favorites 
        WHERE business_id = ?
    ");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

// Toggle favorite (add if not exists, remove if exists)
function toggleFavorite($customerId, $businessId) {
    error_log("toggleFavorite called - Customer: $customerId, Business: $businessId");
    
    $conn = getDbConnection();
    
    if (!$conn) {
        error_log("Database connection failed");
        return false;
    }
    
    $checkStmt = $conn->prepare("
        SELECT favorite_id 
        FROM favorites 
        WHERE customer_id = ? AND business_id = ?
    ");
    $checkStmt->bind_param("ii", $customerId, $businessId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($exists) {
        error_log("Removing favorite");
        $stmt = $conn->prepare("
            DELETE FROM favorites 
            WHERE customer_id = ? AND business_id = ?
        ");
        $stmt->bind_param("ii", $customerId, $businessId);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Delete failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    } else {
        error_log("Adding favorite");
        $stmt = $conn->prepare("
            INSERT INTO favorites (customer_id, business_id, is_new) 
            VALUES (?, ?, 1)
        ");
        $stmt->bind_param("ii", $customerId, $businessId);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
}
?>