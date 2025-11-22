<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_customers.php';
require_once 'backend/function_appointments.php';
require_once 'backend/function_notifications.php';

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

/* Crop Modal Styles */
.crop-modal-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.85);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.crop-modal-overlay.show {
    display: flex;
}

.crop-modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

.crop-modal-header {
    text-align: center;
    margin-bottom: 25px;
}

.crop-modal-header h3 {
    color: var(--color-burgundy);
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.crop-modal-header p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.crop-preview-area {
    position: relative;
    width: 300px;
    height: 300px;
    margin: 0 auto 25px;
    border-radius: 50%;
    overflow: hidden;
    background: #f0f0f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    cursor: grab;
}

.crop-preview-area.dragging {
    cursor: grabbing;
}

.crop-preview-image {
    position: absolute;
    max-width: none;
    user-select: none;
    -webkit-user-drag: none;
}

.crop-instructions {
    text-align: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid var(--color-burgundy);
}

.crop-instructions i {
    color: var(--color-burgundy);
    font-size: 1.2rem;
    margin-right: 8px;
}

.crop-instructions p {
    margin: 0;
    color: #555;
    font-size: 0.9rem;
}

.crop-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

.crop-modal-buttons button {
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel-crop {
    background-color: #6c757d;
    color: white;
}

.btn-cancel-crop:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn-confirm-crop {
    background-color: var(--color-burgundy);
    color: white;
}

.btn-confirm-crop:hover {
    background-color: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Zoom Controls */
.zoom-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 20px;
}

.zoom-button {
    background: var(--color-burgundy);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

.zoom-button:hover {
    background: var(--color-rose);
    transform: scale(1.1);
}

.zoom-slider {
    width: 200px;
    height: 6px;
    border-radius: 3px;
    background: #e0e0e0;
    outline: none;
    -webkit-appearance: none;
}

.zoom-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-burgundy);
    cursor: pointer;
}

.zoom-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-burgundy);
    cursor: pointer;
    border: none;
}

.zoom-level {
    font-size: 0.9rem;
    color: #666;
    min-width: 50px;
    text-align: center;
}

