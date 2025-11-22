<?php

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_businesses.php';
require_once 'backend/function_albums.php';
require_once 'backend/function_services.php';
require_once 'backend/function_employees.php';
require_once 'backend/function_appointments.php';
require_once 'backend/function_reviews.php';
require_once 'backend/function_notifications.php';

if (!isBusinessLoggedIn()) { header('Location: login.php'); exit; }

$business = getCurrentBusiness();
$businessId = $business['business_id'];
$album = getOrCreateBusinessAlbum($businessId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $businessData = [
        'business_name' => sanitize($_POST['business_name']),
        'business_type' => $_POST['business_type'],
        'business_desc' => sanitize($_POST['description']),
        'business_address' => sanitize($_POST['address']),
        'city' => sanitize($_POST['city']),
        'latitude' => floatval($_POST['latitude']),
        'longitude' => floatval($_POST['longitude'])
    ];
    if (updateBusiness($businessId, $businessData)) {
        $_SESSION['success'] = 'Profile updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
    header('Location: business-profile.php'); exit;
}

// Handle portfolio images update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_portfolio'])) {
    $hasError = false; $updatedAny = false;
    
    // Handle cropped logo data (base64)
    if (!empty($_POST['cropped_logo_data'])) {
        $base64Data = $_POST['cropped_logo_data'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $imageData = base64_decode($base64Data);
            if ($imageData !== false) {
                $compressedLogo = compressImage($imageData, 800, 800, 85);
                if (updateSingleAlbumImage($businessId, 'logo', $compressedLogo)) { $updatedAny = true; }
                else { $_SESSION['error'] = 'Failed to upload logo'; $hasError = true; }
            }
        }
    }
    elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['logo']['type'], $allowedTypes) && $_FILES['logo']['size'] <= 5 * 1024 * 1024) {
            $imageData = file_get_contents($_FILES['logo']['tmp_name']);
            $compressedLogo = compressImage($imageData, 800, 800, 85);
            if (updateSingleAlbumImage($businessId, 'logo', $compressedLogo)) { $updatedAny = true; }
            else { $_SESSION['error'] = 'Failed to upload logo'; $hasError = true; }
        } else { $_SESSION['error'] = 'Logo: Invalid file type or size (max 5MB)'; $hasError = true; }
    }
    
    if (!$hasError) {
        for ($i = 1; $i <= 10; $i++) {
            $fileKey = 'image_' . $i;
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES[$fileKey]['type'], $allowedTypes) && $_FILES[$fileKey]['size'] <= 5 * 1024 * 1024) {
                    $imageData = file_get_contents($_FILES[$fileKey]['tmp_name']);
                    $compressedImage = compressImage($imageData, 1200, 800, 85);
                    if (updateSingleAlbumImage($businessId, 'image' . $i, $compressedImage)) { $updatedAny = true; }
                    else { $_SESSION['error'] = 'Failed to upload image ' . $i; $hasError = true; break; }
                } else { $_SESSION['error'] = 'Image ' . $i . ': Invalid type or too large'; $hasError = true; break; }
            }
        }
    }
    
    if (!$hasError && $updatedAny) { $_SESSION['success'] = 'Portfolio updated successfully'; }
    elseif (!$hasError && !$updatedAny) { $_SESSION['error'] = 'No images selected'; }
    header('Location: business-profile.php'); exit;
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageSlot = $_POST['image_slot'];
    $validSlots = ['logo', 'image1', 'image2', 'image3', 'image4', 'image5', 'image6', 'image7', 'image8', 'image9', 'image10'];
    if (preg_match('/^image(\d+)$/', $imageSlot, $m)) { $imageSlot = 'image' . $m[1]; }
    if (in_array($imageSlot, $validSlots)) {
        if (deleteAlbumImage($businessId, $imageSlot)) { $_SESSION['success'] = 'Image deleted'; }
        else { $_SESSION['error'] = 'Failed to delete image'; }
    }
    header('Location: business-profile.php'); exit;
}

$pageTitle = 'Business Profile - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/business-profile.css">

