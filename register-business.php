<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_albums.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Business Registration - BeautyGo';
include 'includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="css/register-business.css">
<link rel="stylesheet" href="css/styles.css">

<main>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4" style="color: var(--color-burgundy);">
                            <i class="bi bi-shop"></i> Business Registration
                        </h2>
                        
                        <form action="backend/auth.php" method="POST" enctype="multipart/form-data" id="businessRegisterForm">
                            <input type="hidden" name="action" value="register_business">
                            <input type="hidden" id="croppedLogoData" name="cropped_logo_data" value="">
                            <input type="hidden" id="latitude" name="latitude" value="14.0697">
                            <input type="hidden" id="longitude" name="longitude" value="120.6328">
                            
                            <!-- Business Logo -->
                            <h5 class="mb-3">Business Logo (Optional)</h5>
                            <div class="text-center mb-4">
                                <div class="logo-preview" id="logo_preview">
                                    <img src="" alt="Logo Preview" id="logoPreviewImg">
                                    <div class="placeholder" id="logoPlaceholder">
                                        <i class="bi bi-shop" style="font-size: 3rem; color: var(--color-burgundy);"></i>
                                        <p class="mb-0 small">Upload your logo</p>
                                    </div>
                                </div>
                                
                                <div class="file-input-wrapper">
                                    <input type="file" name="business_logo" id="business_logo" accept="image/*" onchange="handleLogoSelect(this)">
                                    <label for="business_logo" class="file-input-label">
                                        <i class="bi bi-upload"></i> <span id="uploadBtnText">Choose Logo Image</span>
                                    </label>
                                </div>
                                
                                <!-- File info display -->
                                <div id="fileInfo" style="display: none;">
                                    <small class="text-muted">
                                        <i class="bi bi-file-earmark-image"></i> 
                                        <span id="fileName"></span>
                                    </small>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-logo-btn" onclick="removeLogo()">
                                        <i class="bi bi-x-circle"></i> Remove
                                    </button>
                                </div>
                                
                                <small class="text-muted d-block mt-2" id="fileHint">JPG, PNG, GIF, WebP - Max 5MB</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Business Information -->
                            <h5 class="mb-3">Business Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_name" class="form-label">Business Name *</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="business_type" class="form-label">Business Type *</label>
                                    <select class="form-select" id="business_type" name="business_type" required>
                                        <option value="">Select a type...</option>
                                        <option value="Hair Salon">Hair Salon</option>
                                        <option value="Spa & Wellness">Spa & Wellness</option>
                                        <option value="Barbershop">Barbershop</option>
                                        <option value="Nail Salon">Nail Salon</option>
                                        <option value="Beauty Clinic">Beauty Clinic</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_desc" class="form-label">Business Description *</label>
                                <textarea class="form-control" id="business_desc" name="business_desc" rows="3" required placeholder="Describe your business, services, and what makes you unique..."></textarea>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Business Hours -->
                            <h5 class="mb-3">Business Hours</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="opening_hour" class="form-label">Opening Time *</label>
                                    <input type="time" class="form-control" id="opening_hour" name="opening_hour" required>
                                    <small class="text-muted d-block mt-1">e.g., 09:00</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="closing_hour" class="form-label">Closing Time *</label>
                                    <input type="time" class="form-control" id="closing_hour" name="closing_hour" required>
                                    <small class="text-muted d-block mt-1">e.g., 18:00</small>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Contact Information -->
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="business_email" name="business_email" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="business_num" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="business_num" name="business_num" required placeholder="+63 912 345 6789">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="business_password" name="business_password" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('business_password', 'businessPasswordEye')">
                                        <i class="bi bi-eye" id="businessPasswordEye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 8 characters with numbers and symbols</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'businessConfirmPasswordEye')">
                                        <i class="bi bi-eye" id="businessConfirmPasswordEye"></i>
                                    </button>
                                </div>
                                <div class="form-text text-danger small d-none" id="businessPwMismatch" role="alert">Passwords do not match</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Location -->
                            <h5 class="mb-3">Business Location *</h5>
                            <div class="mb-3">
                                <label for="business_address" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="business_address" name="business_address" required placeholder="Street, Barangay">
                            </div>
                            
                            <div class="mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" value="Nasugbu" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt-fill"></i> Pin Your Location on Map *
                                </label>
                                <div class="map-info-box">
                                    <i class="bi bi-info-circle"></i>
                                    <small>Click anywhere on the map to set your business location. The address will be automatically filled based on your selection.</small>
                                </div>
                                <div id="locationMap"></div>
                                <div class="coordinates-display">
                                    <strong>Selected Coordinates:</strong><br>
                                    Latitude: <span id="displayLat">14.0697</span> | Longitude: <span id="displayLng">120.6328</span>
                                </div>
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
    
    <!-- Crop Modal -->
    <div class="crop-modal-overlay" id="cropModal">
        <div class="crop-modal-content">
            <div class="crop-modal-header">
                <h3><i class="bi bi-crop"></i> Adjust Your Logo</h3>
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
                <p>Drag to move • Zoom to resize • Your logo will be cropped to fit</p>
            </div>
            
            <div class="crop-modal-buttons">
                <button type="button" class="btn-cancel-crop" id="btnCancelCrop">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn-confirm-crop" id="btnConfirmCrop">
                    <i class="bi bi-check-circle"></i> Set as Logo
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
</main>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ========================================
// ERROR MODAL FUNCTIONALITY
// ========================================
function showErrorModal(message) {
    document.getElementById('errorModalMessage').textContent = message;
    document.getElementById('errorModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.getElementById('errorModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeErrorModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeErrorModal();
    }
});