/* Edit Photo Button */
.edit-photo-btn {
    background: var(--color-burgundy);
    color: white;
    border: none;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.edit-photo-btn:hover {
    background: var(--color-rose);
    transform: translateY(-2px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
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
                                    <label for="cstmr_address" class="form-label">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> Barangay *
                                    </label>
                                    <select class="form-select" id="cstmr_address" name="cstmr_address" required>
                                        <option value="">Select your barangay</option>
                                        <option value="Aga" <?php echo ($user['cstmr_address'] ?? '') == 'Aga' ? 'selected' : ''; ?>>Aga</option>
                                        <option value="Balaytigui" <?php echo ($user['cstmr_address'] ?? '') == 'Balaytigui' ? 'selected' : ''; ?>>Balaytigui</option>
                                        <option value="Banilad" <?php echo ($user['cstmr_address'] ?? '') == 'Banilad' ? 'selected' : ''; ?>>Banilad</option>
                                        <option value="Bilaran" <?php echo ($user['cstmr_address'] ?? '') == 'Bilaran' ? 'selected' : ''; ?>>Bilaran</option>
                                        <option value="Bucana" <?php echo ($user['cstmr_address'] ?? '') == 'Bucana' ? 'selected' : ''; ?>>Bucana</option>
                                        <option value="Buhay" <?php echo ($user['cstmr_address'] ?? '') == 'Buhay' ? 'selected' : ''; ?>>Buhay</option>
                                        <option value="Bulihan" <?php echo ($user['cstmr_address'] ?? '') == 'Bulihan' ? 'selected' : ''; ?>>Bulihan</option>
                                        <option value="Bunducan" <?php echo ($user['cstmr_address'] ?? '') == 'Bunducan' ? 'selected' : ''; ?>>Bunducan</option>
                                        <option value="Butucan" <?php echo ($user['cstmr_address'] ?? '') == 'Butucan' ? 'selected' : ''; ?>>Butucan</option>
                                        <option value="Calayo" <?php echo ($user['cstmr_address'] ?? '') == 'Calayo' ? 'selected' : ''; ?>>Calayo</option>
                                        <option value="Catandaan" <?php echo ($user['cstmr_address'] ?? '') == 'Catandaan' ? 'selected' : ''; ?>>Catandaan</option>
                                        <option value="Caybunga" <?php echo ($user['cstmr_address'] ?? '') == 'Caybunga' ? 'selected' : ''; ?>>Caybunga</option>
                                        <option value="Cogunan" <?php echo ($user['cstmr_address'] ?? '') == 'Cogunan' ? 'selected' : ''; ?>>Cogunan</option>
                                        <option value="Dayap" <?php echo ($user['cstmr_address'] ?? '') == 'Dayap' ? 'selected' : ''; ?>>Dayap</option>
                                        <option value="Kaylaway" <?php echo ($user['cstmr_address'] ?? '') == 'Kaylaway' ? 'selected' : ''; ?>>Kaylaway</option>
                                        <option value="Latag" <?php echo ($user['cstmr_address'] ?? '') == 'Latag' ? 'selected' : ''; ?>>Latag</option>
                                        <option value="Looc" <?php echo ($user['cstmr_address'] ?? '') == 'Looc' ? 'selected' : ''; ?>>Looc</option>
                                        <option value="Lumbangan" <?php echo ($user['cstmr_address'] ?? '') == 'Lumbangan' ? 'selected' : ''; ?>>Lumbangan</option>
                                        <option value="Malapad na Bato" <?php echo ($user['cstmr_address'] ?? '') == 'Malapad na Bato' ? 'selected' : ''; ?>>Malapad na Bato</option>
                                        <option value="Mataas na Pulo" <?php echo ($user['cstmr_address'] ?? '') == 'Mataas na Pulo' ? 'selected' : ''; ?>>Mataas na Pulo</option>
                                        <option value="Munting Indan" <?php echo ($user['cstmr_address'] ?? '') == 'Munting Indan' ? 'selected' : ''; ?>>Munting Indan</option>
                                        <option value="Natipuan" <?php echo ($user['cstmr_address'] ?? '') == 'Natipuan' ? 'selected' : ''; ?>>Natipuan</option>
                                        <option value="Pantalan" <?php echo ($user['cstmr_address'] ?? '') == 'Pantalan' ? 'selected' : ''; ?>>Pantalan</option>
                                        <option value="Papaya" <?php echo ($user['cstmr_address'] ?? '') == 'Papaya' ? 'selected' : ''; ?>>Papaya</option>
                                        <option value="Poblacion" <?php echo ($user['cstmr_address'] ?? '') == 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
                                        <option value="Putat" <?php echo ($user['cstmr_address'] ?? '') == 'Putat' ? 'selected' : ''; ?>>Putat</option>
                                        <option value="Reparo" <?php echo ($user['cstmr_address'] ?? '') == 'Reparo' ? 'selected' : ''; ?>>Reparo</option>
                                        <option value="San Diego" <?php echo ($user['cstmr_address'] ?? '') == 'San Diego' ? 'selected' : ''; ?>>San Diego</option>
                                        <option value="San Jose" <?php echo ($user['cstmr_address'] ?? '') == 'San Jose' ? 'selected' : ''; ?>>San Jose</option>
                                        <option value="San Juan" <?php echo ($user['cstmr_address'] ?? '') == 'San Juan' ? 'selected' : ''; ?>>San Juan</option>
                                        <option value="Talangan" <?php echo ($user['cstmr_address'] ?? '') == 'Talangan' ? 'selected' : ''; ?>>Talangan</option>
                                        <option value="Tumalim" <?php echo ($user['cstmr_address'] ?? '') == 'Tumalim' ? 'selected' : ''; ?>>Tumalim</option>
                                        <option value="Utod" <?php echo ($user['cstmr_address'] ?? '') == 'Utod' ? 'selected' : ''; ?>>Utod</option>
                                        <option value="Wawa" <?php echo ($user['cstmr_address'] ?? '') == 'Wawa' ? 'selected' : ''; ?>>Wawa</option>
                                    </select>
                                    <small class="text-muted">Select your barangay in Nasugbu, Batangas</small>
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
    <div class="crop-modal-overlay" id="cropModal">
    <div class="crop-modal-content">
        <div class="crop-modal-header">
            <h3><i class="bi bi-crop"></i> Adjust Your Photo</h3>
            <p>Drag to reposition • Use zoom to adjust size</p>
        </div>
        
        <div class="crop-preview-area" id="cropPreviewArea">
            <img src="" alt="Crop Preview" class="crop-preview-image" id="cropPreviewImage">
        </div>
        
        <!-- ZOOM CONTROLS -->
        <div class="zoom-controls">
            <button type="button" class="zoom-button" id="zoomOut">
                <i class="bi bi-dash-lg"></i>
            </button>
            <input type="range" class="zoom-slider" id="zoomSlider" min="1" max="3" step="0.1" value="1">
            <button type="button" class="zoom-button" id="zoomIn">
                <i class="bi bi-plus-lg"></i>
            </button>
            <span class="zoom-level" id="zoomLevel">100%</span>
        </div>
        
        <div class="crop-instructions">
            <i class="bi bi-hand-index"></i>
            <p>Drag to move • Zoom to resize • Your photo will be cropped to a circle</p>
        </div>
        
        <div class="crop-modal-buttons">
            <button type="button" class="btn-cancel-crop" id="btnCancelCrop">
                <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn-confirm-crop" id="btnConfirmCrop">
                <i class="bi bi-check-circle"></i> Set as Profile Photo
            </button>
        </div>
    </div>
</div>
</main>

<script>
// Crop modal elements
const profilePicInput = document.getElementById('profile_pic');
const cropModal = document.getElementById('cropModal');
const cropPreviewArea = document.getElementById('cropPreviewArea');
const cropPreviewImage = document.getElementById('cropPreviewImage');
const btnCancelCrop = document.getElementById('btnCancelCrop');
const btnConfirmCrop = document.getElementById('btnConfirmCrop');

// Zoom elements
const zoomSlider = document.getElementById('zoomSlider');
const zoomIn = document.getElementById('zoomIn');
const zoomOut = document.getElementById('zoomOut');
const zoomLevel = document.getElementById('zoomLevel');

let isDragging = false;
let startX, startY;
let initialX = 0, initialY = 0;
let currentX = 0, currentY = 0;
let currentZoom = 1;
let currentFile = null;
let originalImageSrc = '';
let hasCroppedImage = false;

// When user selects a file
profilePicInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
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
        
        currentFile = file;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            originalImageSrc = e.target.result;
            openCropModal(originalImageSrc);
        };
        reader.readAsDataURL(file);
    }
});

