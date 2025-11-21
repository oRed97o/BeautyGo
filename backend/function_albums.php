<?php
// ============================================================
// ALBUM FUNCTIONS (for business images)
// ============================================================

// Create album for business
function createAlbumForBusiness($businessId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO albums (business_id)
        VALUES (?)
        ON DUPLICATE KEY UPDATE business_id = business_id
    ");
    $stmt->bind_param("i", $businessId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get album for business
function getBusinessAlbum($businessId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM albums WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

// Get existing album or create a new one if none exists 
function getOrCreateBusinessAlbum($businessId) {
    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT * FROM albums WHERE business_id = ?");
    $stmt->bind_param("i", $businessId); 
    $stmt->execute();
    $result = $stmt->get_result();
    $album = $result->fetch_assoc();
    $stmt->close();

    if (!$album) {
        $stmt = $conn->prepare("INSERT INTO albums (business_id) VALUES (?)");
        $stmt->bind_param("i", $businessId);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT * FROM albums WHERE album_id = ?");
            $stmt->bind_param("i", $newId);
            $stmt->execute();
            $result = $stmt->get_result();
            $album = $result->fetch_assoc();
            $stmt->close();
        }
    }

    return $album;
}

/**
 * Update album images (logo + up to 10 images)
 * Only updates the slots that are provided in the $images array
 * 
 * @param int $businessId
 * @param array $images - ['logo' => data, 0 => data, 1 => data, ...] 
 *                        Use empty string '' to delete an image
 *                        Use null to skip (keep existing)
 * @return bool
 */
function updateAlbumImages($businessId, $images) {
    $conn = getDbConnection();
    
    // Map array keys to column names
    $columnMap = [
        'logo' => 'logo',
        0 => 'image1', 1 => 'image2', 2 => 'image3', 3 => 'image4', 4 => 'image5',
        5 => 'image6', 6 => 'image7', 7 => 'image8', 8 => 'image9', 9 => 'image10'
    ];
    
    // Build dynamic query for only the columns we're updating
    $setClauses = [];
    $params = [];
    $types = '';
    
    foreach ($images as $key => $value) {
        if (!isset($columnMap[$key])) continue;
        
        $column = $columnMap[$key];
        
        if ($value === null) {
            // Skip - keep existing value
            continue;
        } elseif ($value === '') {
            // Delete - set to NULL
            $setClauses[] = "$column = NULL";
        } else {
            // Update with new image data
            $setClauses[] = "$column = ?";
            $params[] = $value;
            $types .= 'b'; // blob type for binary data
        }
    }
    
    // If nothing to update, return true
    if (empty($setClauses)) {
        return true;
    }
    
    $sql = "UPDATE albums SET " . implode(', ', $setClauses) . " WHERE business_id = ?";
    $types .= 'i';
    $params[] = $businessId;
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    // Bind parameters dynamically
    if (!empty($types)) {
        // Create references array for bind_param
        $bindParams = [$types];
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        
        // Send long data for blob parameters (all except the last one which is businessId)
        $blobCount = strlen($types) - 1; // exclude the 'i' for businessId
        for ($i = 0; $i < $blobCount; $i++) {
            if ($types[$i] === 'b') {
                $stmt->send_long_data($i, $params[$i]);
            }
        }
    }
    
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Album update failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

/**
 * Update a single image slot in the album
 * 
 * @param int $businessId
 * @param string $slot - 'logo', 'image1', 'image2', etc.
 * @param string|null $imageData - Binary image data, or null to delete
 * @return bool
 */
function updateSingleAlbumImage($businessId, $slot, $imageData) {
    $conn = getDbConnection();
    
    $validSlots = ['logo', 'image1', 'image2', 'image3', 'image4', 'image5', 
                   'image6', 'image7', 'image8', 'image9', 'image10'];
    
    if (!in_array($slot, $validSlots)) {
        error_log("Invalid slot: $slot");
        return false;
    }
    
    if ($imageData === null || $imageData === '') {
        // Delete image
        $sql = "UPDATE albums SET $slot = NULL WHERE business_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $businessId);
    } else {
        // Update with new image
        $sql = "UPDATE albums SET $slot = ? WHERE business_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("bi", $null, $businessId);
        $stmt->send_long_data(0, $imageData);
    }
    
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Single image update failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

/**
 * Delete a specific image from album
 * 
 * @param int $businessId
 * @param string $slot - 'logo', 'image1', 'image2', etc.
 * @return bool
 */
function deleteAlbumImage($businessId, $slot) {
    return updateSingleAlbumImage($businessId, $slot, null);
}

// Get all album images (logo + 10 gallery images)
function getAlbumImagesArray($businessId, $asBase64 = true) {
    $album = getOrCreateBusinessAlbum($businessId);
    $images = [];

    if ($album) {
        if (!empty($album['logo'])) {
            $images['logo'] = $asBase64
                ? 'data:image/jpeg;base64,' . base64_encode($album['logo'])
                : $album['logo'];
        }

        for ($i = 1; $i <= 10; $i++) {
            $key = 'image' . $i;
            if (!empty($album[$key])) {
                $images[$key] = $asBase64
                    ? 'data:image/jpeg;base64,' . base64_encode($album[$key])
                    : $album[$key];
            }
        }
    }

    return $images;
}
?>