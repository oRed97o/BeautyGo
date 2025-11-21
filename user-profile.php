<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_customers.php';
require_once 'backend/function_appointments.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$customer = getCurrentCustomer();
$customerId = $customer['customer_id'];

// Get customer profile data
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT c.*, p.* 
    FROM customers c
    LEFT JOIN profiles p ON c.customer_id = p.customer_id
    WHERE c.customer_id = ?
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Check if user wants to remove profile picture
    $removePhoto = isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] === '1';
    
    // Validate file upload if provided
    $uploadError = null;
    $hasNewImage = false;
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['profile_pic']['type'];
            $fileSize = $_FILES['profile_pic']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $uploadError = 'Invalid file type. Please upload JPG, JPEG, or PNG only.';
            } elseif ($fileSize > $maxSize) {
                $uploadError = 'File is too large. Maximum size is 5MB.';
            } else {
                // File is valid, mark that we have a new image
                $hasNewImage = true;
            }
        } else {
            $uploadError = 'Error uploading file. Please try again. Error code: ' . $_FILES['profile_pic']['error'];
        }
    }
    
    if ($uploadError) {
        $_SESSION['error'] = $uploadError;
        header('Location: user-profile.php');
        exit;
    }
    
    $userData = [
        'fname' => sanitize($_POST['fname']),
        'mname' => sanitize($_POST['mname'] ?? ''),
        'surname' => sanitize($_POST['surname'] ?? ''),
        'cstmr_num' => sanitize($_POST['cstmr_num']),
        'cstmr_email' => $user['cstmr_email'], // Keep existing email
        'cstmr_address' => sanitize($_POST['cstmr_address'] ?? ''),
        'face_shape' => $_POST['face_shape'] ?? '',
        'body_type' => $_POST['body_type'] ?? '',
        'eye_color' => $_POST['eye_color'] ?? '',
        'skin_tone' => $_POST['skin_tone'] ?? '',
        'hair_type' => $_POST['hair_type'] ?? '',
        'hair_color' => $_POST['hair_color'] ?? '',
        'current_hair_length' => $_POST['current_hair_length'] ?? '',
        'desired_hair_length' => $_POST['desired_hair_length'] ?? '',
        'remove_profile_pic' => $removePhoto
    ];
    
    if (updateCustomer($customerId, $userData)) {
        if ($removePhoto) {
            $_SESSION['success'] = 'Profile photo removed successfully!';
        } elseif ($hasNewImage) {
            $_SESSION['success'] = 'Profile and photo updated successfully!';
        } else {
            $_SESSION['success'] = 'Profile updated successfully';
        }
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
    header('Location: user-profile.php');
    exit;
}

$pageTitle = 'My Profile - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">

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

.profile-image-container {
    text-align: center;
    margin-bottom: 20px;
    position: relative;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--color-cream);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.default-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-burgundy), var(--color-rose));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profile-image-wrapper {
    position: relative;
    display: inline-block;
}

.image-upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: var(--color-burgundy);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.image-upload-overlay:hover {
    background: var(--color-rose);
    transform: scale(1.1);
}

.image-upload-overlay i {
    font-size: 1.2rem;
}

#profile_pic {
    display: none;
}