// Open crop modal
function openCropModal(imageSrc) {
    const img = new Image();
    img.onload = function() {
        const containerSize = 300;
        currentZoom = 1;
        zoomSlider.value = 1;
        updateZoomLevel();
        
        const scale = Math.max(containerSize / img.width, containerSize / img.height);
        
        const baseWidth = img.width * scale;
        const baseHeight = img.height * scale;
        
        // Store base dimensions for zoom calculation
        cropPreviewImage.setAttribute('data-base-width', baseWidth);
        cropPreviewImage.setAttribute('data-base-height', baseHeight);
        
        cropPreviewImage.style.width = baseWidth + 'px';
        cropPreviewImage.style.height = baseHeight + 'px';
        
        currentX = (containerSize - baseWidth) / 2;
        currentY = (containerSize - baseHeight) / 2;
        
        cropPreviewImage.style.left = currentX + 'px';
        cropPreviewImage.style.top = currentY + 'px';
        
        initialX = currentX;
        initialY = currentY;
        
        cropPreviewImage.src = imageSrc;
        cropModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    img.src = imageSrc;
}

// Zoom functionality - Modified to change actual size instead of transform
function updateZoom(newZoom) {
    currentZoom = Math.max(1, Math.min(3, newZoom));
    zoomSlider.value = currentZoom;
    
    // Get the base dimensions
    const baseWidth = parseFloat(cropPreviewImage.getAttribute('data-base-width'));
    const baseHeight = parseFloat(cropPreviewImage.getAttribute('data-base-height'));
    
    // Apply zoom by changing actual dimensions
    cropPreviewImage.style.width = (baseWidth * currentZoom) + 'px';
    cropPreviewImage.style.height = (baseHeight * currentZoom) + 'px';
    
    updateZoomLevel();
}

function updateZoomLevel() {
    zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
}

zoomSlider.addEventListener('input', function() {
    updateZoom(parseFloat(this.value));
});

zoomIn.addEventListener('click', function() {
    updateZoom(currentZoom + 0.2);
});

zoomOut.addEventListener('click', function() {
    updateZoom(currentZoom - 0.2);
});

// Close crop modal
function closeCropModal() {
    cropModal.classList.remove('show');
    document.body.style.overflow = '';
    if (!hasCroppedImage) {
        profilePicInput.value = '';
    }
    currentX = 0;
    currentY = 0;
    initialX = 0;
    initialY = 0;
    currentZoom = 1;
}

// Drag functionality
cropPreviewArea.addEventListener('mousedown', startDrag);
cropPreviewArea.addEventListener('touchstart', startDrag);

function startDrag(e) {
    isDragging = true;
    cropPreviewArea.classList.add('dragging');
    
    if (e.type === 'touchstart') {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    } else {
        startX = e.clientX;
        startY = e.clientY;
        e.preventDefault();
    }
    
    initialX = currentX;
    initialY = currentY;
}

document.addEventListener('mousemove', drag);
document.addEventListener('touchmove', drag);

function drag(e) {
    if (!isDragging) return;
    
    let clientX, clientY;
    
    if (e.type === 'touchmove') {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    
    const deltaX = clientX - startX;
    const deltaY = clientY - startY;
    
    currentX = initialX + deltaX;
    currentY = initialY + deltaY;
    
    cropPreviewImage.style.left = currentX + 'px';
    cropPreviewImage.style.top = currentY + 'px';
}

document.addEventListener('mouseup', endDrag);
document.addEventListener('touchend', endDrag);

function endDrag() {
    if (isDragging) {
        isDragging = false;
        cropPreviewArea.classList.remove('dragging');
    }
}

// Cancel crop
btnCancelCrop.addEventListener('click', closeCropModal);

// Confirm crop and set as profile picture
btnConfirmCrop.addEventListener('click', function() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const outputSize = 400; // Final output size
    
    canvas.width = outputSize;
    canvas.height = outputSize;
    
    // Create circular clipping path
    ctx.beginPath();
    ctx.arc(outputSize / 2, outputSize / 2, outputSize / 2, 0, Math.PI * 2);
    ctx.closePath();
    ctx.clip();
    
    const img = new Image();
    img.onload = function() {
        const previewSize = 300; // Preview container size
        const scale = outputSize / previewSize; // Scale factor
        
        // Get current image dimensions and position
        const imgWidth = parseFloat(cropPreviewImage.style.width);
        const imgHeight = parseFloat(cropPreviewImage.style.height);
        
        // Calculate the portion of the image visible in the preview circle
        // Scale everything up to the output canvas size
        const scaledX = currentX * scale;
        const scaledY = currentY * scale;
        const scaledWidth = imgWidth * scale;
        const scaledHeight = imgHeight * scale;
        
        // Draw the image
        ctx.drawImage(img, scaledX, scaledY, scaledWidth, scaledHeight);
        
        canvas.toBlob(function(blob) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePreview');
                const previewName = document.getElementById('imagePreviewName');
                const removeInput = document.getElementById('remove_profile_pic');
                
                // Reset remove flag
                removeInput.value = '0';
                
                // Show preview
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    // Replace default avatar with image
                    const imgElement = document.createElement('img');
                    imgElement.src = e.target.result;
                    imgElement.className = 'profile-image';
                    imgElement.alt = 'Profile Picture';
                    imgElement.id = 'profilePreview';
                    preview.parentNode.replaceChild(imgElement, preview);
                }
                
                previewName.innerHTML = `Selected: ${currentFile.name} <button type="button" class="edit-photo-btn" id="editPhotoBtnDynamic"><i class="bi bi-pencil-fill"></i> Re-crop Photo</button>`;
                
                // Show remove button if it exists
                const removeBtn = document.getElementById('removePhotoBtn');
                if (removeBtn) {
                    removeBtn.style.display = 'inline-block';
                }
                
                hasCroppedImage = true;
                closeCropModal();
                
                // Add event listener to the dynamically created edit button
                setTimeout(() => {
                    const editBtn = document.getElementById('editPhotoBtnDynamic');
                    if (editBtn) {
                        editBtn.addEventListener('click', function() {
                            if (originalImageSrc) {
                                openCropModal(originalImageSrc);
                            }
                        });
                    }
                }, 100);
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.95);
    };
    img.src = originalImageSrc;
});

// Remove photo button functionality
const removePhotoBtn = document.getElementById('removePhotoBtn');
if (removePhotoBtn) {
    removePhotoBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to remove your profile picture?')) {
            document.getElementById('remove_profile_pic').value = '1';
            
            // Clear file input
            profilePicInput.value = '';
            hasCroppedImage = false;
            originalImageSrc = '';
            
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

// Close modal when clicking outside
cropModal.addEventListener('click', function(e) {
    if (e.target === cropModal) {
        closeCropModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>