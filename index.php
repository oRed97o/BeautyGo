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

// Get user location if available
$userLat = $_SESSION['user_latitude'] ?? null;
$userLon = $_SESSION['user_longitude'] ?? null;

// Get categorized businesses
$topRatedBusinesses = getTopRatedBusinesses(4);
$newBusinesses = getNewBusinesses(4);
$popularBusinesses = getPopularBusinesses(4);

// Collect IDs of featured businesses to exclude from "All Businesses"
$featuredIds = [];
foreach ($topRatedBusinesses as $b) $featuredIds[] = $b['business_id'];
foreach ($newBusinesses as $b) $featuredIds[] = $b['business_id'];
foreach ($popularBusinesses as $b) $featuredIds[] = $b['business_id'];
$featuredIds = array_unique($featuredIds);

// Get remaining businesses
$allBusinesses = getAllBusinesses();

// Get category counts
$allBiz = getAllBusinesses();
$categoryCounts = [
    'Hair Salon' => 0,
    'Spa & Wellness' => 0,
    'Barbershop' => 0,
    'Nail Salon' => 0,
    'Beauty Clinic' => 0
];
foreach ($allBiz as $biz) {
    if (isset($categoryCounts[$biz['business_type']])) {
        $categoryCounts[$biz['business_type']]++;
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/index.css">

<main>
    <!-- Hero Carousel -->
    <div class="hero-carousel-wrapper">
        <div class="carousel-slide active">
            <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e" alt="Beauty Services">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Discover Your Beauty</h1>
                    <p>Premium beauty services in Nasugbu, Batangas</p>
                    <a href="#featured-section" class="carousel-btn">Explore Services</a>
                </div>
            </div>
        </div>

        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874" alt="Spa & Wellness">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Relax & Rejuvenate</h1>
                    <p>Experience world-class spa treatments</p>
                    <a href="#featured-section" class="carousel-btn">Book Now</a>
                </div>
            </div>
        </div>

        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1503951914875-452162b0f3f1" alt="Professional Care">
            <div class="carousel-overlay">
                <div class="carousel-content">
                    <h1>Expert Beauty Care</h1>
                    <p>Transform your look with our professionals</p>
                    <a href="#featured-section" class="carousel-btn">Get Started</a>
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

    <!-- Search Section -->
    <section class="hero-section-new">
        <div class="container">
            <div class="search-bar-wrapper">
                <div class="search-bar-container">
                    <div class="search-input-group">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search salons, services..." onkeyup="handleSearchInput(event)">
                    </div>
                    <div class="search-divider"></div>
                    <div class="search-category-group">
                        <select class="search-category-select" id="typeFilter" onchange="handleCategoryDropdown()">
                            <option value="">All Categories</option>
                            <option value="hair salon">Hair Salon</option>
                            <option value="spa & wellness">Spa & Wellness</option>
                            <option value="barbershop">Barbershop</option>
                            <option value="nail salon">Nail Salon</option>
                            <option value="beauty clinic">Beauty Clinic</option>
                        </select>
                        <i class="bi bi-chevron-down category-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Browse by Category -->
    <div class="container my-4">
        <div class="section-header">
            <h2 class="section-title">Browse by Category</h2>
            <p class="section-subtitle">Find the perfect service for you</p>
        </div>
        
        <div class="category-grid">
            <div class="category-card" onclick="filterByCategory('hair salon', this)">
                <div class="category-icon">
                    <img src="resources/icon_salon.png" alt="Hair Salon" class="category-icon-img">
                </div>
                <div class="category-name">Hair Salon</div>
                <div class="category-count"><?php echo $categoryCounts['Hair Salon']; ?> businesses</div>
            </div>
            
            <div class="category-card" onclick="filterByCategory('spa & wellness', this)">
                <div class="category-icon">
                    <img src="resources/icon_spa.png" alt="Spa & Wellness" class="category-icon-img">
                </div>
                <div class="category-name">Spa & Wellness</div>
                <div class="category-count"><?php echo $categoryCounts['Spa & Wellness']; ?> businesses</div>
            </div>
            
            <div class="category-card" onclick="filterByCategory('barbershop', this)">
                <div class="category-icon">
                    <img src="resources/icon_barbers.png" alt="Barbershop" class="category-icon-img">
                </div>
                <div class="category-name">Barbershop</div>
                <div class="category-count"><?php echo $categoryCounts['Barbershop']; ?> businesses</div>
            </div>
            
            <div class="category-card" onclick="filterByCategory('nail salon', this)">
                <div class="category-icon">
                    <img src="resources/icon_nails.png" alt="Nail Salon" class="category-icon-img">
                </div>
                <div class="category-name">Nail Salon</div>
                <div class="category-count"><?php echo $categoryCounts['Nail Salon']; ?> businesses</div>
            </div>
            
            <div class="category-card" onclick="filterByCategory('beauty clinic', this)">
                <div class="category-icon">
                    <img src="resources/icon_clinic.png" alt="Beauty Clinic" class="category-icon-img">
                </div>
                <div class="category-name">Beauty Clinic</div>
                <div class="category-count"><?php echo $categoryCounts['Beauty Clinic']; ?> businesses</div>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- Top Rated Section -->
    <?php if (!empty($topRatedBusinesses)): ?>
    <section class="featured-section" id="featured-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="bi bi-award-fill"></i> Top Rated</h2>
                <p class="section-subtitle">Excellence in beauty services with 4.5+ star ratings</p>
            </div>
            
            <div class="business-row collapsed" id="topRatedRow">
                <?php foreach ($topRatedBusinesses as $business): ?>
                    <?php echo renderBusinessCard($business, 'top-rated'); ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($topRatedBusinesses) > 4): ?>
            <div class="show-more-container">
                <button class="show-more-btn" onclick="toggleSection('topRatedRow', this)">
                    <span class="btn-text">Show More</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular This Month Section -->
    <?php if (!empty($popularBusinesses)): ?>
    <div class="container businesses-section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-fire"></i> Popular This Month</h2>
            <p class="section-subtitle">Most booked services in the last 30 days</p>
        </div>
        
        <div class="business-row collapsed" id="popularRow">
            <?php foreach ($popularBusinesses as $business): ?>
                <?php echo renderBusinessCard($business, 'popular'); ?>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($popularBusinesses) > 4): ?>
        <div class="show-more-container">
            <button class="show-more-btn" onclick="toggleSection('popularRow', this)">
                <span class="btn-text">Show More</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="section-divider"></div>
    <?php endif; ?>

    <!-- New Businesses Section -->
    <?php if (!empty($newBusinesses)): ?>
    <div class="container businesses-section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-star-fill"></i> New to BeautyGo</h2>
            <p class="section-subtitle">Welcome our newest beauty partners</p>
        </div>
        
        <div class="business-row collapsed" id="newRow">
            <?php foreach ($newBusinesses as $business): ?>
                <?php echo renderBusinessCard($business, 'new'); ?>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($newBusinesses) > 4): ?>
        <div class="show-more-container">
            <button class="show-more-btn" onclick="toggleSection('newRow', this)">
                <span class="btn-text">Show More</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="section-divider"></div>
    <?php endif; ?>

    <!-- All Businesses Section -->
    <div class="container businesses-section" id="all-businesses-section">
        <div class="section-header">
            <h2 class="section-title">All Beauty Services</h2>
            <p class="section-subtitle">Explore all available services in Nasugbu</p>
        </div>
        
        <?php if (empty($allBusinesses) && empty($topRatedBusinesses) && empty($newBusinesses) && empty($popularBusinesses)): ?>
            <div class="empty-state">
                <i class="bi bi-shop"></i>
                <h4>No Businesses Found</h4>
                <p>Be the first to register your beauty business!</p>
                <a href="register-business.php" class="btn btn-primary">Register Business</a>
            </div>
        <?php else: ?>
            <div class="business-grid-paginated" id="allBusinessesGrid">
                <?php foreach ($allBusinesses as $business): ?>
                    <?php echo renderBusinessCard($business, 'regular'); ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($allBusinesses) > 20): ?>
            <div class="load-more-container" id="loadMoreContainer">
                <button class="load-more-btn" id="loadMoreBtn" onclick="loadMoreBusinesses()">
                    <span id="loadMoreText">Load More</span>
                    <i class="bi bi-arrow-down-circle"></i>
                </button>
                <div class="businesses-count">
                    Showing <span id="currentCount">20</span> of <span id="totalCount"><?php echo count($allBusinesses); ?></span> businesses
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-center" id="noResultsMessage" style="display: none;">
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h4>No Results Found</h4>
                    <p>Try adjusting your search or filters</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Back to Top Button - Add before closing </main> tag -->
    <button id="backToTop" class="back-to-top-btn" onclick="scrollToTop()">
        <i class="bi bi-arrow-up-circle-fill"></i>
        <span class="back-to-top-text">Top</span>
    </button>
