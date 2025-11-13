<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'BeautyGo - Beauty Services in Nasugbu, Batangas';

// Get user location if available (for distance calculation)
$userLat = $_SESSION['user_latitude'] ?? null;
$userLon = $_SESSION['user_longitude'] ?? null;

// Get all businesses with distance calculation
$businesses = getBusinessesWithDistance($userLat, $userLon);

include 'includes/header.php';
?>

<style>
    :root {
        --brand-burgundy: #850E35;
        --brand-rose: #EE6983;
        --brand-pink: #FFC4C4;
        --brand-cream: #FFF5E4;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background-color: var(--brand-cream);
    }

    /* Hero Carousel Styles */
    .hero-carousel-wrapper {
        position: relative;
        width: 100%;
        height: 600px;
        overflow: hidden;
    }

    .carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease-in-out;
    }

    .carousel-slide.active {
        opacity: 1;
    }

    .carousel-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .carousel-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(133, 14, 53, 0.6));
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-content {
        text-align: center;
        color: white;
        z-index: 10;
        padding: 2rem;
        max-width: 800px;
    }

    .carousel-content h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .carousel-content p {
        font-size: 1.5rem;
        margin-bottom: 2rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }

    .carousel-btn {
        background-color: var(--brand-burgundy);
        color: white;
        padding: 1rem 2.5rem;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .carousel-btn:hover {
        background-color: var(--brand-rose);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(238, 105, 131, 0.4);
        color: white;
    }

    .carousel-nav {
        position: absolute;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 1rem;
        z-index: 20;
    }

    .carousel-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid white;
    }

    .carousel-dot.active {
        background-color: white;
        transform: scale(1.2);
    }

    .carousel-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-arrow:hover {
        background-color: rgba(255, 255, 255, 0.5);
    }

    .carousel-arrow.left {
        left: 2rem;
    }

    .carousel-arrow.right {
        right: 2rem;
    }

    /* Hero Section with Search */
    .hero-section-new {
        background: linear-gradient(135deg, var(--brand-burgundy) 0%, var(--brand-rose) 100%);
        padding: 4rem 0 3rem 0;
        color: white;
    }

    .hero-content {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .hero-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--brand-cream);
    }

    .hero-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: white;
    }

    /* Search Bar */
    .search-bar-wrapper {
        max-width: 800px;
        margin: 0 auto;
    }

    .search-bar-container {
        background: white;
        border-radius: 50px;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .search-input-group {
        flex: 1;
        display: flex;
        align-items: center;
        padding: 0 1rem;
    }

    .search-icon {
        color: var(--brand-burgundy);
        font-size: 1.2rem;
        margin-right: 0.75rem;
    }

    .search-input {
        border: none;
        outline: none;
        font-size: 1rem;
        width: 100%;
        color: #333;
    }

    .search-input::placeholder {
        color: #999;
    }

    .search-divider {
        width: 1px;
        height: 30px;
        background-color: #ddd;
    }

    .search-category-group {
        position: relative;
        padding: 0 1rem;
    }

    .search-category-select {
        border: none;
        outline: none;
        font-size: 1rem;
        padding-right: 2rem;
        background: transparent;
        cursor: pointer;
        color: #333;
        appearance: none;
    }

    .category-arrow {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--brand-burgundy);
    }

    /* Business Card Base */
    .business-card {
        position: relative;
        width: 280px;
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .business-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 24px rgba(0,0,0,0.15);
    }

    /* Image Section */
    .business-card-img {
        position: relative;
        height: 220px;
        overflow: hidden;
    }

    .business-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .business-card:hover .business-card-img img {
        transform: scale(1.05);
    }

    /* Badges */
    .airbnb-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--brand-burgundy);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .distance-badge {
        position: absolute;
        bottom: 12px;
        left: 12px;
        background: rgba(255, 255, 255, 0.95);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--brand-burgundy);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* Heart Button */
    .airbnb-favorite-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        width: 34px;
        height: 34px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s;
        z-index: 10;
    }

    .airbnb-favorite-btn:hover {
        background: var(--brand-pink);
        color: var(--brand-burgundy);
    }

    /* Card Content */
    .business-card-content {
        padding: 15px;
    }

    .business-name {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--brand-burgundy);
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .business-type-location {
        color: #717171;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    /* Rating */
    .airbnb-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #222;
        font-size: 0.9rem;
    }

    .airbnb-rating i {
        color: var(--brand-rose);
    }

    .rating-count {
        color: #717171;
        font-size: 0.85rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #717171;
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--brand-burgundy);
        margin-bottom: 1rem;
    }

    .business-grid {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 2rem;
        padding: 2rem 0;
    }

    @media (max-width: 768px) {
        .hero-carousel-wrapper {
            height: 500px;
        }

        .carousel-content h1 {
            font-size: 2rem;
        }

        .carousel-content p {
            font-size: 1.1rem;
        }

        .carousel-arrow {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .carousel-arrow.left {
            left: 1rem;
        }

        .carousel-arrow.right {
            right: 1rem;
        }

        .hero-title {
            font-size: 1.75rem;
        }

        .business-card {
            width: 100%;
            max-width: 400px;
        }

        .search-bar-container {
            flex-direction: column;
            border-radius: 20px;
        }

        .search-divider {
            width: 100%;
            height: 1px;
            margin: 0.5rem 0;
        }
    }
</style>

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

        <!-- Navigation Arrows -->
        <button class="carousel-arrow left" onclick="changeSlide(-1)">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="carousel-arrow right" onclick="changeSlide(1)">
            <i class="bi bi-chevron-right"></i>
        </button>

        <!-- Navigation Dots -->
        <div class="carousel-nav">
            <div class="carousel-dot active" onclick="goToSlide(0)"></div>
            <div class="carousel-dot" onclick="goToSlide(1)"></div>
            <div class="carousel-dot" onclick="goToSlide(2)"></div>
        </div>
    </div>

    <!-- Hero Section with Search -->
    <section class="hero-section-new">
        <div class="container">
            <!-- Search Bar -->
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
                    // Get business album/logo
                    $album = getBusinessAlbum($business['business_id']);
                    $businessImage = null;
                    
                    // Try to get logo from album
                    if ($album && !empty($album['logo'])) {
                        $businessImage = 'data:image/jpeg;base64,' . base64_encode($album['logo']);
                    }
                    
                    // Fallback to default images based on type
                    if (!$businessImage) {
                        $defaultImages = [
                            'salon' => 'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=600',
                            'spa' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=600',
                            'barbershop' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600',
                            'clinic' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600'
                        ];
                        $businessType = strtolower($business['business_type'] ?? 'salon');
                        $businessImage = $defaultImages[$businessType] ?? 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600';
                    }
                    
                    // Calculate average rating
                    $avgRating = calculateAverageRating($business['business_id']);
                    $reviews = getBusinessReviews($business['business_id']);
                    $reviewCount = count($reviews);
                    
                    // Get location info
                    $location = $business['city'] ?? 'Nasugbu';
                    if (!empty($business['business_address'])) {
                        $addressParts = explode(',', $business['business_address']);
                        $location = trim($addressParts[0]);
                    }
                    ?>
                    
                    <div class="business-card" 
                         data-type="<?php echo strtolower($business['business_type'] ?? 'salon'); ?>"
                         data-name="<?php echo htmlspecialchars($business['business_name']); ?>"
                         onclick="window.location.href='business-detail.php?id=<?php echo $business['business_id']; ?>'">

                        <div class="business-card-img">
                            <img src="<?php echo $businessImage; ?>" 
                                 alt="<?php echo htmlspecialchars($business['business_name']); ?>">

                            <!-- Top Rated Badge -->
                            <?php if ($avgRating >= 4.5): ?>
                                <span class="airbnb-badge">
                                    <i class="bi bi-award-fill"></i> Top Rated
                                </span>
                            <?php endif; ?>

                            <!-- Distance Badge -->
                            <?php if (isset($business['distance']) && $business['distance'] < 999): ?>
                                <span class="distance-badge">
                                    <i class="bi bi-geo-alt-fill"></i> <?php echo $business['distance']; ?> km
                                </span>
                            <?php endif; ?>

                            <!-- Heart Favorite Button -->
                            <button class="airbnb-favorite-btn" 
                                    onclick="event.stopPropagation(); toggleFavorite(<?php echo $business['business_id']; ?>)">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>

                        <div class="business-card-content">
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

    // Smooth scroll for carousel buttons
    document.querySelectorAll('.carousel-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('#business-section').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    // Start automatic sliding
    startAutoSlide();

    // Filter businesses function - UPDATED
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
        
        // Show/hide empty state
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

    // Toggle favorite function
    function toggleFavorite(businessId) {
        // Check if user is logged in
        <?php if (isCustomerLoggedIn()): ?>
            // Add your favorite toggle logic here
            console.log('Toggle favorite for business:', businessId);
            
            // You can make an AJAX call to save favorites
            // Example:
            // fetch('api/toggle-favorite.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ business_id: businessId })
            // })
            // .then(response => response.json())
            // .then(data => {
            //     // Update UI
            //     event.target.classList.toggle('favorited');
            // });
        <?php else: ?>
            alert('Please login to add favorites');
            window.location.href = 'login.php';
        <?php endif; ?>
    }

    // Request user location for distance calculation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            // Send location to server via AJAX to update session
            fetch('api/update-location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            })
            .then(() => {
                // Optionally reload to show distances
                // location.reload();
            })
            .catch(err => console.log('Location update failed:', err));
        });
    }
</script>

<?php include 'includes/footer.php'; ?>