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
                                    <button class="btn btn-primary" onclick="showLoginModal(event)">
                                        <i class="bi bi-calendar-plus"></i> Book Now
                                    </button>
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
                                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                                     alt="Review image" 
                                                     class="review-image" 
                                                     onclick="openImageModal('<?php echo htmlspecialchars($image); ?>')"
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
                                                            <p class="mb-1 mt-1"><?php echo htmlspecialchars($reply['reply_text']); ?></p>
                                                            
                                                            <!-- Display reply image if exists -->
                                                            <?php if (!empty($reply['reply_image'])): ?>
                                                                <img src="<?php echo htmlspecialchars($reply['reply_image']); ?>" 
                                                                     alt="Reply image" 
                                                                     class="reply-image" 
                                                                     onclick="openImageModal('<?php echo htmlspecialchars($reply['reply_image']); ?>')"
                                                                     style="cursor: pointer; width: 80px; height: 80px; object-fit: cover; margin-top: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                                            <?php endif; ?>
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
                                        <form method="POST" action="backend/reply-review.php" id="customerReplyForm<?php echo $review['review_id']; ?>" style="display: none;" class="mt-2" enctype="multipart/form-data">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="business_id" value="<?php echo $business['business_id']; ?>">
                                            <div class="input-group-vertical">
                                                <textarea class="form-control" name="reply_text" rows="2" placeholder="Write your reply..." required></textarea>
                                                <div class="mt-2 mb-2">
                                                    <label for="replyImage<?php echo $review['review_id']; ?>" class="form-label small">
                                                        <i class="bi bi-image"></i> Add Photo (Optional)
                                                    </label>
                                                    <input type="file" 
                                                           class="form-control form-control-sm" 
                                                           id="replyImage<?php echo $review['review_id']; ?>" 
                                                           name="reply_image" 
                                                           accept="image/*"
                                                           onchange="previewReplyImage(this, <?php echo $review['review_id']; ?>)">
                                                    <div id="replyImagePreview<?php echo $review['review_id']; ?>" class="mt-2"></div>
                                                    <small class="text-muted d-block">Max 5MB (JPG, PNG, GIF, WebP)</small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="submit" name="reply_to_review" class="btn btn-primary">
                                                        <i class="bi bi-send"></i> Send
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" onclick="hideCustomerReplyForm(<?php echo $review['review_id']; ?>)">
                                                        Cancel
                                                    </button>
                                                </div>
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
                <!-- Business Hours -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-clock-fill"></i> Business Hours</h4>
                        <?php 
                        $openingHour = $business['opening_hour'] ?? null;
                        $closingHour = $business['closing_hour'] ?? null;
                        
                        if (!empty($openingHour) && !empty($closingHour)): 
                        ?>
                            <div class="business-hours">
                                <div class="hours-item">
                                    <strong>Opens:</strong>
                                    <span class="hours-time"><?php echo date('g:i A', strtotime($openingHour)); ?></span>
                                </div>
                                <div class="hours-item">
                                    <strong>Closes:</strong>
                                    <span class="hours-time"><?php echo date('g:i A', strtotime($closingHour)); ?></span>
                                </div>
                                <div class="hours-status mt-3 p-2 rounded text-center" id="businessStatus">
                                    <small id="statusText"></small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state py-3">
                                <p class="text-muted">Business hours not available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Staff Section - COMPLETELY FIXED -->
