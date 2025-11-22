<?php


require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';      // for isCustomerLoggedIn(), formatDate(), formatDateTime()
require_once 'backend/function_customers.php';      // for getCurrentCustomer()
require_once 'backend/function_businesses.php';     // for header.php getCurrentBusiness()
require_once 'backend/function_appointments.php';   // for getCustomerAppointments()
require_once 'backend/function_notifications.php';  // for header.php notifications

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$customer = getCurrentCustomer();
$appointments = getCustomerAppointments($customer['customer_id']);

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
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>

        <h2 class="mb-4">My Bookings</h2>
        
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
            <?php foreach ($appointments as $appointment): ?>
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
                                    
                                    <?php if ($appointment['appoint_status'] == 'pending'): ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="if(confirm('Cancel this appointment?')) { updateAppointmentStatus('<?php echo $appointment['appointment_id']; ?>', 'cancelled'); }">
                                            <i class="bi bi-x-circle"></i> Cancel Appointment
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