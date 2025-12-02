<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Customer Registration - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
.profile-upload-container {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-preview-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
}

.profile-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--color-cream);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
}

.profile-preview.show {
    display: block;
}

.default-profile-icon {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-burgundy), var(--color-rose));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.default-profile-icon.hide {
    display: none;
}

.upload-btn {
    background-color: var(--color-burgundy);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-block;
    margin-top: 10px;
}

.upload-btn:hover {
    background-color: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.upload-btn i {
    margin-right: 5px;
}

.remove-photo-btn {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--color-burgundy);
    color: white;
    border: 2px solid white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    z-index: 10;
}

.remove-photo-btn.show {
    display: flex;
}

.remove-photo-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.file-name-display {
    margin-top: 8px;
    font-size: 0.875rem;
    color: var(--color-burgundy);
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

.error-modal-overlay {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease;
}

.error-modal-overlay.show {
    display: flex;
}

.error-modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 16px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
    text-align: center;
}

.error-modal-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    animation: shake 0.5s ease;
}

.error-modal-title {
    color: #dc3545;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.error-modal-message {
    color: #555;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
}

.error-modal-button {
    background: var(--color-burgundy);
    color: white;
    border: none;
    padding: 12px 40px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.error-modal-button:hover {
    background: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(133, 14, 53, 0.3);
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
    padding: 8px 20px;
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

.map-info-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #e7f3ff;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #2196F3;
}

.map-info-box i {
    color: #2196F3;
    font-size: 1.1rem;
}

.map-info-box small {
    margin: 0;
    color: #555;
}

.coordinates-display {
    font-size: 0.9rem;
    line-height: 1.6;
}

#locationMap {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}

.custom-marker {
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
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

.error-modal-overlay {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease;
}

.error-modal-overlay.show {
    display: flex;
}

.error-modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 16px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
    text-align: center;
}

.error-modal-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    animation: shake 0.5s ease;
}

.error-modal-title {
    color: #dc3545;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.error-modal-message {
    color: #555;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
}

