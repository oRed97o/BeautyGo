<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Customer Registration - BeautyGo';
include 'includes/header.php';
?>

<style>
.profile-picture-upload {
    text-align: center;
    padding: 20px;
    background: var(--color-cream);
    border-radius: 12px;
    margin-bottom: 20px;
}

.profile-preview-container {
    position: relative;
    display: inline-block;
    margin-bottom: 15px;
}

.profile-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--color-burgundy);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-preview i {
    font-size: 4rem;
    color: var(--color-burgundy);
}

.profile-preview img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.upload-btn-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.choose-photo-btn {
    background: var(--color-burgundy);
    color: white;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
}

.choose-photo-btn:hover {
    background: var(--color-rose);
    transform: translateY(-2px);
}

.upload-btn-wrapper input[type=file] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.remove-photo-btn {
    display: none;
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.remove-photo-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.photo-requirements {
    font-size: 0.85rem;
    color: #666;
    margin-top: 10px;
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
                        
                        <form action="auth.php" method="POST" enctype="multipart/form-data" id="userRegisterForm">
                            <input type="hidden" name="action" value="register_user">
                            
                            <!-- Profile Picture Upload Section -->
                            <div class="profile-picture-upload">
                                <h5 class="mb-3">Profile Picture</h5>
                                <div class="profile-preview-container">
                                    <div class="profile-preview" id="profilePreviewBox">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                </div>
                                <div>
                                    <!-- Choose Photo Button (visible by default) -->
                                    <div class="upload-btn-wrapper" id="choosePhotoWrapper">
                                        <button type="button" class="choose-photo-btn">
                                            <i class="bi bi-camera"></i> Choose Photo
                                        </button>
                                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                    </div>
                                    
                                    <!-- Remove Photo Button (hidden by default) -->
                                    <button type="button" class="remove-photo-btn" id="removePhotoBtn">
                                        <i class="bi bi-trash"></i> Remove Photo
                                    </button>
                                </div>
                                <div class="photo-requirements">
                                    <i class="bi bi-info-circle"></i> JPG, PNG or GIF. Max size: 5MB
                                </div>
                            </div>
                            
                            <!-- Account Information -->
                            <div class="registration-section-header">
                                <h5 class="section-title">Basic Information</h5>
                                <p class="section-subtitle">Tell us about yourself</p>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                            
                            <hr class="my-4">
                            
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
                                    <label for="body_mass" class="form-label">Body Type</label>
                                    <select class="form-select" id="body_mass" name="body_mass">
                                        <option value="">Select...</option>
                                        <option value="slim">Slim</option>
                                        <option value="average">Average</option>
                                        <option value="athletic">Athletic</option>
                                        <option value="curvy">Curvy</option>
                                        <option value="plus-size">Plus Size</option>
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
                            
                            <div class="mb-3">
                                <label for="preferences" class="form-label">Beauty Preferences & Notes</label>
                                <textarea class="form-control" id="preferences" name="preferences" rows="3" placeholder="Any specific preferences, allergies, or notes for beauticians..."></textarea>
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
</main>

<script>
// Profile picture upload and preview functionality
const profilePictureInput = document.getElementById('profile_picture');
const profilePreviewBox = document.getElementById('profilePreviewBox');
const choosePhotoWrapper = document.getElementById('choosePhotoWrapper');
const removePhotoBtn = document.getElementById('removePhotoBtn');

profilePictureInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            e.target.value = '';
            return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            e.target.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePreviewBox.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview">';
            
            // Hide Choose Photo button, show Remove Photo button
            choosePhotoWrapper.style.display = 'none';
            removePhotoBtn.style.display = 'inline-block';
        };
        reader.readAsDataURL(file);
    }
});

// Remove photo functionality
removePhotoBtn.addEventListener('click', function() {
    // Clear file input
    profilePictureInput.value = '';
    
    // Reset preview to default icon
    profilePreviewBox.innerHTML = '<i class="bi bi-person-circle"></i>';
    
    // Show Choose Photo button, hide Remove Photo button
    choosePhotoWrapper.style.display = 'inline-block';
    removePhotoBtn.style.display = 'none';
});
</script>

<?php include 'includes/footer.php'; ?>