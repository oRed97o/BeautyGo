<?php

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isCustomerLoggedIn(), formatDate()
require_once 'backend/function_businesses.php';     // for getBusinessById()
require_once 'backend/function_services.php';       // for getBusinessServices()
require_once 'backend/function_employees.php';      // for getBusinessEmployees()
require_once 'backend/function_reviews.php';        // for getBusinessReviews(), calculateAverageRating()
require_once 'backend/function_albums.php';         // for getBusinessAlbum()
require_once 'backend/function_notifications.php';  // for header.php notifications
require_once 'backend/function_customers.php';      // for header.php getCurrentCustomer()
require_once 'backend/function_favorites.php';      // for header.php getCustomerFavorites()



$businessId = $_GET['id'] ?? '';
if (empty($businessId)) {
    header('Location: index.php');
    exit;
}

$business = getBusinessById($businessId);

if (!$business) {
    header('Location: index.php');
    exit;
}

$services = getBusinessServices($businessId);
$staff = getBusinessEmployees($businessId);
$reviews = getBusinessReviews($businessId);
$averageRating = calculateAverageRating($businessId);
$album = getBusinessAlbum($businessId);

// Get all available images from album
$albumImages = [];
for ($i = 1; $i <= 10; $i++) {
    $imageKey = 'image' . $i;
    if (isset($album[$imageKey]) && !empty($album[$imageKey])) {
        // Convert BLOB to base64 for display
        $albumImages[] = 'data:image/jpeg;base64,' . base64_encode($album[$imageKey]);
    }
}

// Handle logo if it exists
if (isset($album['logo']) && !empty($album['logo'])) {
    array_unshift($albumImages, 'data:image/jpeg;base64,' . base64_encode($album['logo']));
}

// If no images, use default based on business type
if (empty($albumImages)) {
    $defaultImages = [
        'hair salon' => 'resources/salon.png',
        'spa & wellness' => 'resources/spa.png',
        'barbershop' => 'resources/barbers.png',
        'beauty clinic' => 'resources/clinic.png',
        'nail salon' => 'resources/nails.png'
    ];
    
    $businessType = strtolower($business['business_type'] ?? 'salon');
    $defaultImage = $defaultImages[$businessType] ?? 'resources/default.png';
    
    // Must be an array for the slideshow to work
    $albumImages = [$defaultImage];
}

