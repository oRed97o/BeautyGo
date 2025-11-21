<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Customer Registration - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">

<style>
.profile-upload-container {
    text-align: center;
    margin-bottom: 20px;
}

.profile-preview-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 15px;
}

.profile-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--color-cream);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
}

.profile-preview.show {
    display: block;
}

.default-profile-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-burgundy), var(--color-rose));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.default-profile-icon.hide {
    display: none;
}

.upload-btn {
    background-color: var(--color-burgundy);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-block;
    margin-top: 10px;
}

.upload-btn:hover {
    background-color: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.upload-btn i {
    margin-right: 5px;
}

.remove-photo-btn {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--color-burgundy);
    color: white;
    border: 2px solid white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    z-index: 10;
}

.remove-photo-btn.show {
    display: flex;
}

.remove-photo-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.file-name-display {
    margin-top: 8px;
    font-size: 0.875rem;
    color: var(--color-burgundy);
    font-weight: 500;
}

/* Crop Modal Styles */
.crop-modal-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.85);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.crop-modal-overlay.show {
    display: flex;
}

.crop-modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

.crop-modal-header {
    text-align: center;
    margin-bottom: 25px;
}

.crop-modal-header h3 {
    color: var(--color-burgundy);
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.crop-modal-header p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.crop-preview-area {
    position: relative;
    width: 300px;
    height: 300px;
    margin: 0 auto 25px;
    border-radius: 50%;
    overflow: hidden;
    background: #f0f0f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    cursor: grab;
}

.crop-preview-area.dragging {
    cursor: grabbing;
}

.crop-preview-image {
    position: absolute;
    max-width: none;
    user-select: none;
    -webkit-user-drag: none;
}

.crop-instructions {
    text-align: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid var(--color-burgundy);
}

.crop-instructions i {
    color: var(--color-burgundy);
    font-size: 1.2rem;
    margin-right: 8px;
}

.crop-instructions p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
}

.crop-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

