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

// Handle portfolio images update (logo + 10 images) - FIXED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_portfolio'])) {
    $hasError = false;
    $updatedAny = false;
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['logo']['type'], $allowedTypes) && $_FILES['logo']['size'] <= 5 * 1024 * 1024) {
            $imageData = file_get_contents($_FILES['logo']['tmp_name']);
            $compressedLogo = compressImage($imageData, 800, 800, 85);
            
            if (updateSingleAlbumImage($businessId, 'logo', $compressedLogo)) {
                $updatedAny = true;
            } else {
                $_SESSION['error'] = 'Failed to upload logo';
                $hasError = true;
            }
        } else {
            $_SESSION['error'] = 'Logo: Invalid file type or size too large (max 5MB)';
            $hasError = true;
        }
    }
    
    // Handle image uploads (1-10)
    if (!$hasError) {
        for ($i = 1; $i <= 10; $i++) {
            $fileKey = 'image_' . $i;
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES[$fileKey]['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    if ($_FILES[$fileKey]['size'] <= 5 * 1024 * 1024) {
                        $imageData = file_get_contents($_FILES[$fileKey]['tmp_name']);
                        $compressedImage = compressImage($imageData, 1200, 800, 85);
                        
                        $slot = 'image' . $i;
                        if (updateSingleAlbumImage($businessId, $slot, $compressedImage)) {
                            $updatedAny = true;
                        } else {
                            $_SESSION['error'] = 'Failed to upload image ' . $i;
                            $hasError = true;
                            break;
                        }
                    } else {
                        $_SESSION['error'] = 'Image ' . $i . ' is too large. Maximum size is 5MB.';
                        $hasError = true;
                        break;
                    }
                } else {
                    $_SESSION['error'] = 'Image ' . $i . ' has invalid file type.';
                    $hasError = true;
                    break;
                }
            }
        }
    }
    
    if (!$hasError && $updatedAny) {
        $_SESSION['success'] = 'Portfolio images updated successfully';
    } elseif (!$hasError && !$updatedAny) {
        $_SESSION['error'] = 'No images were selected for upload';
    }
    
    header('Location: business-profile.php');
    exit;
}

// Handle individual image deletion - FIXED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageSlot = $_POST['image_slot'];
    
    $validSlots = ['logo', 'image1', 'image2', 'image3', 'image4', 'image5', 
                   'image6', 'image7', 'image8', 'image9', 'image10'];
    
    if (preg_match('/^image(\d+)$/', $imageSlot, $matches)) {
        $imageSlot = 'image' . $matches[1];
    }
    
    if (in_array($imageSlot, $validSlots)) {
        if (deleteAlbumImage($businessId, $imageSlot)) {
            $_SESSION['success'] = 'Image deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete image';
        }
    } else {
        $_SESSION['error'] = 'Invalid image slot';
    }
    
    header('Location: business-profile.php');
    exit;
}

$pageTitle = 'Business Profile - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/business-profile.css">

<style>
.logo-preview {
    width: 200px;
    height: 200px;
    border-radius: 10px;
    border: 3px dashed var(--color-burgundy);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    overflow: hidden;
    background: #f8f9fa;
}
.logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.logo-preview .placeholder {
    text-align: center;
    color: #999;
}
.portfolio-preview {
    width: 100%;
    height: 180px;
    border-radius: 8px;
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f8f9fa;
    margin-bottom: 10px;
}
.portfolio-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.portfolio-preview .placeholder {
    text-align: center;
    color: #999;
}
.file-input-wrapper {
    position: relative;
}
.file-input-wrapper input[type="file"] {
    display: none;
}
.file-input-label {
    display: block;
    padding: 8px 15px;
    background: var(--color-burgundy);
    color: white;
    border-radius: 6px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}
.file-input-label:hover {
    background: var(--color-rose);
    transform: translateY(-2px);
}
.image-slot {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: all 0.3s ease;
}
.image-slot:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
.add-image-slot {
    border: 3px dashed var(--color-burgundy);
    border-radius: 8px;
    padding: 15px;
    background: #fef8f5;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.add-image-slot:hover {
    background: var(--color-cream);
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}
.add-image-slot i {
    font-size: 3rem;
    color: var(--color-burgundy);
    margin-bottom: 10px;
}
.add-image-slot p {
    color: var(--color-burgundy);
    font-weight: 500;
    margin: 0;
}
.image-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.image-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.image-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.image-card-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}
.image-card-actions button {
    opacity: 0;
    transition: opacity 0.3s ease;
}
.image-card:hover .image-card-actions button {
    opacity: 1;
}
</style>

