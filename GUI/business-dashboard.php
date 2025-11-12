<?php
require_once 'config.php';
require_once 'functions.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

$business = getCurrentBusiness();
$businessId = $business['business_id'] ?? $business['id'];

// Handle appointment status update with notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $newStatus = sanitize($_POST['status']);
    
    // Get appointment details before updating
    $appointment = getAppointmentById($appointmentId);
    
    if ($appointment && updateAppointmentStatus($appointmentId, $newStatus)) {
        // Send notification to customer
        createAppointmentNotification(
            $appointment['customer_id'],
            $businessId,
            $appointmentId,
            $newStatus
        );
        
        $statusText = ucfirst($newStatus);
        $_SESSION['success'] = "Appointment {$statusText} successfully! Customer has been notified.";
    } else {
        $_SESSION['error'] = 'Failed to update appointment status';
    }
    
    header('Location: business-dashboard.php');
    exit;
}

// Handle service management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $serviceData = [
        'business_id' => $businessId,
        'service_name' => sanitize($_POST['service_name']),
        'service_type' => sanitize($_POST['service_type'] ?? ''),
        'service_desc' => sanitize($_POST['service_desc']),
        'cost' => floatval($_POST['cost']),
        'duration' => intval($_POST['duration'])
    ];
    
    if (createService($serviceData)) {
        $_SESSION['success'] = 'Service added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add service';
    }
    
    header('Location: business-dashboard.php#services');
    exit;
}

// Handle staff management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staffData = [
        'business_id' => $businessId,
        'employ_fname' => sanitize($_POST['employ_fname']),
        'employ_lname' => sanitize($_POST['employ_lname']),
        'specialization' => sanitize($_POST['specialization']),
        'skills' => sanitize($_POST['skills'] ?? ''),
        'employ_bio' => sanitize($_POST['employ_bio'] ?? ''),
        'employ_status' => 'available'
    ];
    
    if (createEmployee($staffData)) {
        $_SESSION['success'] = 'Staff member added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add staff member';
    }
    
    header('Location: business-dashboard.php#staff');
    exit;
}

$bookings = getBusinessAppointments($businessId);
$services = getBusinessServices($businessId);
$staff = getBusinessEmployees($businessId);
$reviews = getBusinessReviews($businessId);

// Calculate stats
$todayBookings = array_filter($bookings, function($b) {
    return date('Y-m-d', strtotime($b['appoint_date'])) == date('Y-m-d');
});

$pendingBookings = array_filter($bookings, function($b) {
    return $b['appoint_status'] == 'pending';
});

$totalRevenue = array_sum(array_column($bookings, 'cost'));

$pageTitle = 'Business Dashboard - BeautyGo';
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

/* Status action buttons */
.status-actions {
    display: flex;
    gap: 5px;
}

.status-actions form {
    margin: 0;
}