// ========================================
// PASSWORD VISIBILITY TOGGLE
// ========================================
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


        document.getElementById('businessRegisterForm').addEventListener('submit', function(e) {
        const password = document.getElementById('business_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Check if passwords match
        if (password !== confirmPassword) {
            e.preventDefault();
            const pwMismatch = document.getElementById('businessPwMismatch');
            if (pwMismatch) {
                pwMismatch.classList.remove('d-none');
                pwMismatch.textContent = 'Passwords do not match! Please make sure both password fields are correct.';
            }
            document.getElementById('confirm_password').focus();
            return false;
        }
    
    // Validate password length
    if (password.length < 8) {
        e.preventDefault();
        showErrorModal('Password must be at least 8 characters long!');
        document.getElementById('business_password').focus();
        return false;
    }
    
    // Check for at least one number
    if (!/\d/.test(password)) {
        e.preventDefault();
        showErrorModal('Password must contain at least one number (0-9)!');
        document.getElementById('business_password').focus();
        return false;
    }
    
    // Check for at least one special character
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        e.preventDefault();
        showErrorModal('Password must contain at least one special character (!@#$%^&* etc.)');
        document.getElementById('business_password').focus();
        return false;
    }
    
    // All validation passed
    return true;
});

// ========================================
// MAP FUNCTIONALITY
// ========================================
let map;
let marker;
const defaultLat = 14.0697;
const defaultLng = 120.6328;

// Initialize map
function initMap() {
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
    
    // Click to set location
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        updateLocation(lat, lng);
    });
    
    // Drag marker to set location
    marker.on('dragend', function(e) {
        const lat = e.target.getLatLng().lat;
        const lng = e.target.getLatLng().lng;
        updateLocation(lat, lng);
    });
}

function updateLocation(lat, lng) {
    marker.setLatLng([lat, lng]);
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    
    // Reverse geocode to get address
    reverseGeocode(lat, lng);
}

