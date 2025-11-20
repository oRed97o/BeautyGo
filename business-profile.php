<?php

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isBusinessLoggedIn(), getCurrentBusiness(), sanitize()
require_once 'backend/function_businesses.php';     // for getCurrentBusiness() -> getBusinessById(), updateBusiness()
require_once 'backend/function_albums.php';         // for getOrCreateBusinessAlbum(), updateAlbumImages()
require_once 'backend/function_services.php';       // for getBusinessServices() (used in stats)
require_once 'backend/function_employees.php';      // for getBusinessEmployees() (used in stats)
require_once 'backend/function_appointments.php';   // for getBusinessAppointments() (used in stats)
require_once 'backend/function_reviews.php';        // for calculateAverageRating() (used in stats)
require_once 'backend/function_notifications.php';  // for header.php notifications

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Rest of your code...

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

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
    header('Location: business-profile.php');
    exit;
}

// Handle portfolio images update (logo + 10 images)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_portfolio'])) {
    $images = [];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $images['logo'] = file_get_contents($_FILES['logo']['tmp_name']);
    } else {
        $images['logo'] = null; // Keep existing logo if not uploading new one
    }
    
    // Handle image uploads (1-10)
    for ($i = 0; $i < 10; $i++) {
        $fileKey = 'image_' . ($i + 1);
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES[$fileKey]['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                // Validate file size (max 5MB)
                if ($_FILES[$fileKey]['size'] <= 5 * 1024 * 1024) {
                    $images[$i] = file_get_contents($_FILES[$fileKey]['tmp_name']);
                } else {
                    $_SESSION['error'] = 'Image ' . ($i + 1) . ' is too large. Maximum size is 5MB.';
                }
            } else {
                $_SESSION['error'] = 'Image ' . ($i + 1) . ' has invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            }
        } else {
            $images[$i] = null; // Keep existing image if not uploading new one
        }
    }
    
    if (updateAlbumImages($businessId, $images)) {
        $_SESSION['success'] = 'Portfolio images updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update portfolio images';
    }
    header('Location: business-profile.php');
    exit;
}

// Handle individual image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageSlot = $_POST['image_slot'];
    
    // Create array with null for the specific slot to delete
    $images = [];
    if ($imageSlot === 'logo') {
        $images['logo'] = ''; // Empty string to clear the logo
    } else {
        $slotIndex = intval(str_replace('image', '', $imageSlot)) - 1;
        $images[$slotIndex] = ''; // Empty string to clear the image
    }
    
    if (updateAlbumImages($businessId, $images)) {
        $_SESSION['success'] = 'Image deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete image';
    }
    header('Location: business-profile.php');
    exit;
}

$pageTitle = 'Business Profile - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/business-profile.css">

