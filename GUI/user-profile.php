<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$userId = $user['customer_id'] ?? $user['id']; // Support both column names

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $userData = [
        'name' => sanitize($_POST['name']),
        'surname' => sanitize($_POST['surname'] ?? ''),
        'phone' => sanitize($_POST['phone']),
        'celler_num' => sanitize($_POST['phone']),
        'face_shape' => $_POST['face_shape'] ?? '',
        'skin_tone' => $_POST['skin_tone'] ?? '',
        'body_mass' => $_POST['body_mass'] ?? '',
        'desired_hair_length' => $_POST['desired_hair_length'] ?? '',
        'total_length' => $_POST['desired_hair_length'] ?? ''
    ];
    
    if (updateCustomer($userId, $userData)) {
        $_SESSION['success'] = 'Profile updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
    header('Location: user-profile.php');
    exit;
}

$pageTitle = 'My Profile - BeautyGo';
include 'includes/header.php';
?>

<style>
/* Back button styling */
.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--color-burgundy);
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.back-button:hover {
    background-color: var(--color-cream);
    color: var(--color-rose);
    transform: translateX(-4px);
}

.back-button i {
    font-size: 1.2rem;
}
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="mb-4">My Profile</h2>
                        
                        <form action="" method="POST">
                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['celler_num'] ?? $user['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="member_since" class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo formatDate($user['created_at']); ?>" disabled>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Beauty Profile -->
                            <h5 class="mb-3">Beauty Profile</h5>
                            <p class="text-muted small mb-3">Help us provide personalized recommendations</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="face_shape" class="form-label">Face Shape</label>
                                    <select class="form-select" id="face_shape" name="face_shape">
                                        <option value="">Not specified</option>
                                        <option value="oval" <?php echo ($user['face_shape'] ?? '') == 'oval' ? 'selected' : ''; ?>>Oval</option>
                                        <option value="round" <?php echo ($user['face_shape'] ?? '') == 'round' ? 'selected' : ''; ?>>Round</option>
                                        <option value="square" <?php echo ($user['face_shape'] ?? '') == 'square' ? 'selected' : ''; ?>>Square</option>
                                        <option value="heart" <?php echo ($user['face_shape'] ?? '') == 'heart' ? 'selected' : ''; ?>>Heart</option>
                                        <option value="diamond" <?php echo ($user['face_shape'] ?? '') == 'diamond' ? 'selected' : ''; ?>>Diamond</option>
                                        <option value="oblong" <?php echo ($user['face_shape'] ?? '') == 'oblong' ? 'selected' : ''; ?>>Oblong</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="skin_tone" class="form-label">Skin Tone</label>
                                    <select class="form-select" id="skin_tone" name="skin_tone">
                                        <option value="">Not specified</option>
                                        <option value="fair" <?php echo ($user['skin_tone'] ?? '') == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                        <option value="light" <?php echo ($user['skin_tone'] ?? '') == 'light' ? 'selected' : ''; ?>>Light</option>
                                        <option value="medium" <?php echo ($user['skin_tone'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="tan" <?php echo ($user['skin_tone'] ?? '') == 'tan' ? 'selected' : ''; ?>>Tan</option>
                                        <option value="deep" <?php echo ($user['skin_tone'] ?? '') == 'deep' ? 'selected' : ''; ?>>Deep</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="body_mass" class="form-label">Body Type</label>
                                    <select class="form-select" id="body_mass" name="body_mass">
                                        <option value="">Not specified</option>
                                        <option value="slim" <?php echo ($user['body_mass'] ?? '') == 'slim' ? 'selected' : ''; ?>>Slim</option>
                                        <option value="average" <?php echo ($user['body_mass'] ?? '') == 'average' ? 'selected' : ''; ?>>Average</option>
                                        <option value="athletic" <?php echo ($user['body_mass'] ?? '') == 'athletic' ? 'selected' : ''; ?>>Athletic</option>
                                        <option value="curvy" <?php echo ($user['body_mass'] ?? '') == 'curvy' ? 'selected' : ''; ?>>Curvy</option>
                                        <option value="plus-size" <?php echo ($user['body_mass'] ?? '') == 'plus-size' ? 'selected' : ''; ?>>Plus Size</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="desired_hair_length" class="form-label">Desired Hair Length</label>
                                    <select class="form-select" id="desired_hair_length" name="desired_hair_length">
                                        <option value="">Not specified</option>
                                        <option value="very-short" <?php echo ($user['total_length'] ?? '') == 'very-short' ? 'selected' : ''; ?>>Very Short</option>
                                        <option value="short" <?php echo ($user['total_length'] ?? '') == 'short' ? 'selected' : ''; ?>>Short</option>
                                        <option value="medium" <?php echo ($user['total_length'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="long" <?php echo ($user['total_length'] ?? '') == 'long' ? 'selected' : ''; ?>>Long</option>
                                        <option value="very-long" <?php echo ($user['total_length'] ?? '') == 'very-long' ? 'selected' : ''; ?>>Very Long</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Save Changes
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Booking Stats -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="mb-3">Quick Stats</h5>
                        <div class="row text-center">
                            <div class="col-4">
                                <h3 style="color: var(--color-burgundy);">
                                    <?php 
                                    $bookings = getCustomerAppointments($userId);
                                    echo count($bookings); 
                                    ?>
                                </h3>
                                <small class="text-muted">Total Bookings</small>
                            </div>
                            <div class="col-4">
                                <h3 style="color: var(--color-rose);">
                                    <?php
                                    $completed = array_filter($bookings, function($b) {
                                        return $b['status'] == 'completed';
                                    });
                                    echo count($completed);
                                    ?>
                                </h3>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-4">
                                <h3 style="color: var(--color-pink);">
                                    <?php
                                    $conn = getDbConnection();
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE customer_id = ?");
                                    $stmt->bind_param("s", $userId);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <small class="text-muted">Reviews Written</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>