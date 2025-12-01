<?php

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isCustomerLoggedIn(), formatDateTime()
require_once 'backend/function_customers.php';      // for getCurrentCustomer() (header.php)
require_once 'backend/function_businesses.php';     // for header.php getCurrentBusiness()
require_once 'backend/function_favorites.php';      // for getCustomerFavorites()
require_once 'backend/function_albums.php';         // for getBusinessAlbum()
require_once 'backend/function_reviews.php';        // for calculateAverageRating(), getBusinessReviews()
require_once 'backend/function_notifications.php';  // for header.php notifications

// Require login
if (!isCustomerLoggedIn()) {
    $_SESSION['error'] = 'Please login to view your favorites.';
    header('Location: login.php');
    exit;
}

$pageTitle = 'My Favorites - BeautyGo';
$favorites = getCustomerFavorites($_SESSION['customer_id']);

// Mark all favorites as seen when visiting this page
markFavoritesAsSeen($_SESSION['customer_id']);

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
    
    /* Favorites page heading */
    .favorites-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.75rem;
        margin-bottom: 24px;
    }
    
    .favorites-heading i {
        color: #dc3545;
        font-size: 2rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .back-button {
            padding: 6px 12px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .back-button i {
            font-size: 1rem;
        }
        
        .favorites-heading {
            font-size: 1.35rem;
        }
        
        .favorites-heading i {
            font-size: 1.5rem;
        }
        
        .card {
            margin-bottom: 0.75rem !important;
        }

        .row {
            margin-left: -0.25rem;
            margin-right: -0.25rem;
        }

        [class*='col-'] {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    }
    
    @media (max-width: 768px) {
        .favorites-heading {
            font-size: 1.5rem;
        }
        
        .favorites-heading i {
            font-size: 1.75rem;
        }
    }
</style>

<main class="container-fluid px-2 px-md-3 py-3 py-md-4">
    <div class="row">
        <div class="col-12">
            <!-- Back Button -->
            <a href="index.php" class="back-button">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Back to Home</span>
            </a>
            
            <h2 class="mb-4 favorites-heading">
                <i class="bi bi-heart-fill text-danger"></i> My Favorite Businesses
            </h2>

            <?php if (empty($favorites)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-heart" style="font-size: 4rem; color: var(--brand-burgundy);"></i>
                    <h4 class="mt-3">No favorites yet</h4>
                    <p class="text-muted">Start adding your favorite beauty businesses!</p>
                    <a href="index.php" class="btn btn-primary mt-3">
                        <i class="bi bi-search"></i> Browse Businesses
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorites as $business): ?>
                <?php 
                $album = getBusinessAlbum($business['business_id']);
                $businessImage = null;
                
                if ($album && !empty($album['logo'])) {
                    $businessImage = 'data:image/jpeg;base64,' . base64_encode($album['logo']);
                } else {
                    $businessImage = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600';
                }
                
                $avgRating = calculateAverageRating($business['business_id']);
                $reviews = getBusinessReviews($business['business_id']);
                $reviewCount = count($reviews);
                ?>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $businessImage; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($business['business_name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($business['business_name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php echo ucfirst($business['business_type']); ?> • <?php echo htmlspecialchars($business['city']); ?>
                                </small>
                            </p>
                            
                            <div class="mb-2">
                                <i class="bi bi-star-fill text-warning"></i>
                                <strong><?php echo number_format($avgRating, 1); ?></strong>
                                <small class="text-muted">(<?php echo $reviewCount; ?> reviews)</small>
                            </div>
                            
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> 
                                Favorited <?php echo formatDateTime($business['favorited_at']); ?>
                            </small>
                            
                            <div class="mt-3">
                                <a href="business-detail.php?id=<?php echo $business['business_id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="removeFavorite(<?php echo $business['business_id']; ?>)">
                                    <i class="bi bi-heart-fill"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
async function removeFavorite(businessId) {
    if (!confirm('Remove this business from your favorites?')) {
        return;
    }
    
    try {
        const response = await fetch('backend/ajax/ajax-favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle&business_id=${businessId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to remove favorite');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to remove favorite');
    }
}
</script>

<?php include 'includes/footer.php'; ?>