.crop-modal-buttons button {
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel-crop {
    background-color: #6c757d;
    color: white;
}

.btn-cancel-crop:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn-confirm-crop {
    background-color: var(--color-burgundy);
    color: white;
}

.btn-confirm-crop:hover {
    background-color: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card registration-card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="brand-logo-new d-inline-flex mb-3">
                                <i class="bi bi-scissors"></i> <span>BeautyGo</span>
                            </div>
                            <h2 class="registration-title">Customer Registration</h2>
                            <p class="registration-subtitle">Create your personalized beauty profile</p>
                        </div>
                        
                        <!-- Step Indicator -->
                        <div class="step-indicator mb-4">
                            <div class="step active">
                                <div class="step-number">1</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-number">2</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-number">3</div>
                            </div>
                        </div>
                        
                        <form action="backend/auth.php" method="POST" enctype="multipart/form-data" id="userRegisterForm">
                            <input type="hidden" name="action" value="register_user">
                            
                            <!-- Account Information -->
                            <div class="registration-section-header">
                                <h5 class="section-title">Basic Information</h5>
                                <p class="section-subtitle">Tell us about yourself</p>
                            </div>

                            <!-- Profile Picture Upload with Preview -->
                            <div class="profile-upload-container">
                                <div class="profile-preview-wrapper">
                                    <img src="" alt="Profile Preview" class="profile-preview" id="profilePreview">
                                    <div class="default-profile-icon" id="defaultIcon">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                    <button type="button" class="remove-photo-btn" id="removePhotoBtn" title="Remove photo">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div>
                                    <label for="profile_pic" class="upload-btn">
                                        <i class="bi bi-camera-fill"></i>
                                        <span id="uploadBtnText">Upload Photo</span>
                                    </label>
                                    <input type="file" class="form-control d-none" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/webp">
                                </div>
                                <div class="file-name-display" id="fileNameDisplay"></div>
                                <small class="text-muted d-block mt-2">Optional - JPG, PNG, GIF, WebP (Max 5MB)</small>
                                
                                <!-- Hidden input to store the cropped image -->
                                <input type="hidden" id="croppedImageData" name="cropped_image_data" value="">
                            </div>
                            
                            <!-- Beauty Profile (for personalized recommendations) -->
                            <h5 class="mb-3">Beauty Profile <small class="text-muted">(Optional - for personalized recommendations)</small></h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="face_shape" class="form-label">Face Shape</label>
                                    <select class="form-select" id="face_shape" name="face_shape">
                                        <option value="">Select...</option>
                                        <option value="oval">Oval</option>
                                        <option value="round">Round</option>
                                        <option value="square">Square</option>
                                        <option value="heart">Heart</option>
                                        <option value="diamond">Diamond</option>
                                        <option value="oblong">Oblong</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="body_type" class="form-label">Body Type</label>
                                    <select class="form-select" id="body_type" name="body_type">
                                        <option value="">Select...</option>
                                        <option value="slim">Slim</option>
                                        <option value="average">Average</option>
                                        <option value="athletic">Athletic</option>
                                        <option value="curvy">Curvy</option>
                                        <option value="plus-size">Plus Size</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="eye_color" class="form-label">Eye Color</label>
                                    <select class="form-select" id="eye_color" name="eye_color">
                                        <option value="">Select...</option>
                                        <option value="brown">Brown</option>
                                        <option value="black">Black</option>
                                        <option value="blue">Blue</option>
                                        <option value="green">Green</option>
                                        <option value="hazel">Hazel</option>
                                        <option value="gray">Gray</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="skin_tone" class="form-label">Skin Tone</label>
                                    <select class="form-select" id="skin_tone" name="skin_tone">
                                        <option value="">Select...</option>
                                        <option value="fair">Fair</option>
                                        <option value="light">Light</option>
                                        <option value="medium">Medium</option>
                                        <option value="tan">Tan</option>
                                        <option value="deep">Deep</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="hair_type" class="form-label">Hair Type</label>
                                    <select class="form-select" id="hair_type" name="hair_type">
                                        <option value="">Select...</option>
                                        <option value="straight">Straight</option>
                                        <option value="wavy">Wavy</option>
                                        <option value="curly">Curly</option>
                                        <option value="coily">Coily</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="hair_color" class="form-label">Hair Color</label>
                                    <select class="form-select" id="hair_color" name="hair_color">
                                        <option value="">Select...</option>
                                        <option value="black">Black</option>
                                        <option value="brown">Brown</option>
                                        <option value="blonde">Blonde</option>
                                        <option value="red">Red</option>
                                        <option value="gray">Gray</option>
                                        <option value="white">White</option>
                                        <option value="dyed">Dyed/Colored</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="current_hair_length" class="form-label">Current Hair Length</label>
                                    <select class="form-select" id="current_hair_length" name="current_hair_length">
                                        <option value="">Select...</option>
                                        <option value="very-short">Very Short</option>
                                        <option value="short">Short</option>
                                        <option value="medium">Medium</option>
                                        <option value="long">Long</option>
                                        <option value="very-long">Very Long</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="desired_hair_length" class="form-label">Desired Hair Length</label>
                                    <select class="form-select" id="desired_hair_length" name="desired_hair_length">
                                        <option value="">Select...</option>
                                        <option value="very-short">Very Short</option>
                                        <option value="short">Short</option>
                                        <option value="medium">Medium</option>
                                        <option value="long">Long</option>
                                        <option value="very-long">Very Long</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the Terms of Service and Privacy Policy
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-person-check"></i> Register Account
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="crop-modal-overlay" id="cropModal">
    <div class="crop-modal-content">
        <div class="crop-modal-header">
            <h3><i class="bi bi-crop"></i> Adjust Your Photo</h3>
            <p>Drag the image to position it perfectly</p>
        </div>
        
        <div class="crop-preview-area" id="cropPreviewArea">
            <img src="" alt="Crop Preview" class="crop-preview-image" id="cropPreviewImage">
        </div>
        
        <div class="crop-instructions">
            <i class="bi bi-hand-index"></i>
            <p>Click and drag the image to reposition • Your photo will be cropped to a circle</p>
        </div>
        
        <div class="crop-modal-buttons">
            <button type="button" class="btn-cancel-crop" id="btnCancelCrop">
                <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn-confirm-crop" id="btnConfirmCrop">
                <i class="bi bi-check-circle"></i> Set as Profile Photo
            </button>
        </div>
    </div>
</div>
</main>

<script>
// Password confirmation validation
document.getElementById('userRegisterForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return false;
    }
});

// Profile picture upload with crop modal
const profilePicInput = document.getElementById('profile_pic');
const profilePreview = document.getElementById('profilePreview');
const defaultIcon = document.getElementById('defaultIcon');
const removePhotoBtn = document.getElementById('removePhotoBtn');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const uploadBtnText = document.getElementById('uploadBtnText');
const croppedImageData = document.getElementById('croppedImageData');

// Crop modal elements
const cropModal = document.getElementById('cropModal');
const cropPreviewArea = document.getElementById('cropPreviewArea');
const cropPreviewImage = document.getElementById('cropPreviewImage');
const btnCancelCrop = document.getElementById('btnCancelCrop');
const btnConfirmCrop = document.getElementById('btnConfirmCrop');

