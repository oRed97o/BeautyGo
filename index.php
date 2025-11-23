<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_businesses.php';
require_once 'backend/function_customers.php';
require_once 'backend/function_reviews.php';
require_once 'backend/function_albums.php';
require_once 'backend/function_favorites.php';
require_once 'backend/function_notifications.php';

$pageTitle = 'BeautyGo - Beauty Services in Nasugbu, Batangas';

$userLat = $_SESSION['user_latitude'] ?? null;
$userLon = $_SESSION['user_longitude'] ?? null;

$businesses = getBusinessesWithDistance($userLat, $userLon);

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/index.css">

<main>
    <!-- Hero Carousel -->
    <div class="hero-carousel-wrapper">
        <!-- Slide 1 -->
        <div class="carousel-slide active">
            <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e" alt="Beauty Services">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Discover Your Beauty</h1>
                    <p>Premium beauty services in Nasugbu, Batangas</p>
                    <a href="#business-section" class="carousel-btn">Explore Services</a>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874" alt="Spa & Wellness">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Relax & Rejuvenate</h1>
                    <p>Experience world-class spa treatments</p>
                    <a href="#business-section" class="carousel-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1503951914875-452162b0f3f1" alt="Professional Care">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Expert Beauty Care</h1>
                    <p>Transform your look with our professionals</p>
                    <a href="#business-section" class="carousel-btn">Get Started</a>
                </div>
            </div>
        </div>

        <button class="carousel-arrow left" onclick="changeSlide(-1)">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="carousel-arrow right" onclick="changeSlide(1)">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div class="carousel-nav">
            <div class="carousel-dot active" onclick="goToSlide(0)"></div>
            <div class="carousel-dot" onclick="goToSlide(1)"></div>
            <div class="carousel-dot" onclick="goToSlide(2)"></div>
        </div>
    </div>

    <!-- Hero Section with Search -->
    <section class="hero-section-new">
        <div class="container">
            <div class="search-bar-wrapper">
                <div class="search-bar-container">
                    <div class="search-input-group">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search salons, services..." onkeyup="filterBusinesses()">
                    </div>
                    <div class="search-divider"></div>
                    <div class="search-category-group">
                        <select class="search-category-select" id="typeFilter" onchange="filterBusinesses()">
                            <option value="">All Categories</option>
                            <option value="salon">Salon</option>
                            <option value="spa">Spa</option>
                            <option value="barbershop">Barbershop</option>
                            <option value="clinic">Clinic</option>
                        </select>
                        <i class="bi bi-chevron-down category-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Business Listings Section -->
    <div class="container my-5 text-center" id="business-section">
        <h4 class="mb-4">Featured Beauty Services in Nasugbu</h4>

        <?php if (empty($businesses)): ?>
            <div class="empty-state">
                <i class="bi bi-shop"></i>
                <h4>No Businesses Found</h4>
                <p>Be the first to register your beauty business!</p>
                <a href="register-business.php" class="btn btn-primary">Register Business</a>
            </div>
        <?php else: ?>
            <div class="business-grid">
                <?php foreach ($businesses as $business): ?>
            <?php 
            $album = getBusinessAlbum($business['business_id']);
            $businessImage = null;
            
            if ($album && !empty($album['logo'])) {
                $businessImage = 'data:image/jpeg;base64,' . base64_encode($album['logo']);
            }
            
            if (!$businessImage) {
                $defaultImages = [
                    'hair salon' => 'resources/salon.png',
                    'spa & wellness' => 'resources/spa.png',
                    'barbershop' => 'resources/barbers.png',
                    'beauty clinic' => 'resources/clinic.png',
                    'nail salon' => 'resources/nails.png'
                ];
                $businessType = strtolower($business['business_type'] ?? 'salon');
                $businessImage = $defaultImages[$businessType] ?? 'resources/default.png';
            }
            
            $avgRating = calculateAverageRating($business['business_id']);
            $reviews = getBusinessReviews($business['business_id']);
            $reviewCount = count($reviews);
            
            $location = $business['city'] ?? 'Nasugbu';
            if (!empty($business['business_address'])) {
                $addressParts = explode(',', $business['business_address']);
                $location = trim($addressParts[0]);
            }
            
            $isFavorited = false;
            if (isCustomerLoggedIn()) {
                $isFavorited = isFavorite($_SESSION['customer_id'], $business['business_id']);
            }
            ?>

            <div class="business-card" 
                data-type="<?php echo strtolower($business['business_type'] ?? 'salon'); ?>"
                data-name="<?php echo htmlspecialchars($business['business_name']); ?>"
                data-business-id="<?php echo $business['business_id']; ?>">

                <div class="business-card-img" onclick="window.location.href='business-detail.php?id=<?php echo $business['business_id']; ?>'">
                    <img src="<?php echo $businessImage; ?>" 
                        alt="<?php echo htmlspecialchars($business['business_name']); ?>">

                    <?php if ($avgRating >= 4.5): ?>
                        <span class="airbnb-badge">
                            <i class="bi bi-award-fill"></i> Top Rated
                        </span>
                    <?php endif; ?>

                    <?php if (isset($business['distance']) && $business['distance'] < 999): ?>
                        <span class="distance-badge">
                            <i class="bi bi-geo-alt-fill"></i> <?php echo $business['distance']; ?> km
                        </span>
                    <?php endif; ?>

                    <!-- Heart Favorite Button -->
                    <button class="airbnb-favorite-btn favorite-btn-<?php echo $business['business_id']; ?> <?php echo $isFavorited ? 'favorited' : ''; ?>" 
                            data-business-id="<?php echo $business['business_id']; ?>"
                            <?php if (!isCustomerLoggedIn()): ?>
                            onclick="event.stopPropagation(); alert('Please login to add favorites'); window.location.href='login.php';"
                            <?php endif; ?>>
                        <i class="bi bi-heart<?php echo $isFavorited ? '-fill' : ''; ?>"></i>
                    </button>
                </div>

                <div class="business-card-content" onclick="window.location.href='business-detail.php?id=<?php echo $business['business_id']; ?>'">
                    <h5 class="business-name"><?php echo htmlspecialchars($business['business_name']); ?></h5>
                    
                    <p class="business-type-location">
                        <?php echo ucfirst($business['business_type'] ?? 'Salon'); ?> • <?php echo htmlspecialchars($location); ?>
                    </p>

                    <div class="airbnb-rating">
                        <i class="bi bi-star-fill"></i>
                        <strong><?php echo number_format($avgRating, 1); ?></strong>
                        <?php if ($reviewCount > 0): ?>
                            <span class="rating-count">(<?php echo $reviewCount; ?> <?php echo $reviewCount === 1 ? 'review' : 'reviews'; ?>)</span>
                        <?php else: ?>
                            <span class="rating-count">(New)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.carousel-dot');
