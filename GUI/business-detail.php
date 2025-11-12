<?php //full "Analysis"
require_once 'config.php';
require_once 'functions.php';

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
$staff = getBusinessEmployees($businessId); // Fixed: was getBusinessStaff
$reviews = getBusinessReviews($businessId);
$averageRating = calculateAverageRating($businessId);
$album = getBusinessAlbum($businessId);

// Get all available images from album
$albumImages = [];
for ($i = 1; $i <= 10; $i++) {
    $imageKey = 'image' . $i; // Fixed: removed underscore
    if (isset($album[$imageKey]) && !empty($album[$imageKey])) {
        // Convert BLOB to base64 for display
        $albumImages[] = 'data:image/jpeg;base64,' . base64_encode($album[$imageKey]);
    }
}

// Handle logo if it exists
if (isset($album['logo']) && !empty($album['logo'])) {
    array_unshift($albumImages, 'data:image/jpeg;base64,' . base64_encode($album['logo']));
}

// If no images, use default
if (empty($albumImages)) {
    $albumImages[] = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400';
}

$pageTitle = $business['business_name'] . ' - BeautyGo';
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

/* Slideshow Styles */
.slideshow-container {
    position: relative;
    height: 300px;
    width: 100%;
    overflow: hidden;
    background-color: #000;
}

.slides-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
    transform: scale(1.05);
}

.slide.active {
    opacity: 1;
    transform: scale(1);
    z-index: 2;
}

.slide.prev {
    opacity: 0;
    transform: scale(0.95);
    z-index: 1;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-controls {
    position: absolute;
    top: 50%;
    width: 100%;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 10px;
    pointer-events: none;
    z-index: 10;
}

.slide-btn {
    pointer-events: all;
    background-color: rgba(255, 255, 255, 0.9);
    border: none;
    color: #333;
    font-size: 1.5rem;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    opacity: 0;
}

.slideshow-container:hover .slide-btn {
    opacity: 1;
}

.slide-btn:hover {
    background-color: #fff;
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.slide-btn:active {
    transform: scale(1.05);
}

.slide-indicators {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    transition: all 0.4s ease;
    border: 2px solid transparent;
}

.indicator.active {
    background-color: #fff;
    transform: scale(1.3);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
}

.indicator:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.9);
    transform: scale(1.15);
}

.slide-counter {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 10;
    backdrop-filter: blur(5px);
    transition: all 0.3s ease;
}

.slideshow-container:hover .slide-counter {
    background-color: rgba(0, 0, 0, 0.8);
}

/* Loading animation for images */
.slide img {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
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
                            <?php if (isCustomerLoggedIn()): ?>
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
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Customer Reviews</h4>
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state py-3">
                                <i class="bi bi-chat-square-text"></i>
                                <p>No reviews yet. Be the first to review!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong><?php echo htmlspecialchars(trim(($review['customer_fname'] ?? '') . ' ' . ($review['customer_lname'] ?? '')) ?: 'Anonymous'); ?></strong>
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
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></p>
                                    <small class="text-muted"><?php echo formatDate($review['review_date'] ?? ''); ?></small>
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
            </div>
        </div>
    </div>
</main>

<script>
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
</script>

<?php include 'includes/footer.php'; ?>