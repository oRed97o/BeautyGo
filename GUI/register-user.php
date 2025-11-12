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
                            
                            <!-- Account Information -->
                            <div class="registration-section-header">
                                <h5 class="section-title">Basic Information</h5>
                                <p class="section-subtitle">Tell us about yourself</p>
                            </div>

                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                                <small class="text-muted">Optional - Upload a profile picture (JPG, PNG, GIF, WebP - Max 5MB)</small>
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

// Profile picture preview
document.getElementById('profile_pic').addEventListener('change', function(e) {
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
    }
});
</script>

<?php include 'includes/footer.php'; ?>