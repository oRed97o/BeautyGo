<?php
// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log received data
    error_log("=== REGISTRATION TEST DATA ===");
    error_log("Action: " . ($_POST['action'] ?? 'NOT SET'));
    error_log("First Name: " . ($_POST['fname'] ?? 'NOT SET'));
    error_log("Email: " . ($_POST['cstmr_email'] ?? 'NOT SET'));
    error_log("Latitude: " . ($_POST['customer_latitude'] ?? 'NOT SET'));
    error_log("Longitude: " . ($_POST['customer_longitude'] ?? 'NOT SET'));
    
    // Call the registration function
    require_once __DIR__ . '/backend/auth.php';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4>Manual Registration Test</h4>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="register_user">
                
                <div class="mb-3">
                    <label>First Name</label>
                    <input type="text" name="fname" class="form-control" value="John" required>
                </div>
                
                <div class="mb-3">
                    <label>Middle Name</label>
                    <input type="text" name="mname" class="form-control" value="">
                </div>
                
                <div class="mb-3">
                    <label>Last Name</label>
                    <input type="text" name="surname" class="form-control" value="Test" required>
                </div>
                
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="cstmr_email" class="form-control" value="test<?php echo time(); ?>@example.com" required>
                </div>
                
                <div class="mb-3">
                    <label>Phone</label>
                    <input type="tel" name="cstmr_num" class="form-control" value="09123456789" required>
                </div>
                
                <div class="mb-3">
                    <label>Barangay</label>
                    <input type="text" name="cstmr_address" class="form-control" value="Poblacion" required>
                </div>
                
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" value="Test@123" required>
                </div>
                
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" value="Test@123" required>
                </div>
                
                <div class="mb-3">
                    <label>Latitude</label>
                    <input type="hidden" name="customer_latitude" value="14.0697" required>
                    <span>14.0697</span>
                </div>
                
                <div class="mb-3">
                    <label>Longitude</label>
                    <input type="hidden" name="customer_longitude" value="120.6328" required>
                    <span>120.6328</span>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Test Registration</button>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <h5>Check PHP error log for registration details:</h5>
        <code>C:\xampp1\php\logs\php_error.log</code>
    </div>
</div>
</body>
</html>
