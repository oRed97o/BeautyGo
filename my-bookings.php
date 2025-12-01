<?php


require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isCustomerLoggedIn(), formatDate(), formatDateTime()
require_once 'backend/function_customers.php';      // for getCurrentCustomer()
require_once 'backend/function_businesses.php';     // for header.php getCurrentBusiness()
require_once 'backend/function_appointments.php';   // for getCustomerAppointments()
require_once 'backend/function_notifications.php';  // for header.php notifications
require_once 'backend/function_favorites.php';      // for header.php getCustomerFavorites()


// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$customer = getCurrentCustomer();
$appointments = getCustomerAppointments($customer['customer_id']);

// Mark notifications as read when viewing bookings page
markCustomerNotificationsAsRead($customer['customer_id']);

// Sort appointments by set_date (newest booked first)
usort($appointments, function($a, $b) {
    return strtotime($b['set_date']) - strtotime($a['set_date']);
});

$pageTitle = 'My Bookings - BeautyGo';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">

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

/* My Bookings page heading */
.bookings-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.75rem;
    margin-bottom: 24px;
}

.bookings-heading i {
    color: var(--color-burgundy);
    font-size: 2rem;
}

/* Status badges */
.status-pending { background-color: #ffc107; color: #000; }
.status-confirmed { background-color: #28a745; color: #fff; }
.status-cancelled { background-color: #dc3545; color: #fff; }
.status-completed { background-color: #007bff; color: #fff; }

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: var(--color-rose);
    margin-bottom: 20px;
}

/* Card content responsiveness */
.appointment-card-content {
    display: flex;
    flex-direction: column;
}

.appointment-details {
    row-gap: 1rem;
}

.appointment-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

/* Filter section styling */
.filter-section {
    background: linear-gradient(135deg, var(--color-cream) 0%, #f9f3f0 100%);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    border-left: 4px solid var(--color-burgundy);
}

.filter-section h5 {
    color: var(--color-burgundy);
    margin-bottom: 16px;
    font-weight: 600;
}

.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: var(--color-burgundy);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.filter-badge .bi {
    cursor: pointer;
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
    
    .bookings-heading {
        font-size: 1.35rem;
    }
    
    .bookings-heading i {
        font-size: 1.5rem;
    }
    
    .card {
        margin-bottom: 0.75rem !important;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .row {
        margin-left: -0.25rem;
        margin-right: -0.25rem;
    }
    
    [class*='col-'] {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }
    
    .col-md-8 {
        margin-bottom: 1rem;
    }
    
    .col-md-4.text-end {
        text-align: left !important;
    }
    
    .appointment-card-content {
        row-gap: 0.75rem;
    }
    
    .appointment-details p {
        font-size: 0.9rem;
        margin-bottom: 0.5rem !important;
    }
    
    .d-grid gap-2 {
        row-gap: 0.5rem !important;
    }
    
    .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }
    
    h3 {
        font-size: 1.35rem !important;
    }
    
    .filter-section {
        padding: 15px;
    }
    
    .filter-section .row {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    
    .filter-section [class*='col-'] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .bookings-heading {
        font-size: 1.5rem;
    }
    
    .bookings-heading i {
        font-size: 1.75rem;
    }
    
    .card-body {
        padding: 16px !important;
    }
}
</style>

<main>
    <div class="container px-2 px-md-3 py-3 py-md-4" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <a href="index.php" class="back-button">
                    <i class="bi bi-arrow-left-circle"></i>
                    <span>Back to Home</span>
                </a>

                <h2 class="bookings-heading"><i class="bi bi-calendar-check-fill"></i> My Bookings</h2>
                
                <?php if (!empty($appointments)): ?>
                    <!-- Filter Section - NEW -->
                    <div class="filter-section">
                        <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Bookings</h5>
                        
                        <form method="GET" id="bookingFilterForm" class="row g-3">
                            <div class="col-md-3">
                                <label for="filterStatus" class="form-label small">Status</label>
                                <select class="form-select form-select-sm" id="filterStatus" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($_GET['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo ($_GET['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="filterDateFrom" class="form-label small">Date From</label>
                                <input type="date" class="form-control form-control-sm" id="filterDateFrom" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="filterDateTo" class="form-label small">Date To</label>
                                <input type="date" class="form-control form-control-sm" id="filterDateTo" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="filterBusiness" class="form-label small">Business Name</label>
                                <input type="text" class="form-control form-control-sm" id="filterBusiness" name="business" placeholder="Search..." value="<?php echo $_GET['business'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                                <a href="my-bookings.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> Clear Filters
                                </a>
                            </div>
                        </form>
                        
                        <!-- Active Filters Display - NEW -->
                        <?php 
                        $activeFilters = [];
                        if (!empty($_GET['status'])) $activeFilters[] = 'Status: ' . ucfirst($_GET['status']);
                        if (!empty($_GET['date_from'])) $activeFilters[] = 'From: ' . date('M j, Y', strtotime($_GET['date_from']));
                        if (!empty($_GET['date_to'])) $activeFilters[] = 'To: ' . date('M j, Y', strtotime($_GET['date_to']));
                        if (!empty($_GET['business'])) $activeFilters[] = 'Business: ' . htmlspecialchars($_GET['business']);
                        ?>
                        
                        <?php if (!empty($activeFilters)): ?>
                            <div class="active-filters mt-3">
                                <?php foreach ($activeFilters as $filter): ?>
                                    <span class="filter-badge">
                                        <?php echo $filter; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($appointments)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h4>No Appointments Yet</h4>
                                <p>You haven't made any appointments. Browse our businesses to get started!</p>
                                <a href="index.php" class="btn btn-primary">Browse Businesses</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    // APPLY FILTERS - NEW
                    $filteredAppointments = $appointments;
                    
                    // Filter by status
                    if (!empty($_GET['status'])) {
                        $filteredAppointments = array_filter($filteredAppointments, function($a) {
                            return $a['appoint_status'] == $_GET['status'];
                        });
                    }
                    
                    // Filter by date range
                    if (!empty($_GET['date_from'])) {
                        $dateFrom = strtotime($_GET['date_from']);
                        $filteredAppointments = array_filter($filteredAppointments, function($a) use ($dateFrom) {
                            return strtotime(date('Y-m-d', strtotime($a['appoint_date']))) >= $dateFrom;
                        });
                    }
                    
                    if (!empty($_GET['date_to'])) {
                        $dateTo = strtotime($_GET['date_to'] . ' 23:59:59');
                        $filteredAppointments = array_filter($filteredAppointments, function($a) use ($dateTo) {
                            return strtotime(date('Y-m-d', strtotime($a['appoint_date']))) <= $dateTo;
                        });
                    }
                    
                    // Filter by business name
                    if (!empty($_GET['business'])) {
                        $searchTerm = strtolower($_GET['business']);
                        $filteredAppointments = array_filter($filteredAppointments, function($a) use ($searchTerm) {
                            return strpos(strtolower($a['business_name']), $searchTerm) !== false;
                        });
                    }
                    ?>
                    
                    <?php if (empty($filteredAppointments)): ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="bi bi-search"></i>
                                    <h4>No Bookings Found</h4>
                                    <p>No bookings match your filters. Try adjusting your search criteria.</p>
                                    <a href="my-bookings.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> 
                            Showing <strong><?php echo count($filteredAppointments); ?></strong> of <strong><?php echo count($appointments); ?></strong> bookings
                        </div>
                        
                        <?php foreach ($filteredAppointments as $appointment): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($appointment['business_name']); ?></h5>
                                                <span class="badge status-<?php echo $appointment['appoint_status']; ?>">
                                                    <?php echo ucfirst($appointment['appoint_status']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <p class="mb-1">
                                                        <i class="bi bi-scissors text-muted"></i>
                                                        <strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_name']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="bi bi-person text-muted"></i>
                                                        <strong>Staff:</strong> <?php echo htmlspecialchars($appointment['staff_fname'] . ' ' . $appointment['staff_lname']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="bi bi-star text-muted"></i>
                                                        <strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['specialization']); ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1">
                                                        <i class="bi bi-calendar text-muted"></i>
                                                        <strong>Date & Time:</strong> <?php echo formatDateTime($appointment['appoint_date']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="bi bi-clock text-muted"></i>
                                                        <strong>Duration:</strong> <?php echo $appointment['duration']; ?> minutes
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="bi bi-geo-alt text-muted"></i>
                                                        <strong>Address:</strong> <?php echo htmlspecialchars($appointment['business_address']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($appointment['appoint_desc'])): ?>
                                                <p class="mb-1">
                                                    <i class="bi bi-chat-left-text text-muted"></i>
                                                    <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['appoint_desc']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <p class="mb-0 mt-2">
                                                <strong>Appointment ID:</strong> <small class="text-muted"><?php echo $appointment['appointment_id']; ?></small>
                                            </p>
                                        </div>
                                        
                                        <div class="col-md-4 text-end">
                                            <div class="mb-3">
                                                <h3 style="color: var(--color-burgundy);">₱<?php echo number_format($appointment['cost'], 2); ?></h3>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="business-detail.php?id=<?php echo $appointment['business_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-shop"></i> View Business
                                                </a>

                                                <a href="booking.php?business_id=<?php echo $appointment['business_id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-calendar-plus"></i> Book Another
                                                </a>
                                                
                                                <?php if ($appointment['appoint_status'] == 'pending' || $appointment['appoint_status'] == 'confirmed'): ?>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="if(confirm('Cancel this appointment?')) { updateAppointmentStatus('<?php echo $appointment['appointment_id']; ?>', 'cancelled'); }">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($appointment['appoint_status'] == 'completed'): ?>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $appointment['appointment_id']; ?>">
                                                        <i class="bi bi-star"></i> Write Review
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent text-muted small">
                                    Booked on <?php echo formatDate($appointment['set_date']); ?>
                                </div>
                            </div>
                            
                            <!-- Review Modal -->
                            <?php if ($appointment['appoint_status'] == 'completed'): ?>
                                <div class="modal fade" id="reviewModal<?php echo $appointment['appointment_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Write a Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="backend/submit-review.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="business_id" value="<?php echo $appointment['business_id']; ?>">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Rating</label>
                                                        <div class="btn-group d-flex" role="group">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <input type="radio" class="btn-check" name="rating" id="rating<?php echo $appointment['appointment_id']; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                                <label class="btn btn-outline-warning" for="rating<?php echo $appointment['appointment_id']; ?>_<?php echo $i; ?>">
                                                                    <?php echo $i; ?> <i class="bi bi-star-fill"></i>
                                                                </label>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                
                                                    <div class="mb-3">
                                                        <label for="review_text" class="form-label">Your Review</label>
                                                        <textarea class="form-control" name="review_text" rows="4" required placeholder="Share your experience..."></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Add Photos (Optional - Max 5 images)</label>
                                                        <input type="file" class="form-control mb-2" name="review_img1" accept="image/*">
                                                        <input type="file" class="form-control mb-2" name="review_img2" accept="image/*">
                                                        <input type="file" class="form-control mb-2" name="review_img3" accept="image/*">
                                                        <input type="file" class="form-control mb-2" name="review_img4" accept="image/*">
                                                        <input type="file" class="form-control" name="review_img5" accept="image/*">
                                                        <small class="text-muted">JPG, PNG, GIF, WebP - Max 5MB per image</small>
                                                    </div>

                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function updateAppointmentStatus(appointmentId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'backend/update-booking.php';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'appointment_id';
    idInput.value = appointmentId;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'appoint_status';
    statusInput.value = status;
    
    form.appendChild(idInput);
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include 'includes/footer.php'; ?>