.notification-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    animation: slideIn 0.3s ease-out;
}

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
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-speedometer2"></i> Business Dashboard</h2>
            <a href="business-profile.php" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> Manage Profile
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-day" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Today's Bookings</h6>
                                <h3 class="mb-0"><?php echo count($todayBookings); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card burgundy">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-hourglass-split" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Pending Bookings</h6>
                                <h3 class="mb-0"><?php echo count($pendingBookings); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card pink">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-scissors" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Services</h6>
                                <h3 class="mb-0"><?php echo count($services); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cash-stack" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Revenue</h6>
                                <h3 class="mb-0">₱<?php echo number_format($totalRevenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">
                    <i class="bi bi-calendar-check"></i> Bookings
                    <?php if (count($pendingBookings) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pendingBookings); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button">
                    <i class="bi bi-scissors"></i> Services
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button">
                    <i class="bi bi-people"></i> Staff
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">
                    <i class="bi bi-star"></i> Reviews
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="dashboardTabContent">
            <!-- Bookings Tab -->
            <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3">
                            <i class="bi bi-calendar-check"></i> Manage Bookings
                            <small class="text-muted">(Customer will be notified automatically)</small>
                        </h4>
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No bookings yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date & Time</th>
                                            <th>Customer</th>
                                            <th>Phone</th>
                                            <th>Service</th>
                                            <th>Staff</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>#<?php echo $booking['appointment_id']; ?></td>
                                                <td>
                                                    <strong><?php echo date('M j, Y', strtotime($booking['appoint_date'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['appoint_date'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['customer_fname'] . ' ' . $booking['customer_lname']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['customer_phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                                <td><?php echo htmlspecialchars(($booking['staff_fname'] ?? '') . ' ' . ($booking['staff_lname'] ?? '') ?: 'Any Available'); ?></td>
                                                <td><strong>₱<?php echo number_format($booking['cost'] ?? 0, 2); ?></strong></td>
                                                <td>
                                                    <span class="badge status-<?php echo $booking['appoint_status']; ?> bg-<?php 
                                                        echo $booking['appoint_status'] === 'confirmed' ? 'success' : 
                                                             ($booking['appoint_status'] === 'cancelled' ? 'danger' : 
                                                             ($booking['appoint_status'] === 'completed' ? 'info' : 'warning')); 
                                                    ?>">
                                                        <?php echo ucfirst($booking['appoint_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="status-actions">
                                                        <?php if ($booking['appoint_status'] == 'pending'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="confirmed">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-success" title="Confirm & Notify Customer">
                                                                    <i class="bi bi-check-circle"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-danger" title="Cancel & Notify Customer" onclick="return confirm('Cancel this appointment? Customer will be notified.')">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            </form>
                                                        <?php elseif ($booking['appoint_status'] == 'confirmed'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-info" title="Mark as Completed">
                                                                    <i class="bi bi-check-all"></i> Complete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Services Tab -->
            <div class="tab-pane fade" id="services" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="bi bi-scissors"></i> Manage Services</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                <i class="bi bi-plus-circle"></i> Add Service
                            </button>
                        </div>
                        <?php if (empty($services)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-clipboard-x" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No services added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($service['service_type'] ?? 'General'); ?></td>
                                                <td><?php echo htmlspecialchars(substr($service['service_desc'] ?? '', 0, 50)); ?><?php echo strlen($service['service_desc'] ?? '') > 50 ? '...' : ''; ?></td>
                                                <td><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                                <td><strong>₱<?php echo number_format($service['cost'], 2); ?></strong></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" title="Edit Service">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service?')" title="Delete Service">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Staff Tab -->
            <div class="tab-pane fade" id="staff" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="bi bi-people"></i> Manage Staff</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                <i class="bi bi-person-plus"></i> Add Staff
                            </button>
                        </div>
                        <?php if (empty($staff)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No staff members added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($staff as $member): ?>
                                    <?php 
                                        $employeeName = trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?: 'Staff Member';
                                        $specialty = $member['specialization'] ?? 'General Services';
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <?php if (isset($member['employ_img']) && !empty($member['employ_img'])): ?>
                                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($member['employ_img']); ?>" alt="<?php echo htmlspecialchars($employeeName); ?>" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px;">
                                                        <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <h6><?php echo htmlspecialchars($employeeName); ?></h6>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($specialty); ?></p>
                                                <span class="badge bg-<?php echo $member['employ_status'] === 'available' ? 'success' : 'secondary'; ?> mb-2">
                                                    <?php echo ucfirst($member['employ_status'] ?? 'available'); ?>
                                                </span>
                                                <div class="btn-group btn-group-sm d-block">
                                                    <button class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</button>
                                                    <button class="btn btn-outline-danger" onclick="return confirm('Remove this staff member?')"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Tab -->
            <div class="tab-pane fade" id="reviews" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3"><i class="bi bi-star-fill"></i> Customer Reviews</h4>
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-chat-square-text" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No reviews yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['customer_fname'] . ' ' . $review['customer_lname']); ?></strong>
                                            <br><small class="text-muted"><?php echo formatDate($review['review_date']); ?></small>
                                        </div>
                                        <div class="rating" style="color: #ffc107;">
                                            <?php
                                            $rating = $review['rating'] ?? 0;
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="bi bi-star-fill"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_desc" class="form-label">Description *</label>
                        <textarea class="form-control" id="service_desc" name="service_desc" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Duration (minutes) *</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="15" step="15" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cost" class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" class="form-control" id="cost" name="cost" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_service" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employ_fname" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="employ_fname" name="employ_fname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="employ_lname" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="employ_lname" name="employ_lname" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization *</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" placeholder="e.g., Hair Colorist, Massage Therapist" required>
                    </div>
                    <div class="mb-3">
                        <label for="skills" class="form-label">Skills</label>
                        <input type="text" class="form-control" id="skills" name="skills" placeholder="e.g., Balayage, Deep Tissue Massage">
                    </div>
                    <div class="mb-3">
                        <label for="employ_bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="employ_bio" name="employ_bio" rows="2" placeholder="Brief description about the staff member"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_staff" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Add Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Confirm delete action
function confirmDelete(message) {
    return confirm(message);
}
</script>

<?php include 'includes/footer.php'; ?>