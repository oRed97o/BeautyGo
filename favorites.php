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
    
    /* Business card styling */
    .favorite-business-card {
        transition: all 0.3s ease;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .favorite-business-card:hover {
        box-shadow: 0 8px 16px rgba(196, 30, 58, 0.15);
        transform: translateY(-4px);
    }
    
    .business-image {
        height: 200px;
        object-fit: cover;
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        position: relative;
        overflow: hidden;
    }
    
    /* Business hours styling */
    .business-hours-info {
        background-color: #f8f9fa;
        padding: 12px;
        border-radius: 6px;
        margin: 12px 0;
        border-left: 3px solid var(--color-burgundy);
    }
    
    .hours-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 0.85rem;
    }
    
    .hours-time {
        color: var(--color-burgundy);
        font-weight: 600;
    }
    
    .business-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 4px;
        margin-top: 8px;
    }
    
    .business-status.open {
        background-color: #d4edda;
        color: #155724;
    }
    
    .business-status.closed {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    
    .status-dot.open {
        background-color: #28a745;
        animation: pulse 2s infinite;
    }
    
    .status-dot.closed {
        background-color: #dc3545;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
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
        
        .business-image {
            height: 150px;
        }
        
        .hours-row {
            font-size: 0.75rem;
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
                    <i class="bi bi-heart" style="font-size: 4rem; color: var(--color-burgundy);"></i>
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
                        
                        // Use logo if available
                        if ($album && !empty($album['logo'])) {
                            $businessImage = 'data:image/jpeg;base64,' . base64_encode($album['logo']);
                        }
                        
                        // If no logo, use default image based on business type
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
                        
                        // Get business opening and closing hours from businesses table
                        $conn = getDbConnection();
                        $stmtBusiness = $conn->prepare("
                            SELECT opening_hour, closing_hour 
                            FROM businesses 
                            WHERE business_id = ?
                        ");
                        $stmtBusiness->bind_param("i", $business['business_id']);
                        $stmtBusiness->execute();
                        $resultBusiness = $stmtBusiness->get_result();
                        $businessHours = $resultBusiness->fetch_assoc();
                        $stmtBusiness->close();
                        
                        // Check if business is open now
                        $isOpen = false;
                        if ($businessHours) {
                            // Get current Philippines time
                            date_default_timezone_set('Asia/Manila');
                            
                            // Format times as HH:MM for comparison
                            $openingTime = substr($businessHours['opening_hour'], 0, 5);
                            $closingTime = substr($businessHours['closing_hour'], 0, 5);
                            $currentTime = date('H:i');
                            
                            // String comparison for HH:MM format
                            $isOpen = ($currentTime >= $openingTime && $currentTime < $closingTime);
                        }
                        ?>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 favorite-business-card">
                                <img src="<?php echo htmlspecialchars($businessImage); ?>" 
                                     class="card-img-top business-image" 
                                     alt="<?php echo htmlspecialchars($business['business_name']); ?>"
                                     onerror="this.src='resources/default.png'">
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($business['business_name']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <?php echo ucfirst($business['business_type']); ?> • <?php echo htmlspecialchars($business['city']); ?>
                                        </small>
                                    </p>
                                    
                                    <!-- Rating -->
                                    <div class="mb-2">
                                        <i class="bi bi-star-fill" style="color: var(--color-burgundy);"></i>
                                        <strong><?php echo number_format($avgRating, 1); ?></strong>
                                        <small class="text-muted">(<?php echo $reviewCount; ?> reviews)</small>
                                    </div>
                                    
                                    <!-- Business Hours -->
                                    <?php if ($businessHours): ?>
                                        <div class="business-hours-info">
                                            <small><strong>Business Hours</strong></small>
                                            <div class="hours-row">
                                                <span class="hours-time">
                                                    <?php echo date('g:i A', strtotime($businessHours['opening_hour'])); ?> - <?php echo date('g:i A', strtotime($businessHours['closing_hour'])); ?>
                                                </span>
                                                <span class="business-status <?php echo $isOpen ? 'open' : 'closed'; ?>">
                                                    <span class="status-dot <?php echo $isOpen ? 'open' : 'closed'; ?>"></span>
                                                    <?php echo $isOpen ? 'Open Now' : 'Closed'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Favorited Date -->
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-heart"></i> 
                                        Favorited <?php echo formatDateTime($business['favorited_at']); ?>
                                    </small>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-3">
                                        <a href="business-detail.php?id=<?php echo $business['business_id']; ?>" 
                                           class="btn btn-primary btn-sm w-100 mb-2">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm w-100" 
                                                onclick="removeFavorite(<?php echo $business['business_id']; ?>)">
                                            <i class="bi bi-heart-fill"></i> Remove from Favorites
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