</main>

<?php
// Updated renderBusinessCard function - replace the existing one in index.php
function renderBusinessCard($business, $type = 'regular') {
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
    
    // Only check favorites if user is a CUSTOMER (not business)
    $isFavorited = false;
    $showFavoriteButton = false;
    
    if (isCustomerLoggedIn() && !isBusinessLoggedIn()) {
        $showFavoriteButton = true;
        $isFavorited = isFavorite($_SESSION['customer_id'], $business['business_id']);
    }
    
    // Determine badge based on type
    $badge = '';
    if ($type === 'top-rated' && $avgRating >= 4.5) {
        $badge = '<span class="airbnb-badge"><i class="bi bi-award-fill"></i> Top Rated</span>';
    } elseif ($type === 'popular') {
        $badge = '<span class="popular-badge"><i class="bi bi-fire"></i> Popular</span>';
    } elseif ($type === 'new') {
        $badge = '<span class="new-badge"><i class="bi bi-star-fill"></i> New</span>';
    }
    
    $distanceBadge = '';
    if (isset($business['distance']) && $business['distance'] < 999) {
        $distanceBadge = '<span class="distance-badge"><i class="bi bi-geo-alt-fill"></i> ' . $business['distance'] . ' km</span>';
    }
    
    ob_start();
    ?>
    <div class="business-card" 
        data-type="<?php echo strtolower($business['business_type'] ?? 'salon'); ?>"
        data-name="<?php echo htmlspecialchars($business['business_name']); ?>"
        data-business-id="<?php echo $business['business_id']; ?>">

        <div class="business-card-img" onclick="window.location.href='business-detail.php?id=<?php echo $business['business_id']; ?>'">
            <img src="<?php echo $businessImage; ?>" 
                alt="<?php echo htmlspecialchars($business['business_name']); ?>">

            <?php echo $badge; ?>
            <?php echo $distanceBadge; ?>
            
            <!-- Favorite Heart Button - ONLY SHOW FOR CUSTOMERS -->
            <?php if ($showFavoriteButton): ?>
                <button class="airbnb-favorite-btn favorite-btn-<?php echo $business['business_id']; ?> <?php echo $isFavorited ? 'favorited' : ''; ?>" 
                        data-business-id="<?php echo $business['business_id']; ?>">
                    <i class="bi bi-heart<?php echo $isFavorited ? '-fill' : ''; ?>"></i>
                </button>
            <?php elseif (!isCustomerLoggedIn() && !isBusinessLoggedIn()): ?>
                <!-- Show for non-logged users but use custom toast -->
                <button class="airbnb-favorite-btn" 
                        onclick="handleNonLoggedInFavorite(event)">
                    <i class="bi bi-heart"></i>
                </button>
            <?php endif; ?>
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
    <?php
    return ob_get_clean();
}
?>