<main>
    <div class="container my-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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
                            
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($business['business_email'] ?? ''); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <hr class="my-4">
                            
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
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="deleteImage('logo')">
                                        <i class="bi bi-trash"></i> Delete Logo
                                    </button>
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
                            <span class="badge bg-primary ms-2" id="imageCount">
                                <?php 
                                $imageCount = 0;
                                for ($i = 1; $i <= 10; $i++) {
                                    if (!empty($album['image' . $i])) $imageCount++;
                                }
                                echo $imageCount;
                                ?> / 10 images
                            </span>
                        </h5>
                        
                        <form action="" method="POST" enctype="multipart/form-data" id="portfolioForm">
                            <div class="portfolio-grid" id="portfolioGrid">
                                <?php 
                                $displayedImages = 0;
                                for ($i = 1; $i <= 10; $i++): 
                                    $imageKey = 'image' . $i;
                                    if (!empty($album[$imageKey])): 
                                        $displayedImages++;
                                ?>
                                    <div class="image-card" data-slot="<?php echo $i; ?>">
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($album[$imageKey]); ?>" alt="Portfolio <?php echo $i; ?>">
                                        <div class="image-card-actions">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteImage('<?php echo $imageKey; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endfor;
                                
                                if ($displayedImages < 10):
                                ?>
                                    <label for="newImage" class="add-image-slot">
                                        <i class="bi bi-plus-circle"></i>
                                        <p>Add Image</p>
                                        <small class="text-muted"><?php echo (10 - $displayedImages); ?> slots remaining</small>
                                    </label>
                                    <input type="file" id="newImage" name="new_image" accept="image/*" style="display: none;" onchange="handleNewImage(this)">
                                <?php endif; ?>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> <strong>Tips for great portfolio images:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Upload high-quality images (recommended: 1200x800px or larger)</li>
                                    <li>Maximum file size: 5MB per image</li>
                                    <li>Supported formats: JPG, PNG, GIF, WebP</li>
                                    <li>Images are automatically compressed and optimized</li>
                                    <li>Showcase your best work - before/after photos, finished styles, your shop interior</li>
                                </ul>
                            </div>
                            
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <input type="file" id="image_<?php echo $i; ?>" name="image_<?php echo $i; ?>" accept="image/*" style="display: none;">
                            <?php endfor; ?>
                            
                            <div class="d-grid" id="uploadButtonContainer" style="display: none;">
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
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function handleNewImage(input) {
    if (input.files && input.files[0]) {
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(input.files[0].type)) {
            alert('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
            input.value = '';
            return;
        }
        // Find first empty slot
        for (let i = 1; i <= 10; i++) {
            const slotInput = document.getElementById('image_' + i);
            const existingCard = document.querySelector(`.image-card[data-slot="${i}"]`);
            if (!existingCard) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(input.files[0]);
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
                    } else {
                        document.getElementById('portfolioGrid').appendChild(card);
                    }
                    updateImageCount();
                    document.getElementById('uploadButtonContainer').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
                break;
            }
        }
        input.value = '';
    }
}

function removePreview(slot) {
    const card = document.querySelector(`.image-card[data-slot="${slot}"]`);
    if (card && !card.querySelector('img').src.includes('base64')) {
        card.remove();
        document.getElementById('image_' + slot).value = '';
        updateImageCount();
        const addButton = document.querySelector('.add-image-slot');
        if (!addButton) {
            const newAddButton = document.createElement('label');
            newAddButton.setAttribute('for', 'newImage');
            newAddButton.className = 'add-image-slot';
            newAddButton.innerHTML = `
                <i class="bi bi-plus-circle"></i>
                <p>Add Image</p>
                <small class="text-muted">Click to upload</small>
            `;
            document.getElementById('portfolioGrid').appendChild(newAddButton);
        }
        const hasNewImages = document.querySelectorAll('.image-card img:not([src*="base64"])').length > 0;
        if (!hasNewImages) {
            document.getElementById('uploadButtonContainer').style.display = 'none';
        }
    }
}

function updateImageCount() {
    const existingImages = document.querySelectorAll('.image-card img[src*="base64"]').length;
    const newImages = document.querySelectorAll('.image-card img:not([src*="base64"])').length;
    const totalImages = existingImages + newImages;
    document.getElementById('imageCount').textContent = totalImages + ' / 10 images';
    const addButton = document.querySelector('.add-image-slot');
    if (totalImages >= 10 && addButton) {
        addButton.remove();
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