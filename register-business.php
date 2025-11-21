<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_albums.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Business Registration - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4" style="color: var(--color-burgundy);">
                            <i class="bi bi-building"></i> Business Registration
                        </h2>
                        
                        <form action="backend/auth.php" method="POST" id="businessRegisterForm">
                            <input type="hidden" name="action" value="register_business">
                            
                            <!-- Business Information -->
                            <h5 class="mb-3">Business Information</h5>
                            <div class="mb-3">
                                <label for="business_name" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_type" class="form-label">Business Type *</label>
                                <select class="form-select" id="business_type" name="business_type" required>
                                    <option value="">Select a type...</option>
                                    <option value="Hair Salon">Hair Salon</option>
                                    <option value="Spa & Wellness">Spa & Wellness</option>
                                    <option value="Barbershop">Barbershop</option>
                                    <option value="Nail Salon">Nail Salon</option>
                                    <option value="Beauty Clinic">Beauty Clinic</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_desc" class="form-label">Business Description *</label>
                                <textarea class="form-control" id="business_desc" name="business_desc" rows="3" required placeholder="Describe your business, services, and what makes you unique..."></textarea>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Contact Information -->
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="business_email" name="business_email" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="business_num" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="business_num" name="business_num" required placeholder="+63 912 345 6789">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="business_password" name="business_password" minlength="6" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Location -->
                            <h5 class="mb-3">Location</h5>
                            <div class="mb-3">
                                <label for="business_address" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="business_address" name="business_address" required placeholder="Street, Barangay">
                            </div>
                            
                            <div class="mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" value="Nasugbu" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" step="0.000001" class="form-control" id="latitude" name="latitude" value="14.0697">
                                    <small class="text-muted">For location-based recommendations</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" step="0.000001" class="form-control" id="longitude" name="longitude" value="120.6328">
                                    <small class="text-muted">For location-based recommendations</small>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the Terms of Service and confirm that I have the authority to register this business
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-building-check"></i> Register Business
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
document.getElementById('businessRegisterForm').addEventListener('submit', function(e) {
    const password = document.getElementById('business_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>