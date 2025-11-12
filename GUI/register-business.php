<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Business Registration - BeautyGo';
include 'includes/header.php';
?>

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4" style="color: var(--color-burgundy);">
                            <i class="bi bi-building"></i> Business Registration
                        </h2>
                        
                        <form action="auth.php" method="POST" id="businessRegisterForm">
                            <input type="hidden" name="action" value="register_business">
                            
                            <!-- Business Information -->
                            <h5 class="mb-3">Business Information</h5>
                            <div class="mb-3">
                                <label for="business_name" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_type" class="form-label">Business Type *</label>
                                    <select class="form-select" id="business_type" name="business_type" required>
                                        <option value="salon">Hair Salon</option>
                                        <option value="spa">Spa & Wellness</option>
                                        <option value="barbershop">Barbershop</option>
                                        <option value="nail-salon">Nail Salon</option>
                                        <option value="beauty-clinic">Beauty Clinic</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="owner_name" class="form-label">Owner Name *</label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Business Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required placeholder="Describe your business, services, and what makes you unique..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Business Image URL</label>
                                <input type="url" class="form-control" id="image" name="image" placeholder="https://example.com/image.jpg">
                                <small class="text-muted">Enter a URL to your business image or logo</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Contact Information -->
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Location -->
                            <h5 class="mb-3">Location</h5>
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" value="Nasugbu" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="opening_hours" class="form-label">Opening Hours *</label>
                                    <input type="text" class="form-control" id="opening_hours" name="opening_hours" placeholder="e.g., 9:00 AM - 6:00 PM" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" step="0.0001" class="form-control" id="latitude" name="latitude" value="14.0697">
                                    <small class="text-muted">For location-based recommendations</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" step="0.0001" class="form-control" id="longitude" name="longitude" value="120.6328">
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

<?php include 'includes/footer.php'; ?>