<script>
// Carousel functionality
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
        document.querySelector('#featured-section').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
});

startAutoSlide();

// Debounce timer for filtering
let filterTimeout = null;

// SMOOTH Filter by category
function filterByCategory(category, clickedCard) {
    const typeFilter = document.getElementById('typeFilter');
    const categoryCards = document.querySelectorAll('.category-card');
    
    // Check if clicking the same active category
    const isSameCategory = typeFilter.value === category;
    
    // Update filter value immediately
    typeFilter.value = isSameCategory ? '' : category;
    
    // Use requestAnimationFrame for smooth DOM updates
    requestAnimationFrame(() => {
        // Remove ALL active states first
        categoryCards.forEach(card => {
            card.classList.remove('active');
            card.classList.remove('force-inactive');
        });
        
        // If turning off the filter, add force-inactive to prevent hover while still hovering
        if (isSameCategory && clickedCard) {
            clickedCard.classList.add('force-inactive');
            
            // Remove force-inactive when mouse leaves
            const removeForceInactive = function() {
                clickedCard.classList.remove('force-inactive');
                clickedCard.removeEventListener('mouseleave', removeForceInactive);
            };
            clickedCard.addEventListener('mouseleave', removeForceInactive);
        }
        
        // Add active to clicked card only if not turning off
        if (!isSameCategory && clickedCard) {
            clickedCard.classList.add('active');
        }
        
        // Debounce the filtering to avoid lag
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            filterBusinesses();
        }, 50);
    });
}