$pageTitle = $business['business_name'] . ' - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/business-detail.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <!-- Business Header -->
        <div class="card mb-4">
            <div class="row g-0">
                <div class="col-md-4">
                    <!-- Slideshow Container -->
                    <div class="slideshow-container">
                        <div class="slides-wrapper">
                            <?php foreach ($albumImages as $index => $image): ?>
                                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($business['business_name']); ?> - Image <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($albumImages) > 1): ?>
                            <!-- Navigation Controls -->
                            <div class="slide-controls">
                                <button class="slide-btn" onclick="changeSlide(-1)" aria-label="Previous slide">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button class="slide-btn" onclick="changeSlide(1)" aria-label="Next slide">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                            
                            <!-- Slide Counter -->
                            <div class="slide-counter">
                                <span id="currentSlide">1</span> / <?php echo count($albumImages); ?>
                            </div>
                            
                            <!-- Indicators -->
                            <div class="slide-indicators">
                                <?php foreach ($albumImages as $index => $image): ?>
                                    <span class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)" aria-label="Go to slide <?php echo $index + 1; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="mb-1"><?php echo htmlspecialchars($business['business_name']); ?></h2>
                                <span class="badge badge-rose"><?php echo ucfirst($business['business_type']); ?></span>
                            </div>
                                <?php if (isBusinessLoggedIn()): ?>
                                    <!-- Business owners cannot book appointments -->
                                    <span class="badge bg-secondary" style="padding: 10px 20px; font-size: 14px;">
                                        <i class="bi bi-info-circle"></i> Business View Only
                                    </span>
                                <?php elseif (isCustomerLoggedIn()): ?>
                                    <a href="booking.php?business_id=<?php echo $business['business_id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-calendar-plus"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right"></i> Login to Book
                                    </a>
                                <?php endif; ?>
                        </div>
                        
                        <p class="business-description"><?php echo htmlspecialchars($business['business_desc'] ?? ''); ?></p>
                        
                        <div class="mb-2">
                            <div class="rating mb-2">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $averageRating) {
                                        echo '<i class="bi bi-star-fill"></i>';
                                    } else {
                                        echo '<i class="bi bi-star"></i>';
                                    }
                                }
                                echo ' <strong>' . number_format($averageRating, 1) . '</strong> ';
                                echo '<small class="text-muted">(' . count($reviews) . ' reviews)</small>';
                                ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="bi bi-geo-alt text-muted"></i>
                                    <?php echo htmlspecialchars($business['business_address'] ?? ''); ?><?php if (!empty($business['city'])) echo ', ' . htmlspecialchars($business['city']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="bi bi-envelope text-muted"></i>
                                    <?php echo htmlspecialchars($business['business_email'] ?? ''); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Services -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Services Offered</h4>
                        <?php if (empty($services)): ?>
                            <div class="empty-state py-3">
                                <i class="bi bi-clipboard-x"></i>
                                <p>No services listed yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($services as $service): ?>
                                <div class="service-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                            <p class="text-muted mb-1"><?php echo htmlspecialchars($service['service_desc'] ?? ''); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?php echo htmlspecialchars($service['duration']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong style="color: var(--color-burgundy);">₱<?php echo number_format($service['cost'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews -->
                <div class="card mb-4" id="reviews">
                    <div class="card-body">
                        <h4 class="mb-3">Customer Reviews</h4>
                        
                        <!-- Write Review Form (Only for logged-in customers) -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <?php 
                            // Check if customer has already reviewed this business
                            $currentCustomer = getCurrentCustomer();
                            $customerId = $currentCustomer['customer_id'];
                            
                            $conn = getDbConnection();
                            $checkStmt = $conn->prepare("SELECT review_id FROM reviews WHERE customer_id = ? AND business_id = ?");
                            $checkStmt->bind_param("ii", $customerId, $businessId);
                            $checkStmt->execute();
                            $existingReview = $checkStmt->get_result()->fetch_assoc();
                            $checkStmt->close();
                            
                            if (!$existingReview): ?>
                                <div class="write-review-section mb-4">
                                    <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#reviewForm" aria-expanded="false">
                                        <i class="bi bi-star-fill"></i> Write a Review
                                    </button>
                                    
                                    <div class="collapse" id="reviewForm">
                                        <div class="card card-body">
                                            <form action="backend/submit-review.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                                                
                                                <!-- Rating -->
                                                <div class="mb-3">
                                                    <label class="form-label">Your Rating *</label>
                                                    <div class="star-rating">
                                                        <input type="radio" name="rating" value="5" id="star5" required>
                                                        <label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                                                        
                                                        <input type="radio" name="rating" value="4" id="star4">
                                                        <label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                                                        
                                                        <input type="radio" name="rating" value="3" id="star3">
                                                        <label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                                                        
                                                        <input type="radio" name="rating" value="2" id="star2">
                                                        <label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                                                        
                                                        <input type="radio" name="rating" value="1" id="star1">
                                                        <label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                                                    </div>
                                                </div>
                                                
                                                <!-- Review Text -->
                                                <div class="mb-3">
                                                    <label class="form-label">Your Review *</label>
                                                    <textarea class="form-control" name="review_text" rows="4" placeholder="Share your experience..." required></textarea>
                                                </div>
                                                
                                                <!-- Photos (Optional) -->
                                                <div class="mb-3">
                                                    <label class="form-label">Add Photos (Optional)</label>
                                                    <input type="file" class="form-control" name="review_images[]" accept="image/*" multiple max="5">
                                                    <small class="text-muted">You can upload up to 5 photos</small>
                                                </div>
                                                
                                                <button type="submit" name="submit_review" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Submit Review
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> You have already reviewed this business.
                                </div>
                            <?php endif; ?>
                        <?php elseif (!isBusinessLoggedIn()): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Please <a href="login.php">login</a> to write a review.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Display existing reviews -->
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state py-3">
                                <i class="bi bi-chat-square-text"></i>
                                <p>No reviews yet. Be the first to review!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars(($review['customer_fname'] ?? '') . ' ' . ($review['customer_lname'] ?? '')); ?></strong>
                                            <div class="rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= ($review['rating'] ?? 0)) {
                                                        echo '<i class="bi bi-star-fill"></i>';
                                                    } else {
                                                        echo '<i class="bi bi-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo formatDate($review['review_date'] ?? ''); ?></small>
                                    </div>
                                    
                                    <p class="mb-2"><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></p>
                                    
                                    <!-- Review Images if any -->
                                    <?php if (!empty($review['images'])): ?>
                                        <div class="review-images mb-2">
                                            <?php foreach ($review['images'] as $image): ?>
                                                <img src="data:image/jpeg;base64,<?php echo base64_encode($image); ?>" 
                                                     alt="Review image" 
                                                     class="review-image" 
                                                     onclick="openImageModal('data:image/jpeg;base64,<?php echo base64_encode($image); ?>')"
                                                     style="cursor: pointer; width: 100px; height: 100px; object-fit: cover; margin-right: 5px; border-radius: 5px;">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Display Replies -->
                                    <?php if (!empty($review['replies'])): ?>
                                        <div class="review-replies mt-3">
                                            <?php foreach ($review['replies'] as $reply): ?>
                                                <div class="review-reply mb-2">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <?php if ($reply['sender_type'] === 'business'): ?>
                                                            <i class="bi bi-shop text-primary"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-person-circle text-secondary"></i>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <strong class="<?php echo $reply['sender_type'] === 'business' ? 'text-primary' : ''; ?>">
                                                                    <?php echo htmlspecialchars($reply['sender_name']); ?>
                                                                    <?php if ($reply['sender_type'] === 'business'): ?>
                                                                        <span class="badge bg-primary ms-1" style="font-size: 0.7rem;">Owner</span>
                                                                    <?php endif; ?>
                                                                </strong>
                                                                <small class="text-muted"><?php echo formatDate($reply['reply_date']); ?></small>
                                                            </div>
                                                            <p class="mb-0 mt-1"><?php echo htmlspecialchars($reply['reply_text']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Reply button for logged-in customers -->
                                    <?php if (isCustomerLoggedIn()): ?>
                                        <?php 
                                        $currentCustomer = getCurrentCustomer();
                                        ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" 
                                                onclick="showCustomerReplyForm(<?php echo $review['review_id']; ?>)">
                                            <i class="bi bi-reply"></i> Reply
                                        </button>
                                        
                                        <!-- Customer reply form (hidden by default) -->
                                        <form method="POST" action="backend/reply-review.php" id="customerReplyForm<?php echo $review['review_id']; ?>" style="display: none;" class="mt-2">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="business_id" value="<?php echo $business['business_id']; ?>">
                                            <div class="input-group">
                                                <textarea class="form-control" name="reply_text" rows="2" placeholder="Write your reply..." required></textarea>
                                                <button type="submit" name="reply_to_review" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Send
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="hideCustomerReplyForm(<?php echo $review['review_id']; ?>)">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Staff -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Our Team</h4>
                        <?php if (empty($staff)): ?>
                            <div class="empty-state py-3">
                                <i class="bi bi-people"></i>
                                <p>No staff listed yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($staff as $member): ?>
                                <?php
                                $employeeName = trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?: 'Staff Member';
                                $employeeImg = $member['employ_img'] ?? null;
                                ?>
                                <div class="staff-member">
                                    <?php if (!empty($employeeImg)): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($employeeImg); ?>" alt="<?php echo htmlspecialchars($employeeName); ?>">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; margin-bottom: 0.5rem;">
                                            <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($employeeName); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($member['specialization'] ?? ''); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Location Map -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-geo-alt-fill"></i> Location</h4>
                        <div id="businessMap" style="height: 250px; border-radius: 10px; overflow: hidden;"></div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-pin-map"></i> 
                                <?php echo htmlspecialchars($business['business_address'] ?? ''); ?><?php if (!empty($business['city'])) echo ', ' . htmlspecialchars($business['city']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Image Modal for Review Images -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Review image" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Slideshow functionality
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const indicators = document.querySelectorAll('.indicator');
const slideCounter = document.getElementById('currentSlide');
const totalSlides = slides.length;

const SLIDE_DURATION = 5000; // 5 seconds
let autoSlideInterval;

function showSlide(index, direction = 1) {
    // Mark previous slide
    slides[currentSlideIndex].classList.add('prev');
    
    // Ensure index wraps around
    if (index >= totalSlides) {
        currentSlideIndex = 0;
    } else if (index < 0) {
        currentSlideIndex = totalSlides - 1;
    } else {
        currentSlideIndex = index;
    }
    
    // Update slides with smooth transition
    slides.forEach((slide, i) => {
        slide.classList.remove('active', 'prev');
        if (i === currentSlideIndex) {
            slide.classList.add('active');
        }
    });
    
    // Update indicators
    if (indicators.length > 0) {
        indicators.forEach((indicator, i) => {
            indicator.classList.remove('active');
            if (i === currentSlideIndex) {
                indicator.classList.add('active');
            }
        });
    }
    
    // Update counter
    if (slideCounter) {
        slideCounter.textContent = currentSlideIndex + 1;
    }
}

function changeSlide(direction) {
    showSlide(currentSlideIndex + direction, direction);
    resetAutoSlide();
}

function goToSlide(index) {
    const direction = index > currentSlideIndex ? 1 : -1;
    showSlide(index, direction);
    resetAutoSlide();
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    autoSlideInterval = setInterval(() => {
        showSlide(currentSlideIndex + 1);
    }, SLIDE_DURATION);
}

// Start auto-slide on page load
if (totalSlides > 1) {
    autoSlideInterval = setInterval(() => {
        showSlide(currentSlideIndex + 1);
    }, SLIDE_DURATION);
}

// Pause on hover
const slideshowContainer = document.querySelector('.slideshow-container');
if (slideshowContainer) {
    slideshowContainer.addEventListener('mouseenter', () => {
        clearInterval(autoSlideInterval);
    });
    
    slideshowContainer.addEventListener('mouseleave', () => {
        resetAutoSlide();
    });
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        changeSlide(-1);
    } else if (e.key === 'ArrowRight') {
        changeSlide(1);
    }
});

// Touch/Swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

if (slideshowContainer) {
    slideshowContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    slideshowContainer.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
}

function handleSwipe() {
    const swipeThreshold = 50;
    if (touchEndX < touchStartX - swipeThreshold) {
        changeSlide(1); // Swipe left
    }
    if (touchEndX > touchStartX + swipeThreshold) {
        changeSlide(-1); // Swipe right
    }
}

// Review image modal functionality
function openImageModal(imageSrc) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('modalImage').src = imageSrc;
    modal.show();
}

