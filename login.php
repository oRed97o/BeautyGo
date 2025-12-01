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

    // Password visibility toggle function
    function togglePassword(inputId, eyeId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(eyeId);
        
        if (passwordInput.type === 'password') {
            // Show password
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            // Hide password
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    }

    // Live validation for login.php
    (function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function validateEmail(val) {
            const v = val.trim().toLowerCase();
            return emailRegex.test(v); // Only requires @ and valid email format, NOT .com
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
            
            function resetCustomerPassword() {
                const pwField = document.getElementById('customerPassword');
                pwField.value = '';
                pwField.classList.remove('valid', 'invalid');
                pwField.type = 'password';
                const customerEye = document.getElementById('customerEye');
                customerEye.classList.remove('bi-eye-slash');
                customerEye.classList.add('bi-eye');
            }
            
            function resetBusinessPassword() {
                const pwField = document.getElementById('businessPassword');
                pwField.value = '';
                pwField.classList.remove('valid', 'invalid');
                pwField.type = 'password';
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
            
            if (customerEmail) {
                resetCustomerPassword();
                customerTab.click();
            }
            
            if (businessEmail) {
                resetBusinessPassword();
                businessTab.click();
            }
            
            // Reset forms when switching tabs
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

            // Prevent form submission if fields are invalid
            const customerForm = document.querySelector('#customer form');
            const businessForm = document.querySelector('#business form');

            if (customerForm) {
                customerForm.addEventListener('submit', function(e) {
                    const emailField = document.getElementById('customerEmail');
                    const passwordField = document.getElementById('customerPassword');
                    
                    let isValid = true;
                    let firstInvalid = null;

                    // Check email
                    if (!emailField.value.trim() || emailField.classList.contains('invalid')) {
                        emailField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || emailField;
                    }

                    // Check password
                    if (!passwordField.value.trim() || passwordField.classList.contains('invalid')) {
                        passwordField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || passwordField;
                    }

                    if (!isValid) {
                        e.preventDefault();
                        if (firstInvalid) firstInvalid.focus();
                        alert('Please fix all highlighted fields before continuing.');
                    }
                });
            }

            if (businessForm) {
                businessForm.addEventListener('submit', function(e) {
                    const emailField = document.getElementById('businessEmail');
                    const passwordField = document.getElementById('businessPassword');
                    
                    let isValid = true;
                    let firstInvalid = null;

                    // Check email
                    if (!emailField.value.trim() || emailField.classList.contains('invalid')) {
                        emailField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || emailField;
                    }

                    // Check password
                    if (!passwordField.value.trim() || passwordField.classList.contains('invalid')) {
                        passwordField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || passwordField;
                    }

                    if (!isValid) {
                        e.preventDefault();
                        if (firstInvalid) firstInvalid.focus();
                        alert('Please fix all highlighted fields before continuing.');
                    }
                });
            }
        });
    })();
</script>