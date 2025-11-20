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

// Update album images (logo + up to 10 images)
function updateAlbumImages($businessId, $images) {
    $conn = getDbConnection();

    $null = null;

    $stmt = $conn->prepare("
        UPDATE albums 
        SET logo = ?, image1 = ?, image2 = ?, image3 = ?, image4 = ?, 
            image5 = ?, image6 = ?, image7 = ?, image8 = ?, image9 = ?, image10 = ?
        WHERE business_id = ?
    ");

    $stmt->bind_param(
        "sssssssssssi",
        $null, $null, $null, $null, $null,
        $null, $null, $null, $null, $null, $null,
        $businessId
    );

    $imageSlots = [
        'logo' => 0,
        0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5,
        5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10
    ];

    foreach ($imageSlots as $key => $index) {
        if (isset($images[$key]) && !empty($images[$key])) {
            $stmt->send_long_data($index, $images[$key]);
        }
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
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