<div class="card mb-4">
    <div class="card-body">
        <h4 class="mb-3">Our Team</h4>
        <?php if (empty($staff)): ?>
            <div class="empty-state py-3">
                <i class="bi bi-people"></i>
                <p>No staff listed yet</p>
            </div>
        <?php else: ?>
            <div class="staff-grid">
                <?php foreach ($staff as $member): ?>
                    <?php
                    $employeeName = trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?: 'Staff Member';
                    $employeeImg = $member['employ_img'] ?? null;
                    
                    // FIXED: Properly encode image for display AND for JavaScript
                    $imageDataUrl = '';
                    if (!empty($employeeImg)) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($employeeImg);
                        $imageDataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($employeeImg);
                    }
                    
                    // FIXED: Prepare safe member data for JavaScript
                    $memberData = [
                        'employ_id' => $member['employ_id'] ?? '',
                        'employ_fname' => $member['employ_fname'] ?? '',
                        'employ_lname' => $member['employ_lname'] ?? '',
                        'specialization' => $member['specialization'] ?? 'No specialization',
                        'skills' => $member['skills'] ?? 'No skills listed',
                        'employ_bio' => $member['employ_bio'] ?? 'No bio available',
                        'employ_status' => $member['employ_status'] ?? 'available',
                        'employ_img_url' => $imageDataUrl // Pass the data URL, not raw binary
                    ];
                    
                    // FIXED: Properly escape JSON for HTML attribute
                    $memberDataJson = htmlspecialchars(json_encode($memberData), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="staff-member" 
                         onclick='openStaffModal(<?php echo $memberDataJson; ?>)'
                         role="button"
                         tabindex="0"
                         onkeypress="if(event.key==='Enter') openStaffModal(<?php echo $memberDataJson; ?>)">
                        <?php if (!empty($imageDataUrl)): ?>
                            <img src="<?php echo htmlspecialchars($imageDataUrl); ?>" 
                                 alt="<?php echo htmlspecialchars($employeeName); ?>">
                        <?php else: ?>
                            <div class="staff-placeholder">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                        <h6 class="mb-0"><?php echo htmlspecialchars($employeeName); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($member['specialization'] ?? ''); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

                <!-- Location Map -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-geo-alt-fill"></i> Location</h4>
                        <div id="businessMap" style="height: 250px; border-radius: 10px; overflow: hidden; cursor: pointer;" 
                             onclick="openMapModal()" 
                             role="button" 
                             tabindex="0"
                             title="Click to enlarge map">
                        </div>
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

<!-- Staff Information Modal - FIXED -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="staffImageContainer" style="width: 150px; height: 150px; margin: 0 auto 1rem; overflow: hidden; border-radius: 50%; border: 3px solid #e0e0e0; display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                        <!-- Image will be inserted here by JavaScript -->
                    </div>
                </div>
                <h5 id="staffName" class="text-center mb-1"></h5>
                <p id="staffSpecialization" class="text-center text-muted mb-3"></p>
                
                <div class="staff-details">
                    <div class="detail-item mb-3">
                        <h6 class="mb-2"><i class="bi bi-star-fill"></i> Specialization</h6>
                        <p id="staffSpecializationDetail" class="mb-0"></p>
                    </div>
                    
                    <div class="detail-item mb-3">
                        <h6 class="mb-2"><i class="bi bi-person-badge"></i> Bio</h6>
                        <p id="staffBio" class="mb-0"></p>
                    </div>
                    
                    <div class="detail-item mb-3">
                        <h6 class="mb-2"><i class="bi bi-award"></i> Skills</h6>
                        <p id="staffSkills" class="mb-0"></p>
                    </div>
                    
                    <div class="detail-item">
                        <h6 class="mb-2"><i class="bi bi-circle-fill"></i> Status</h6>
                        <p id="staffStatus" class="mb-0"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enlarged Location Map Modal -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt-fill"></i> 
                    <?php echo htmlspecialchars($business['business_name']); ?> - Location
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="enlargedBusinessMap" style="height: 500px; width: 100%;"></div>
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

// Staff Modal Functionality
function openStaffModal(staffMember) {
    console.log('Opening staff modal:', staffMember);
    
    // Prepare display data
    const staffName = ((staffMember.employ_fname || '') + ' ' + (staffMember.employ_lname || '')).trim() || 'Staff Member';
    const specialization = staffMember.specialization || 'No specialization listed';
    const bio = staffMember.employ_bio || 'No bio available';
    const skills = staffMember.skills || 'No skills listed';
    const status = staffMember.employ_status || 'available';
    const statusFormatted = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
    
    // FIXED: Set image using the pre-encoded data URL
    const imageContainer = document.getElementById('staffImageContainer');
    if (imageContainer) {
        if (staffMember.employ_img_url && staffMember.employ_img_url.trim() !== '') {
            imageContainer.innerHTML = '<img src="' + staffMember.employ_img_url + '" alt="' + staffName + '" style="width: 100%; height: 100%; object-fit: cover;">';
        } else {
            imageContainer.innerHTML = '<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 100%; height: 100%;"><i class="bi bi-person-fill text-white" style="font-size: 3.5rem;"></i></div>';
        }
    }
    
    // Set text content
    document.getElementById('staffName').textContent = staffName;
    document.getElementById('staffSpecialization').textContent = specialization;
    document.getElementById('staffSpecializationDetail').textContent = specialization;
    document.getElementById('staffBio').textContent = bio;
    document.getElementById('staffSkills').textContent = skills;
    
    // Set status badge
    const statusBadgeClass = status === 'available' ? 'bg-success' : (status === 'on_leave' ? 'bg-warning' : 'bg-secondary');
    document.getElementById('staffStatus').innerHTML = '<span class="badge ' + statusBadgeClass + '">' + statusFormatted + '</span>';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('staffModal'));
    modal.show();
}

// Enlarged Map Modal Functionality
let enlargedMap = null;
let mapModalInstance = null;