<main>
<div class="container my-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <!-- Business Information -->
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Business Profile</h2>
                        <a href="business-dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    </div>
                    
                    <form action="" method="POST">
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($business['latitude'] ?? '14.0697'); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($business['longitude'] ?? '120.6328'); ?>">
                        
                        <h5 class="mb-3">Business Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="business_name" class="form-label">Business Name</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($business['business_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="business_type" class="form-label">Business Type</label>
                                <select class="form-select" id="business_type" name="business_type" required>
                                    <?php foreach(['Hair Salon','Spa & Wellness','Barbershop','Nail Salon','Beauty Clinic'] as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo ($business['business_type'] ?? '') == $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($business['business_desc'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3">Contact</h5>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($business['business_email'] ?? ''); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="bi bi-geo-alt-fill"></i> Location</h5>
                        <div class="mb-3">
                            <label class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($business['business_address'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($business['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-map"></i> Pin Your Location</label>
                            <div class="map-info-box">
                                <i class="bi bi-info-circle"></i>
                                <small>Click on the map or drag the marker to update your location.</small>
                            </div>
                            <div id="locationMap"></div>
                            <div class="coordinates-display">
                                <strong>Coordinates:</strong> Lat: <span id="displayLat"><?php echo $business['latitude'] ?? '14.0697'; ?></span> | Lng: <span id="displayLng"><?php echo $business['longitude'] ?? '120.6328'; ?></span>
                            </div>
                        </div>
                        
                        <div class="d-grid"><button type="submit" name="update_profile" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button></div>
                    </form>
                </div>
            </div>
            
            <!-- Logo Card -->
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="bi bi-image"></i> Business Logo</h5>
                    <form action="" method="POST" enctype="multipart/form-data" id="logoForm">
                        <input type="hidden" id="croppedLogoData" name="cropped_logo_data" value="">
                        <div class="text-center">
                            <div class="logo-preview" id="logo_preview">
                                <?php if (!empty($album['logo'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($album['logo']); ?>" id="logoPreviewImg">
                                    <div class="placeholder hide" id="logoPlaceholder"><i class="bi bi-building" style="font-size: 3rem;"></i><p class="mb-0 small">No logo</p></div>
                                <?php else: ?>
                                    <img src="" id="logoPreviewImg" class="hide">
                                    <div class="placeholder" id="logoPlaceholder"><i class="bi bi-building" style="font-size: 3rem;"></i><p class="mb-0 small">No logo</p></div>
                                <?php endif; ?>
                            </div>
                            <div class="file-input-wrapper">
                                <input type="file" name="logo" id="logo" accept="image/*" onchange="handleLogoSelect(this)">
                                <label for="logo" class="file-input-label"><i class="bi bi-upload"></i> <span id="uploadBtnText"><?php echo !empty($album['logo']) ? 'Change Logo' : 'Choose Logo'; ?></span></label>
                            </div>
                            <div id="fileInfo" style="display: none;"><small class="text-muted"><i class="bi bi-file-earmark-image"></i> <span id="fileName"></span></small>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="resetLogoSelection()"><i class="bi bi-x-circle"></i></button>
                            </div>
                            <?php if (!empty($album['logo'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="deleteImage('logo')"><i class="bi bi-trash"></i> Delete</button>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" name="update_portfolio" class="btn btn-success">
                                <i class="bi bi-cloud-upload"></i> Upload Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Portfolio Card -->
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="bi bi-images"></i> Portfolio <span class="badge bg-primary ms-2" id="imageCount"><?php $c=0; for($i=1;$i<=10;$i++) if(!empty($album['image'.$i])) $c++; echo $c; ?>/10</span></h5>
                    <form action="" method="POST" enctype="multipart/form-data" id="portfolioForm">
                        <div class="portfolio-grid" id="portfolioGrid">
                            <?php $d=0; for($i=1;$i<=10;$i++): $k='image'.$i; if(!empty($album[$k])): $d++; ?>
                                <div class="image-card" data-slot="<?php echo $i; ?>">
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($album[$k]); ?>">
                                    <div class="image-card-actions"><button type="button" class="btn btn-sm btn-danger" onclick="deleteImage('<?php echo $k; ?>')"><i class="bi bi-trash"></i></button></div>
                                </div>
                            <?php endif; endfor; if($d<10): ?>
                                <label for="newImage" class="add-image-slot"><i class="bi bi-plus-circle"></i><p>Add Image</p><small class="text-muted"><?php echo 10-$d; ?> slots left</small></label>
                                <input type="file" id="newImage" accept="image/*" style="display:none;" onchange="handleNewImage(this)">
                            <?php endif; ?>
                        </div>
                        <?php for($i=1;$i<=10;$i++): ?><input type="file" id="image_<?php echo $i; ?>" name="image_<?php echo $i; ?>" accept="image/*" style="display:none;"><?php endfor; ?>
                        <div class="d-grid mt-3" id="uploadButtonContainer" style="display:none;"><button type="submit" name="update_portfolio" class="btn btn-success btn-lg"><i class="bi bi-cloud-upload"></i> Upload Images</button></div>
                    </form>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Overview</h5>
                    <div class="row text-center">
                        <div class="col-3"><h3 style="color:var(--color-burgundy);"><?php echo count(getBusinessServices($businessId)); ?></h3><small>Services</small></div>
                        <div class="col-3"><h3 style="color:var(--color-rose);"><?php echo count(getBusinessEmployees($businessId)); ?></h3><small>Staff</small></div>
                        <div class="col-3"><h3 style="color:var(--color-pink);"><?php echo count(getBusinessAppointments($businessId)); ?></h3><small>Bookings</small></div>
                        <div class="col-3"><h3 style="color:var(--color-burgundy);"><?php echo number_format(calculateAverageRating($businessId), 1); ?></h3><small>Rating</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="crop-modal-overlay" id="cropModal">
    <div class="crop-modal-content">
        <div class="crop-modal-header"><h3><i class="bi bi-crop"></i> Adjust Your Logo</h3><p>Drag the image to position it</p></div>
        <div class="crop-preview-area" id="cropPreviewArea"><img src="" class="crop-preview-image" id="cropPreviewImage"></div>
        <div class="crop-instructions"><i class="bi bi-hand-index"></i><p>Drag to reposition • Logo will be cropped to fit</p></div>
        <div class="crop-modal-buttons">
            <button type="button" class="btn-cancel-crop" id="btnCancelCrop"><i class="bi bi-x-circle"></i> Cancel</button>
            <button type="button" class="btn-confirm-crop" id="btnConfirmCrop"><i class="bi bi-check-circle"></i> Set as Logo</button>
        </div>
    </div>
</div>
</main>

<!-- Add this AFTER the </main> tag and BEFORE <?php include 'includes/footer.php'; ?> -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ========== MAP FUNCTIONALITY ==========
let map, marker;
const initLat = <?php echo $business['latitude'] ?? 14.0697; ?>;
const initLng = <?php echo $business['longitude'] ?? 120.6328; ?>;

function initMap() {
    map = L.map('locationMap').setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);
    
    const customIcon = L.divIcon({
        html: '<i class="bi bi-geo-alt-fill" style="font-size:2.5rem;color:#850E35;"></i>',
        className: 'custom-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    
    marker = L.marker([initLat, initLng], { icon: customIcon, draggable: true }).addTo(map);
    
    map.on('click', function(e) {
        updateLocation(e.latlng.lat, e.latlng.lng);
    });
    
    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        updateLocation(pos.lat, pos.lng);
    });
}

function updateLocation(lat, lng) {
    marker.setLatLng([lat, lng]);
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    reverseGeocode(lat, lng);
}

async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`);
        const data = await response.json();
        if (data && data.address) {
            const addr = data.address;
            const road = addr.road || addr.street || '';
            const barangay = addr.suburb || addr.neighbourhood || addr.village || '';
            let streetAddress = road ? (road + (barangay && barangay !== road ? ', ' + barangay : '')) : barangay;
            if (streetAddress) document.getElementById('address').value = streetAddress;
            const city = addr.city || addr.town || addr.municipality;
            if (city) document.getElementById('city').value = city;
        }
    } catch (error) {
        console.error('Geocode error:', error);
    }
}

document.addEventListener('DOMContentLoaded', initMap);

// ========== CROP MODAL FUNCTIONALITY ==========
const cropModal = document.getElementById('cropModal');
const cropPreviewArea = document.getElementById('cropPreviewArea');
const cropPreviewImage = document.getElementById('cropPreviewImage');

let isDragging = false;
let startX, startY;
let initialX = 0, initialY = 0;
let currentX = 0, currentY = 0;
let currentFile = null;
let originalImageSrc = '';

function handleLogoSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        alert('File is too large. Maximum size is 5MB.');
        input.value = '';
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        input.value = '';
        return;
    }
    
    currentFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        originalImageSrc = e.target.result;
        openCropModal(originalImageSrc);
    };
    reader.readAsDataURL(file);
}

function openCropModal(imageSrc) {
    const img = new Image();
    img.onload = function() {
        const containerSize = 300;
        const scale = Math.max(containerSize / img.width, containerSize / img.height);
        
        cropPreviewImage.style.width = (img.width * scale) + 'px';
        cropPreviewImage.style.height = (img.height * scale) + 'px';
        
        currentX = (containerSize - img.width * scale) / 2;
        currentY = (containerSize - img.height * scale) / 2;
        
        cropPreviewImage.style.left = currentX + 'px';
        cropPreviewImage.style.top = currentY + 'px';
        
        initialX = currentX;
        initialY = currentY;
        
        cropPreviewImage.src = imageSrc;
        cropModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    img.src = imageSrc;
}

function closeCropModal() {
    cropModal.classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('logo').value = '';
    currentX = currentY = initialX = initialY = 0;
}

// Mouse/Touch events for dragging
cropPreviewArea.addEventListener('mousedown', function(e) {
    isDragging = true;
    cropPreviewArea.classList.add('dragging');
    startX = e.clientX;
    startY = e.clientY;
    initialX = currentX;
    initialY = currentY;
    e.preventDefault();
});

cropPreviewArea.addEventListener('touchstart', function(e) {
    isDragging = true;
    cropPreviewArea.classList.add('dragging');
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    initialX = currentX;
    initialY = currentY;
});

document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    currentX = initialX + (e.clientX - startX);
    currentY = initialY + (e.clientY - startY);
    cropPreviewImage.style.left = currentX + 'px';
    cropPreviewImage.style.top = currentY + 'px';
});

document.addEventListener('touchmove', function(e) {
    if (!isDragging) return;
    currentX = initialX + (e.touches[0].clientX - startX);
    currentY = initialY + (e.touches[0].clientY - startY);
    cropPreviewImage.style.left = currentX + 'px';
    cropPreviewImage.style.top = currentY + 'px';
});

document.addEventListener('mouseup', function() {
    isDragging = false;
    cropPreviewArea.classList.remove('dragging');
});

document.addEventListener('touchend', function() {
    isDragging = false;
    cropPreviewArea.classList.remove('dragging');
});

// Cancel crop
document.getElementById('btnCancelCrop').addEventListener('click', closeCropModal);
cropModal.addEventListener('click', function(e) {
    if (e.target === cropModal) closeCropModal();
});

// Confirm crop
document.getElementById('btnConfirmCrop').addEventListener('click', function() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const size = 300;
    
    canvas.width = size;
    canvas.height = size;
    
    const img = new Image();
    img.onload = function() {
        ctx.drawImage(img, currentX, currentY, parseFloat(cropPreviewImage.style.width), parseFloat(cropPreviewImage.style.height));
        
        canvas.toBlob(function(blob) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Update preview
                document.getElementById('logoPreviewImg').src = e.target.result;
                document.getElementById('logoPreviewImg').classList.remove('hide');
                document.getElementById('logoPlaceholder').classList.add('hide');
                
                // Update file info
                document.getElementById('fileName').textContent = currentFile.name;
                document.getElementById('fileInfo').style.display = 'inline-block';
                document.getElementById('uploadBtnText').textContent = 'Change Logo';
                
                // Store cropped data
                document.getElementById('croppedLogoData').value = e.target.result;
                
                closeCropModal();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.9);
    };
    img.src = originalImageSrc;
});

function resetLogoSelection() {
    document.getElementById('logo').value = '';
    document.getElementById('croppedLogoData').value = '';
    document.getElementById('fileInfo').style.display = 'none';
    
    // Check if there's an existing logo
    const existingLogo = '<?php echo !empty($album['logo']) ? "exists" : ""; ?>';
    if (!existingLogo) {
        document.getElementById('logoPreviewImg').src = '';
        document.getElementById('logoPreviewImg').classList.add('hide');
        document.getElementById('logoPlaceholder').classList.remove('hide');
        document.getElementById('uploadBtnText').textContent = 'Choose Logo';
    } else {
        document.getElementById('uploadBtnText').textContent = 'Change Logo';
    }
}

// ========== PORTFOLIO FUNCTIONS ==========
function handleNewImage(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('File is too large. Maximum size is 5MB.');
        input.value = '';
        return;
    }
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type.');
        input.value = '';
        return;
    }
    
    // Find first empty slot
    for (let i = 1; i <= 10; i++) {
        const existingCard = document.querySelector(`.image-card[data-slot="${i}"]`);
        if (!existingCard) {
            const slotInput = document.getElementById('image_' + i);
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            slotInput.files = dataTransfer.files;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.className = 'image-card';
                card.setAttribute('data-slot', i);
                card.innerHTML = `
                    <img src="${e.target.result}" alt="New Image">
                    <div class="image-card-actions">
                        <button type="button" class="btn btn-sm btn-warning" onclick="removePreview(${i})" style="opacity: 1;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `;
                const addButton = document.querySelector('.add-image-slot');
                if (addButton) {
                    addButton.parentNode.insertBefore(card, addButton);
                }
                updateImageCount();
                document.getElementById('uploadButtonContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
            break;
        }
    }
    input.value = '';
}

function removePreview(slot) {
    const card = document.querySelector(`.image-card[data-slot="${slot}"]`);
    if (card) {
        card.remove();
        document.getElementById('image_' + slot).value = '';
        updateImageCount();
        
        // Check if upload button should be hidden
        const newImages = document.querySelectorAll('.image-card img:not([src*="base64"])').length;
        if (newImages === 0) {
            document.getElementById('uploadButtonContainer').style.display = 'none';
        }
    }
}

function updateImageCount() {
    const existingImages = document.querySelectorAll('.image-card').length;
    document.getElementById('imageCount').textContent = existingImages + '/10';
}

function deleteImage(imageSlot) {
    if (confirm('Are you sure you want to delete this image?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_image" value="1">
            <input type="hidden" name="image_slot" value="${imageSlot}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>