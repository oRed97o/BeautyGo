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

// Store failed login info if available
$failedEmail = $_SESSION['failed_email'] ?? '';
$failedType = $_SESSION['failed_type'] ?? '';

// Clear session variables after storing them
unset($_SESSION['failed_email']);
unset($_SESSION['failed_type']);
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
                                        <input type="email" class="form-control" id="customerEmail" name="email" value="<?php echo $failedType === 'customer' ? htmlspecialchars($failedEmail) : ''; ?>" required>
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
                                        <input type="email" class="form-control" id="businessEmail" name="email" value="<?php echo $failedType === 'business' ? htmlspecialchars($failedEmail) : ''; ?>" required>
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

<script>
    // Live validation
(function() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateEmail(val) {
        const v = val.trim().toLowerCase();
        return emailRegex.test(v); // Removed the .endsWith('.com') requirement
    }

    function validatePassword(val) {
        return val.trim().length >= 8;
    }

    function setState(el, ok) {
        if (!el) return;
        el.classList.remove('valid', 'invalid');
        const val = el.value.trim();
        if (!val) {
            el.removeAttribute('aria-invalid');
            return;
        }
        if (ok) {
            el.classList.add('valid');
            el.setAttribute('aria-invalid', 'false');
        } else {
            el.classList.add('invalid');
            el.setAttribute('aria-invalid', 'true');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const fields = [
            { id: 'customerEmail', validator: (el) => validateEmail(el.value) },
            { id: 'customerPassword', validator: (el) => validatePassword(el.value) },
            { id: 'businessEmail', validator: (el) => validateEmail(el.value) },
            { id: 'businessPassword', validator: (el) => validatePassword(el.value) }
        ];

        fields.forEach(item => {
            const el = document.getElementById(item.id);
            if (!el) return;
            el.classList.add('validate-field');
            
            // Initial state
            setState(el, item.validator(el));

            const handler = function() {
                setState(el, item.validator(el));
            };

            el.addEventListener('input', handler);
            el.addEventListener('blur', handler);
        });

        // Clear form fields when switching tabs
        const customerTab = document.getElementById('customer-tab');
        const businessTab = document.getElementById('business-tab');
        
        // Function to reset form fields but keep email
        function resetCustomerPassword() {
            const pwField = document.getElementById('customerPassword');
            pwField.value = '';
            pwField.classList.remove('valid', 'invalid');
            // Reset password field type to 'password'
            pwField.type = 'password';
            // Reset eye icon
            const customerEye = document.getElementById('customerEye');
            customerEye.classList.remove('bi-eye-slash');
            customerEye.classList.add('bi-eye');
        }
        
        function resetBusinessPassword() {
            const pwField = document.getElementById('businessPassword');
            pwField.value = '';
            pwField.classList.remove('valid', 'invalid');
            // Reset password field type to 'password'
            pwField.type = 'password';
            // Reset eye icon
            const businessEye = document.getElementById('businessEye');
            businessEye.classList.remove('bi-eye-slash');
            businessEye.classList.add('bi-eye');
        }
        
        function resetCustomerForm() {
            const emailField = document.getElementById('customerEmail');
            emailField.value = '';
            emailField.classList.remove('valid', 'invalid');
            resetCustomerPassword();
        }
        
        function resetBusinessForm() {
            const emailField = document.getElementById('businessEmail');
            emailField.value = '';
            emailField.classList.remove('valid', 'invalid');
            resetBusinessPassword();
        }
        
        // Check if we're coming from a failed login
        const customerEmail = document.getElementById('customerEmail').value;
        const businessEmail = document.getElementById('businessEmail').value;
        
        // If customer email is populated (failed login), only clear password and switch to customer tab
        if (customerEmail) {
            resetCustomerPassword();
            customerTab.click();
        }
        
        // If business email is populated (failed login), only clear password and switch to business tab
        if (businessEmail) {
            resetBusinessPassword();
            businessTab.click();
        }
        
        // Reset forms when switching tabs (clears both email and password)
        customerTab.addEventListener('click', function() {
            resetBusinessForm();
            if (!customerEmail) {
                resetCustomerForm();
            }
        });
        
        businessTab.addEventListener('click', function() {
            resetCustomerForm();
            if (!businessEmail) {
                resetBusinessForm();
            }
        });
    });
})();