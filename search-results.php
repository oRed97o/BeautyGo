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
/* Updated Search Results Styles - Replace the <style> section in search-results.php */

.container {
    padding-top: 0;
    padding-bottom: 40px;
}

.search-again-section {
    background: linear-gradient(135deg, var(--color-burgundy) 0%, var(--color-rose) 100%);
    padding: 3rem 0 2rem 0;
    border-bottom: none;
    margin-bottom: 0;
}

main {
    background: #fef3e8;
    min-height: calc(100vh - 80px);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
    margin-top: 0;
    padding-top: 30px;
    margin-bottom: 50px;
    justify-content: center;
}

.results-grid .business-card {
    width: 260px;
    justify-self: center;
}

.no-results {
    text-align: center;
    padding: 80px 20px;
}

.no-results i {
    font-size: 80px;
    color: var(--color-burgundy);
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

/* Results header styling to match index */
.results-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-top: 2rem;
}

.results-header .section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-burgundy);
    margin-bottom: 0.25rem;
}

.results-header .section-subtitle {
    font-size: 0.95rem;
    color: #717171;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .search-again-section {
        padding: 2rem 0 1.5rem 0;
    }
}
</style>

<main>
    <!-- Search Again Section -->
    <div class="search-again-section">
        <div class="container">
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

    <!-- Results Section -->
    <div class="container">
        <div class="results-header" style="<?php echo $resultCount === 0 ? 'display: none;' : ''; ?>">
            <h2 class="section-title">
                <?php if ($category): ?>
                    <?php echo ucfirst($category); ?>
                <?php elseif ($searchQuery): ?>
                    Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php else: ?>
                    All Beauty Services
                <?php endif; ?>
            </h2>
            <p class="section-subtitle">
                Found <?php echo $resultCount; ?> <?php echo $resultCount === 1 ? 'business' : 'businesses'; ?>
            </p>
        </div>
        
        <div class="results-grid" style="<?php echo $resultCount === 0 ? 'display: none;' : ''; ?>">
            <?php foreach ($filteredBusinesses as $business): ?>
                <?php echo renderBusinessCard($business, 'regular'); ?>
            <?php endforeach; ?>
        </div>

        <div class="no-results" style="<?php echo $resultCount > 0 ? 'display: none;' : 'display: block;'; ?>">
            <i class="bi bi-inbox"></i>
            <h3>We couldn't find any matches</h3>
            <p>
                <?php if ($searchQuery && $category): ?>
                    No <?php echo htmlspecialchars($category); ?> businesses match "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php elseif ($searchQuery): ?>
                    No businesses match "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php elseif ($category): ?>
                    No <?php echo htmlspecialchars($category); ?> businesses found
                <?php else: ?>
                    Try different keywords or browse all categories
                <?php endif; ?>
            </p>
            <a href="index.php" class="view-all-btn">
                <i class="bi bi-house-door btn-icon"></i>
                <span>Back to Home</span>
            </a>
        </div>
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
    <div class="business-card" 
        data-type="<?php echo strtolower($business['business_type'] ?? 'salon'); ?>"
        data-name="<?php echo htmlspecialchars($business['business_name']); ?>"
        data-business-id="<?php echo $business['business_id']; ?>">

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
// Updated JavaScript for search-results.php - Replace the existing <script> section

// Handle search input - press Enter to search OR live filter
function handleSearchKeyup(event) {
    // Live filter as user types (with debounce)
    clearTimeout(window.searchDebounce);
    window.searchDebounce = setTimeout(() => {
        filterResults();
    }, 300);
}

// Handle category dropdown change
function handleCategoryChange() {
    filterResults();
}

// Live filter results without page reload
function filterResults() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase().trim();
    const categoryValue = document.getElementById('categoryFilter').value.toLowerCase();
    
    const businessCards = document.querySelectorAll('.results-grid .business-card');
    const resultsHeader = document.querySelector('.results-header');
    const noResults = document.querySelector('.no-results');
    const resultsGrid = document.querySelector('.results-grid');
    
    let visibleCount = 0;
    
    businessCards.forEach(card => {
        const businessName = card.getAttribute('data-name').toLowerCase();
        const businessType = card.getAttribute('data-type').toLowerCase();
        
        const matchesSearch = !searchValue || businessName.includes(searchValue);
        const matchesCategory = !categoryValue || businessType === categoryValue;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update header
    const titleElement = resultsHeader.querySelector('.section-title');
    const subtitleElement = resultsHeader.querySelector('.section-subtitle');
    
    if (categoryValue) {
        titleElement.textContent = categoryValue.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    } else if (searchValue) {
        titleElement.textContent = `Search Results for "${searchValue}"`;
    } else {
        titleElement.textContent = 'All Beauty Services';
    }
    
    subtitleElement.textContent = `Found ${visibleCount} ${visibleCount === 1 ? 'business' : 'businesses'}`;
    
    // Show/hide no results message
    if (visibleCount === 0) {
        if (noResults) {
            noResults.style.display = 'block';
            const noResultsMessage = noResults.querySelector('p');
            if (noResultsMessage) {
                if (searchValue && categoryValue) {
                    noResultsMessage.textContent = `No ${categoryValue} businesses match "${searchValue}"`;
                } else if (searchValue) {
                    noResultsMessage.textContent = `No businesses match "${searchValue}"`;
                } else if (categoryValue) {
                    noResultsMessage.textContent = `No ${categoryValue} businesses found`;
                } else {
                    noResultsMessage.textContent = 'Try different keywords or browse all categories';
                }
            }
        }
        if (resultsGrid) resultsGrid.style.display = 'none';
        if (resultsHeader) resultsHeader.style.display = 'none';
    } else {
        if (noResults) noResults.style.display = 'none';
        if (resultsGrid) resultsGrid.style.display = 'grid';
        if (resultsHeader) resultsHeader.style.display = 'block';
    }
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

// Category arrow animation
document.addEventListener('DOMContentLoaded', function() {
    const categoryFilter = document.getElementById('categoryFilter');
    const categoryArrow = document.querySelector('.category-arrow');
    
    if (categoryFilter && categoryArrow) {
        let isDropdownOpen = false;
        
        categoryFilter.addEventListener('mousedown', function(e) {
            isDropdownOpen = !isDropdownOpen;
            updateArrowState(isDropdownOpen);
        });
        
        categoryFilter.addEventListener('change', function() {
            isDropdownOpen = true;
            updateArrowState(true);
            
            setTimeout(() => {
                isDropdownOpen = false;
                updateArrowState(false);
            }, 200);
        });
        
        document.addEventListener('click', function(e) {
            if (!categoryFilter.contains(e.target) && !categoryArrow.contains(e.target)) {
                if (isDropdownOpen) {
                    isDropdownOpen = false;
                    updateArrowState(false);
                }
            }
        });
        
        categoryFilter.addEventListener('blur', function() {
            setTimeout(() => {
                if (isDropdownOpen) {
                    isDropdownOpen = false;
                    updateArrowState(false);
                }
            }, 150);
        });
        
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
});

// Ensure the icon color changes on hover
document.addEventListener('DOMContentLoaded', function() {
    const viewAllBtn = document.querySelector('.view-all-btn');
    if (viewAllBtn) {
        const icon = viewAllBtn.querySelector('.btn-icon');
        
        viewAllBtn.addEventListener('mouseenter', function() {
            if (icon) icon.style.color = 'white';
        });
        
        viewAllBtn.addEventListener('mouseleave', function() {
            if (icon) icon.style.color = 'var(--color-burgundy)';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>