// Back to Top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide back to top button on scroll
window.addEventListener('scroll', function() {
    const backToTopBtn = document.getElementById('backToTopBtn');
    
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

// OPTIMIZED filterBusinesses function with better performance
function filterBusinesses() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    const businessCards = document.querySelectorAll('#allBusinessesGrid .business-card');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    let visibleCount = 0;
    let totalMatches = 0;
    
    // Batch DOM reads and writes
    const updates = [];
    
    businessCards.forEach((card, index) => {
        const businessName = card.getAttribute('data-name').toLowerCase();
        const businessType = card.getAttribute('data-type').toLowerCase();
        
        const matchesSearch = businessName.includes(searchTerm);
        const matchesType = typeFilter === '' || businessType === typeFilter;
        
        if (matchesSearch && matchesType) {
            totalMatches++;
            const grid = document.getElementById('allBusinessesGrid');
            const shouldShow = grid.classList.contains('show-all') || 
                (grid.classList.contains('show-more-60') && index < 60) ||
                (grid.classList.contains('show-more-40') && index < 40) ||
                index < 20;
            
            if (shouldShow) {
                visibleCount++;
                updates.push({ card, display: 'block' });
            } else {
                updates.push({ card, display: 'none' });
            }
        } else {
            updates.push({ card, display: 'none' });
        }
    });
    
    // Apply all updates at once
    requestAnimationFrame(() => {
        updates.forEach(({ card, display }) => {
            card.style.display = display;
        });
        
        // Update UI elements
        if (searchTerm || typeFilter) {
            if (loadMoreContainer) loadMoreContainer.style.display = 'none';
        } else {
            if (loadMoreContainer && typeof totalBusinesses !== 'undefined' && totalBusinesses > 20) {
                loadMoreContainer.style.display = 'block';
            }
        }
        
        const noResults = document.getElementById('noResultsMessage');
        const allGrid = document.getElementById('allBusinessesGrid');
        
        if (totalMatches === 0) {
            if (noResults) noResults.style.display = 'block';
            if (allGrid) allGrid.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            if (allGrid) allGrid.style.display = 'grid';
        }
        
        // Filter other sections
        filterOtherSections(searchTerm, typeFilter);
    });
}

// Request user location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        fetch('backend/api/update-location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            })
        }).catch(err => console.log('Location update failed:', err));
    });
}

