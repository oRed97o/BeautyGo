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

// Get search parameters
$searchQuery = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build page title
$pageTitle = 'Search Results';
if ($category) {
    $pageTitle = ucfirst($category) . ' - BeautyGo';
} elseif ($searchQuery) {
    $pageTitle = 'Search: ' . htmlspecialchars($searchQuery) . ' - BeautyGo';
}

// Get all businesses
$allBusinesses = getAllBusinesses();

// Filter businesses based on search and category
$filteredBusinesses = [];
foreach ($allBusinesses as $business) {
    $matchesSearch = true;
    $matchesCategory = true;
    
    // Check search query
    if ($searchQuery) {
        $businessName = strtolower($business['business_name']);
        $searchLower = strtolower($searchQuery);
        $matchesSearch = strpos($businessName, $searchLower) !== false;
    }
    
    // Check category
    if ($category) {
        $businessType = strtolower($business['business_type']);
        $categoryLower = strtolower($category);
        $matchesCategory = $businessType === $categoryLower;
    }
    
    if ($matchesSearch && $matchesCategory) {
        $filteredBusinesses[] = $business;
    }
}

$resultCount = count($filteredBusinesses);

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/index.css">

<style>
.container {
    padding-top: 20px;
    padding-bottom: 40px;
}

.search-again-section {
    background: #f9fafb;
    padding: 20px 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 20px;
    transition: gap 0.3s;
}

.back-link:hover {
    gap: 12px;
    color: #764ba2;
}

main {
    background: #fef3e8;
    min-height: calc(100vh - 80px);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 0;
    padding-top: 30px;
    margin-bottom: 50px;
}

.no-results {
    text-align: center;
    padding: 80px 20px;
}

.no-results i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
}

.no-results h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 10px;
}

.no-results p {
    color: #666;
    margin-bottom: 30px;
}
</style>

<main>
    <!-- Search Again Section -->
    <div class="search-again-section">
        <div class="container">
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>
                Back to Home
            </a>
            
            <div class="search-bar-wrapper">
                <div class="search-bar-container">
                    <div class="search-input-group">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="searchInput" 
                               placeholder="Search salons, services..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               onkeyup="handleSearchKeyup(event)">
                    </div>
                    <div class="search-divider"></div>
                    <div class="search-category-group">
                        <select class="search-category-select" id="categoryFilter" onchange="handleCategoryChange()">
                            <option value="">All Categories</option>
                            <option value="hair salon" <?php echo $category === 'hair salon' ? 'selected' : ''; ?>>Hair Salon</option>
                            <option value="spa & wellness" <?php echo $category === 'spa & wellness' ? 'selected' : ''; ?>>Spa & Wellness</option>
                            <option value="barbershop" <?php echo $category === 'barbershop' ? 'selected' : ''; ?>>Barbershop</option>
                            <option value="nail salon" <?php echo $category === 'nail salon' ? 'selected' : ''; ?>>Nail Salon</option>
                            <option value="beauty clinic" <?php echo $category === 'beauty clinic' ? 'selected' : ''; ?>>Beauty Clinic</option>
                        </select>
                        <i class="bi bi-chevron-down category-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="container">
        <?php if ($resultCount > 0): ?>
            <div class="results-grid">
                <?php foreach ($filteredBusinesses as $business): ?>
                    <?php echo renderBusinessCard($business, 'regular'); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="bi bi-inbox"></i>
                <h3>No Results Found</h3>
                <p>We couldn't find any businesses matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Reuse the renderBusinessCard function from index.php
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
    
    $isFavorited = false;
    $showFavoriteButton = false;
    
    if (isCustomerLoggedIn() && !isBusinessLoggedIn()) {
        $showFavoriteButton = true;
        $isFavorited = isFavorite($_SESSION['customer_id'], $business['business_id']);
    }
    
    ob_start();
    ?>
    <div class="business-card">
        <div class="business-card-img" onclick="window.location.href='business-detail.php?id=<?php echo $business['business_id']; ?>'">
            <img src="<?php echo $businessImage; ?>" 
                alt="<?php echo htmlspecialchars($business['business_name']); ?>">
            
            <?php if ($showFavoriteButton): ?>
                <button class="airbnb-favorite-btn favorite-btn-<?php echo $business['business_id']; ?> <?php echo $isFavorited ? 'favorited' : ''; ?>" 
                        data-business-id="<?php echo $business['business_id']; ?>">
                    <i class="bi bi-heart<?php echo $isFavorited ? '-fill' : ''; ?>"></i>
                </button>
            <?php elseif (!isCustomerLoggedIn() && !isBusinessLoggedIn()): ?>
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
// Handle search input - press Enter to search
function handleSearchKeyup(event) {
    if (event.key === 'Enter') {
        const searchValue = document.getElementById('searchInput').value.trim();
        const categoryValue = document.getElementById('categoryFilter').value;
        performSearch(searchValue, categoryValue);
    }
}

// Handle category dropdown change
function handleCategoryChange() {
    const searchValue = document.getElementById('searchInput').value.trim();
    const categoryValue = document.getElementById('categoryFilter').value;
    performSearch(searchValue, categoryValue);
}

// Perform search with current values
function performSearch(search, category) {
    let url = 'search-results.php?';
    const params = [];
    
    if (search) {
        params.push('search=' + encodeURIComponent(search));
    }
    if (category) {
        params.push('category=' + encodeURIComponent(category));
    }
    
    if (params.length > 0) {
        url += params.join('&');
    }
    
    window.location.href = url;
}

// Initialize favorite buttons
window.addEventListener('load', function() {
    const favoriteButtons = document.querySelectorAll('.airbnb-favorite-btn[data-business-id]');
    
    favoriteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            const businessId = this.getAttribute('data-business-id');
            toggleFavorite(businessId, this);
        });
    });
});

// Copy favorite toggle function from index.php
async function toggleFavorite(businessId, button) {
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
        
        const text = await response.text();
        const data = JSON.parse(text);
        
        if (data.success) {
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
            
            if (typeof showToast === 'function') {
                showToast(
                    data.is_favorite ? 'Added to Favorites' : 'Removed from Favorites',
                    data.is_favorite ? 'Added to your collection successfully!' : 'Item removed from your collection',
                    data.is_favorite ? 'success' : 'info'
                );
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('Error', 'Failed to update favorite', 'error');
        }
    } finally {
        allButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }
}

function handleNonLoggedInFavorite(event) {
    event.stopPropagation();
    event.preventDefault();
    alert('Please login to add favorites');
    window.location.href = 'login.php';
}
</script>

<?php include 'includes/footer.php'; ?>