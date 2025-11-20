<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_services.php';
require_once 'backend/function_businesses.php';

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

<style>
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: var(--color-rose);
    margin-bottom: 20px;
}

.business-services-card {
    border: 1px solid #dee2e6;
}

.service-card .card {
    border: 1px solid #e0e0e0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.service-card .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(133, 14, 53, 0.15);
}
</style>

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
                // Get business info for this group of services
                $business = getBusinessById($businessId);
                if (!$business) continue;
                ?>
                
                <div class="card mb-4 business-services-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($business['business_name']); ?></h4>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($business['business_address'] . ', ' . $business['city']); ?>
                                </small>
                            </div>
                            <a href="business-detail.php?id=<?php echo $business['business_id']; ?>" class="btn btn-outline-primary btn-sm">
                                View Business
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($services as $service): ?>
                                <div class="col-md-6 col-lg-4 mb-3 service-card" data-service-name="<?php echo strtolower($service['service_name']); ?>">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                                <?php if (!empty($service['service_type'])): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($service['service_type']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (!empty($service['service_desc'])): ?>
                                                <p class="card-text text-muted small"><?php echo htmlspecialchars($service['service_desc']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> <?php echo $service['duration']; ?> mins
                                                </small>
                                                <strong style="color: var(--color-burgundy);">₱<?php echo number_format($service['cost'], 2); ?></strong>
                                            </div>
                                            
                                            <?php if (isCustomerLoggedIn()): ?>
                                                <a href="booking.php?business_id=<?php echo $business['business_id']; ?>&service_id=<?php echo $service['service_id']; ?>" class="btn btn-primary btn-sm w-100">
                                                    <i class="bi bi-calendar-check"></i> Book Now
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="bi bi-box-arrow-in-right"></i> Login to Book
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