<main>
    <div class="container my-4">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Business Information Card -->
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Business Profile</h2>
                            <a href="business-dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                        
                        <form action="" method="POST">
                            <!-- Business Information -->
                            <h5 class="mb-3">Business Information</h5>
                            <div class="mb-3">
                                <label for="business_name" class="form-label">Business Name</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($business['business_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_type" class="form-label">Business Type</label>
                                <select class="form-select" id="business_type" name="business_type" required>
                                    <option value="Hair Salon" <?php echo ($business['business_type'] ?? '') == 'Hair Salon' ? 'selected' : ''; ?>>Hair Salon</option>
                                    <option value="Spa & Wellness" <?php echo ($business['business_type'] ?? '') == 'Spa & Wellness' ? 'selected' : ''; ?>>Spa & Wellness</option>
                                    <option value="Barbershop" <?php echo ($business['business_type'] ?? '') == 'Barbershop' ? 'selected' : ''; ?>>Barbershop</option>
                                    <option value="Nail Salon" <?php echo ($business['business_type'] ?? '') == 'Nail Salon' ? 'selected' : ''; ?>>Nail Salon</option>
                                    <option value="Beauty Clinic" <?php echo ($business['business_type'] ?? '') == 'Beauty Clinic' ? 'selected' : ''; ?>>Beauty Clinic</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Business Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($business['business_desc'] ?? ''); ?></textarea>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Contact Information -->
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($business['business_email'] ?? ''); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Location -->
                            <h5 class="mb-3">Location</h5>
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($business['business_address'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($business['city'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" step="0.0001" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($business['latitude'] ?? ''); ?>">
                                    <small class="text-muted">Leave empty to use default location</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" step="0.0001" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($business['longitude'] ?? ''); ?>">
                                    <small class="text-muted">Leave empty to use default location</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Business Logo Card -->
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <h5 class="mb-3">
                            <i class="bi bi-image"></i> Business Logo
                        </h5>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="text-center">
                                <div class="logo-preview" id="logo_preview">
                                    <?php if (!empty($album['logo'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($album['logo']); ?>" alt="Business Logo">
                                    <?php else: ?>
                                        <div class="placeholder">
                                            <i class="bi bi-building" style="font-size: 3rem;"></i>
                                            <p class="mb-0 small">No logo uploaded</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="file-input-wrapper">
                                    <input type="file" name="logo" id="logo" accept="image/*" onchange="previewLogo(this)">
                                    <label for="logo" class="file-input-label">
                                        <i class="bi bi-upload"></i> Choose Logo Image
                                    </label>
                                </div>
                                
                                <?php if (!empty($album['logo'])): ?>
                                    <button type="submit" name="delete_image" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Are you sure you want to delete the logo?')">
                                        <i class="bi bi-trash"></i> Delete Logo
                                    </button>
                                    <input type="hidden" name="image_slot" value="logo">
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid mt-3">
                                <button type="submit" name="update_portfolio" class="btn btn-success">
                                    <i class="bi bi-cloud-upload"></i> Upload Logo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Portfolio Images Card -->
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <h5 class="mb-3">
                            <i class="bi bi-images"></i> Portfolio Gallery
                            <span class="badge bg-primary ms-2">Maximum 10 images</span>
                        </h5>
                        
                        <form action="" method="POST" enctype="multipart/form-data" id="portfolioForm">
                            <div class="row">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <?php 
                                    $imageKey = 'image' . $i;
                                    $hasImage = !empty($album[$imageKey]);
                                    ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                        <div class="image-slot">
                                            <label class="form-label fw-bold">Image <?php echo $i; ?></label>
                                            
                                            <div class="portfolio-preview" id="preview_<?php echo $i; ?>">
                                                <?php if ($hasImage): ?>
                                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($album[$imageKey]); ?>" alt="Portfolio <?php echo $i; ?>">
                                                <?php else: ?>
                                                    <div class="placeholder">
                                                        <i class="bi bi-image" style="font-size: 2rem;"></i>
                                                        <p class="mb-0 small">No image</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="file-input-wrapper mt-2">
                                                <input 
                                                    type="file" 
                                                    name="image_<?php echo $i; ?>" 
                                                    id="image_<?php echo $i; ?>" 
                                                    accept="image/*"
                                                    onchange="previewImage(this, <?php echo $i; ?>)">
                                                <label for="image_<?php echo $i; ?>" class="file-input-label">
                                                    <i class="bi bi-upload"></i> Choose Image
                                                </label>
                                            </div>
                                            
                                            <?php if ($hasImage): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-1" onclick="deleteImage('<?php echo $imageKey; ?>')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> <strong>Tips for great portfolio images:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Upload high-quality images (recommended: 800x600px or larger)</li>
                                    <li>Maximum file size: 5MB per image</li>
                                    <li>Supported formats: JPG, PNG, GIF, WebP</li>
                                    <li>Showcase your best work - before/after photos, finished styles, your shop interior, etc.</li>
                                    <li>You can upload up to 10 images to showcase your business</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="update_portfolio" class="btn btn-success btn-lg">
                                    <i class="bi bi-cloud-upload"></i> Upload Selected Images
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Business Stats -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Business Overview</h5>
                        <div class="row text-center">
                            <div class="col-3">
                                <h3 style="color: var(--color-burgundy);"><?php echo count(getBusinessServices($businessId)); ?></h3>
                                <small class="text-muted">Services</small>
                            </div>
                            <div class="col-3">
                                <h3 style="color: var(--color-rose);"><?php echo count(getBusinessEmployees($businessId)); ?></h3>
                                <small class="text-muted">Staff</small>
                            </div>
                            <div class="col-3">
                                <h3 style="color: var(--color-pink);"><?php echo count(getBusinessAppointments($businessId)); ?></h3>
                                <small class="text-muted">Bookings</small>
                            </div>
                            <div class="col-3">
                                <h3 style="color: var(--color-burgundy);"><?php echo number_format(calculateAverageRating($businessId), 1); ?></h3>
                                <small class="text-muted">Rating</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function previewLogo(input) {
    const preview = document.getElementById('logo_preview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function previewImage(input, slot) {
    const preview = document.getElementById('preview_' + slot);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview ${slot}">`;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function deleteImage(imageSlot) {
    if (confirm('Are you sure you want to delete this image?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_image';
        deleteInput.value = '1';
        
        const slotInput = document.createElement('input');
        slotInput.type = 'hidden';
        slotInput.name = 'image_slot';
        slotInput.value = imageSlot;
        
        form.appendChild(deleteInput);
        form.appendChild(slotInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>