// Show/hide customer reply form
function showCustomerReplyForm(reviewId) {
    document.getElementById('customerReplyForm' + reviewId).style.display = 'block';
}

function hideCustomerReplyForm(reviewId) {
    document.getElementById('customerReplyForm' + reviewId).style.display = 'none';
}

// Scroll to reviews section if hash is present
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#reviews') {
        const reviewsSection = document.getElementById('reviews');
        if (reviewsSection) {
            reviewsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
});

// Business Location Map
const bizLat = <?php echo $business['latitude'] ?? 14.0697; ?>;
const bizLng = <?php echo $business['longitude'] ?? 120.6328; ?>;

const businessMap = L.map('businessMap').setView([bizLat, bizLng], 16);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19
}).addTo(businessMap);

const markerIcon = L.divIcon({
    html: '<i class="bi bi-geo-alt-fill" style="font-size: 2rem; color: #850E35;"></i>',
    className: 'custom-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 32]
});

const businessName = <?php echo json_encode($business['business_name']); ?>;
const businessAddress = <?php echo json_encode($business['business_address'] ?? ''); ?>;

L.marker([bizLat, bizLng], { icon: markerIcon })
    .addTo(businessMap)
    .bindPopup('<strong>' + businessName + '</strong><br>' + businessAddress);
</script>

<?php include 'includes/footer.php'; ?>