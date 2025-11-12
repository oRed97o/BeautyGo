<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$appointments = getCustomerAppointments($user['customer_id'] ?? $user['id']);

// Sort appointments by booking date (newest booked first)
usort($appointments, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$pageTitle = 'My Bookings - BeautyGo';
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
                                    <h5 class="mb-0"><?php echo $appointment['business_name']; ?></h5>
                                    <span class="badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="bi bi-scissors text-muted"></i>
                                            <strong>Service:</strong> <?php echo $appointment['service_name']; ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="bi bi-person text-muted"></i>
                                            <strong>Staff:</strong> <?php echo htmlspecialchars($appointment['staff_name'] ?? 'Any Available'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="bi bi-calendar text-muted"></i>
                                            <strong>Date & Time:</strong> <?php echo formatDateTime($appointment['appointment_datetime']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($appointment['notes'])): ?>
                                    <p class="mb-1">
                                        <i class="bi bi-chat-left-text text-muted"></i>
                                        <strong>Notes:</strong> <?php echo $appointment['notes']; ?>
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
                                    
                                    <?php if ($appointment['status'] == 'pending'): ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="if(confirm('Cancel this appointment?')) { updateAppointmentStatus('<?php echo $appointment['appointment_id']; ?>', 'cancelled'); }">
                                            <i class="bi bi-x-circle"></i> Cancel Appointment
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['status'] == 'completed'): ?>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $appointment['appointment_id']; ?>">
                                            <i class="bi bi-star"></i> Write Review
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent text-muted small">
                        Booked on <?php echo formatDate($appointment['created_at']); ?>
                    </div>
                </div>
                
                <!-- Review Modal -->
                <?php if ($appointment['status'] == 'completed'): ?>
                    <div class="modal fade" id="reviewModal<?php echo $appointment['appointment_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Write a Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="submit-review.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <input type="hidden" name="business_id" value="<?php echo $appointment['business_id']; ?>">
                                        <input type="hidden" name="customer_id" value="<?php echo $user['customer_id'] ?? $user['id']; ?>">
                                        <input type="hidden" name="user_name" value="<?php echo $user['name']; ?>">
                                        
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
                                            <label for="comment" class="form-label">Your Review</label>
                                            <textarea class="form-control" name="comment" rows="4" required placeholder="Share your experience..."></textarea>
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
    // Create a form and submit it
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

// Legacy function name for backwards compatibility
function updateBookingStatus(id, status) {
    updateAppointmentStatus(id, status);
}
</script>

<?php include 'includes/footer.php'; ?>