function openMapModal() {
    const mapModal = document.getElementById('mapModal');
    if (!mapModalInstance) {
        mapModalInstance = new bootstrap.Modal(mapModal);
    }
    
    mapModalInstance.show();
    
    // Initialize the enlarged map after modal is shown
    mapModal.addEventListener('shown.bs.modal', function() {
        setTimeout(function() {
            if (!enlargedMap) {
                enlargedMap = L.map('enlargedBusinessMap').setView([bizLat, bizLng], 16);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 19
                }).addTo(enlargedMap);
                
                L.marker([bizLat, bizLng], { icon: markerIcon })
                    .addTo(enlargedMap)
                    .bindPopup('<strong>' + businessName + '</strong><br>' + businessAddress)
                    .openPopup();
            } else {
                // Refresh existing map
                enlargedMap.invalidateSize();
            }
        }, 100);
    }, { once: true });
}

// Clean up map when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const mapModal = document.getElementById('mapModal');
    if (mapModal) {
        mapModal.addEventListener('hide.bs.modal', function() {
            if (enlargedMap) {
                enlargedMap.remove();
                enlargedMap = null;
            }
        });
    }
});

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

// ==================== LOGIN MODAL FUNCTIONALITY ====================

// Create login modal HTML when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Create modal HTML if it doesn't exist
    if (!document.getElementById('loginModalOverlay')) {
        const modalHTML = `
            <div class="login-modal-overlay" id="loginModalOverlay">
                <div class="login-modal">
                    <button class="login-modal-close" onclick="closeLoginModal()">
                        <i class="bi bi-x"></i>
                    </button>
                    <div class="login-modal-icon">
                        <i class="bi bi-calendar-heart"></i>
                    </div>
                    <h2 class="login-modal-title">Login Required</h2>
                    <p class="login-modal-message">
                        Please login as a customer to book appointments and enjoy our services
                    </p>
                    <div class="login-modal-buttons">
                        <a href="login.php" class="login-modal-btn login-modal-btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login Now
                        </a>
                        <button class="login-modal-btn login-modal-btn-secondary" onclick="closeLoginModal()">
                            <i class="bi bi-x-circle"></i>
                            Maybe Later
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Close modal when clicking outside
        document.getElementById('loginModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('loginModalOverlay');
                if (overlay && overlay.classList.contains('show')) {
                    closeLoginModal();
                }
            }
        });
    }
});

// Show login modal
function showLoginModal(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const overlay = document.getElementById('loginModalOverlay');
    if (overlay) {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

// Close login modal
function closeLoginModal() {
    const overlay = document.getElementById('loginModalOverlay');
    if (overlay) {
        overlay.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
}

const businessName = <?php echo json_encode($business['business_name']); ?>;
const businessAddress = <?php echo json_encode($business['business_address'] ?? ''); ?>;

L.marker([bizLat, bizLng], { icon: markerIcon })
    .addTo(businessMap)
    .bindPopup('<strong>' + businessName + '</strong><br>' + businessAddress);

// ==================== BUSINESS HOURS STATUS ====================

// Update business hours status in real-time
function updateBusinessStatus() {
    const statusElement = document.getElementById('businessStatus');
    const statusText = document.getElementById('statusText');
    
    if (!statusElement) return;
    
    <?php if (!empty($business['opening_hour']) && !empty($business['closing_hour'])): ?>
        const openingTime = '<?php echo date('H:i', strtotime($business['opening_hour'])); ?>';
        const closingTime = '<?php echo date('H:i', strtotime($business['closing_hour'])); ?>';
        
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const currentTime = hours + ':' + minutes;
        
        // Convert time strings to comparable format
        const isOpen = currentTime >= openingTime && currentTime < closingTime;
        
        if (isOpen) {
            statusElement.classList.remove('closed');
            statusElement.classList.add('open');
            statusText.innerHTML = '<i class="bi bi-check-circle-fill"></i> <strong>Open Now</strong> (Closes at ' + closingTime.substring(0, 5).replace(':', ':') + ')';
        } else {
            statusElement.classList.add('closed');
            statusElement.classList.remove('open');
            statusText.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> <strong>Closed</strong> (Opens at ' + openingTime.substring(0, 5).replace(':', ':') + ')';
        }
    <?php endif; ?>
}

// Update status on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBusinessStatus();
    // Update status every minute
    setInterval(updateBusinessStatus, 60000);
});

// Function to display half-star ratings
function displayStarRating(rating, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            container.innerHTML += '<i class="bi bi-star-fill"></i>';
        } else if (i - 1 < fullStars && hasHalfStar) {
            container.innerHTML += '<i class="bi bi-star-half"></i>';
        } else {
            container.innerHTML += '<i class="bi bi-star"></i>';
        }
    }
}

// Update review display with half-star support
document.addEventListener('DOMContentLoaded', function() {
    // Display half stars for each review
    const reviews = document.querySelectorAll('.review-item');
    reviews.forEach((review, index) => {
        const ratingDiv = review.querySelector('.rating');
        if (ratingDiv) {
            // Get the rating value from data attribute or stars
            const ratingText = ratingDiv.textContent;
            // This will be handled by server-side rendering
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>