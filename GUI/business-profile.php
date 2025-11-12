<?php
require_once 'config.php';
require_once 'functions.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

$business = getCurrentBusiness();
$businessId = $business['business_id'] ?? $business['id'];
$album = getOrCreateBusinessAlbum($businessId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $businessData = [
        'business_name' => sanitize($_POST['business_name']),
        'business_type' => $_POST['business_type'],
        'description' => sanitize($_POST['description']),
        'business_desc' => sanitize($_POST['description']),
        'address' => sanitize($_POST['address']),
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

// Handle portfolio images update (10 images max)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_portfolio'])) {
    $uploadDir = 'uploads/portfolio/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $images = [];
    $uploadErrors = [];
    
    // Get existing images from album
    for ($i = 1; $i <= 10; $i++) {
        $existingImage = $album['image_' . $i] ?? '';
        if (!empty($existingImage)) {
            $images[$i] = $existingImage;
        }
    }
    
    // Process file uploads
    for ($i = 1; $i <= 10; $i++) {
        // Check if there's a file uploaded for this slot
        if (isset($_FILES['image_' . $i]) && $_FILES['image_' . $i]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_' . $i];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                $uploadErrors[] = "Image $i: Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
                continue;
            }
            
            // Validate file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $uploadErrors[] = "Image $i: File size exceeds 5MB limit.";
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'business_' . $businessId . '_img_' . $i . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            // Delete old image if exists
            if (isset($images[$i]) && !empty($images[$i]) && file_exists($images[$i])) {
                unlink($images[$i]);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $images[$i] = $targetPath;
            } else {
                $uploadErrors[] = "Image $i: Failed to upload file.";
            }
        }
        // Check if user wants to remove the image
        elseif (isset($_POST['remove_image_' . $i])) {
            // Delete the file if it exists
            if (isset($images[$i]) && !empty($images[$i]) && file_exists($images[$i])) {
                unlink($images[$i]);
            }
            unset($images[$i]);
        }
    }
    
    // Prepare images array for database (fill empty slots with empty strings)
    $imagesForDb = [];
    for ($i = 1; $i <= 10; $i++) {
        $imagesForDb[] = $images[$i] ?? '';
    }
    
    if (updateAlbumImages($businessId, $imagesForDb)) {
        if (empty($uploadErrors)) {
            $_SESSION['success'] = 'Portfolio images updated successfully';
        } else {
            $_SESSION['warning'] = 'Portfolio updated with some errors: ' . implode(' ', $uploadErrors);
        }
    } else {
        $_SESSION['error'] = 'Failed to update portfolio images';
    }
    header('Location: business-profile.php');
    exit;
}

$pageTitle = 'Business Profile - BeautyGo';
include 'includes/header.php';
?>

<style>
.portfolio-preview {
    position: relative;
    width: 100%;
    height: 150px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.portfolio-preview:hover {
    border-color: var(--color-rose);
    background-color: #f0f0f0;
}

.portfolio-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.portfolio-preview .placeholder {
    color: #999;
    text-align: center;
    pointer-events: none;
}

.image-slot {
    position: relative;
}

.remove-image-btn {
    position: absolute;
    top: 30px;
    right: 20px;
    z-index: 10;
    background: rgba(220, 53, 69, 0.9);
    border: none;
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-image-btn:hover {
    background: rgb(220, 53, 69);
    transform: scale(1.1);
}

.image-slot:hover .remove-image-btn {
    display: flex;
}

.file-input-hidden {
    display: none;
}



.file-name-display {
    font-size: 0.75rem;
    color: #666;
    margin-top: 0.25rem;
    word-break: break-all;
}
</style>

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
                
                <!-- Portfolio Images Card -->
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <h5 class="mb-3">
                            <i class="bi bi-images"></i> Portfolio Images
                            <span class="badge bg-primary ms-2">Maximum 10 images per business</span>
                        </h5>
                        
                        <form action="" method="POST" id="portfolioForm" enctype="multipart/form-data">
                            <div class="row">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <?php $imageUrl = $album['image_' . $i] ?? ''; ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                        <div class="image-slot">
                                            <label class="form-label fw-bold">Image <?php echo $i; ?></label>
                                            
                                            <div class="portfolio-preview" id="preview_<?php echo $i; ?>">
                                                <?php if (!empty($imageUrl)): ?>
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Portfolio <?php echo $i; ?>">
                                                    <button type="button" class="remove-image-btn" onclick="event.preventDefault(); removeImage(<?php echo $i; ?>)">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <div class="placeholder">
                                                        <i class="bi bi-cloud-upload" style="font-size: 2rem;"></i>
                                                        <p class="mb-0 small">No image</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <input 
                                                type="file" 
                                                class="file-input-hidden" 
                                                id="file_input_<?php echo $i; ?>" 
                                                name="image_<?php echo $i; ?>" 
                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                                onchange="previewImage(<?php echo $i; ?>, this)">
                                            
                                            <input type="hidden" name="remove_image_<?php echo $i; ?>" id="remove_flag_<?php echo $i; ?>" value="0">
                                            
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="document.getElementById('file_input_<?php echo $i; ?>').click()">
                                                <i class="bi bi-cloud-upload"></i> Click to Upload
                                            </button>
                                            
                                            <div class="file-name-display" id="filename_<?php echo $i; ?>">
                                                <?php if (!empty($imageUrl)): ?>
                                                    <?php echo basename($imageUrl); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> <strong>Tips for great portfolio images:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Click the "Click to Upload" button below each image slot to select a photo</li>
                                    <li>Supported formats: JPG, PNG, GIF, WEBP</li>
                                    <li>Maximum file size: 5MB per image</li>
                                    <li>Recommended size: 800x600px or larger for best quality</li>
                                    <li>Showcase your best work - before/after photos, finished styles, your shop interior, etc.</li>
                                    <li>Hover over existing images to see the remove button</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="update_portfolio" class="btn btn-success btn-lg">
                                    <i class="bi bi-cloud-upload"></i> Update Portfolio Images
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
                                <h3 style="color: var(--color-rose);"><?php echo count(getBusinessStaff($businessId)); ?></h3>
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
function previewImage(slot, input) {
    const preview = document.getElementById('preview_' + slot);
    const filenameDisplay = document.getElementById('filename_' + slot);
    const removeFlag = document.getElementById('remove_flag_' + slot);
    
    // Reset remove flag when new file is selected
    removeFlag.value = '0';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const file = input.files[0];
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            return;
        }
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Portfolio ${slot}">
                <button type="button" class="remove-image-btn" onclick="event.preventDefault(); removeImage(${slot})">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;
            filenameDisplay.textContent = file.name;
        };
        
        reader.readAsDataURL(file);
    }
}

function removeImage(slot) {
    const input = document.getElementById('file_input_' + slot);
    const preview = document.getElementById('preview_' + slot);
    const filenameDisplay = document.getElementById('filename_' + slot);
    const removeFlag = document.getElementById('remove_flag_' + slot);
    
    // Clear file input
    input.value = '';
    
    // Set remove flag
    removeFlag.value = '1';
    
    // Reset preview
    preview.innerHTML = `
        <div class="placeholder">
            <i class="bi bi-cloud-upload" style="font-size: 2rem;"></i>
            <p class="mb-0 small">Click to upload</p>
        </div>
    `;
    
    // Clear filename display
    filenameDisplay.textContent = '';
}

// Prevent form submission when clicking remove button
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-image-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>