.image-preview-name {
    font-size: 0.875rem;
    color: var(--color-burgundy);
    margin-top: 10px;
    font-weight: 500;
}
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="mb-4">My Profile</h2>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- Profile Picture -->
                            <div class="profile-image-container">
                                <div class="profile-image-wrapper">
                                    <?php if (!empty($user['profile_pic'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($user['profile_pic']); ?>" 
                                            class="profile-image" 
                                            alt="Profile Picture"
                                            id="profilePreview">
                                    <?php else: ?>
                                        <div class="default-avatar" id="profilePreview">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <label for="profile_pic" class="image-upload-overlay" title="Change profile picture">
                                        <i class="bi bi-camera-fill"></i>
                                    </label>
                                    <input type="file" 
                                        class="form-control" 
                                        id="profile_pic" 
                                        name="profile_pic" 
                                        accept="image/jpeg,image/jpg,image/png">
                                    <input type="hidden" id="remove_profile_pic" name="remove_profile_pic" value="0">
                                </div>
                                <div id="imagePreviewName" class="image-preview-name"></div>
                                
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removePhotoBtn">
                                        <i class="bi bi-trash"></i> Remove Photo
                                    </button>
                                <?php endif; ?>
                                
                                <small class="text-muted d-block mt-2">Click camera icon to change picture (JPG, PNG - Max 5MB)</small>
                            </div>

                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="fname" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="fname" name="fname" 
                                           value="<?php echo htmlspecialchars($user['fname'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="mname" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="mname" name="mname" 
                                           value="<?php echo htmlspecialchars($user['mname'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="surname" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="surname" name="surname" 
                                           value="<?php echo htmlspecialchars($user['surname'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="cstmr_email" 
                                           value="<?php echo htmlspecialchars($user['cstmr_email'] ?? ''); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_num" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="cstmr_num" name="cstmr_num" 
                                           value="<?php echo htmlspecialchars($user['cstmr_num'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="cstmr_address" name="cstmr_address" 
                                           value="<?php echo htmlspecialchars($user['cstmr_address'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="member_since" class="form-label">Member Since</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo formatDate($user['registration_date']); ?>" disabled>
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
                                    <label for="body_type" class="form-label">Body Type</label>
                                    <select class="form-select" id="body_type" name="body_type">
                                        <option value="">Not specified</option>
                                        <option value="slim" <?php echo ($user['body_type'] ?? '') == 'slim' ? 'selected' : ''; ?>>Slim</option>
                                        <option value="average" <?php echo ($user['body_type'] ?? '') == 'average' ? 'selected' : ''; ?>>Average</option>
                                        <option value="athletic" <?php echo ($user['body_type'] ?? '') == 'athletic' ? 'selected' : ''; ?>>Athletic</option>
                                        <option value="curvy" <?php echo ($user['body_type'] ?? '') == 'curvy' ? 'selected' : ''; ?>>Curvy</option>
                                        <option value="plus-size" <?php echo ($user['body_type'] ?? '') == 'plus-size' ? 'selected' : ''; ?>>Plus Size</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="eye_color" class="form-label">Eye Color</label>
                                    <select class="form-select" id="eye_color" name="eye_color">
                                        <option value="">Not specified</option>
                                        <option value="brown" <?php echo ($user['eye_color'] ?? '') == 'brown' ? 'selected' : ''; ?>>Brown</option>
                                        <option value="black" <?php echo ($user['eye_color'] ?? '') == 'black' ? 'selected' : ''; ?>>Black</option>
                                        <option value="blue" <?php echo ($user['eye_color'] ?? '') == 'blue' ? 'selected' : ''; ?>>Blue</option>
                                        <option value="green" <?php echo ($user['eye_color'] ?? '') == 'green' ? 'selected' : ''; ?>>Green</option>
                                        <option value="hazel" <?php echo ($user['eye_color'] ?? '') == 'hazel' ? 'selected' : ''; ?>>Hazel</option>
                                        <option value="gray" <?php echo ($user['eye_color'] ?? '') == 'gray' ? 'selected' : ''; ?>>Gray</option>
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
                                    <label for="hair_type" class="form-label">Hair Type</label>
                                    <select class="form-select" id="hair_type" name="hair_type">
                                        <option value="">Not specified</option>
                                        <option value="straight" <?php echo ($user['hair_type'] ?? '') == 'straight' ? 'selected' : ''; ?>>Straight</option>
                                        <option value="wavy" <?php echo ($user['hair_type'] ?? '') == 'wavy' ? 'selected' : ''; ?>>Wavy</option>
                                        <option value="curly" <?php echo ($user['hair_type'] ?? '') == 'curly' ? 'selected' : ''; ?>>Curly</option>
                                        <option value="coily" <?php echo ($user['hair_type'] ?? '') == 'coily' ? 'selected' : ''; ?>>Coily</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="hair_color" class="form-label">Hair Color</label>
                                    <input type="text" class="form-control" id="hair_color" name="hair_color" 
                                           value="<?php echo htmlspecialchars($user['hair_color'] ?? ''); ?>" 
                                           placeholder="e.g., Black, Brown, Blonde">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="current_hair_length" class="form-label">Current Hair Length</label>
                                    <select class="form-select" id="current_hair_length" name="current_hair_length">
                                        <option value="">Not specified</option>
                                        <option value="very-short" <?php echo ($user['current_hair_length'] ?? '') == 'very-short' ? 'selected' : ''; ?>>Very Short</option>
                                        <option value="short" <?php echo ($user['current_hair_length'] ?? '') == 'short' ? 'selected' : ''; ?>>Short</option>
                                        <option value="medium" <?php echo ($user['current_hair_length'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="long" <?php echo ($user['current_hair_length'] ?? '') == 'long' ? 'selected' : ''; ?>>Long</option>
                                        <option value="very-long" <?php echo ($user['current_hair_length'] ?? '') == 'very-long' ? 'selected' : ''; ?>>Very Long</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="desired_hair_length" class="form-label">Desired Hair Length</label>
                                    <select class="form-select" id="desired_hair_length" name="desired_hair_length">
                                        <option value="">Not specified</option>
                                        <option value="very-short" <?php echo ($user['desired_hair_length'] ?? '') == 'very-short' ? 'selected' : ''; ?>>Very Short</option>
                                        <option value="short" <?php echo ($user['desired_hair_length'] ?? '') == 'short' ? 'selected' : ''; ?>>Short</option>
                                        <option value="medium" <?php echo ($user['desired_hair_length'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="long" <?php echo ($user['desired_hair_length'] ?? '') == 'long' ? 'selected' : ''; ?>>Long</option>
                                        <option value="very-long" <?php echo ($user['desired_hair_length'] ?? '') == 'very-long' ? 'selected' : ''; ?>>Very Long</option>
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
                                    $appointments = getCustomerAppointments($customerId);
                                    echo count($appointments); 
                                    ?>
                                </h3>
                                <small class="text-muted">Total Bookings</small>
                            </div>
                            <div class="col-4">
                                <h3 style="color: var(--color-rose);">
                                    <?php
                                    $completed = array_filter($appointments, function($a) {
                                        return $a['appoint_status'] == 'completed';
                                    });
                                    echo count($completed);
                                    ?>
                                </h3>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-4">
                                <h3 style="color: var(--color-pink);">
                                    <?php
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE customer_id = ?");
                                    $stmt->bind_param("i", $customerId);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    $stmt->close();
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

<script>
// Image preview functionality
document.getElementById('profile_pic').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('profilePreview');
    const previewName = document.getElementById('imagePreviewName');
    const removeInput = document.getElementById('remove_profile_pic');
    
    if (file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a JPG or PNG image.');
            this.value = '';
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            this.value = '';
            return;
        }
        
        // Reset remove flag when new file is selected
        removeInput.value = '0';
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace default avatar with image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'profile-image';
                img.alt = 'Profile Picture';
                img.id = 'profilePreview';
                preview.parentNode.replaceChild(img, preview);
            }
        };
        reader.readAsDataURL(file);
        
        previewName.textContent = `Selected: ${file.name}`;
    }
});

// Remove photo button functionality
const removePhotoBtn = document.getElementById('removePhotoBtn');
if (removePhotoBtn) {
    removePhotoBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to remove your profile picture?')) {
            document.getElementById('remove_profile_pic').value = '1';
            
            // Clear file input
            document.getElementById('profile_pic').value = '';
            
            // Replace image with default avatar
            const preview = document.getElementById('profilePreview');
            const defaultAvatar = document.createElement('div');
            defaultAvatar.className = 'default-avatar';
            defaultAvatar.id = 'profilePreview';
            defaultAvatar.innerHTML = '<i class="bi bi-person-circle"></i>';
            preview.parentNode.replaceChild(defaultAvatar, preview);
            
            // Clear preview name
            document.getElementById('imagePreviewName').textContent = 'Photo will be removed when you save';
            
            // Hide remove button
            this.style.display = 'none';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>