// Reverse geocoding to get address from coordinates
async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`,
            {
                headers: {
                    'Accept-Language': 'en'
                }
            }
        );
        
        const data = await response.json();
        
        if (data && data.address) {
            const address = data.address;
            
            // Extract address components
            const road = address.road || address.street || '';
            const suburb = address.suburb || address.neighbourhood || address.village || '';
            const barangay = address.suburb || address.neighbourhood || address.village || address.hamlet || '';
            const city = address.city || address.town || address.municipality || 'Nasugbu';
            
            // Build street address (road + barangay/suburb)
            let streetAddress = '';
            if (road) {
                streetAddress = road;
                if (barangay && barangay !== road) {
                    streetAddress += ', ' + barangay;
                }
            } else if (barangay) {
                streetAddress = barangay;
            }
            
            // Update form fields if we got valid data
            if (streetAddress) {
                document.getElementById('business_address').value = streetAddress;
            }
            
            if (city) {
                document.getElementById('city').value = city;
            }
            
            // If reverse geocode filled fields, trigger input events so live validation picks up the changes
            const addrEl = document.getElementById('business_address');
            const cityEl = document.getElementById('city');
            if (addrEl) {
                addrEl.dispatchEvent(new Event('input', { bubbles: true }));
                addrEl.dispatchEvent(new Event('blur', { bubbles: true }));
            }
            if (cityEl) {
                cityEl.dispatchEvent(new Event('input', { bubbles: true }));
                cityEl.dispatchEvent(new Event('blur', { bubbles: true }));
            }
            // Show a small notification
            showAddressNotification(streetAddress || 'Location selected');
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }
}

// Show temporary notification
function showAddressNotification(message) {
    // Remove existing notification if any
    const existing = document.getElementById('addressNotification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.id = 'addressNotification';
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #850E35;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInUp 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="bi bi-check-circle-fill"></i> ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});

// ========================================
// CROP MODAL FUNCTIONALITY WITH ZOOM
// ========================================
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

function handleLogoSelect(input) {
    const file = input.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            showErrorModal('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showErrorModal('Only JPG, PNG, GIF, and WebP images are allowed');
            input.value = '';
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
}

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

// Zoom functionality
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

function closeCropModal() {
    cropModal.classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('business_logo').value = '';
    currentX = 0;
    currentY = 0;
    initialX = 0;
    initialY = 0;
    currentZoom = 1;
}

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

btnCancelCrop.addEventListener('click', function() {
    closeCropModal();
});

btnConfirmCrop.addEventListener('click', function() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const size = 300;
    
    canvas.width = size;
    canvas.height = size;
    
    const img = new Image();
    img.onload = function() {
        const previewSize = 300;
        const scale = size / previewSize;
        
        const imgWidth = parseFloat(cropPreviewImage.style.width);
        const imgHeight = parseFloat(cropPreviewImage.style.height);
        
        const scaledX = currentX * scale;
        const scaledY = currentY * scale;
        const scaledWidth = imgWidth * scale;
        const scaledHeight = imgHeight * scale;
        
        ctx.drawImage(img, scaledX, scaledY, scaledWidth, scaledHeight);
        
        canvas.toBlob(function(blob) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreviewImg').src = e.target.result;
                document.getElementById('logoPreviewImg').classList.add('show');
                document.getElementById('logoPlaceholder').classList.add('hide');
                document.getElementById('fileName').textContent = currentFile.name;
                document.getElementById('fileInfo').style.display = 'block';
                document.getElementById('fileHint').style.display = 'none';
                document.getElementById('uploadBtnText').textContent = 'Change Logo';
                document.getElementById('croppedLogoData').value = e.target.result;
                
                closeCropModal();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.9);
    };
    img.src = originalImageSrc;
});

function removeLogo() {
    if (confirm('Remove this logo?')) {
        document.getElementById('business_logo').value = '';
        document.getElementById('logoPreviewImg').src = '';
        document.getElementById('logoPreviewImg').classList.remove('show');
        document.getElementById('logoPlaceholder').classList.remove('hide');
        document.getElementById('fileInfo').style.display = 'none';
        document.getElementById('fileHint').style.display = 'block';
        document.getElementById('uploadBtnText').textContent = 'Choose Logo Image';
        document.getElementById('croppedLogoData').value = '';
    }
}

cropModal.addEventListener('click', function(e) {
    if (e.target === cropModal) {
        closeCropModal();
    }
});
</script>

<script>
// Live validation for business contact and location fields
// Live validation for business information, hours, contact and location fields
(function() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateEmail(val) {
        const v = val.trim().toLowerCase();
        return emailRegex.test(v) && v.endsWith('.com');
    }

    function validatePhone(val) {
        if (!val) return false;
        const digits = val.replace(/\D/g, '');
        return digits.length === 11;
    }

    function validateNonEmpty(val) {
        return val.trim().length > 0;
    }

    function validateTime(val) {
        return val.trim().length > 0;
    }

    function setState(el, ok) {
        if (!el) return;
        el.classList.remove('valid','invalid');
        const val = (el.tagName === 'SELECT') ? el.value : el.value.trim();
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

    function validatePassword(val) {
        if (!val) return false;
        return val.length >= 8 &&
               /\d/.test(val) &&
               /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const fields = [
            // Business Information
            { id: 'business_name', validator: (el)=> validateNonEmpty(el.value) },
            { id: 'business_type', validator: (el)=> el.value !== '' },
            { id: 'business_desc', validator: (el)=> validateNonEmpty(el.value) },
            
            // Business Hours
            { id: 'opening_hour', validator: (el)=> validateTime(el.value) },
            { id: 'closing_hour', validator: (el)=> validateTime(el.value) },
            
            // Contact Information
            { id: 'business_email', validator: (el)=> validateEmail(el.value) },
            { id: 'business_num', validator: (el)=> validatePhone(el.value) },
            { id: 'business_password', validator: (el)=> validatePassword(el.value) },
            { id: 'confirm_password', validator: (el)=> {
                    const pw = document.getElementById('business_password') ? document.getElementById('business_password').value : '';
                    return el.value === pw && validatePassword(pw);
                }
            },
            
            // Location
            { id: 'business_address', validator: (el)=> validateNonEmpty(el.value) },
            { id: 'city', validator: (el)=> validateNonEmpty(el.value) }
        ];

        fields.forEach(item => {
            const el = document.getElementById(item.id);
            if (!el) return;
            el.classList.add('validate-field');
            // initial
            setState(el, item.validator(el));

            const handler = function() {
                setState(el, item.validator(el));
                // if business_password changes, revalidate confirm_password and hide mismatch if resolved
                if (item.id === 'business_password') {
                    const confirm = document.getElementById('confirm_password');
                    if (confirm) {
                        const confirmValidator = fields.find(f => f.id === 'confirm_password');
                        if (confirmValidator) setState(confirm, confirmValidator.validator(confirm));
                        if (confirm.classList.contains('valid')) {
                            const pwMismatch = document.getElementById('businessPwMismatch');
                            if (pwMismatch) pwMismatch.classList.add('d-none');
                        }
                    }
                }

                // if confirm_password changes, hide mismatch when valid
                if (item.id === 'confirm_password') {
                    const pwMismatch = document.getElementById('businessPwMismatch');
                    if (pwMismatch && el.classList.contains('valid')) pwMismatch.classList.add('d-none');
                }
            };

            // For select elements, use 'change' event
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', handler);
                el.addEventListener('blur', handler);
            } else {
                el.addEventListener('input', handler);
                el.addEventListener('blur', handler);
            }
        });

        // On submit, ensure all required fields are valid
        const form = document.getElementById('businessRegisterForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const requiredIds = [
                    'business_name', 'business_type', 'business_desc',
                    'opening_hour', 'closing_hour',
                    'business_email', 'business_num',
                    'business_address', 'city'
                ];
                let firstInvalid = null;
                let anyInvalid = false;

                requiredIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    const val = (el.tagName === 'SELECT') ? el.value : el.value.trim();
                    if (!val) {
                        el.classList.add('invalid');
                        anyInvalid = true;
                        firstInvalid = firstInvalid || el;
                        return;
                    }
                    if (!el.classList.contains('valid')) {
                        el.classList.add('invalid');
                        anyInvalid = true;
                        firstInvalid = firstInvalid || el;
                    }
                });

                if (anyInvalid) {
                    e.preventDefault();
                    if (firstInvalid) firstInvalid.focus();
                    if (typeof showErrorModal === 'function') {
                        showErrorModal('Please fix all highlighted fields before continuing.');
                    } else {
                        alert('Please fix all highlighted fields before continuing.');
                    }
                }
            });
        }
    });
})();
</script>

<?php include 'includes/footer.php'; ?>