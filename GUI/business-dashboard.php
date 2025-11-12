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
$bookings = getBusinessAppointments($businessId);
$services = getBusinessServices($businessId);
$staff = getBusinessStaff($businessId);
$reviews = getBusinessReviews($businessId);

// Calculate stats - Updated for new schema
$todayBookings = array_filter($bookings, function($b) {
    return date('Y-m-d', strtotime($b['appointment_datetime'])) == date('Y-m-d');
});

$pendingBookings = array_filter($bookings, function($b) {
    return $b['status'] == 'pending';
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
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Business Dashboard</h2>
            <a href="business-profile.php" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> Manage Profile
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Today's Bookings</h6>
                        <h3 class="mb-0"><?php echo count($todayBookings); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card burgundy">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending Bookings</h6>
                        <h3 class="mb-0"><?php echo count($pendingBookings); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card pink">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Services</h6>
                        <h3 class="mb-0"><?php echo count($services); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h3 class="mb-0">₱<?php echo number_format($totalRevenue, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">
                    <i class="bi bi-calendar-check"></i> Bookings
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
                        <h4 class="mb-3">Manage Bookings</h4>
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>No bookings yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
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
                                                <td><?php echo formatDateTime($booking['appointment_datetime']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['customer_phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['staff_name'] ?? 'Any Available'); ?></td>
                                                <td>₱<?php echo number_format($booking['cost'], 2); ?></td>
                                                <td>
                                                    <span class="badge status-<?php echo $booking['status']; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updateBookingStatus('<?php echo $booking['appointment_id']; ?>', 'confirmed')">
                                                            Confirm
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="updateBookingStatus('<?php echo $booking['appointment_id']; ?>', 'cancelled')">
                                                            Cancel
                                                        </button>
                                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                                        <button class="btn btn-sm btn-info" onclick="updateBookingStatus('<?php echo $booking['appointment_id']; ?>', 'completed')">
                                                            Complete
                                                        </button>
                                                    <?php endif; ?>
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
                            <h4 class="mb-0">Manage Services</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                <i class="bi bi-plus-circle"></i> Add Service
                            </button>
                        </div>
                        <?php if (empty($services)): ?>
                            <div class="empty-state">
                                <i class="bi bi-clipboard-x"></i>
                                <p>No services added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($service['service_desc'] ?? '', 0, 50)); ?><?php echo strlen($service['service_desc'] ?? '') > 50 ? '...' : ''; ?></td>
                                                <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                                <td>₱<?php echo number_format($service['cost'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Edit</button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirmDelete('Delete this service?')">Delete</button>
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
                            <h4 class="mb-0">Manage Staff</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                <i class="bi bi-person-plus"></i> Add Staff
                            </button>
                        </div>
                        <?php if (empty($staff)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <p>No staff members added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($staff as $member): ?>
                                    <?php 
                                        $employeeName = $member['employee_name'] ?? $member['name'] ?? 'Staff Member';
                                        $specialty = $member['specialization'] ?? $member['specialty'] ?? 'General Services';
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <?php if (isset($member['photo']) && !empty($member['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo htmlspecialchars($employeeName); ?>" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px;">
                                                        <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <h6><?php echo htmlspecialchars($employeeName); ?></h6>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($specialty); ?></p>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary">Edit</button>
                                                    <button class="btn btn-outline-danger" onclick="return confirmDelete('Remove this staff member?')">Remove</button>
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
                        <h4 class="mb-3">Customer Reviews</h4>
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state">
                                <i class="bi bi-chat-square-text"></i>
                                <p>No reviews yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                        <div class="rating">
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
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
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
                <h5 class="modal-title">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage-service.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                    
                    <div class="mb-3">
                        <label for="service_name" class="form-label">Service Name</label>
                        <input type="text" class="form-control" id="service_name" name="service_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_description" class="form-label">Description</label>
                        <textarea class="form-control" id="service_description" name="description" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="service_duration" name="duration" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="service_price" class="form-label">Price (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="service_price" name="price" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Staff Modal with File Upload -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage-staff.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                    
                    <div class="mb-3">
                        <label for="staff_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="staff_name" name="employee_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="staff_specialty" class="form-label">Specialty</label>
                        <input type="text" class="form-control" id="staff_specialty" name="specialization" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="staff_photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="staff_photo" name="photo" accept="image/*">
                        
                    </div>
                    
                    <!-- Photo Preview -->
                    <div class="mb-3" id="photoPreviewContainer" style="display: none;">
                        <label class="form-label">Preview</label>
                        <div class="text-center">
                            <img id="photoPreview" src="" alt="Preview" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Photo preview functionality
document.getElementById('staff_photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            e.target.value = '';
            document.getElementById('photoPreviewContainer').style.display = 'none';
            return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            e.target.value = '';
            document.getElementById('photoPreviewContainer').style.display = 'none';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreviewContainer').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('photoPreviewContainer').style.display = 'none';
    }
});
</script>

<script>
function updateBookingStatus(appointmentId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'update-booking.php';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'appointment_id';
    idInput.value = appointmentId;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status;
    
    form.appendChild(idInput);
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}

function confirmDelete(message) {
    return confirm(message);
}
</script>

<?php include 'includes/footer.php'; ?>