let autoSlideInterval;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));

    if (index >= slides.length) {
        currentSlide = 0;
    } else if (index < 0) {
        currentSlide = slides.length - 1;
    } else {
        currentSlide = index;
    }

    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function changeSlide(direction) {
    showSlide(currentSlide + direction);
    resetAutoSlide();
}

function goToSlide(index) {
    showSlide(index);
    resetAutoSlide();
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
}

document.querySelectorAll('.carousel-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('#business-section').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});

startAutoSlide();

function filterBusinesses() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    const businessCards = document.querySelectorAll('.business-card');
    
    let visibleCount = 0;
    
    businessCards.forEach(card => {
        const businessName = card.getAttribute('data-name').toLowerCase();
        const businessType = card.getAttribute('data-type').toLowerCase();
        
        const matchesSearch = businessName.includes(searchTerm);
        const matchesType = typeFilter === '' || businessType === typeFilter;
        
        if (matchesSearch && matchesType) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const businessGrid = document.querySelector('.business-grid');
    const emptyState = document.querySelector('.empty-state');
    
    if (visibleCount === 0 && businessCards.length > 0) {
        if (businessGrid) businessGrid.style.display = 'none';
        if (!emptyState) {
            const section = document.querySelector('#business-section');
            const newEmptyState = document.createElement('div');
            newEmptyState.className = 'empty-state';
            newEmptyState.innerHTML = `
                <i class="bi bi-search"></i>
                <h4>No Results Found</h4>
                <p>Try adjusting your search or filters</p>
            `;
            section.appendChild(newEmptyState);
        }
    } else {
        if (businessGrid) businessGrid.style.display = 'flex';
        if (emptyState && businessCards.length > 0) {
            emptyState.remove();
        }
    }
}

// Initialize favorite buttons when page loads
window.addEventListener('load', function() {
    const favoriteButtons = document.querySelectorAll('.airbnb-favorite-btn');
    
    favoriteButtons.forEach(function(button) {
        // Skip if button already has login check (for non-logged users)
        if (button.hasAttribute('onclick')) {
            return;
        }
        
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            const businessId = this.getAttribute('data-business-id');
            toggleFavorite(businessId, this);
        });
    });
});

