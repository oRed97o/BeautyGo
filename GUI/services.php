<?php
require_once 'config.php';
require_once 'functions.php';

$pageTitle = 'Browse Services - BeautyGo';

// Get all services
$allServices = getAllServices();

// Group services by business
$servicesByBusiness = [];
foreach ($allServices as $service) {
    if (!isset($servicesByBusiness[$service['business_id']])) {
        $servicesByBusiness[$service['business_id']] = [];
    }
    $servicesByBusiness[$service['business_id']][] = $service;
}

include 'includes/header.php';
?>

<main>
    <div class="container my-4">
        <h2 class="mb-4">Browse All Services</h2>
        
        <!-- Search and Filter -->
        <div class="filter-section mb-4">
            <div class="row align-items-end">
                <div class="col-md-8 mb-3 mb-md-0">
                    <label for="searchServices" class="form-label">Search Services</label>
                    <input type="text" class="form-control" id="searchServices" placeholder="Search by service name..." onkeyup="filterServices()">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" onclick="filterServices()">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (empty($servicesByBusiness)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-clipboard-x"></i>
                        <h4>No Services Available</h4>
                        <p>Check back later for available services</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($servicesByBusiness as $businessId => $services): ?>
                <?php
                $business = null;
                foreach ($businesses as $b) {
                    if ($b['id'] == $businessId) {
                        $business = $b;
                        break;
                    }
                }
                if (!$business) continue;
                ?>
                
                <div class="card mb-4 business-services-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1"><?php echo $business['business_name']; ?></h4>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> <?php echo $business['address']; ?>
                                </small>
                            </div>
                            <a href="business-detail.php?id=<?php echo $business['id']; ?>" class="btn btn-outline-primary btn-sm">
                                View Business
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($services as $service): ?>
                                <div class="col-md-6 col-lg-4 mb-3 service-card" data-service-name="<?php echo strtolower($service['name']); ?>">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $service['name']; ?></h5>
                                            <p class="card-text text-muted small"><?php echo $service['description']; ?></p>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> <?php echo $service['duration']; ?> mins
                                                </small>
                                                <strong style="color: var(--color-burgundy);">₱<?php echo number_format($service['price'], 2); ?></strong>
                                            </div>
                                            <?php if (isCustomerLoggedIn()): ?>
                                                <a href="booking.php?business_id=<?php echo $business['id']; ?>" class="btn btn-primary btn-sm w-100">
                                                    Book Now
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-outline-primary btn-sm w-100">
                                                    Login to Book
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
function filterServices() {
    const searchTerm = document.getElementById('searchServices').value.toLowerCase();
    const serviceCards = document.querySelectorAll('.service-card');
    const businessCards = document.querySelectorAll('.business-services-card');
    
    serviceCards.forEach(card => {
        const serviceName = card.dataset.serviceName;
        if (serviceName.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Hide business card if all services are hidden
    businessCards.forEach(businessCard => {
        const visibleServices = businessCard.querySelectorAll('.service-card:not([style*="display: none"])');
        if (visibleServices.length === 0) {
            businessCard.style.display = 'none';
        } else {
            businessCard.style.display = '';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
