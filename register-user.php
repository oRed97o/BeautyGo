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

.upload-btn-wrapper {
    position: relative;
    display: inline-block;
    margin-top: 10px;
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
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="fname" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="fname" name="fname" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="mname" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="mname" name="mname">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="surname" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="surname" name="surname" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="cstmr_email" name="cstmr_email" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_num" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="cstmr_num" name="cstmr_num" required placeholder="+63 912 345 6789">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cstmr_address" class="form-label">Address *</label>
                                <textarea class="form-control" id="cstmr_address" name="cstmr_address" rows="2" required placeholder="Street, Barangay, City"></textarea>
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

// Profile picture preview functionality
const profilePicInput = document.getElementById('profile_pic');
const profilePreview = document.getElementById('profilePreview');
const defaultIcon = document.getElementById('defaultIcon');
const removePhotoBtn = document.getElementById('removePhotoBtn');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const uploadBtnText = document.getElementById('uploadBtnText');

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
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePreview.src = e.target.result;
            profilePreview.classList.add('show');
            defaultIcon.classList.add('hide');
            removePhotoBtn.classList.add('show');
            fileNameDisplay.textContent = file.name;
            uploadBtnText.textContent = 'Change Photo';
        };
        reader.readAsDataURL(file);
    }
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
    }
});
</script>

<?php include 'includes/footer.php'; ?>