// Toggle Show More/Less
function toggleSection(rowId, button) {
    const row = document.getElementById(rowId);
    const btnText = button.querySelector('.btn-text');
    const icon = button.querySelector('i');
    
    if (row.classList.contains('collapsed')) {
        row.classList.remove('collapsed');
        btnText.textContent = 'Show Less';
        button.classList.add('expanded');
    } else {
        row.classList.add('collapsed');
        btnText.textContent = 'Show More';
        button.classList.remove('expanded');
        
        // Scroll back to section header
        row.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Load More Businesses functionality
let currentLoadLevel = 0;
const totalBusinesses = <?php echo count($allBusinesses); ?>;

function loadMoreBusinesses() {
    const grid = document.getElementById('allBusinessesGrid');
    const btn = document.getElementById('loadMoreBtn');
    const loadMoreText = document.getElementById('loadMoreText');
    const currentCountSpan = document.getElementById('currentCount');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    currentLoadLevel++;
    
    if (currentLoadLevel === 1) {
        // Show next 20 (21-40)
        grid.classList.add('show-more-40');
        currentCountSpan.textContent = Math.min(40, totalBusinesses);
    } else if (currentLoadLevel === 2) {
        // Show next 20 (41-60)
        grid.classList.add('show-more-60');
        currentCountSpan.textContent = Math.min(60, totalBusinesses);
    } else {
        // Show all remaining
        grid.classList.add('show-all');
        currentCountSpan.textContent = totalBusinesses;
        loadMoreContainer.style.display = 'none';
    }
    
    // Update button text
    const remaining = totalBusinesses - (currentLoadLevel * 20 + 20);
    if (remaining > 20) {
        loadMoreText.textContent = 'Load More';
    } else if (remaining > 0) {
        loadMoreText.textContent = `Load ${remaining} More`;
    }
    
    // Smooth scroll to newly loaded content
    setTimeout(() => {
        const newlyVisible = grid.querySelectorAll('.business-card:not([style*="display: none"])');
        if (newlyVisible.length > 20 * currentLoadLevel) {
            newlyVisible[20 * currentLoadLevel].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }, 100);
}

// Update filter function to respect pagination and show empty states for each section
function filterBusinesses() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    const businessCards = document.querySelectorAll('#allBusinessesGrid .business-card');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    let visibleCount = 0;
    let totalMatches = 0;
    
    businessCards.forEach((card, index) => {
        const businessName = card.getAttribute('data-name').toLowerCase();
        const businessType = card.getAttribute('data-type').toLowerCase();
        
        const matchesSearch = businessName.includes(searchTerm);
        const matchesType = typeFilter === '' || businessType === typeFilter;
        
        if (matchesSearch && matchesType) {
            totalMatches++;
            // Check if this card should be visible based on current load level
            const grid = document.getElementById('allBusinessesGrid');
            if (grid.classList.contains('show-all') || 
                (grid.classList.contains('show-more-60') && index < 60) ||
                (grid.classList.contains('show-more-40') && index < 40) ||
                index < 20) {
                card.style.display = 'block';
                visibleCount++;
            }
        } else {
            card.style.display = 'none';
        }
    });
    
    // Hide load more button when filtering
    if (searchTerm || typeFilter) {
        if (loadMoreContainer) loadMoreContainer.style.display = 'none';
    } else {
        if (loadMoreContainer && totalBusinesses > 20) {
            loadMoreContainer.style.display = 'block';
        }
    }
    
    // Show/hide no results message for All Businesses section
    const noResults = document.getElementById('noResultsMessage');
    const allGrid = document.getElementById('allBusinessesGrid');
    if (totalMatches === 0) {
        if (noResults) noResults.style.display = 'block';
        if (allGrid) allGrid.style.display = 'none';
    } else {
        if (noResults) noResults.style.display = 'none';
        if (allGrid) allGrid.style.display = 'grid';
    }
    
    // Filter and show empty states for OTHER sections (Top Rated, Popular, New)
    const sections = [
        { id: 'topRatedRow', name: 'Top Rated' },
        { id: 'popularRow', name: 'Popular This Month' },
        { id: 'newRow', name: 'New to BeautyGo' }
    ];
    
    sections.forEach(section => {
        const sectionElement = document.getElementById(section.id);
        if (!sectionElement) return;
        
        const sectionCards = sectionElement.querySelectorAll('.business-card');
        let sectionVisibleCount = 0;
        
        sectionCards.forEach(card => {
            const businessName = card.getAttribute('data-name').toLowerCase();
            const businessType = card.getAttribute('data-type').toLowerCase();
            
            const matchesSearch = businessName.includes(searchTerm);
            const matchesType = typeFilter === '' || businessType === typeFilter;
            
            if (matchesSearch && matchesType) {
                card.style.display = 'block';
                sectionVisibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Get or create empty state for this section
        let emptyState = sectionElement.querySelector('.section-empty-state');
        
        if (sectionVisibleCount === 0) {
            // Show empty state
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'section-empty-state';
                emptyState.innerHTML = `
                    <div class="empty-state-mini">
                        <i class="bi bi-search"></i>
                        <p>No ${section.name.toLowerCase()} businesses match your filter</p>
                    </div>
                `;
                sectionElement.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
        } else {
            // Hide empty state
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    });
}

// Initialize favorite buttons when page loads - ONLY FOR CUSTOMERS
window.addEventListener('load', function() {
    const favoriteButtons = document.querySelectorAll('.airbnb-favorite-btn');
    
    favoriteButtons.forEach(function(button) {
        // Skip if button already has login check (for non-logged users or business users)
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
            const badge = document.createElement('span');
            badge.className = 'favorites-badge';
            badge.textContent = newCount;
            favoritesHeartLink.parentElement.appendChild(badge);
        }
    } else {
        if (favoriteBadge) {
            favoriteBadge.remove();
        }
    }
}

// Update your toggleFavorite function
async function toggleFavorite(businessId, button) {
    // Disable ALL buttons for this business during request
    const allButtons = document.querySelectorAll(`.favorite-btn-${businessId}`);
    allButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    
    try {
        const response = await fetch('backend/ajax/ajax-favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle&business_id=' + businessId
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (data.success) {
            // Update ALL buttons for this business across all sections
            allButtons.forEach(btn => {
                const icon = btn.querySelector('i');
                
                if (data.is_favorite) {
                    icon.className = 'bi bi-heart-fill';
                    btn.classList.add('favorited');
                } else {
                    icon.className = 'bi bi-heart';
                    btn.classList.remove('favorited');
                }
            });
            
            // Show toast notification
            if (data.is_favorite) {
                showToast('Added to Favorites', 'Added to your collection successfully!', 'success');
                updateFavoritesCount(1);
            } else {
                showToast('Removed from Favorites', 'Item removed from your collection', 'info');
                updateFavoritesCount(-1);
            }
        } else {
            // Handle different error cases
            if (data.message === 'Please login first') {
                showToast('Login Required', 'Please login to add favorites', 'warning');
                setTimeout(() => {
                    window.location.href = data.redirect || 'login.php';
                }, 2000);
            } else if (data.message === 'Business accounts cannot favorite') {
                showToast('Not Allowed', 'Business accounts cannot add favorites', 'warning');
            } else {
                showToast('Error', data.message || 'Failed to update favorite', 'error');
            }
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        showToast('Connection Error', 'Please try again later', 'error');
    } finally {
        // Re-enable ALL buttons for this business
        allButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }
}

// Create toast container if it doesn't exist
if (!document.getElementById('toastContainer')) {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
}

// Custom Toast Notification Function
function showToast(title, message, type = 'success') {
    const container = document.getElementById('toastContainer');
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    
    // Icon mapping
    const icons = {
        success: 'bi-heart-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-heart'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="bi ${icons[type]}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)">
            <i class="bi bi-x"></i>
        </button>
        <div class="toast-progress"></div>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto remove after 3 seconds
    const autoRemoveTimer = setTimeout(() => {
        removeToast(toast);
    }, 3000);
    
    // Click to dismiss
    toast.addEventListener('click', function(e) {
        if (!e.target.closest('.toast-close')) {
            clearTimeout(autoRemoveTimer);
            removeToast(toast);
        }
    });
    
    // Store timer on element for cleanup
    toast.autoRemoveTimer = autoRemoveTimer;
}

function closeToast(button) {
    const toast = button.closest('.custom-toast');
    clearTimeout(toast.autoRemoveTimer);
    removeToast(toast);
}

function removeToast(toast) {
    toast.classList.remove('show');
    toast.classList.add('hide');
    
    setTimeout(() => {
        toast.remove();
    }, 400);
}

// Helper function to get category from card
function getCategoryFromCard(card) {
    const categoryName = card.querySelector('.category-name').textContent.trim().toLowerCase();
    
    const categoryMap = {
        'hair salon': 'hair salon',
        'spa & wellness': 'spa & wellness',
        'barbershop': 'barbershop',
        'nail salon': 'nail salon',
        'beauty clinic': 'beauty clinic'
    };
    
    return categoryMap[categoryName] || '';
}



// IMPROVED: Clear active states when dropdown is manually changed
document.getElementById('typeFilter').addEventListener('change', function() {
    const categoryCards = document.querySelectorAll('.category-card');
    const selectedValue = this.value.toLowerCase();
    
    // First remove ALL active states
    categoryCards.forEach(card => {
        card.classList.remove('active');
    });
    
    // Only add active if a category is selected (not empty)
    if (selectedValue !== '') {
        categoryCards.forEach(card => {
            const cardCategory = getCategoryFromCard(card);
            if (cardCategory === selectedValue) {
                card.classList.add('active');
            }
        });
    }
});

// OPTIMIZED: Clear active states when dropdown is manually changed
document.addEventListener('DOMContentLoaded', function() {
    const typeFilter = document.getElementById('typeFilter');
    const searchInput = document.getElementById('searchInput');
    const categoryArrow = document.querySelector('.category-arrow');
    
    // Responsive dropdown arrow animation with rapid click support
    if (typeFilter && categoryArrow) {
    let isDropdownOpen = false;
    
    // Toggle arrow on each click
    typeFilter.addEventListener('mousedown', function(e) {
        isDropdownOpen = !isDropdownOpen;
        updateArrowState(isDropdownOpen);
    });
    
    // Keep arrow up after making a selection
    typeFilter.addEventListener('change', function() {
        isDropdownOpen = true;
        updateArrowState(true);
        
        // Close dropdown after selection
        setTimeout(() => {
            isDropdownOpen = false;
            updateArrowState(false);
        }, 200);
    });
    
    // Reset arrow when clicking outside
    document.addEventListener('click', function(e) {
        if (!typeFilter.contains(e.target) && !categoryArrow.contains(e.target)) {
            if (isDropdownOpen) {
                isDropdownOpen = false;
                updateArrowState(false);
            }
        }
    });
    
    // Handle blur event (when focus is lost)
    typeFilter.addEventListener('blur', function() {
        setTimeout(() => {
            if (isDropdownOpen) {
                isDropdownOpen = false;
                updateArrowState(false);
            }
        }, 150);
    });
    
   // Function to update arrow appearance instantly
        function updateArrowState(isOpen) {
        if (isOpen) {
            categoryArrow.style.transform = 'translateY(-50%) rotateZ(180deg)';
            categoryArrow.style.color = 'var(--color-rose)';
        } else {
            categoryArrow.style.transform = 'translateY(-50%) rotateZ(0deg)';
            categoryArrow.style.color = '#6B7280';
        }
    }
}
    
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            const categoryCards = document.querySelectorAll('.category-card');
            const selectedValue = this.value.toLowerCase();
            
            requestAnimationFrame(() => {
                // Remove ALL active states
                categoryCards.forEach(card => {
                    card.classList.remove('active');
                });
                
                // Add active only if value selected
                if (selectedValue !== '') {
                    categoryCards.forEach(card => {
                        const cardCategory = getCategoryFromCard(card);
                        if (cardCategory === selectedValue) {
                            card.classList.add('active');
                        }
                    });
                }
                
                // Debounced filter
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    filterBusinesses();
                }, 50);
            });
        });
    }
    
    // OPTIMIZED: Clear active states when search input is used
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                const categoryCards = document.querySelectorAll('.category-card');
                
                requestAnimationFrame(() => {
                    document.getElementById('typeFilter').value = '';
                    categoryCards.forEach(card => {
                        card.classList.remove('active');
                    });
                    
                    // Debounced filter
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(() => {
                        filterBusinesses();
                    }, 150); // Slightly longer for search typing
                });
            }
        });
    }
});

// Helper to filter other sections
function filterOtherSections(searchTerm, typeFilter) {
    const sections = [
        { id: 'topRatedRow', name: 'Top Rated' },
        { id: 'popularRow', name: 'Popular This Month' },
        { id: 'newRow', name: 'New to BeautyGo' }
    ];
    
    sections.forEach(section => {
        const sectionElement = document.getElementById(section.id);
        if (!sectionElement) return;
        
        const sectionCards = sectionElement.querySelectorAll('.business-card');
        let sectionVisibleCount = 0;
        
        sectionCards.forEach(card => {
            const businessName = card.getAttribute('data-name').toLowerCase();
            const businessType = card.getAttribute('data-type').toLowerCase();
            
            const matchesSearch = businessName.includes(searchTerm);
            const matchesType = typeFilter === '' || businessType === typeFilter;
            
            if (matchesSearch && matchesType) {
                card.style.display = 'block';
                sectionVisibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        let emptyState = sectionElement.querySelector('.section-empty-state');
        
        if (sectionVisibleCount === 0) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'section-empty-state';
                emptyState.innerHTML = `
                    <div class="empty-state-mini">
                        <i class="bi bi-search"></i>
                        <p>No ${section.name.toLowerCase()} businesses match your filter</p>
                    </div>
                `;
                sectionElement.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
        } else {
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    });
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
                        <i class="bi bi-heart"></i>
                    </div>
                    <h2 class="login-modal-title">Login Required</h2>
                    <p class="login-modal-message">
                        Please login as a customer to add favorites and book services
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
                closeLoginModal();
            }
        });
    }
});

// Show login modal
function showLoginModal() {
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

// Handle favorite click for non-logged-in users (UPDATED)
function handleNonLoggedInFavorite(event) {
    event.stopPropagation();
    event.preventDefault();
    showLoginModal(); // Show modal instead of toast
}

// Allow clicking toast to dismiss
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toast-notification')) {
        e.target.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => e.target.remove(), 300);
    }
});

// Back to Top Button Functionality
const backToTopBtn = document.getElementById('backToTop');

// Show/hide button on scroll
window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
// Handle search input
function handleSearchInput(event) {
    if (event.key === 'Enter') {
        const searchValue = document.getElementById('searchInput').value.trim();
        if (searchValue) {
            window.location.href = 'search-results.php?search=' + encodeURIComponent(searchValue);
        }
    }
}


</script>



<?php include 'includes/footer.php'; ?>