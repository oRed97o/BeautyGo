<?php


require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isLoggedIn(), isBusinessLoggedIn()
require_once 'backend/function_customers.php';      // for header.php getCurrentCustomer()
require_once 'backend/function_businesses.php';     // for header.php getCurrentBusiness()
require_once 'backend/function_notifications.php';  // for header.php notifications

// Redirect if already logged in
if (isLoggedIn()) {
    if (isBusinessLoggedIn()) {
        header('Location: business-dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$pageTitle = 'Login - BeautyGo';
include 'includes/header.php';
?>

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-4">
                        <h2 class="login-title">Login to BeautyGo</h2>
                        <p class="login-subtitle">Access your account to book appointments and more</p>
                        
                        <!-- Login Type Tabs -->
                        <ul class="nav nav-pills login-tabs mb-4" id="loginTabs" role="tablist">
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link active w-100" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab">
                                    <i class="bi bi-person-circle"></i> Customer
                                </button>
                            </li>
                            <li class="nav-item flex-fill" role="presentation">
                                <button class="nav-link w-100" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab">
                                    <i class="bi bi-shop"></i> Business
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="loginTabContent">
                            <!-- Customer Login -->
                            <div class="tab-pane fade show active" id="customer" role="tabpanel">
                                <form action="backend/auth.php" method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="type" value="customer">
                                    
                                    <div class="mb-3">
                                        <label for="customerEmail" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="customerEmail" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="customerPassword" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="customerPassword" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('customerPassword', 'customerEye')">
                                                <i class="bi bi-eye" id="customerEye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-login w-100 mb-3">
                                        Login as Customer
                                    </button>
                                    
                                    <div class="text-center">
                                        <p class="mb-0 signup-text">Don't have an account? <a href="register-user.php" class="signup-link">Sign up</a></p>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Business Login -->
                            <div class="tab-pane fade" id="business" role="tabpanel">
                                <form action="backend/auth.php" method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <input type="hidden" name="type" value="business">
                                    
                                    <div class="mb-3">
                                        <label for="businessEmail" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="businessEmail" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="businessPassword" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="businessPassword" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('businessPassword', 'businessEye')">
                                                <i class="bi bi-eye" id="businessEye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-login w-100 mb-3">
                                        Login as Business
                                    </button>
                                    
                                    <div class="text-center">
                                        <p class="mb-0 signup-text">Don't have an account? <a href="register-business.php" class="signup-link">Sign up</a></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
