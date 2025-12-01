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
                        <p class="login-subtitle">Access your account</p>
                        
                        <!-- Unified Login Form -->
                        <form action="backend/auth.php" method="POST" id="loginForm">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($failedEmail); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'passwordEye')">
                                        <i class="bi bi-eye" id="passwordEye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-login w-100 mb-3">Login</button>
                            
                            <div class="text-center signup-section">
                                <p class="mb-2 signup-text">Don't have an account?</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="register-user.php" class="signup-link flex-fill">Customer Sign Up</a>
                                    <a href="register-business.php" class="signup-link flex-fill">Business Sign Up</a>
                                </div>
                            </div>
                        </form>
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
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    }

    // Live validation for unified login form
    (function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function validateEmail(val) {
            return emailRegex.test(val.trim().toLowerCase());
        }

        function validatePassword(val) {
            return val.trim().length >= 6; // Accept both 6+ (business) and 8+ (customer)
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
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');

            if (emailField) emailField.classList.add('validate-field');
            if (passwordField) passwordField.classList.add('validate-field');

            // Validate fields
            const validateFields = function() {
                if (emailField) setState(emailField, validateEmail(emailField.value));
                if (passwordField) setState(passwordField, validatePassword(passwordField.value));
            };

            validateFields();

            if (emailField) {
                emailField.addEventListener('input', validateFields);
                emailField.addEventListener('blur', validateFields);
            }

            if (passwordField) {
                passwordField.addEventListener('input', validateFields);
                passwordField.addEventListener('blur', validateFields);
            }

            // Form submission validation
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    let isValid = true;
                    let firstInvalid = null;

                    if (!emailField.value.trim() || emailField.classList.contains('invalid')) {
                        emailField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || emailField;
                    }

                    if (!passwordField.value.trim() || passwordField.classList.contains('invalid')) {
                        passwordField.classList.add('invalid');
                        isValid = false;
                        firstInvalid = firstInvalid || passwordField;
                    }

                    if (!isValid) {
                        e.preventDefault();
                        if (firstInvalid) firstInvalid.focus();
                        alert('Please fill in all fields correctly.');
                    }
                });
            }
        });
    })();
</script>