.error-modal-button {
    background: var(--color-burgundy);
    color: white;
    border: none;
    padding: 12px 40px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.error-modal-button:hover {
    background: var(--color-rose);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(133, 14, 53, 0.3);
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
    padding: 8px 20px;
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

.map-info-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #e7f3ff;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #2196F3;
}

.map-info-box i {
    color: #2196F3;
    font-size: 1.1rem;
}

.map-info-box small {
    margin: 0;
    color: #555;
}

.coordinates-display {
    font-size: 0.9rem;
    line-height: 1.6;
}

#locationMap {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}

.custom-marker {
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from { 
        opacity: 0;
        transform: translateY(-50px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
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

<!-- EARLY SCRIPT FOR FORM HANDLERS - MUST RUN BEFORE FORM LOADS -->
<script>
// ========================================
// GLOBAL FORM SUBMISSION HANDLER
// ========================================
function handleFormSubmit(event) {
    console.log('Form submit handler called');
    event.preventDefault(); // Stop default form submission
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    console.log('Password validation:', { password: password.length, match: password === confirmPassword });
    
    // Check if passwords match
    if (password !== confirmPassword) {
        const pwMismatch = document.getElementById('pwMismatch');
        if (pwMismatch) {
            pwMismatch.classList.remove('d-none');
            pwMismatch.textContent = 'Passwords do not match!';
        }
        console.log('Passwords do not match');
        return false;
    }

    // Validate password length
    if (password.length < 8) {
        showErrorModal('Password must be at least 8 characters long!');
        console.log('Password too short');
        return false;
    }
    
    // Check for at least one number
    if (!/\d/.test(password)) {
        showErrorModal('Password must contain at least one number (0-9)!');
        console.log('No number in password');
        return false;
    }
    
    // Check for at least one special character
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        showErrorModal('Password must contain at least one special character (!@#$%^&* etc.)');
        console.log('No special character in password');
        return false;
    }
    
    // Validate coordinates
    const lat = parseFloat(document.getElementById('customer_latitude').value);
    const lng = parseFloat(document.getElementById('customer_longitude').value);
    
    console.log('Coordinates:', { lat, lng });
    
    if (!lat || !lng || (lat === 0 && lng === 0)) {
        showErrorModal('Please pin your location on the map by clicking or dragging the marker.');
        console.log('Invalid coordinates');
        return false;
    }
    
    console.log('All validations passed, submitting form...');
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Registering...';
    
    // Submit form via AJAX
    const formData = new FormData(event.target);
    
    console.log('Sending AJAX request to backend/auth.php');
    
    fetch('backend/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        
        if (data.status === 'success') {
            // Show success message
            showSuccessModal(data.message || 'Registration successful!');
            
            // Redirect after short delay
            setTimeout(() => {
                console.log('Redirecting to:', data.redirect || 'index.php');
                window.location.replace(data.redirect || 'index.php');
            }, 1500);
        } else {
            // Show error message
            showErrorModal(data.message || 'Registration failed. Please try again.');
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showErrorModal('An error occurred. Please try again.');
        
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
    
    return false;
}

// Custom error modal functions
function showErrorModal(message) {
    document.getElementById('errorModalMessage').textContent = message;
    document.getElementById('errorModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModal').classList.remove('show');
    document.body.style.overflow = '';
}

function showSuccessModal(message) {
    let successModal = document.getElementById('successModal');
    
    if (!successModal) {
        successModal = document.createElement('div');
        successModal.id = 'successModal';
        successModal.className = 'error-modal-overlay';
        successModal.innerHTML = `
            <div class="error-modal-content">
                <div class="error-modal-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="error-modal-title" style="color: #28a745;">Success!</h3>
                <p class="error-modal-message" id="successModalMessage">${message}</p>
            </div>
        `;
        document.body.appendChild(successModal);
    } else {
        document.getElementById('successModalMessage').textContent = message;
    }
    
    successModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Toggle password visibility
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
</script>

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card registration-card">
                    <div class="card-body p-4">
                        <!-- Updated Header Section -->
                        <div class="registration-header">
                            <div class="brand-logo-new mb-3">
                                <i class="bi bi-scissors"></i>
                                <span>BeautyGo</span>
                            </div>
                            <h2 class="registration-title">Customer Registration</h2>
                            <p class="registration-subtitle">Create your personalized beauty profile</p>
                        </div>
                    
                        <form method="POST" enctype="multipart/form-data" id="userRegisterForm" onsubmit="return handleFormSubmit(event)">
                            <input type="hidden" name="action" value="register_user">
                            
                            <!-- Account Information -->
                            <div class="registration-section-header">
                                <h5 class="section-title">Basic Information</h5>
                                <p class="section-subtitle">Tell us about yourself</p>
                            </div>

                            <!-- Profile Picture Upload with Preview -->
                            <div class="profile-upload-container">
                                <div class="profile-preview-wrapper">
                                    <img src="" alt="Profile Preview" class="profile-preview" id="profilePreview">
                                    <div class="default-profile-icon" id="defaultIcon">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                    <button type="button" class="remove-photo-btn" id="removePhotoBtn" title="Remove photo">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div>
                                    <label for="profile_pic" class="upload-btn">
                                        <i class="bi bi-camera-fill"></i>
                                        <span id="uploadBtnText">Upload Photo</span>
                                    </label>
                                    <input type="file" class="form-control d-none" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/webp">
                                
                                    <button type="button" class="edit-photo-btn d-none" id="editPhotoBtn">
                                            <i class="bi bi-pencil-fill"></i> Re-crop Photo
                                        </button>
                                    </div>
                                    <div class="file-name-display" id="fileNameDisplay"></div>
                                    <small class="text-muted d-block mt-2">Optional - JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    
                                    <input type="hidden" id="croppedImageData" name="cropped_image_data" value="">
                                </div>
                            

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="fname" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="fname" name="fname" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="mname" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="mname" name="mname">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="surname" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="surname" name="surname" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="cstmr_email" name="cstmr_email" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cstmr_num" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="cstmr_num" name="cstmr_num" required placeholder="09123456789">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'passwordEye')">
                                            <i class="bi bi-eye" id="passwordEye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 8 characters with numbers and symbols</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'confirmPasswordEye')">
                                            <i class="bi bi-eye" id="confirmPasswordEye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text text-danger small d-none" id="pwMismatch" role="alert">Passwords do not match</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="cstmr_address" class="form-label">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> Barangay *
                                    </label>
                                    <select class="form-select" id="cstmr_address" name="cstmr_address" required onchange="updateMapFromAddressUser()">
                                        <option value="">Select your barangay</option>
                                        <option value="Aga">Aga</option>
                                        <option value="Balaytigui">Balaytigui</option>
                                        <option value="Banilad">Banilad</option>
                                        <option value="Bilaran">Bilaran</option>
                                        <option value="Bucana">Bucana</option>
                                        <option value="Buhay">Buhay</option>
                                        <option value="Bulihan">Bulihan</option>
                                        <option value="Bunducan">Bunducan</option>
                                        <option value="Butucan">Butucan</option>
                                        <option value="Calayo">Calayo</option>
                                        <option value="Catandaan">Catandaan</option>
                                        <option value="Caybunga">Caybunga</option>
                                        <option value="Cogunan">Cogunan</option>
                                        <option value="Dayap">Dayap</option>
                                        <option value="Kaylaway">Kaylaway</option>
                                        <option value="Latag">Latag</option>
                                        <option value="Looc">Looc</option>
                                        <option value="Lumbangan">Lumbangan</option>
                                        <option value="Malapad na Bato">Malapad na Bato</option>
                                        <option value="Mataas na Pulo">Mataas na Pulo</option>
                                        <option value="Munting Indan">Munting Indan</option>
                                        <option value="Natipuan">Natipuan</option>
                                        <option value="Pantalan">Pantalan</option>
                                        <option value="Papaya">Papaya</option>
                                        <option value="Poblacion">Poblacion</option>
                                        <option value="Putat">Putat</option>
                                        <option value="Reparo">Reparo</option>
                                        <option value="San Diego">San Diego</option>
                                        <option value="San Jose">San Jose</option>
                                        <option value="San Juan">San Juan</option>
                                        <option value="Talangan">Talangan</option>
                                        <option value="Tumalim">Tumalim</option>
                                        <option value="Utod">Utod</option>
                                        <option value="Wawa">Wawa</option>
                                    </select>
                                    <small class="text-muted">Select your barangay in Nasugbu, Batangas</small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Location Section -->
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> Pin Your Location on Map *
                                    </label>
                                    <div class="map-info-box">
                                        <i class="bi bi-info-circle"></i>
                                        <small>Click anywhere on the map to set your location. This helps us show you nearby beauty services.</small>
                                    </div>
                                    <div id="locationMap" style="height: 400px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"></div>
                                    <div class="coordinates-display" style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--color-burgundy);">
                                        <strong>Selected Coordinates:</strong><br>
                                        Latitude: <span id="displayLat">14.0697</span> | Longitude: <span id="displayLng">120.6328</span>
                                    </div>
                                    <input type="hidden" id="customer_latitude" name="customer_latitude" value="14.0697">
                                    <input type="hidden" id="customer_longitude" name="customer_longitude" value="120.6328">
                                </div>
                            </div>

                            <hr class="my-4">

                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-person-check"></i> Register Account
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

<!-- Error Modal -->
<div class="error-modal-overlay" id="errorModal">
    <div class="error-modal-content">
        <div class="error-modal-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 class="error-modal-title">Oops!</h3>
        <p class="error-modal-message" id="errorModalMessage">Something went wrong.</p>
        <button type="button" class="error-modal-button" onclick="closeErrorModal()">Got it</button>
    </div>
</div>

<!-- Photo Upload and Crop Script -->
<script>
// Profile picture upload with crop modal
const profilePicInput = document.getElementById('profile_pic');
const profilePreview = document.getElementById('profilePreview');
const defaultIcon = document.getElementById('defaultIcon');
const removePhotoBtn = document.getElementById('removePhotoBtn');
const editPhotoBtn = document.getElementById('editPhotoBtn');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const uploadBtnText = document.getElementById('uploadBtnText');
const croppedImageData = document.getElementById('croppedImageData');

// Crop modal elements
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

// When user selects a file
profilePicInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            showErrorModal('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showErrorModal('Only JPG, PNG, GIF, and WebP images are allowed');
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

// Edit photo button - reopen crop modal
editPhotoBtn.addEventListener('click', function() {
    if (originalImageSrc) {
        openCropModal(originalImageSrc);
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
    profilePicInput.value = '';
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
                profilePreview.src = e.target.result;
                profilePreview.classList.add('show');
                defaultIcon.classList.add('hide');
                removePhotoBtn.classList.add('show');
                editPhotoBtn.classList.remove('d-none');
                fileNameDisplay.textContent = currentFile.name;
                uploadBtnText.textContent = 'Change Photo';
                croppedImageData.value = e.target.result;
                
                closeCropModal();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.95);
    };
    img.src = originalImageSrc;
});

// Remove photo functionality
removePhotoBtn.addEventListener('click', function() {
    if (confirm('Remove this photo?')) {
        profilePicInput.value = '';
        profilePreview.src = '';
        profilePreview.classList.remove('show');
        defaultIcon.classList.remove('hide');
        removePhotoBtn.classList.remove('show');
        editPhotoBtn.classList.add('d-none');
        fileNameDisplay.textContent = '';
        uploadBtnText.textContent = 'Upload Photo';
        croppedImageData.value = '';
        originalImageSrc = '';
    }
});

// Close modal when clicking outside
cropModal.addEventListener('click', function(e) {
    if (e.target === cropModal) {
        closeCropModal();
    }
});
</script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ========================================
// MAP FUNCTIONALITY FOR CUSTOMER
// ========================================
// ========================================
// MAP FUNCTIONALITY FOR CUSTOMER REGISTRATION - FIXED
// ========================================
let map;
let marker;
const defaultLat = 14.0697;
const defaultLng = 120.6328;

// Initialize map
function initCustomerMap() {
    map = L.map('locationMap').setView([defaultLat, defaultLng], 14);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Custom marker icon
    const customIcon = L.divIcon({
        html: '<i class="bi bi-geo-alt-fill" style="font-size: 2.5rem; color: #850E35;"></i>',
        className: 'custom-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    
    // Add default marker
    marker = L.marker([defaultLat, defaultLng], { 
        icon: customIcon,
        draggable: true 
    }).addTo(map);
    
    // Set initial coordinates
    updateCustomerLocation(defaultLat, defaultLng);
    
    // Click to set location
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        updateCustomerLocation(lat, lng);
    });
    
    // Drag marker to set location
    marker.on('dragend', function(e) {
        const lat = e.target.getLatLng().lat;
        const lng = e.target.getLatLng().lng;
        updateCustomerLocation(lat, lng);
    });
    
    console.log('Map initialized with default coordinates:', defaultLat, defaultLng);
}

function updateCustomerLocation(lat, lng) {
    marker.setLatLng([lat, lng]);
    document.getElementById('customer_latitude').value = lat.toFixed(8);
    document.getElementById('customer_longitude').value = lng.toFixed(8);
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    
    console.log('Location updated:', lat.toFixed(6), lng.toFixed(6));
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if map element exists
    if (document.getElementById('locationMap')) {
        setTimeout(initCustomerMap, 500); // Small delay to ensure map container is ready
    }
});

// Handle address dropdown change - auto-update map with barangay coordinates
function updateMapFromAddressUser() {
    const addressDropdown = document.getElementById('cstmr_address');
    const selectedBarangay = addressDropdown.value;
    
    if (!selectedBarangay) {
        return; // No selection
    }
    
    // Fetch coordinates for the selected barangay
    fetch('backend/barangay_coordinates.php?barangay=' + encodeURIComponent(selectedBarangay))
        .then(response => response.json())
        .then(data => {
            const lat = data.lat;
            const lng = data.lng;
            
            // Update the map and coordinates
            if (map && marker) {
                // Pan map to new location
                map.setView([lat, lng], 14);
                
                // Update marker position
                marker.setLatLng([lat, lng]);
                
                // Update coordinate displays and hidden fields
                updateCustomerLocation(lat, lng);
                
                console.log('Map updated for barangay:', selectedBarangay, 'Lat:', lat, 'Lng:', lng);
            }
        })
        .catch(error => {
            console.error('Error fetching barangay coordinates:', error);
        });
}

// Function to find nearest barangay based on coordinates
async function findNearestBarangayUser(lat, lng) {
    try {
        const response = await fetch('backend/barangay_coordinates.php');
        const barangays = await response.json();
        
        let nearestBarangay = null;
        let minDistance = Infinity;
        
        for (const [name, coords] of Object.entries(barangays)) {
            // Calculate distance using simple Pythagorean theorem (good enough for nearby points)
            const distance = Math.sqrt(
                Math.pow(coords.lat - lat, 2) + Math.pow(coords.lng - lng, 2)
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                nearestBarangay = name;
            }
        }
        
        return nearestBarangay;
    } catch (error) {
        console.error('Error finding nearest barangay:', error);
        return null;
    }
}

// Auto-select barangay based on current coordinates
async function autoSelectBarangayFromCoordinatesUser() {
    const latValue = document.getElementById('customer_latitude').value;
    const lngValue = document.getElementById('customer_longitude').value;
    
    if (latValue && lngValue && latValue !== '14.0697' && lngValue !== '120.6328') {
        const barangay = await findNearestBarangayUser(parseFloat(latValue), parseFloat(lngValue));
        if (barangay) {
            const dropdown = document.getElementById('cstmr_address');
            dropdown.value = barangay;
            console.log('Auto-selected barangay:', barangay);
        }
    }
}

function updateCustomerLocation(lat, lng) {
    marker.setLatLng([lat, lng]);
    document.getElementById('customer_latitude').value = lat.toFixed(6);
    document.getElementById('customer_longitude').value = lng.toFixed(6);
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
}
</script>

</main>

<script>
// Live validation for register-user.php - UPDATED
(function() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passwordRules = {
        minLength: 8,
        hasNumber: /\d/,
        hasSpecial: /[!@#$%^&*()_+\-\=\[\]{};':"\\|,.<>\/\?]/
    };

    function validateName(val) {
        return val.trim().length >= 1;
    }

    function validateEmail(val) {
        const v = val.trim().toLowerCase();
        return emailRegex.test(v);
    }

    function validatePhone(val) {
        if (!val) return false;
        const digits = val.replace(/\D/g, '');
        return digits.length === 11;
    }

    function validatePassword(val) {
        if (!val) return false;
        return val.length >= passwordRules.minLength &&
               passwordRules.hasNumber.test(val) &&
               passwordRules.hasSpecial.test(val);
    }

    function validateSelect(el) {
        return el.value !== '';
    }

    function validateCoordinates(lat, lng) {
        const latVal = parseFloat(lat);
        const lngVal = parseFloat(lng);
        return latVal !== 0 && lngVal !== 0 && !isNaN(latVal) && !isNaN(lngVal);
    }

    function setState(el, ok) {
        el.classList.remove('valid','invalid');
        const val = (el.tagName === 'SELECT') ? el.value : el.value.trim();
        
        // Special handling for optional middle name field
        if (el.id === 'mname' && !val) {
            el.removeAttribute('aria-invalid');
            return;
        }
        
        if (!val) {
            el.removeAttribute('aria-invalid');
            return;
        }
        if (ok) {
            el.classList.add('valid');
            el.setAttribute('aria-invalid','false');
        } else {
            el.classList.add('invalid');
            el.setAttribute('aria-invalid','true');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const map = [
            { id: 'fname', validator: (el) => validateName(el.value), required: true },
            { id: 'mname', validator: (el) => el.value.trim() === '' || validateName(el.value), required: false },
            { id: 'surname', validator: (el) => validateName(el.value), required: true },
            { id: 'cstmr_email', validator: (el) => validateEmail(el.value), required: true },
            { id: 'cstmr_num', validator: (el) => validatePhone(el.value), required: true },
            { id: 'password', validator: (el) => validatePassword(el.value), required: true },
            { id: 'confirm_password', validator: (el) => {
                    const pw = document.getElementById('password').value;
                    return el.value === pw && validatePassword(pw);
                }, required: true
            },
            { id: 'cstmr_address', validator: (el) => validateSelect(el), required: true }
        ];

        map.forEach(item => {
            const el = document.getElementById(item.id);
            if (!el) return;
            el.classList.add('validate-field');
            
            setState(el, item.validator(el));

            const inputEvent = (e) => {
                setState(el, item.validator(el));
                
                if (item.id === 'password') {
                    const confirm = document.getElementById('confirm_password');
                    if (confirm) {
                        const confirmValidator = map.find(m => m.id === 'confirm_password');
                        if (confirmValidator) setState(confirm, confirmValidator.validator(confirm));
                    }
                    if (confirm && confirm.classList.contains('valid')) {
                        const pwMismatch = document.getElementById('pwMismatch');
                        if (pwMismatch) pwMismatch.classList.add('d-none');
                    }
                }
                
                if (item.id === 'confirm_password') {
                    const pwMismatch = document.getElementById('pwMismatch');
                    if (pwMismatch && el.classList.contains('valid')) {
                        pwMismatch.classList.add('d-none');
                    }
                }
            };

            if (el.tagName === 'SELECT') {
                el.addEventListener('change', inputEvent);
                el.addEventListener('blur', inputEvent);
            } else {
                el.addEventListener('input', inputEvent);
                el.addEventListener('blur', inputEvent);
            }
        });

        // Form submit validation - UPDATED
        const formForValidation = document.getElementById('userRegisterForm');
        if (formForValidation) {
            // Validation already handled by AJAX submit handler above
            // No need for additional validation here
        }
    });
})();
</script>