// Function to update favorites count in header
function updateFavoritesCount(change) {
    const favoriteBadge = document.querySelector('.favorites-badge');
    const favoritesHeartLink = document.querySelector('.favorites-heart');
    
    if (!favoritesHeartLink) {
        return; // User not logged in or no favorites section
    }
    
    let currentCount = 0;
    if (favoriteBadge) {
        currentCount = parseInt(favoriteBadge.textContent) || 0;
    }
    
    const newCount = Math.max(0, currentCount + change);
    
    if (newCount > 0) {
        if (favoriteBadge) {
            favoriteBadge.textContent = newCount;
        } else {
            // Create badge if it doesn't exist
            const badge = document.createElement('span');
            badge.className = 'favorites-badge';
            badge.textContent = newCount;
            favoritesHeartLink.parentElement.appendChild(badge);
        }
    } else {
        // Remove badge if count is 0
        if (favoriteBadge) {
            favoriteBadge.remove();
        }
    }
}

// Toggle favorite function with better error handling
async function toggleFavorite(businessId, button) {
    // Disable button during request
    button.disabled = true;
    button.style.opacity = '0.6';
    
    try {
        const response = await fetch('backend/ajax/ajax-favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle&business_id=' + businessId
        });
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        // Get response text first
        const text = await response.text();
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            const icon = button.querySelector('i');
            
            if (data.is_favorite) {
                icon.className = 'bi bi-heart-fill';
                button.classList.add('favorited');
                showToast('❤️ Added to favorites!', 'success');
                updateFavoritesCount(1); // Increment count
            } else {
                icon.className = 'bi bi-heart';
                button.classList.remove('favorited');
                showToast('💔 Removed from favorites', 'info');
                updateFavoritesCount(-1); // Decrement count
            }
        } else {
            if (data.message === 'Please login first') {
                showToast('⚠️ Please login to add favorites', 'warning');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                showToast('❌ ' + (data.message || 'Failed to update favorite'), 'error');
            }
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        showToast('❌ Connection error. Please try again.', 'error');
    } finally {
        // Re-enable button
        button.disabled = false;
        button.style.opacity = '1';
    }
}

// Improved toast notification function
function showToast(message, type = 'success') {
    // Remove any existing toasts
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.textContent = message;
    
    // Colors based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${colors[type] || colors.success};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS for toast animations if not already present
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-notification {
            cursor: pointer;
        }
        
        .toast-notification:hover {
            transform: scale(1.05);
            transition: transform 0.2s;
        }
    `;
    document.head.appendChild(style);
}

// Allow clicking toast to dismiss
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toast-notification')) {
        e.target.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => e.target.remove(), 300);
    }
});
</script>

<?php include 'includes/footer.php'; ?>