let isDragging = false;
let startX, startY;
let initialX = 0, initialY = 0;
let currentX = 0, currentY = 0;
let currentFile = null;
let originalImageSrc = '';

// When user selects a file
profilePicInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, GIF, and WebP images are allowed');
            this.value = '';
            return;
        }
        
        currentFile = file;
        
        // Read and show in crop modal
        const reader = new FileReader();
        reader.onload = function(e) {
            originalImageSrc = e.target.result;
            openCropModal(originalImageSrc);
        };
        reader.readAsDataURL(file);
    }
});

// Open crop modal
function openCropModal(imageSrc) {
    const img = new Image();
    img.onload = function() {
        const containerSize = 300;
        const scale = Math.max(containerSize / img.width, containerSize / img.height);
        
        cropPreviewImage.style.width = (img.width * scale) + 'px';
        cropPreviewImage.style.height = (img.height * scale) + 'px';
        
        // Center the image
        currentX = (containerSize - img.width * scale) / 2;
        currentY = (containerSize - img.height * scale) / 2;
        
        cropPreviewImage.style.left = currentX + 'px';
        cropPreviewImage.style.top = currentY + 'px';
        
        initialX = currentX;
        initialY = currentY;
        
        cropPreviewImage.src = imageSrc;
        cropModal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    };
    img.src = imageSrc;
}

// Close crop modal
function closeCropModal() {
    cropModal.classList.remove('show');
    document.body.style.overflow = ''; // Restore scroll
    profilePicInput.value = ''; // Clear file input
    currentX = 0;
    currentY = 0;
    initialX = 0;
    initialY = 0;
}

// Drag functionality in crop modal
cropPreviewArea.addEventListener('mousedown', startDrag);
cropPreviewArea.addEventListener('touchstart', startDrag);

function startDrag(e) {
    isDragging = true;
    cropPreviewArea.classList.add('dragging');
    
    if (e.type === 'touchstart') {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    } else {
        startX = e.clientX;
        startY = e.clientY;
        e.preventDefault();
    }
    
    initialX = currentX;
    initialY = currentY;
}

document.addEventListener('mousemove', drag);
document.addEventListener('touchmove', drag);

function drag(e) {
    if (!isDragging) return;
    
    let clientX, clientY;
    
    if (e.type === 'touchmove') {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    
    const deltaX = clientX - startX;
    const deltaY = clientY - startY;
    
    currentX = initialX + deltaX;
    currentY = initialY + deltaY;
    
    cropPreviewImage.style.left = currentX + 'px';
    cropPreviewImage.style.top = currentY + 'px';
}

document.addEventListener('mouseup', endDrag);
document.addEventListener('touchend', endDrag);

function endDrag() {
    if (isDragging) {
        isDragging = false;
        cropPreviewArea.classList.remove('dragging');
    }
}

// Cancel crop
btnCancelCrop.addEventListener('click', function() {
    closeCropModal();
});

// Confirm crop and set as profile picture
btnConfirmCrop.addEventListener('click', function() {
    // Create a canvas to crop the image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const size = 300; // Crop size
    
    canvas.width = size;
    canvas.height = size;
    
    // Create circular clip
    ctx.beginPath();
    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    ctx.closePath();
    ctx.clip();
    
    // Draw the image with current position
    const img = new Image();
    img.onload = function() {
        ctx.drawImage(img, currentX, currentY, parseFloat(cropPreviewImage.style.width), parseFloat(cropPreviewImage.style.height));
        
        // Convert canvas to blob
        canvas.toBlob(function(blob) {
            // Create a data URL for preview
            const reader = new FileReader();
            reader.onload = function(e) {
                // Set the preview image
                profilePreview.src = e.target.result;
                profilePreview.classList.add('show');
                defaultIcon.classList.add('hide');
                removePhotoBtn.classList.add('show');
                fileNameDisplay.textContent = currentFile.name;
                uploadBtnText.textContent = 'Change Photo';
                
                // Store the cropped image data
                croppedImageData.value = e.target.result;
                
                closeCropModal();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.9);
    };
    img.src = originalImageSrc;
});

// Remove photo functionality
removePhotoBtn.addEventListener('click', function() {
    if (confirm('Remove this photo?')) {
        profilePicInput.value = '';
        profilePreview.src = '';
        profilePreview.classList.remove('show');
        defaultIcon.classList.remove('hide');
        removePhotoBtn.classList.remove('show');
        fileNameDisplay.textContent = '';
        uploadBtnText.textContent = 'Upload Photo';
        croppedImageData.value = '';
    }
});

// Close modal when clicking outside
cropModal.addEventListener('click', function(e) {
    if (e.target === cropModal) {
        closeCropModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>